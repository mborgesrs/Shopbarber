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
      $success = 'Pessoa atualizada com sucesso.';
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

<div class="max-w-4xl mx-auto px-4 py-4">
    <div class="flex items-center gap-4 mb-6">
        <a href="clients.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Editar Pessoa</h2>
    </div>

    <?php if($error): ?>
        <div class="bg-red-50 border border-red-100 text-red-700 p-4 rounded-2xl mb-6 flex items-center gap-3 animate-fade-in">
            <i class="fas fa-exclamation-circle"></i>
            <span class="text-sm"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 p-4 rounded-2xl mb-6 flex items-center gap-3 animate-fade-in">
            <i class="fas fa-check-circle"></i>
            <span class="text-sm"><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
        <input type="hidden" name="id" value="<?=htmlspecialchars($id)?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome Completo</label>
                <input name="name" value="<?=htmlspecialchars($client['name'] ?? '')?>" required class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">E-mail</label>
                <input name="email" value="<?=htmlspecialchars($client['email'] ?? '')?>" type="email" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Telefone</label>
                <input name="phone" id="phone" value="<?=htmlspecialchars($client['phone'] ?? '')?>" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700" maxlength="15" onkeyup="handlePhone(event)">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Empresa</label>
                <input name="company" value="<?=htmlspecialchars($client['company'] ?? '')?>" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Data de Nascimento</label>
                <input type="date" name="date_nascto" value="<?=htmlspecialchars($client['date_nascto'] ?? '')?>" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Observações</label>
                <textarea name="notes" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700 min-h-[60px]"><?=htmlspecialchars($client['notes'] ?? '')?></textarea>
            </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
            <a href="clients.php" class="bg-white border border-slate-300 text-slate-700 px-6 py-2.5 rounded-xl hover:bg-slate-50 font-medium transition-colors">Voltar</a>
            <button class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">Salvar Alterações</button>
        </div>
    </form>
</div>

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
