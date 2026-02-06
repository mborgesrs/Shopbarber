<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Forçando atualização de todos os registros...\n";
    
    // Force update all records
    $count = $pdo->exec("UPDATE finance SET status = CASE 
        WHEN status = 'Pago' THEN 'Liquidado'
        WHEN status = 'Pendente' THEN 'Aberto'
        WHEN status IS NULL THEN 'Aberto'
        WHEN status = '' THEN 'Aberto'
        ELSE status
    END");
    
    echo "Total de registros atualizados: $count\n";
    
    // Show distribution
    echo "\nDistribuição final:\n";
    $stmt = $pdo->query("SELECT COALESCE(status, 'NULL') as status, COUNT(*) as total FROM finance GROUP BY status");
    while($row = $stmt->fetch()) {
        echo "  " . $row['status'] . ": " . $row['total'] . "\n";
    }
    
    echo "\nConcluído!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
