<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = trim($_POST['name']);
  $username = trim($_POST['username']);
  $password = $_POST['password'];
  $password_confirm = $_POST['password_confirm'];
  
  // Validations
  if(empty($name) || empty($username) || empty($password)){
    $error = 'Todos os campos são obrigatórios';
  } elseif(strlen($password) < 6){
    $error = 'A senha deve ter no mínimo 6 caracteres';
  } elseif($password !== $password_confirm){
    $error = 'As senhas não coincidem';
  } else {
    // Check if username already exists in this company
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? AND company_id = ?');
    $check->execute([$username, $_SESSION['company_id']]);
    
    if($check->fetch()){
      $error = 'Este nome de usuário já está em uso nesta empresa';
    } else {
      try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, username, password, company_id, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $username, $hashedPassword, $_SESSION['company_id']]);
        
        header('Location: users.php');
        exit;
      } catch(PDOException $e) {
        $error = 'Erro ao criar usuário: ' . $e->getMessage();
      }
    }
  }
}
?>
<?php include __DIR__.'/../views/header.php'; ?>
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold text-slate-800 mb-6">Novo Usuário</h2>
    
    <?php if($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-medium"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Nome Completo *</label>
            <input name="name" value="<?=htmlspecialchars($_POST['name'] ?? '')?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
        </div>
        
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Nome de Usuário *</label>
            <input name="username" value="<?=htmlspecialchars($_POST['username'] ?? '')?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" autocomplete="off">
            <p class="text-xs text-slate-500 mt-1">Este será usado para fazer login no sistema</p>
        </div>

        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Senha *</label>
            <input type="password" name="password" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" autocomplete="new-password">
            <p class="text-xs text-slate-500 mt-1">Mínimo de 6 caracteres</p>
        </div>

        <div class="mb-6">
            <label class="block mb-1 text-sm font-medium text-slate-700">Confirmar Senha *</label>
            <input type="password" name="password_confirm" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" autocomplete="new-password">
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="users.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors font-medium">Voltar</a>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow-sm transition-colors">Criar Usuário</button>
        </div>
    </form>
</div>
<?php include __DIR__.'/../views/footer.php'; ?>
