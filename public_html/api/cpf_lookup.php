<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

require_once __DIR__ . '/../../db.php';

$cpf = $_GET['cpf'] ?? '';
$cpf = preg_replace('/\D/', '', $cpf);

if (strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'CPF inválido']);
    exit;
}

// 1. Check if CPF already exists in local clients table (for this company or globally?)
// We'll check globally for name accuracy, but preferably within company if privacy is concerned. 
// However, since it's just name and DOB, searching globally helps reduce API calls.
$stmt = $pdo->prepare("SELECT name, date_nascto FROM clients WHERE cpf = ? AND name IS NOT NULL AND name != '' LIMIT 1");
$stmt->execute([$cpf]);
$localClient = $stmt->fetch();

if ($localClient) {
    echo json_encode([
        'success' => true,
        'nome' => $localClient['name'],
        'nascimento' => $localClient['date_nascto'],
        'source' => 'local_clients'
    ]);
    exit;
}

// 2. Check local CPF Cache
$stmt = $pdo->prepare("SELECT data FROM cpf_cache WHERE cpf = ? LIMIT 1");
$stmt->execute([$cpf]);
$cached = $stmt->fetch();

if ($cached) {
    echo $cached['data']; // Data is already stored as JSON string from previous API call
    exit;
}

// 3. API Key Check
$stmt = $pdo->prepare("SELECT apicpf_key FROM settings WHERE company_id = ? LIMIT 1");
$stmt->execute([$_SESSION['company_id']]);
$settings = $stmt->fetch();

if (!$settings || empty($settings['apicpf_key'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chave da API CPF não configurada nas Configurações.']);
    exit;
}

$api_key = $settings['apicpf_key'];
$url = "https://apicpf.com/api/consulta?cpf={$cpf}&api_key={$api_key}";

try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) throw new Exception($err);

    if ($httpCode === 429) {
        http_response_code(200); // Send 200 to handle message gracefully in JS
        echo json_encode([
            'success' => false, 
            'message' => 'Limite de consultas da API atingido (HTTP 429). Por favor, verifique seu plano ou tente mais tarde.'
        ]);
        exit;
    }

    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'message' => 'Erro na consulta (HTTP ' . $httpCode . ')', 'details' => $response]);
        exit;
    }

    $data = json_decode($response, true);
    
    // 4. Save to Cache if successful
    if (isset($data['nome'])) {
        $stmt = $pdo->prepare("INSERT INTO cpf_cache (cpf, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = ?, created_at = CURRENT_TIMESTAMP");
        $stmt->execute([$cpf, $response, $response]);
    }

    echo $response;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar: ' . $e->getMessage()]);
}
