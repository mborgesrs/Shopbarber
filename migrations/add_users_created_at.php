<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Verificando estrutura da tabela users...\n";
    
    // Check if created_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "Adicionando coluna created_at à tabela users...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "Coluna created_at adicionada com sucesso!\n";
    } else {
        echo "Coluna created_at já existe.\n";
    }
    
    echo "\nMigração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
