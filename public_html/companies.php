<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$companyId = $_SESSION['company_id'];
$q = $_GET['q'] ?? '';

// Filter to show the company itself or companies that have it as parent_company_id
$sql = 'SELECT * FROM companies WHERE (id = ? OR parent_company_id = ?)';
$params = [$companyId, $companyId];

if($q){
    $qDigits = preg_replace('/\D/', '', $q);
    $sql .= ' AND (name LIKE ? OR fantasy_name LIKE ? OR document LIKE ? OR cpf LIKE ? OR address LIKE ? OR neighborhood LIKE ? OR city LIKE ? OR state LIKE ? OR division LIKE ? OR cep LIKE ?';
    $params = array_merge($params, ["%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%"]);
    
    if($qDigits !== ''){
        $sql .= ' OR REPLACE(REPLACE(REPLACE(document, ".", ""), "/", ""), "-", "") LIKE ?';
        $sql .= ' OR REPLACE(REPLACE(cpf, ".", ""), "-", "") LIKE ?';
        $sql .= ' OR REPLACE(cep, "-", "") LIKE ?';
        $params[] = "%$qDigits%";
        $params[] = "%$qDigits%";
        $params[] = "%$qDigits%";
    }
    $sql .= ')';
}

$sql .= ' ORDER BY name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-6">
  <h2 class="text-2xl font-semibold text-slate-800">Empresas</h2>
  <div class="flex gap-2">
      <a href="dashboard.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors text-sm">Voltar</a>
      <a href="company_create.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded shadow transition-colors flex items-center text-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Nova Empresa
      </a>
  </div>
</div>

<div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 mb-6">
  <form method="get" class="flex gap-4">
    <div class="flex-1 relative">
        <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Pesquisar por nome, fantasia ou documento..." class="w-full border border-slate-300 pl-10 p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 text-sm">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
    </div>
    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition-colors text-sm">Pesquisar</button>
  </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
  <table class="w-full">
    <thead class="bg-slate-50 border-b border-slate-100">
        <tr>
            <th class="px-4 py-1 text-left font-semibold text-slate-600 text-sm">Nome / Razão Social</th>
            <th class="px-4 py-1 text-left font-semibold text-slate-600 text-sm">Divisão</th>
            <th class="px-4 py-1 text-left font-semibold text-slate-600 text-sm">Doc / CPF</th>
            <th class="px-4 py-1 text-left font-semibold text-slate-600 text-sm">Status</th>
            <th class="px-4 py-1 text-right font-semibold text-slate-600 text-sm">Ações</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach($companies as $comp): ?>
      <tr class="hover:bg-slate-50 transition-colors">
        <td class="px-4 py-1">
            <div class="font-medium text-slate-800 text-[13px]"><?=htmlspecialchars($comp['name'])?></div>
            <div class="text-slate-500 text-[11px]"><?=htmlspecialchars($comp['fantasy_name'] ?: '-')?></div>
        </td>
        <td class="px-4 py-1">
            <span class="text-slate-600 text-[12px]"><?=htmlspecialchars($comp['division'] ?: 'Outros')?></span>
        </td>
        <td class="px-4 py-1">
            <div class="text-slate-600 text-[12px]"><?=htmlspecialchars($comp['document'] ?: '-')?></div>
            <div class="text-slate-400 text-[10px]"><?=htmlspecialchars($comp['cpf'] ?: '')?></div>
        </td>
        <td class="px-4 py-1">
            <?php if($comp['status'] == 'active'): ?>
                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">ATIVO</span>
            <?php else: ?>
                <span class="bg-red-100 text-red-800 text-[10px] font-bold px-2 py-0.5 rounded">INATIVO</span>
            <?php endif; ?>
        </td>
        <td class="px-4 py-1 text-right">
          <a class="text-emerald-600 hover:text-emerald-700 mr-2 font-medium text-[11px] inline-flex items-center" href="https://wa.me/<?= preg_replace('/\D/', '', $comp['phone'] ?? '') ?>" target="_blank">
            <i class="fab fa-whatsapp mr-1"></i>
            WhatsApp
          </a>
          <a class="text-slate-500 hover:text-blue-600 mr-2 font-medium text-[11px] inline-flex items-center" href="company_edit.php?id=<?=$comp['id']?>">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Editar
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$companies): ?>
        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">Nenhuma empresa encontrada.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
