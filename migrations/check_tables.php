<?php
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tabelas encontradas:\n";
    foreach($tables as $table) {
        echo "- $table\n";
        
        // Show structure for finance-related tables
        if(stripos($table, 'portador') !== false || 
           stripos($table, 'conta') !== false || 
           stripos($table, 'pagamento') !== false) {
            $desc = $pdo->query("DESCRIBE $table")->fetchAll();
            echo "  Estrutura:\n";
            foreach($desc as $col) {
                echo "    {$col['Field']} ({$col['Type']})\n";
            }
        }
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
