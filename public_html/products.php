<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$q = $_GET['q'] ?? '';
$company_id = $_SESSION['company_id'];

if($q){
  $stmt = $pdo->prepare('SELECT * FROM products WHERE company_id = ? AND (name LIKE ? OR description LIKE ?) ORDER BY name ASC');
  $stmt->execute([$company_id, "%$q%", "%$q%"]); 
}else{
  $stmt = $pdo->prepare('SELECT * FROM products WHERE company_id = ? ORDER BY name ASC');
  $stmt->execute([$company_id]);
}

$products = $stmt->fetchAll();
$totalProducts = count($products);
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
  <div>
    <h2 class="text-xl font-bold text-gray-800">Produtos / Serviços</h2>
    <p class="text-[11px] text-gray-400 uppercase tracking-widest font-bold">Total: <?= $totalProducts ?></p>
  </div>
  
  <div class="flex flex-1 md:max-w-xl gap-2">
    <form method="get" class="flex-1 flex gap-2">
      <div class="relative flex-1">
        <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Buscar produto..." class="w-full border border-gray-200 pl-9 pr-4 py-2 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all shadow-sm">
        <i class="fas fa-search absolute left-3 top-2.5 text-gray-300 text-xs"></i>
      </div>
      <button type="submit" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition-colors shadow-sm">
        <i class="fas fa-filter text-xs"></i>
      </button>
      <?php if($q): ?>
        <a href="products.php" class="px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-sm font-bold hover:bg-rose-100 transition-colors flex items-center gap-2">
          <i class="fas fa-times text-xs"></i>
        </a>
      <?php endif; ?>
    </form>
    
    <a href="product_create.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-indigo-100 font-bold text-sm whitespace-nowrap">
      <i class="fas fa-plus"></i> Novo Item
    </a>
  </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between font-bold text-sm text-gray-700 uppercase tracking-wider">
    <span>Catálogo</span>
    <i class="fas fa-box text-gray-400"></i>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase text-gray-400 tracking-widest font-bold">
        <tr>
          <th class="px-6 py-2 text-left">Descrição</th>
          <th class="px-6 py-2 text-left">Preço</th>
          <th class="px-6 py-2 text-right">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach($products as $p): ?>
        <tr class="hover:bg-gray-50/80 transition-colors group">
          <td class="px-6 py-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 font-bold uppercase text-xs">
                    <?= substr($p['name'], 0, 1) ?>
                </div>
                <div>
                  <span class="text-gray-900 font-bold text-sm block"><?=htmlspecialchars($p['name'])?></span>
                  <?php if(!empty($p['description'])): ?>
                    <span class="text-[10px] text-gray-400 line-clamp-1"><?=htmlspecialchars($p['description'])?></span>
                  <?php endif; ?>
                </div>
            </div>
          </td>
          <td class="px-6 py-2">
            <span class="text-gray-700 font-bold text-sm">R$ <?=number_format($p['price'], 2, ',', '.')?></span>
          </td>
          <td class="px-6 py-2 text-right">
            <div class="flex justify-end gap-1.5 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
              <a title="Editar" class="w-7 h-7 flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" href="product_edit.php?id=<?=$p['id']?>">
                <i class="fas fa-edit text-[10px]"></i>
              </a>
              <a title="Excluir" class="w-7 h-7 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm" href="product_delete.php?id=<?=$p['id']?>" onclick="return confirm('Excluir?')">
                <i class="fas fa-trash text-[10px]"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($products)): ?>
          <tr>
            <td colspan="3" class="px-6 py-10 text-center text-gray-400 italic">Nenhum produto ou serviço cadastrado.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
