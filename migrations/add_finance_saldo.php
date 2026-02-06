<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Adicionando coluna 'saldo' à tabela finance...\n";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM finance LIKE 'saldo'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE finance ADD COLUMN saldo DECIMAL(15,2) NULL DEFAULT NULL AFTER value");
        echo "Coluna 'saldo' adicionada.\n";
    }
    
    // Initialize saldo with value for existing records
    $count = $pdo->exec("UPDATE finance SET saldo = value WHERE saldo IS NULL");
    echo "Saldo inicializado em $count registros.\n";
    
    echo "\nMigração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
