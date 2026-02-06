<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Modifying finance table columns type and status to VARCHAR...\n";
    
    // Modify type column to VARCHAR(50) to allow dPago, cRecebido, etc.
    $pdo->exec("ALTER TABLE finance MODIFY COLUMN type VARCHAR(50) NOT NULL");
    echo "Column 'type' modified to VARCHAR(50).\n";

    // Modify status column to VARCHAR(50) to allow Liquidado, etc.
    $pdo->exec("ALTER TABLE finance MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Aberto'");
    echo "Column 'status' modified to VARCHAR(50).\n";

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
