<?php
require_once __DIR__ . '/../db.php';

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN email VARCHAR(255) NULL AFTER phone");
    echo "Column 'email' added to 'settings' table.\n";
} catch (PDOException $e) {
    echo "Error adding column: " . $e->getMessage() . "\n";
}
