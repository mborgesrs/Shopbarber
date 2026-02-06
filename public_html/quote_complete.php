<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = $_POST['id'] ?? null;
    $paymentMethod = $_POST['payment_method'] ?? 'Dinheiro';
    
    if($id){
        // Fetch Quote info
        $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id = ?");
        $stmt->execute([$id]);
        $quote = $stmt->fetch();

        if($quote && $quote['status'] !== 'Atendido'){
            // Fetch Items
            $stmtItems = $pdo->prepare("SELECT p.name FROM quote_items qi JOIN products p ON p.id=qi.product_id WHERE qi.quote_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_COLUMN);
            
            $desc = "Serviço Ref. Agendamento #{$id}";
            if($items){
                $desc .= ": " . implode(", ", $items);
            }
            $desc .= " (Pagamento: $paymentMethod)";

            try {
                $pdo->beginTransaction();
                
                // Insert Finance Logic
                $stmtFin = $pdo->prepare("INSERT INTO finance (date, client_id, observation, value, type) VALUES (?, ?, ?, ?, 'Entrada')");
                $dateNow = date('Y-m-d');
                $stmtFin->execute([$dateNow, $quote['client_id'], $desc, $quote['total']]);

                // Update Status
                $stmtUp = $pdo->prepare("UPDATE quotes SET status='Atendido' WHERE id=?");
                $stmtUp->execute([$id]);
                
                $pdo->commit();
            } catch(Exception $e) {
                $pdo->rollBack();
                // In a real app we might want to log this or show an error
            }
        }
    }
}
header('Location: quotes.php'); exit;
