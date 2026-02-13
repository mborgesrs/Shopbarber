<?php
session_start(); 
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/accounts.php';

$id = $_GET['id'] ?? null;
if(!$id){ header('Location: finance.php'); exit; }

// Fetch the record to be deleted
$stmt = $pdo->prepare('SELECT * FROM finance WHERE id = ? AND company_id = ?');
$stmt->execute([$id, $_SESSION['company_id']]);
$record = $stmt->fetch();

if(!$record){ 
    header('Location: finance.php'); 
    exit; 
}

try {
    $pdo->beginTransaction();
    
    // Check if it's a liquidation record (has parent_id)
    if($record['parent_id']){
        // Fetch parent record
        $stmtParent = $pdo->prepare('SELECT * FROM finance WHERE id = ?');
        $stmtParent->execute([$record['parent_id']]);
        $parent = $stmtParent->fetch();
        
        if($parent){
            // Restore saldo to parent
            $newSaldo = $parent['saldo'] + $record['value'];
            
            // Determine updates needed
            $updates = [
                'saldo' => $newSaldo,
                'liquidacao_id' => ($parent['liquidacao_id'] == $id) ? null : $parent['liquidacao_id']
            ];
            
            // Revert Status and Type if needed
            // If previous type was dPago (Despesa Paga), revert to Pagar
            if ($parent['type'] == 'dPago') {
                $updates['type'] = 'Pagar';
                $updates['status'] = 'Aberto';
                $updates['data_pagamento'] = null; // Clear payment date
            }
            // If previous type was cRecebido (Conta Recebida), revert to Receber
            elseif ($parent['type'] == 'cRecebido') {
                $updates['type'] = 'Receber';
                $updates['status'] = 'Aberto';
                $updates['data_pagamento'] = null; // Clear payment date
            }
            // Generic fallback: if balance becomes positive, ensure it is considered Open
            elseif ($newSaldo > 0) {
                 $updates['status'] = 'Aberto';
                 // If full reversal, verify logic? 
                 // Assuming partial payments keep it as 'Aberto'.
                 // We don't touch type if it wasn't one of the special converted types
                 if($newSaldo >= $record['value'] && $parent['status'] == 'Liquidado') {
                      $updates['data_pagamento'] = null;
                 }
            }
            
            // dynamic update query construction
            $sql = 'UPDATE finance SET ';
            $params = [];
            foreach($updates as $key => $val){
                $sql .= "$key = ?, ";
                $params[] = $val;
            }
            $sql = rtrim($sql, ', ') . ' WHERE id = ?';
            $params[] = $parent['id'];
            
            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->execute($params);
        }
    }
    
    // Delete the record
    $stmtDelete = $pdo->prepare('DELETE FROM finance WHERE id = ? AND company_id = ?');
    $stmtDelete->execute([$id, $_SESSION['company_id']]);
    
    recalculateAccountTotals($_SESSION['company_id'], $pdo);
    
    $pdo->commit();
    header('Location: finance.php?msg=deleted');
    exit;
    
} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    header('Location: finance.php?error=delete_failed');
    exit;
}
