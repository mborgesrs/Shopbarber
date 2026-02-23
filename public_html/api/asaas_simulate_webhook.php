<?php
session_start();
if(!isset($_SESSION['user_id'])){ http_response_code(403); exit; }
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

$invoice_id = $_POST['invoice_id'] ?? '';

if (empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'ID da fatura não informado.']);
    exit;
}

// Fetch invoice data
$stmt = $pdo->prepare("SELECT * FROM saas_invoices WHERE id = ? AND company_id = ?");
$stmt->execute([$invoice_id, $_SESSION['company_id']]);
$invoice = $stmt->fetch();

if (!$invoice) {
    echo json_encode(['success' => false, 'message' => 'Fatura não encontrada.']);
    exit;
}

// Prepare fake Asaas payload
$payload = [
    'event' => 'PAYMENT_RECEIVED',
    'payment' => [
        'id' => $invoice['asaas_id'],
        'status' => 'RECEIVED',
        'value' => $invoice['amount'],
        'netValue' => $invoice['amount'] * 0.9,
        'billingType' => 'PIX',
        'confirmedDate' => date('Y-m-d'),
        'paymentDate' => date('Y-m-d'),
        'clientPaymentDate' => date('Y-m-d')
    ]
];

// Send POST to local webhook
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$webhook_url = $protocol . $domain . "/webhooks/asaas.php";

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo json_encode(['success' => true, 'message' => 'Simulação de pagamento enviada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao processar simulação (HTTP '.$http_code.'). Resposta: ' . $response]);
}
