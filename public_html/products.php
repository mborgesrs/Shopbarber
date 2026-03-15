<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$q = $_GET['q'] ?? '';
$cat_id = $_GET['category_id'] ?? '';
$company_id = $_SESSION['company_id'];

$stmt_cat = $pdo->prepare("SELECT * FROM categories WHERE company_id = ? ORDER BY name");
$stmt_cat->execute([$company_id]);
$categories = $stmt_cat->fetchAll();

$query = 'SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.company_id = ?';
$params = [$company_id];

if($q){
  $query .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
  $params[] = "%$q%";
  $params[] = "%$q%";
}

if($cat_id){
  $query .= ' AND p.category_id = ?';
  $params[] = $cat_id;
}

$query .= ' ORDER BY p.name ASC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);

$products = $stmt->fetchAll();
$totalProducts = count($products);

$totalStockValue = 0;
foreach ($products as $p) {
    if ($p['type'] === 'Ativo') {
        $totalStockValue += ($p['balance'] * $p['pr_medio']);
    }
}
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
  <div>
    <h2 class="text-xl font-bold text-gray-800">Produtos / Serviços</h2>
    <div class="flex gap-4">
      <p class="text-[11px] text-gray-400 uppercase tracking-widest font-bold">Total Itens: <?= $totalProducts ?></p>
      <p class="text-[11px] text-emerald-600 uppercase tracking-widest font-bold">Valor em Estoque: R$ <?= number_format($totalStockValue, 2, ',', '.') ?></p>
    </div>
  </div>
  
  <div class="flex flex-1 md:max-w-4xl gap-2">
    <form method="get" class="flex-1 flex gap-2" id="searchForm">
      <div class="relative flex-1">
        <input type="search" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Buscar produto..." class="w-full border border-gray-200 pl-9 pr-4 py-2 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all shadow-sm">
        <i class="fas fa-search absolute left-3 top-2.5 text-gray-300 text-xs"></i>
      </div>
      <select name="category_id" class="border border-gray-200 py-2 px-3 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all shadow-sm bg-white text-gray-600 max-w-[160px] md:max-w-[220px] truncate" onchange="this.form.submit()">
        <option value="">Categorias...</option>
        <?php foreach($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if($q || $cat_id): ?>
        <a href="products.php" class="px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-sm font-bold hover:bg-rose-100 transition-colors flex items-center gap-2" title="Limpar filtros">
          <i class="fas fa-times text-xs"></i>
        </a>
      <?php endif; ?>
    </form>
    
    <div class="relative group flex items-center">
        <a href="product_import.php" class="bg-white border border-gray-200 text-gray-600 px-5 py-2 rounded-xl flex items-center gap-2 transition-all shadow-sm font-bold text-sm whitespace-nowrap hover:bg-gray-50">
            <i class="fas fa-file-import"></i> Importar
        </a>
        <div class="ml-2 w-6 h-6 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center cursor-help hover:bg-indigo-50 hover:text-indigo-500 transition-all text-xs border border-gray-200">
            <i class="fas fa-info"></i>
        </div>
        <!-- Tooltip -->
        <div class="absolute top-full mt-2 right-0 w-64 bg-slate-900 text-white p-4 rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-[100] border border-white/10">
            <div class="text-[10px] uppercase tracking-widest text-indigo-400 font-bold mb-2">Layout da Planilha (CSV)</div>
            <ul class="space-y-1.5 text-xs font-medium border-l border-white/20 pl-3">
                <li><span class="text-white/40">1.</span> Categoria</li>
                <li><span class="text-white/40">2.</span> Produto</li>
                <li><span class="text-white/40">3.</span> Unidade</li>
                <li><span class="text-white/40">4.</span> Estoque Mínimo</li>
                <li><span class="text-white/40">5.</span> Preço Custo</li>
                <li><span class="text-white/40">6.</span> Preço Venda</li>
            </ul>
            <div class="mt-3 pt-2 border-t border-white/5 text-[9px] text-white/40 italic">
                * Separador sugerido: ponto e vírgula (;)
            </div>
        </div>
    </div>
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
          <th class="px-6 py-2 text-left">Tipo</th>
          <th class="px-6 py-2 text-left">Preço</th>
          <th class="px-6 py-2 text-left">Saldo</th>
          <th class="px-6 py-2 text-right">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach($products as $p): ?>
        <tr class="hover:bg-gray-50/80 transition-colors group">
          <td class="px-6 py-2">
            <div class="flex items-center gap-3">
                <?php /*
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 font-bold uppercase text-xs">
                    <?php if($p['type'] === 'Ativo'): ?>
                      <i class="fas fa-box"></i>
                    <?php else: ?>
                      <i class="fas fa-tag"></i>
                    <?php endif; ?>
                </div>
                */ ?>
                <div>
                  <span class="text-gray-900 font-bold text-sm block"><?=htmlspecialchars($p['name'])?></span>
                  <div class="flex items-center gap-2 mt-0.5">
                    <?php if(!empty($p['category_name'])): ?>
                      <span class="text-[10px] text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded font-medium"><?=htmlspecialchars($p['category_name'])?></span>
                    <?php endif; ?>
                    <?php if(!empty($p['description'])): ?>
                      <span class="text-[10px] text-gray-400 line-clamp-1"><?=htmlspecialchars($p['description'])?></span>
                    <?php endif; ?>
                  </div>
                </div>
            </div>
          </td>
          <td class="px-6 py-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $p['type'] === 'Ativo' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' ?>">
              <?= $p['type'] ?>
            </span>
          </td>
          <td class="px-6 py-2">
            <span class="text-gray-700 font-bold text-sm">R$ <?=number_format($p['price'], 2, ',', '.')?></span>
          </td>
          <td class="px-6 py-2">
            <?php if($p['type'] === 'Ativo'): ?>
              <span class="text-gray-700 font-bold text-sm"><?= number_format($p['balance'], 2, ',', '.') ?> <span class="text-[10px] text-gray-400 font-normal"><?= htmlspecialchars($p['unit']) ?></span></span>
            <?php else: ?>
              <span class="text-gray-400 italic text-xs">-</span>
            <?php endif; ?>
          </td>
          <td class="px-6 py-2 text-right">
            <div class="flex justify-end gap-1.5 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
              <a title="Editar" class="w-7 h-7 flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" href="product_edit.php?id=<?=$p['id']?>">
                <i class="fas fa-edit text-[10px]"></i>
              </a>
              <a title="Excluir" class="w-7 h-7 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm" href="product_delete.php?id=<?=$p['id']?>">
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
