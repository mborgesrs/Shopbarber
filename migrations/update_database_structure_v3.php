<?php
require_once __DIR__ . '/../db.php';
echo "Starting V3 migration...\n";

try {
    // 1. Ensure professional_id in quotes
    echo "Checking quotes table for professional_id...\n";
    $pdo->exec("ALTER TABLE quotes 
        ADD COLUMN IF NOT EXISTS professional_id INT DEFAULT NULL
    ");
    // FK
    $pdo->exec("ALTER TABLE quotes
        ADD CONSTRAINT fk_quotes_professional FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE SET NULL
    ");

    // 2. Ensure professionals table exists (it was in file list, but maybe not in DB?)
    // If quote_edit.php references it, it should exist. But let's be safe.
    $pdo->exec("CREATE TABLE IF NOT EXISTS professionals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        phone VARCHAR(50),
        active TINYINT(1) DEFAULT 1
    )");

    echo "✅ Migration V3 completed.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') === false && strpos($e->getMessage(), 'Duplicate key') === false) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    } else {
        echo "⚠️  Changes already applied.\n";
    }
}
