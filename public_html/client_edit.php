<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$id = $_GET['id'] ?? null;
$error = '';
$success = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  // accept id from POST (when form posts without querystring)
  $id = $_POST['id'] ?? $id;
  if(!$id){ header('Location: clients.php');exit; }
  $name = trim($_POST['name'] ?? '');
  if($name === ''){ $error = 'O nome é obrigatório.'; }
  else{
    try{
      $stmt = $pdo->prepare('UPDATE clients SET name=:name,email=:email,phone=:phone,company=:company,notes=:notes,date_nascto=:date_nascto WHERE id=:id');
      $date_nascto = !empty($_POST['date_nascto']) ? $_POST['date_nascto'] : null;
      $stmt->execute([
        ':name'=>$name,
        ':email'=>$_POST['email']?:null,
        ':phone'=>$_POST['phone']?:null,
        ':company'=>$_POST['company']?:null,
        ':notes'=>$_POST['notes']?:null,
        ':date_nascto'=>$date_nascto,
        ':id'=>$id
      ]);
      $success = 'Cliente atualizado com sucesso.';
    }catch(PDOException $e){
      $error = 'Erro ao atualizar: '.$e->getMessage();
    }
  }
}

if(!$id){ header('Location: clients.php');exit; }
$stmt = $pdo->prepare('SELECT * FROM clients WHERE id=?'); $stmt->execute([$id]); $client = $stmt->fetch();
if(!$client){ header('Location: clients.php');exit; }
?>
<?php include __DIR__.'/../views/header.php'; ?>
<h2 class="text-xl font-bold mb-4">Editar Cliente</h2>
<?php if($error): ?><div class="p-3 mb-4 bg-red-100 text-red-800 rounded"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if($success): ?><div class="p-3 mb-4 bg-green-100 text-green-800 rounded"><?=htmlspecialchars($success)?></div><?php endif; ?>
<form method="post" class="bg-white p-4 rounded shadow max-w-lg">
  <input type="hidden" name="id" value="<?=htmlspecialchars($id)?>">
  <label class="block mb-2">Nome <input name="name" value="<?=htmlspecialchars($client['name'] ?? '')?>" required class="w-full border p-2 rounded"></label>
  <label class="block mb-2">E-mail <input name="email" value="<?=htmlspecialchars($client['email'] ?? '')?>" class="w-full border p-2 rounded"></label>
  <label class="block mb-2">Telefone <input name="phone" value="<?=htmlspecialchars($client['phone'] ?? '')?>" class="w-full border p-2 rounded"></label>
  <label class="block mb-2">Empresa <input name="company" value="<?=htmlspecialchars($client['company'] ?? '')?>" class="w-full border p-2 rounded"></label>
  <label class="block mb-2">Data Nascimento <input type="date" name="date_nascto" value="<?=htmlspecialchars($client['date_nascto'] ?? '')?>" class="w-full border p-2 rounded"></label>
  <label class="block mb-2">Observações <textarea name="notes" class="w-full border p-2 rounded"><?=htmlspecialchars($client['notes'] ?? '')?></textarea></label>
  <div class="flex items-center gap-2">
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Salvar</button>
    <a href="clients.php" class="text-sm text-gray-600">Voltar</a>
  </div>
</form>
<?php include __DIR__.'/../views/footer.php'; ?>
