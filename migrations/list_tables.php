<?php
require_once __DIR__ . '/../db.php';

echo "=== TABELAS EXISTENTES ===\n\n";

$stmt = $pdo->query("SHOW TABLES");
while($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $table = $row[0];
    echo "Tabela: $table\n";
    
    if(stripos($table, 'portador') !== false || 
       stripos($table, 'conta') !== false || 
       stripos($table, 'tipo') !== false ||
       stripos($table, 'pagamento') !== false) {
        $desc = $pdo->query("DESCRIBE `$table`")->fetchAll();
        foreach($desc as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
    }
}
