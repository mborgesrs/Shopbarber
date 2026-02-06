<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $stmt = $pdo->prepare('INSERT INTO products (name,description,price) VALUES (?,?,?)');
  $stmt->execute([$_POST['name'],$_POST['description'],$_POST['price']]);
  header('Location: products.php');exit;
}
?>
<?php include __DIR__.'/../views/header.php'; ?>
<h2 class="text-xl font-bold mb-4">Novo Produto / Serviço</h2>
<form method="post" class="bg-white p-4 rounded shadow max-w-lg">
  <label class="block mb-2">Nome <input name="name" required class="w-full border p-2 rounded"></label>
  <label class="block mb-2">Descrição <textarea name="description" class="w-full border p-2 rounded"></textarea></label>
  <label class="block mb-4">Preço <input name="price" type="number" step="0.01" class="w-full border p-2 rounded"></label>
  <button class="bg-blue-600 text-white px-4 py-2 rounded">Salvar</button>
</form>
<?php include __DIR__.'/../views/footer.php'; ?>
