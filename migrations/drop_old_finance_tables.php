<?php
require_once __DIR__ . '/../db.php';

echo "Removendo tabelas antigas criadas anteriormente...\n\n";

try {
    // Drop old tables
    $tables = ['finance_payment_types', 'finance_accounts', 'finance_bearers'];
    
    foreach($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "✓ Tabela '$table' removida com sucesso.\n";
        } catch(PDOException $e) {
            echo "⚠ Erro ao remover '$table': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Limpeza concluída!\n";
    echo "\nTabelas corretas em uso:\n";
    echo "- portadores\n";
    echo "- contas\n";
    echo "- tipos_pagamento\n";
    
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
