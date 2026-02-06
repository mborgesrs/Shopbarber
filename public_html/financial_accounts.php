<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$companyId = $_SESSION['company_id'];
$q = $_GET['q'] ?? '';
$params = [];
$sql = 'SELECT * FROM contas WHERE 1=1';

if($q){
  $sql .= ' AND (codigo LIKE ? OR descricao LIKE ?)';
  $params = ["%$q%", "%$q%"];
}
$sql .= ' ORDER BY codigo ASC, descricao ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params); 
$rows = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-6">
  <h2 class="text-2xl font-semibold text-slate-800">Contas</h2>
  <div class="flex gap-2">
      <a href="dashboard.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors">Voltar</a>
      <a href="financial_account_edit.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded shadow transition-colors flex items-center">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Novo
      </a>
  </div>
</div>

<div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 mb-6">
  <form method="get" class="flex gap-4">
    <div class="flex-1 relative">
        <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Pesquisar por código ou descrição..." class="w-full border border-slate-300 pl-10 p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
        <svg class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
    </div>
    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition-colors">Pesquisar</button>
  </form>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
  <table class="w-full">
    <thead class="bg-slate-50 border-b border-slate-100">
        <tr>
            <th class="px-4 py-1 text-left font-semibold text-slate-600">Código</th>
            <th class="px-4 py-1 text-left font-semibold text-slate-600">Descrição</th>
            <th class="px-4 py-1 text-left font-semibold text-slate-600">Tipo</th>
            <th class="px-4 py-1 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-1 text-right font-semibold text-slate-600">Ações</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach($rows as $r): ?>
      <tr class="hover:bg-slate-50 transition-colors">
        <td class="px-4 py-1 font-mono text-slate-800 text-[13px]"><?=htmlspecialchars($r['codigo'] ?? '-')?></td>
        <td class="px-4 py-1 text-slate-600 text-[12px]"><?=htmlspecialchars($r['descricao'])?></td>
        <td class="px-4 py-1 text-slate-600 text-[12px]">
            <span class="px-2 py-0.5 text-[10px] rounded <?= $r['tipo'] == 'Analitica' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' ?>">
                <?= htmlspecialchars($r['tipo']) ?>
            </span>
        </td>
        <td class="px-4 py-1 text-slate-600 text-[12px]">
            <span class="px-2 py-0.5 text-[10px] rounded <?= $r['ativo'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                <?= $r['ativo'] ? 'Ativo' : 'Inativo' ?>
            </span>
        </td>
        <td class="px-4 py-1 text-right">
          <a class="text-slate-500 hover:text-blue-600 mr-2 font-medium text-[11px] inline-flex items-center" href="financial_account_edit.php?id=<?=$r['id']?>">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Editar
          </a>
          <button class="text-slate-500 hover:text-red-600 font-medium text-[11px] inline-flex items-center" onclick="deleteItem(<?=$r['id']?>)">
             <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
             Excluir
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$rows): ?>
        <tr><td colspan="5" class="p-8 text-center text-slate-500">Nenhum registro encontrado.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function deleteItem(id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: "Essa ação não pode ser desfeita.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'financial_account_delete.php?id=' + id;
        }
    })
}
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
