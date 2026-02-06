<?php
require_once __DIR__ . '/../db.php';

try {
    echo "Atualizando TODOS os registros para Aberto...\n";
    
    $count = $pdo->exec("UPDATE finance SET status = 'Aberto'");
    echo "Total atualizado: $count\n";
    
    // Show distribution
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM finance GROUP BY status");
    echo "\nDistribuição:\n";
    while($row = $stmt->fetch()) {
        echo "  '" . $row['status'] . "': " . $row['total'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
