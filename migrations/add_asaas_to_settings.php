<?php
require_once __DIR__ . '/../db.php';

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

try {
    echo "Updating settings table with Asaas API fields...\n";
    
    $cols = [
        'asaas_api_key' => "VARCHAR(255) DEFAULT NULL",
        'asaas_environment' => "ENUM('sandbox', 'production') DEFAULT 'sandbox'"
    ];

    foreach ($cols as $col => $type) {
        if (!columnExists($pdo, 'settings', $col)) {
            echo "Adding $col to settings...\n";
            $pdo->exec("ALTER TABLE settings ADD COLUMN $col $type");
        } else {
            echo "Column $col already exists in settings.\n";
        }
    }

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
