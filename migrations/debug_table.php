<?php
require_once __DIR__ . '/../db.php';
$table = 'contas';
$stmt = $pdo->query("DESCRIBE $table");
echo "Estrutura da tabela $table:\n";
while ($row = $stmt->fetch()) {
    print_r($row);
}
