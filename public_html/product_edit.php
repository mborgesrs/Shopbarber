<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$id = $_GET['id'] ?? null; if(!$id) header('Location: products.php');
$stmt=$pdo->prepare('SELECT * FROM products WHERE id=?'); $stmt->execute([$id]); $p=$stmt->fetch(); if(!$p) header('Location: products.php');
if($_SERVER['REQUEST_METHOD']==='POST'){
  $stmt=$pdo->prepare('UPDATE products SET name=?,description=?,price=? WHERE id=?'); $stmt->execute([$_POST['name'],$_POST['description'],$_POST['price'],$id]);
  header('Location: products.php');exit;
}
?>
<?php include __DIR__.'/../views/header.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-4">
    <div class="flex items-center gap-4 mb-6">
        <a href="products.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Editar Produto</h2>
    </div>

    <form method="post" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
        <div class="grid grid-cols-1 gap-6 mb-8">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome</label>
                <input name="name" value="<?=htmlspecialchars($p['name'])?>" required class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Descrição</label>
                <textarea name="description" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700 min-h-[60px]"><?=htmlspecialchars($p['description'])?></textarea>
            </div>

            <div class="max-w-xs">
                <label class="block text-sm font-medium text-slate-700 mb-2">Preço (R$)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">R$</span>
                    <input name="price" type="number" step="0.01" value="<?=htmlspecialchars($p['price'])?>" class="w-full border border-slate-300 rounded-xl p-3 pl-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
            <a href="products.php" class="bg-white border border-slate-300 text-slate-700 px-6 py-2.5 rounded hover:bg-slate-50 font-medium transition-colors">Voltar</a>
            <button class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">Salvar Alterações</button>
        </div>
    </form>
</div>

<?php include __DIR__.'/../views/footer.php'; ?>
