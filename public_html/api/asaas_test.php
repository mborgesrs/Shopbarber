<?php
session_start();
if(!isset($_SESSION['user_id'])){ http_response_code(403); exit; }
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../lib/Asaas.php';

header('Content-Type: application/json');

$apiKey = $_POST['api_key'] ?? '';
$env = $_POST['env'] ?? 'sandbox';

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'Chave API não informada.']);
    exit;
}

$asaas = new Asaas($apiKey, $env);
$res = $asaas->getAccountInfo();

if ($res['code'] === 200) {
    echo json_encode([
        'success' => true, 
        'message' => 'Conexão realizada com sucesso!',
        'data' => [
            'name' => $res['body']['name'] ?? 'N/A',
            'email' => $res['body']['email'] ?? 'N/A'
        ]
    ]);
} else {
    $message = 'Erro ao conectar.';
    if ($res['curl_errno']) {
        $message = 'Erro de Rede: ' . $res['curl_error'] . ' (Código: ' . $res['curl_errno'] . ')';
    } elseif (isset($res['body']['errors'][0]['description'])) {
        $message = $res['body']['errors'][0]['description'];
    } elseif ($res['code'] > 0) {
        $message = 'Erro HTTP ' . $res['code'] . ': ' . ($res['raw'] ?: 'Sem resposta.');
    } else {
        $message = 'Erro desconhecido ao conectar com Asaas.';
    }
    
    echo json_encode(['success' => false, 'message' => $message]);
}
