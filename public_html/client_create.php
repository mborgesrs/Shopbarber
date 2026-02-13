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

<div class="max-w-4xl mx-auto px-4 py-4">
    <div class="flex items-center gap-4 mb-6">
        <a href="clients.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Nova Pessoa</h2>
    </div>

    <form method="post" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome Completo</label>
                <input name="name" required placeholder="Ex: João Silva" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">E-mail</label>
                <input name="email" type="email" placeholder="email@exemplo.com" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Telefone</label>
                <input name="phone" id="phone" placeholder="(00) 00000-0000" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700" maxlength="15" onkeyup="handlePhone(event)">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Empresa</label>
                <input name="company" placeholder="Nome da empresa (opcional)" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Data de Nascimento</label>
                <input type="date" name="date_nascto" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Observações</label>
                <textarea name="notes" placeholder="Alguma observação importante sobre o cliente..." class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700 min-h-[60px]"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
            <a href="clients.php" class="bg-white border border-slate-300 text-slate-700 px-6 py-2.5 rounded-xl hover:bg-slate-50 font-medium transition-colors">Voltar</a>
            <button class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">Cadastrar Pessoa</button>
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
