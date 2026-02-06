<?php
require_once __DIR__ . '/../db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS banks (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, code VARCHAR(20), company_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS accounts (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, bank_id INT, company_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS bearers (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, company_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_types (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, company_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    echo "Tabelas financeiras criadas com sucesso.";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
