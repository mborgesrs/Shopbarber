<?php
// Documentação: Este script substitui as colunas do Evolution API pelas da Zenvia API na tabela 'settings'
require_once __DIR__ . '/../db.php';

try {
    // 1. Remover colunas antigas (Evolution)
    $oldCols = ['evolution_api_url', 'evolution_instance', 'evolution_api_key', 'evolution_active'];
    foreach ($oldCols as $col) {
        $check = $pdo->prepare("SHOW COLUMNS FROM settings LIKE ?");
        $check->execute([$col]);
        if ($check->fetch()) {
            $pdo->exec("ALTER TABLE settings DROP COLUMN $col");
            echo "Coluna antiga $col removida.\n";
        }
    }

    // 2. Adicionar novas colunas (Zenvia)
    // Documentação: zenvia_api_token armazena o token de autenticação (ex: 'X-API-TOKEN')
    // Documentação: zenvia_sender_id armazena a palavra-chave de remetente aprovada pela Zenvia
    // Documentação: zenvia_active indica se os disparos estão ligados (0 ou 1)
    $newCols = [
        'zenvia_api_token' => 'VARCHAR(255) DEFAULT NULL',
        'zenvia_sender_id' => 'VARCHAR(100) DEFAULT NULL',
        'zenvia_active' => 'TINYINT(1) DEFAULT 0'
    ];

    foreach ($newCols as $col => $def) {
        $check = $pdo->prepare("SHOW COLUMNS FROM settings LIKE ?");
        $check->execute([$col]);
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE settings ADD COLUMN $col $def");
            echo "Nova coluna $col adicionada com sucesso.\n";
        } else {
            echo "Coluna $col já existe.\n";
        }
    }
    
    echo "Migração Zenvia API finalizada com sucesso!\n";
} catch (Exception $e) {
    die("Erro na migração: " . $e->getMessage() . "\n");
}
