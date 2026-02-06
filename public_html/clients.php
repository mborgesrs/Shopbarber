<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$q = $_GET['q'] ?? '';
$company_id = $_SESSION['company_id'];

if($q){
  $stmt = $pdo->prepare('SELECT * FROM clients WHERE company_id = ? AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ? OR notes LIKE ?) ORDER BY name ASC');
  $stmt->execute([$company_id, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%"]); 
}else{
  $stmt = $pdo->prepare('SELECT * FROM clients WHERE company_id = ? ORDER BY name ASC');
  $stmt->execute([$company_id]);
}
$clients = $stmt->fetchAll();
$totalClients = count($clients);
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
  <div>
    <h2 class="text-xl font-bold text-gray-800">Clientes</h2>
    <p class="text-[11px] text-gray-400 uppercase tracking-widest font-bold">Total: <?= $totalClients ?></p>
  </div>
  
  <div class="flex flex-1 md:max-w-xl gap-2">
    <form method="get" class="flex-1 flex gap-2">
      <div class="relative flex-1">
        <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Buscar cliente..." class="w-full border border-gray-200 pl-9 pr-4 py-2 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all shadow-sm">
        <i class="fas fa-search absolute left-3 top-2.5 text-gray-300 text-xs"></i>
      </div>
      <button type="submit" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition-colors shadow-sm">
        <i class="fas fa-filter text-xs"></i>
      </button>
      <?php if($q): ?>
        <a href="clients.php" class="px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-sm font-bold hover:bg-rose-100 transition-colors flex items-center gap-2">
          <i class="fas fa-times text-xs"></i>
        </a>
      <?php endif; ?>
    </form>
    
    <a href="client_create.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-indigo-100 font-bold text-sm whitespace-nowrap">
      <i class="fas fa-plus"></i> Novo Cliente
    </a>
  </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between font-bold text-sm text-gray-700 uppercase tracking-wider">
    <span>Base de Clientes</span>
    <i class="fas fa-address-book text-gray-400"></i>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase text-gray-400 tracking-widest font-bold">
        <tr>
          <th class="px-6 py-2 text-left">Nome</th>
          <th class="px-6 py-2 text-left">Contato</th>
          <th class="px-6 py-2 text-right">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach($clients as $c): ?>
        <tr class="hover:bg-gray-50/80 transition-colors group">
          <td class="px-6 py-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 font-bold uppercase text-xs">
                    <?= substr($c['name'], 0, 1) ?>
                </div>
                <span class="text-gray-900 font-bold text-sm"><?=htmlspecialchars($c['name'])?></span>
            </div>
          </td>
          <td class="px-6 py-2">
            <div class="flex flex-col">
                <span class="text-gray-700 text-xs font-medium"><?=htmlspecialchars($c['phone'])?></span>
                <span class="text-[10px] text-gray-400"><?=htmlspecialchars($c['email'])?></span>
            </div>
          </td>
          <td class="px-6 py-2 text-right">
            <div class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
              <a title="Histórico" class="w-7 h-7 flex items-center justify-center rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white transition-all shadow-sm" href="client_history.php?id=<?=$c['id']?>">
                <i class="fas fa-history text-[10px]"></i>
              </a>
              <a title="Editar" class="w-7 h-7 flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" href="client_edit.php?id=<?=$c['id']?>">
                <i class="fas fa-edit text-[10px]"></i>
              </a>
              <a title="Excluir" class="w-7 h-7 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm" href="client_delete.php?id=<?=$c['id']?>" onclick="return confirm('Excluir?')">
                <i class="fas fa-trash text-[10px]"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
