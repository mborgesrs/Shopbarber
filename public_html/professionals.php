<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$companyId = $_SESSION['company_id'];
$q = $_GET['q'] ?? '';
$sql = "SELECT * FROM professionals WHERE active=1 AND company_id = ?";
$params = [$companyId];
if($q){
    $sql .= " AND (name LIKE ? OR phone LIKE ?)";
    $params = array_merge($params, ["%$q%", "%$q%"]);
}
$sql .= " ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pros = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-6">
  <h2 class="text-2xl font-semibold text-slate-800">Profissionais</h2>
  <div class="flex gap-2">
      <a href="dashboard.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors">Voltar</a>
      <a href="professional_create.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded shadow transition-colors flex items-center">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Novo
      </a>
  </div>
</div>

<div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 mb-6">
  <form method="get" class="flex gap-4">
    <div class="flex-1 relative">
        <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Pesquisar por nome ou telefone..." class="w-full border border-slate-300 pl-10 p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
        <svg class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
    </div>
    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition-colors">Pesquisar</button>
  </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
  <table class="w-full">
    <thead class="bg-slate-50 border-b border-slate-100">
        <tr>
            <th class="px-4 py-1 text-left font-semibold text-slate-600">Nome</th>
            <th class="px-4 py-1 text-left font-semibold text-slate-600">Telefone</th>
            <th class="px-4 py-1 text-right font-semibold text-slate-600">Ações</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach($pros as $p): ?>
      <tr class="hover:bg-slate-50 transition-colors">
        <td class="px-4 py-1 font-medium text-slate-800 text-[13px]"><?=htmlspecialchars($p['name'])?></td>
        <td class="px-4 py-1 text-slate-600 text-[12px]"><?=htmlspecialchars($p['phone'])?></td>
        <td class="px-4 py-1 text-right">
          <a class="text-slate-500 hover:text-blue-600 mr-2 font-medium text-[11px] inline-flex items-center" href="professional_edit.php?id=<?=$p['id']?>">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Editar
          </a>
          <a class="text-slate-500 hover:text-red-600 font-medium text-[11px] inline-flex items-center" href="professional_delete.php?id=<?=$p['id']?>" onclick="return confirm('Excluir?')">
             <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
             Excluir
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$pros): ?>
        <tr><td colspan="3" class="p-8 text-center text-slate-500">Nenhum profissional encontrado.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
