<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$id = $_GET['id'] ?? null;
if($id){
    // Update Status to Cancelado
    $stmt = $pdo->prepare("UPDATE quotes SET status='Cancelado' WHERE id=?");
    $stmt->execute([$id]);
}
header('Location: quotes.php'); exit;
