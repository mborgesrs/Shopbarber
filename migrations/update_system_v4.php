<?php
/**
 * Migration Script - System Improvements V4
 * Adds fields for Companies, Products, and Movements
 */

require_once __DIR__ . '/../db.php';

echo "Starting System Improvements V4 migration...\n";

try {
    // 1. Update companies table
    echo "Updating companies table...\n";
    $pdo->exec("ALTER TABLE companies 
        ADD COLUMN IF NOT EXISTS division ENUM('Clientes', 'Fornecedores', 'Profissionais', 'Outros') DEFAULT 'Outros',
        ADD COLUMN IF NOT EXISTS cep VARCHAR(10) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS number VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS neighborhood VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS state VARCHAR(2) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS cpf VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS parent_company_id INT DEFAULT NULL,
        ADD INDEX idx_parent_company (parent_company_id)
    ");
    echo "Companies table updated successfully.\n";

    // 2. Update products table
    echo "Updating products table...\n";
    $pdo->exec("ALTER TABLE products 
        ADD COLUMN IF NOT EXISTS pr_custo DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS pr_medio DECIMAL(10,2) DEFAULT 0.00
    ");
    echo "Products table updated successfully.\n";

    // 3. Update inventory_movements table
    echo "Updating inventory_movements table...\n";
    $pdo->exec("ALTER TABLE inventory_movements 
        ADD COLUMN IF NOT EXISTS supplier_id INT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS invoice_series VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) DEFAULT 0.00,
        ADD INDEX idx_supplier (supplier_id)
    ");
    echo "Inventory movements table updated successfully.\n";

    echo "\n✅ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
