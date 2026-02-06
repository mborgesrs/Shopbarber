<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query('SHOW TABLES');
while($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $table = $row[0];
    if (in_array($table, ['companies', 'migrations'])) continue;
    $q = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'company_id'");
    if ($q->fetch()) {
        echo "[OK] $table\n";
    } else {
        echo "[!!] $table (SEM company_id)\n";
    }
}
