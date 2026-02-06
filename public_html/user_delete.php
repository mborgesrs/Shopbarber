<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$id = $_GET['id'] ?? null;
if($id){
  // Prevent user from deleting themselves
  if($id == $_SESSION['user_id']){
    header('Location: users.php?error=self_delete');
    exit;
  }
  
  $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND company_id = ?');
  $stmt->execute([$id, $_SESSION['company_id']]);
}
header('Location: users.php');
exit;
