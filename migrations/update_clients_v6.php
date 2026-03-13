<?php
require_once __DIR__ . '/../db.php';

try {
    $sql = "ALTER TABLE clients 
            ADD COLUMN IF NOT EXISTS person_type ENUM('Fisica', 'Juridica') DEFAULT 'Fisica',
            ADD COLUMN IF NOT EXISTS cnpj VARCHAR(20) DEFAULT NULL";
    
    $pdo->exec($sql);
    echo "Migration for clients table (person_type) completed successfully.\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
