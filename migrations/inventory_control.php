<?php
/**
 * Migration Script - Inventory Control
 * Adds fields to products table and creates inventory_movements table
 */

require_once __DIR__ . '/../db.php';

echo "Starting inventory control migration...\n";

try {
    // 1. Update products table
    echo "Updating products table...\n";
    $pdo->exec("ALTER TABLE products 
        ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS type ENUM('Ativo', 'Serviço') DEFAULT 'Serviço',
        ADD COLUMN IF NOT EXISTS unit VARCHAR(20) DEFAULT 'un'
    ");
    echo "Products table updated successfully.\n";

    // 2. Create inventory_movements table
    echo "Creating inventory_movements table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `inventory_movements` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `company_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `date` DATE NOT NULL,
        `supplier` varchar(200) DEFAULT NULL,
        `quantity` decimal(10,2) NOT NULL,
        `price` decimal(10,2) DEFAULT 0.00,
        `type` varchar(50) NOT NULL COMMENT 'especie: consumo, saida, entrada, etc',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_company_product` (`company_id`, `product_id`),
        CONSTRAINT `fk_inv_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_inv_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Inventory movements table created successfully.\n";

    echo "\n✅ Migration completed successfully!\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') === false) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        echo "⚠️  Some columns already exist (this is OK).\n";
    }
}
