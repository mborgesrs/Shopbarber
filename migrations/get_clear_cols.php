<?php
require_once __DIR__ . '/../db.php';
$tables = ['portadores', 'tipos_pagamento'];
foreach ($tables as $t) {
    echo "--- $t ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM `$t` ");
    foreach($stmt->fetchAll() as $row) {
        echo $row['Field'] . "\n";
    }
}
