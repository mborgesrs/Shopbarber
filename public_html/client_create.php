<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $stmt = $pdo->prepare('INSERT INTO clients (name,email,phone,company,notes,date_nascto) VALUES (?,?,?,?,?,?)');
  $stmt->execute([$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['company'],$_POST['notes'],$_POST['date_nascto']?:null]);
  header('Location: clients.php');exit;
}
?>
<?php include __DIR__.'/../views/header.php'; ?>
<h2 class="text-xl font-bold mb-4">Nova Pessoa</h2>
<form method="post" class="bg-white p-4 rounded shadow max-w-lg">
  <label class="block mb-2">Nome <input name="name" required class="w-full border p-2 rounded"></label>
  <label class="block mb-2">E-mail <input name="email" class="w-full border p-2 rounded"></label>
  <label class="block mb-2">Telefone <input name="phone" id="phone" class="w-full border p-2 rounded" maxlength="15" onkeyup="handlePhone(event)"></label>
  <label class="block mb-2">Empresa <input name="company" class="w-full border p-2 rounded"></label>
  <label class="block mb-2">Data Nascimento <input type="date" name="date_nascto" class="w-full border p-2 rounded"></label>
  <label class="block mb-2">Observações <textarea name="notes" class="w-full border p-2 rounded"></textarea></label>
  <div class="flex items-center gap-2 mt-4">
    <a href="clients.php" class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 transition-colors">Voltar</a>
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Salvar</button>
  </div>
</form>
<?php include __DIR__.'/../views/footer.php'; ?>

<script>
function handlePhone(event) {
    let input = event.target;
    input.value = phoneMask(input.value);
}

function phoneMask(value) {
    if (!value) return "";
    value = value.replace(/\D/g, "");
    value = value.replace(/(\d{2})(\d)/, "($1) $2");
    value = value.replace(/(\d{5})(\d)/, "$1-$2");
    return value;
}
</script>
