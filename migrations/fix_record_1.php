<?php
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->prepare('SELECT id, status, type FROM finance WHERE id = ?');
    $stmt->execute([1]);
    $row = $stmt->fetch();
    
    if (!$row) {
        echo "Registro com ID 1 não encontrado.\n";
    } else {
        echo "Antes: ID={$row['id']}, Tipo={$row['type']}, Situação={$row['status']}\n";
        
        $update = $pdo->prepare('UPDATE finance SET status = ? WHERE id = ?');
        $update->execute(['Liquidado', 1]);
        
        echo "Depois: ID={$row['id']}, Tipo={$row['type']}, Situação=Liquidado\n";
        echo "Linhas afetadas: " . $update->rowCount() . "\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
