<?php
require_once __DIR__ . '/../db.php';
$tables = ['contas', 'portadores', 'tipos_pagamento'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $stmt = $pdo->query("DESCRIBE `$table`");
    while ($row = $stmt->fetch()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
