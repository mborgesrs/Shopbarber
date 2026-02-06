<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$id = $_GET['id'] ?? null;
if(!$id){ header('Location: users.php'); exit; }

// Fetch user
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND company_id = ?');
$stmt->execute([$id, $_SESSION['company_id']]);
$user = $stmt->fetch();
if(!$user){ header('Location: users.php'); exit; }

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = trim($_POST['name']);
  $username = trim($_POST['username']);
  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';
  
  // Validations
  if(empty($name) || empty($username)){
    $error = 'Nome e usuário são obrigatórios';
  } else {
    // Check if username is taken by another user in this company
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? AND company_id = ? AND id != ?');
    $check->execute([$username, $_SESSION['company_id'], $id]);
    
    if($check->fetch()){
      $error = 'Este nome de usuário já está em uso nesta empresa';
    } else {
      try {
        // Update user
        if(!empty($password)){
          // Changing password
          if(strlen($password) < 6){
            $error = 'A senha deve ter no mínimo 6 caracteres';
          } elseif($password !== $password_confirm){
            $error = 'As senhas não coincidem';
          } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET name=?, username=?, password=? WHERE id=? AND company_id=?');
            $stmt->execute([$name, $username, $hashedPassword, $id, $_SESSION['company_id']]);
            $success = 'Usuário atualizado com sucesso!';
          }
        } else {
          // Not changing password
          $stmt = $pdo->prepare('UPDATE users SET name=?, username=? WHERE id=? AND company_id=?');
          $stmt->execute([$name, $username, $id, $_SESSION['company_id']]);
          $success = 'Usuário atualizado com sucesso!';
        }
        
        // Reload user data
        if($success){
          $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND company_id = ?');
          $stmt->execute([$id, $_SESSION['company_id']]);
          $user = $stmt->fetch();
        }
      } catch(PDOException $e) {
        $error = 'Erro ao atualizar usuário: ' . $e->getMessage();
      }
    }
  }
}
?>
<?php include __DIR__.'/../views/header.php'; ?>
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold text-slate-800 mb-6">Editar Usuário</h2>
    
    <?php if($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-medium"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div id="successMsg" class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-medium"><?= htmlspecialchars($success) ?></p>
        </div>
        <script>setTimeout(()=>document.getElementById('successMsg').style.display='none', 3000);</script>
    <?php endif; ?>
    
    <form method="post" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Nome Completo *</label>
            <input name="name" value="<?=htmlspecialchars($user['name'])?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
        </div>
        
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Nome de Usuário *</label>
            <input name="username" value="<?=htmlspecialchars($user['username'])?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
        </div>

        <div class="border-t border-slate-200 pt-4 mt-4 mb-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Alterar Senha (opcional)</h3>
            <p class="text-xs text-slate-500 mb-4">Deixe em branco para manter a senha atual</p>
            
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-slate-700">Nova Senha</label>
                <input type="password" name="password" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" autocomplete="new-password">
                <p class="text-xs text-slate-500 mt-1">Mínimo de 6 caracteres</p>
            </div>

            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-slate-700">Confirmar Nova Senha</label>
                <input type="password" name="password_confirm" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" autocomplete="new-password">
            </div>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="users.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors font-medium">Voltar</a>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow-sm transition-colors">Salvar Alterações</button>
        </div>
    </form>
</div>
<?php include __DIR__.'/../views/footer.php'; ?>
