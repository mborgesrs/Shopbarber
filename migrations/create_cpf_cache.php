<?php
/**
 * Cria a tabela `cpf_cache` para armazenar consultas e evitar excesso de requisições na API.
 * Execute via CLI: php migrations/create_cpf_cache.php
 */
require_once __DIR__ . '/../db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS cpf_cache (
        cpf VARCHAR(11) PRIMARY KEY,
        data TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabela `cpf_cache` criada ou já existente.\n";
} catch (PDOException $e) {
    echo "ERRO ao criar tabela: " . $e->getMessage() . "\n";
    exit(1);
}
