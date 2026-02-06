<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Atualizando registros financeiros...\n";
    
    // Convert old ENUM values to new ones
    $pdo->exec("UPDATE finance SET status = 'Aberto' WHERE status = 'Pendente' OR status = '' OR status IS NULL");
    $pdo->exec("UPDATE finance SET status = 'Liquidado' WHERE status = 'Pago'");
    
    // Explicitly update ID 1 as requested
    $stmt = $pdo->prepare("UPDATE finance SET status = 'Liquidado' WHERE id = 1");
    $stmt->execute();
    
    echo "Lançamento 1 atualizado para 'Liquidado'.\n";
    
    // Show final distribution
    $res = $pdo->query("SELECT status, COUNT(*) as c FROM finance GROUP BY status");
    echo "\nDistribuição final:\n";
    while($row = $res->fetch()) {
        echo "  " . ($row['status']?:'EMPTY') . ": " . $row['c'] . "\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
