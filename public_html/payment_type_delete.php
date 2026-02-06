<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
$id = $_GET['id'] ?? null;
if($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tipos_pagamento WHERE id = ?");
        $stmt->execute([$id]);
    } catch(Exception $e) {}
}
header('Location: payment_types.php');
exit;
