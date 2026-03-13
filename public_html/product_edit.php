<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$id = $_GET['id'] ?? null; if(!$id) header('Location: products.php');
$stmt=$pdo->prepare('SELECT * FROM products WHERE id=?'); $stmt->execute([$id]); $p=$stmt->fetch(); if(!$p) header('Location: products.php');
if($_SERVER['REQUEST_METHOD']==='POST'){
  $stmt=$pdo->prepare('UPDATE products SET name=?,description=?,price=?,type=?,unit=?,pr_custo=?,pr_medio=?,category_id=?,min_stock=? WHERE id=?'); 
  $stmt->execute([$_POST['name'],$_POST['description'],$_POST['price'],$_POST['type'],$_POST['unit'],$_POST['pr_custo'],$_POST['pr_medio'],$_POST['category_id']?:null,$_POST['min_stock']?:0,$id]);
  header('Location: products.php');exit;
}
$stmt_cat = $pdo->prepare("SELECT * FROM categories WHERE company_id = ? ORDER BY name");
$stmt_cat->execute([$_SESSION['company_id']]);
$categories = $stmt_cat->fetchAll();
?>
<?php include __DIR__.'/../views/header.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-4">
    <div class="flex items-center gap-4 mb-6">
        <a href="products.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Editar Produto</h2>
    </div>

    <form method="post" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="grid grid-cols-1 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nome</label>
                <input name="name" value="<?=htmlspecialchars($p['name'])?>" required class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                <textarea name="description" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700 min-h-[50px]"><?=htmlspecialchars($p['description'])?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Preço (R$)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">R$</span>
                        <input name="price" type="number" step="0.01" value="<?=htmlspecialchars($p['price'])?>" class="w-full border border-slate-300 rounded-xl p-2.5 pl-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                    <select name="type" required class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="Serviço" <?= $p['type'] === 'Serviço' ? 'selected' : '' ?>>Serviço</option>
                        <option value="Ativo" <?= $p['type'] === 'Ativo' ? 'selected' : '' ?>>Ativo (Estoque)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Categoria</label>
                    <select name="category_id" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="">Nenhuma</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($p['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prc. Custo (R$)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">R$</span>
                        <input name="pr_custo" type="number" step="0.01" value="<?=htmlspecialchars($p['pr_custo'])?>" class="w-full border border-slate-300 rounded-xl p-2.5 pl-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prc. Médio (R$)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">R$</span>
                        <input name="pr_medio" type="number" step="0.01" value="<?=htmlspecialchars($p['pr_medio'])?>" class="w-full border border-slate-300 rounded-xl p-2.5 pl-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Unidade</label>
                    <input name="unit" value="<?=htmlspecialchars($p['unit'])?>" placeholder="Ex: un, ml, kg" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Estoque Mín.</label>
                    <input name="min_stock" type="number" step="0.01" value="<?=htmlspecialchars($p['min_stock'])?>" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="products.php" class="bg-white border border-slate-300 text-slate-700 px-6 py-2 rounded hover:bg-slate-50 font-medium transition-colors">Voltar</a>
            <button class="bg-blue-600 text-white px-8 py-2 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">Salvar Alterações</button>
        </div>
    </form>
</div>

<?php include __DIR__.'/../views/footer.php'; ?>
