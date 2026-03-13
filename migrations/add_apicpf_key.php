<?php
require_once __DIR__ . '/../db.php';

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS apicpf_key VARCHAR(255) DEFAULT NULL");
    echo "Coluna apicpf_key adicionada com sucesso à tabela settings.\n";
} catch (PDOException $e) {
    echo "Erro ao adicionar coluna: " . $e->getMessage() . "\n";
}
