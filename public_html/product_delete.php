<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$id = $_GET['id'] ?? null; if($id){ $stmt=$pdo->prepare('DELETE FROM products WHERE id=?'); $stmt->execute([$id]); }
header('Location: products.php');exit;
