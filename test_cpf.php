<?php
require_once "db.php";
$stmt = $pdo->prepare('SELECT apicpf_key FROM settings ORDER BY id ASC LIMIT 1');
$stmt->execute();
$key = trim($stmt->fetchColumn());

if (empty($key)) {
    die("Error: No API key found in the database. Raw Key length: " . strlen($key) . PHP_EOL);
}

$cpf = '11122233344';
$url = "https://api.cpfhub.io/cpf/{$cpf}";

// Try different combinations of headers requested by CPFHub
$headers = [
    "x-api-key: {$key}",
    "Accept: application/json"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

echo "Testing URL: $url\n";
echo "Header Count: " . count($headers) . "\n";
echo "Key Prefix: " . substr($key, 0, 8) . "...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "\n--- RESULT ---\n";
echo "HTTP Status: $httpCode\n";
if ($err) {
    echo "cURL Error: $err\n";
}
echo "Response Body: \n";
var_dump($response);
echo "\n";
