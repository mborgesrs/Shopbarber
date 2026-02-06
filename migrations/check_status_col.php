<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE finance status");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
