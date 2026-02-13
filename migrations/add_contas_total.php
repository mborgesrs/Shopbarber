<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Adicionando coluna 'total' à tabela contas...\n";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM contas LIKE 'total'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE contas ADD COLUMN total DECIMAL(15,2) DEFAULT 0.00 AFTER tipo");
        echo "Coluna 'total' adicionada com sucesso.\n";
    } else {
        echo "Coluna 'total' já existe.\n";
    }
    
    echo "\nMigração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
