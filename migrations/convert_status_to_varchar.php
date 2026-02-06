<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE finance status");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Tipo atual da coluna status: " . $col['Type'] . "\n";

try {
    echo "Alterando coluna status para permitir 'Aberto' e 'Liquidado'...\n";
    // Using VARCHAR(50) instead of ENUM to be more flexible, or just updating the ENUM
    $pdo->exec("ALTER TABLE finance MODIFY COLUMN status VARCHAR(50) DEFAULT 'Aberto'");
    echo "Coluna alterada para VARCHAR(50).\n";
} catch (Exception $e) {
    echo "Erro ao alterar coluna: " . $e->getMessage() . "\n";
}
