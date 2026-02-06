<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = $_POST['name'];
  $fantasy = $_POST['fantasy_name'];
  $doc = $_POST['document'];
  $status = $_POST['status'];
  
  // Define 7 days trial
  $trial_ends_at = date('Y-m-d H:i:s', strtotime('+7 days'));
  $sub_status = ($status === 'active') ? 'trialing' : 'inactive';

  $stmt = $pdo->prepare('INSERT INTO companies (name,fantasy_name,document,status,subscription_status,trial_ends_at) VALUES (?,?,?,?,?,?)');
  $stmt->execute([$name, $fantasy, $doc, $status, $sub_status, $trial_ends_at]);
  header('Location: companies.php');exit;
}
?>
<?php include __DIR__.'/../views/header.php'; ?>
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold text-slate-800 mb-6">Nova Empresa</h2>
    <form method="post" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Razão Social</label>
            <input name="name" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
        </div>
        
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Nome Fantasia</label>
            <input name="fantasy_name" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
        </div>

        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Documento (CNPJ/CPF)</label>
            <input name="document" id="document" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" placeholder="00.000.000/0000-00 ou 000.000.000-00">
            <p class="text-xs text-slate-500 mt-1">Digite apenas números - a máscara será aplicada automaticamente</p>
        </div>

        <div class="mb-6">
            <label class="block mb-1 text-sm font-medium text-slate-700">Status</label>
            <select name="status" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
            </select>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="companies.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors font-medium">Voltar</a>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow-sm transition-colors">Salvar Empresa</button>
        </div>
    </form>
</div>

<script>
// Automatic CPF/CNPJ mask
document.getElementById('document').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        // CPF mask: 000.000.000-00
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        // CNPJ mask: 00.000.000/0000-00
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
    }
    
    e.target.value = value;
});
</script>

<?php include __DIR__.'/../views/footer.php'; ?>
