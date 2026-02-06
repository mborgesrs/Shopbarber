<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("SHOW CREATE TABLE finance");
echo $stmt->fetchColumn(1);
$stmt = $pdo->query("SHOW TRIGGERS LIKE 'finance'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
