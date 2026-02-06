<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$id = $_GET['id'] ?? 0;
// Check if used in quotes
$companyId = $_SESSION['company_id'];
$chk = $pdo->prepare("SELECT count(*) FROM quotes WHERE professional_id=? AND company_id=?");
$chk->execute([$id, $companyId]);
if($chk->fetchColumn() > 0){
    // Determine soft delete or error. Plan was 'Deletar', but soft delete is safer.
    // Let's implement active=0
    $pdo->prepare("UPDATE professionals SET active=0 WHERE id=? AND company_id=?")->execute([$id, $companyId]);
} else {
    $pdo->prepare("DELETE FROM professionals WHERE id=? AND company_id=?")->execute([$id, $companyId]);
}
header('Location: professionals.php');
