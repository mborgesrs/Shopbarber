<?php
require_once __DIR__ . '/../db.php';

try {
    $sql = "ALTER TABLE clients 
            ADD COLUMN IF NOT EXISTS division ENUM('Clientes', 'Fornecedores', 'Profissionais', 'Outros') DEFAULT 'Clientes',
            ADD COLUMN IF NOT EXISTS cep VARCHAR(10) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS number VARCHAR(20) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS neighborhood VARCHAR(100) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS state VARCHAR(2) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS cpf VARCHAR(20) DEFAULT NULL";
    
    $pdo->exec($sql);
    echo "Migration for clients table completed successfully.\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
