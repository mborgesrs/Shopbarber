<?php
require_once __DIR__ . '/../db.php';
$tables = ['portadores', 'tipos_pagamento'];
foreach ($tables as $t) {
    echo "[$t]";
    $stmt = $pdo->query("DESCRIBE `$t` ");
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ",";
    }
    echo "\n";
}
