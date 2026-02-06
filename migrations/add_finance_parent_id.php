<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Adicionando coluna 'parent_id' à tabela finance...\n";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM finance LIKE 'parent_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE finance ADD COLUMN parent_id INT NULL DEFAULT NULL AFTER liquidacao_id");
        $pdo->exec("ALTER TABLE finance ADD CONSTRAINT fk_finance_parent FOREIGN KEY (parent_id) REFERENCES finance(id) ON DELETE SET NULL");
        echo "Coluna 'parent_id' adicionada com chave estrangeira.\n";
    }
    
    echo "\nMigração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
