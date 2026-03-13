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

// Get API Key from settings
$stmt = $pdo->prepare("SELECT apicpf_key FROM settings WHERE company_id = ? LIMIT 1");
$stmt->execute([$_SESSION['company_id']]);
$settings = $stmt->fetch();

if (!$settings || empty($settings['apicpf_key'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chave da API CPF não configurada nas Configurações do sistema.']);
    exit;
}

$api_key = $settings['apicpf_key'];
$url = "https://apicpf.com/api/consulta?cpf={$cpf}&api_key={$api_key}";

try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing if needed
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new Exception($err);
    }

    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'message' => 'Erro na consulta (HTTP ' . $httpCode . ')', 'details' => $response]);
        exit;
    }

    // Proxy the response from apicpf.com
    echo $response;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar com servidor de CPF: ' . $e->getMessage()]);
}
