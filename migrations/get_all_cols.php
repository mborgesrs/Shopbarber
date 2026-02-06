<?php
require_once __DIR__ . '/../db.php';
$tables = ['contas', 'portadores', 'tipos_pagamento'];
foreach ($tables as $t) {
    $stmt = $pdo->query("DESCRIBE `$t` text"); // typo here, let's just do it right
    $cols = [];
    $stmt = $pdo->query("DESCRIBE `$t` ");
    while($row = $stmt->fetch()) {
        $cols[] = $row['Field'];
    }
    echo "$t: " . implode(",", $cols) . "\n";
}
