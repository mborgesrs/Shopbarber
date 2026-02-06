<?php
require_once __DIR__ . '/../db.php';
try {
    // List of tables that need company_id
    $tables = [
        'banks', 
        'payment_types', 
        'quote_items', 
        'appointments', 
        'contas', 
        'portadores', 
        'services', 
        'tipos_pagamento'
    ];
    
    foreach ($tables as $table) {
        $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'company_id'")->fetch();
        if (!$check) {
            echo "Adicionando company_id na tabela $table...\n";
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN company_id INT NOT NULL DEFAULT 1");
        } else {
            echo "Tabela $table já possui company_id.\n";
        }
    }
    
    echo "Verificação e correção concluída.";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
