<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Atualizando valores de status na tabela finance...\n";
    
    // Update NULL or empty to Aberto
    $stmt = $pdo->exec("UPDATE finance SET status = 'Aberto' WHERE status IS NULL OR status = ''");
    echo "Registros NULL/vazios atualizados para 'Aberto': $stmt\n";
    
    // Update Pendente to Aberto
    $stmt = $pdo->exec("UPDATE finance SET status = 'Aberto' WHERE status = 'Pendente'");
    echo "Registros 'Pendente' atualizados para 'Aberto': $stmt\n";
    
    // Update Pago to Liquidado
    $stmt = $pdo->exec("UPDATE finance SET status = 'Liquidado' WHERE status = 'Pago'");
    echo "Registros 'Pago' atualizados para 'Liquidado': $stmt\n";
    
    // Show current status distribution
    echo "\nDistribuição atual:\n";
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM finance GROUP BY status");
    while($row = $stmt->fetch()) {
        echo "  " . ($row['status'] ?: 'NULL') . ": " . $row['total'] . "\n";
    }
    
    echo "\nMigração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
