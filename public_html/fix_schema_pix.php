<?php
require_once __DIR__ . '/../db.php';

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN pix_key_type VARCHAR(20) DEFAULT 'cpf'");
    $pdo->exec("ALTER TABLE settings ADD COLUMN pix_key VARCHAR(100) DEFAULT ''");
    $pdo->exec("ALTER TABLE settings ADD COLUMN pix_merchant_name VARCHAR(100) DEFAULT ''");
    $pdo->exec("ALTER TABLE settings ADD COLUMN pix_merchant_city VARCHAR(100) DEFAULT ''");
    echo "Schema updated successfully.";
} catch (PDOException $e) {
    if(strpos($e->getMessage(), 'Duplicate column') !== false) {
         echo "Columns already exist.";
    } else {
         echo "Error: " . $e->getMessage();
    }
}
