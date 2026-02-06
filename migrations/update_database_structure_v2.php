<?php
require_once __DIR__ . '/../db.php';

echo "Starting V2 database migration...\n";

try {
    // 1. Settings: Name Fantasia, Logo
    echo "Updating settings table...\n";
    $pdo->exec("ALTER TABLE settings 
        ADD COLUMN IF NOT EXISTS nome_fantasia VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS logo VARCHAR(255) DEFAULT NULL
    ");

    // 2. Products: Duration
    echo "Updating products table...\n";
    $pdo->exec("ALTER TABLE products 
        ADD COLUMN IF NOT EXISTS duration_minutes INT DEFAULT 30
    ");

    // 3. Quotes: End Time
    echo "Updating quotes table...\n";
    $pdo->exec("ALTER TABLE quotes 
        ADD COLUMN IF NOT EXISTS end_time DATETIME DEFAULT NULL
    ");

    // 4. Quote Items: Duration (in case we want to customize per item)
    echo "Updating quote_items table...\n";
    $pdo->exec("ALTER TABLE quote_items 
        ADD COLUMN IF NOT EXISTS duration INT DEFAULT 30
    ");

    // 5. Configurar link com financeiro se precisar (já inferido pelo fluxo, mas talvez adicionar status pago na quote ajude, mas já tem status)

    echo "\n✅ Migration V2 completed successfully!\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') === false) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    } else {
        echo "⚠️  Columns already exist.\n";
    }
}
