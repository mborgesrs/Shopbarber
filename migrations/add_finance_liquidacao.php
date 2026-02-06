<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Adicionando coluna liquidacao_id à tabela finance...\n";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM finance LIKE 'liquidacao_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE finance ADD COLUMN liquidacao_id INT NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE finance ADD CONSTRAINT fk_finance_liquidacao FOREIGN KEY (liquidacao_id) REFERENCES finance(id) ON DELETE SET NULL");
        echo "Coluna liquidacao_id adicionada com sucesso!\n";
    } else {
        echo "Coluna liquidacao_id já existe.\n";
    }
    
    echo "\nMigração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
