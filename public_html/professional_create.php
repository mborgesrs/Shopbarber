<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    // Checking if email exists in table is tricky without checking, but I'll assume name/phone for now based on previous file logic.
    // If I want to be safe I can try catchy or just stick to name/phone.
    $stmt=$pdo->prepare('INSERT INTO professionals (name,phone,company_id) VALUES (?,?,?)'); 
    $stmt->execute([$_POST['name'],$_POST['phone'], $_SESSION['company_id']]);
    header('Location: professionals.php'); exit;
}
?>
<?php include __DIR__ . '/../views/header.php'; ?>
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold text-slate-800 mb-6">Novo Profissional</h2>
    <form method="post" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <label class="block mb-4">
            <span class="text-sm font-medium text-slate-700">Nome</span>
            <input name="name" class="w-full border border-slate-300 rounded p-2 mt-1 focus:ring-blue-500 text-slate-700" required>
        </label>
        <label class="block mb-6">
            <span class="text-sm font-medium text-slate-700">Telefone</span>
            <input name="phone" class="w-full border border-slate-300 rounded p-2 mt-1 focus:ring-blue-500 text-slate-700 mask-phone">
        </label>
        
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="professionals.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors font-medium">Voltar</a>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow-sm transition-colors">Salvar</button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
