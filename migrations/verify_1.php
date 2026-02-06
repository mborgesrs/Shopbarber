<?php
require_once __DIR__ . '/../db.php';

echo "Tentando atualizar ID 1...\n";
$stmt = $pdo->prepare("UPDATE finance SET status = 'Liquidado' WHERE id = 1");
$stmt->execute();
echo "Linhas afetadas: " . $stmt->rowCount() . "\n";

$check = $pdo->query("SELECT id, status FROM finance WHERE id = 1")->fetch();
echo "Status atual do ID 1: '" . ($check['status'] ?? 'NULL') . "'\n";
