<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$company_id = $_SESSION['company_id'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['delete_id'])){
        $stmt = $pdo->prepare('DELETE FROM contas WHERE id = ? AND company_id = ?');
        $stmt->execute([$_POST['delete_id'], $company_id]);
    } elseif (isset($_POST['id']) && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE contas SET codigo = ?, descricao = ?, tipo = ? WHERE id = ? AND company_id = ?');
        $stmt->execute([$_POST['codigo'], $_POST['name'], $_POST['tipo'], $_POST['id'], $company_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO contas (codigo, descricao, tipo, company_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$_POST['codigo'], $_POST['name'], $_POST['tipo'], $company_id]);
    }
    header('Location: contas.php'); exit;
}

$stmt = $pdo->prepare('SELECT * FROM contas WHERE company_id = ? ORDER BY codigo');
$stmt->execute([$company_id]);
$list = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="finance.php" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors shadow-sm" title="Voltar">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Contas</h2>
    </div>
    <button onclick="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center gap-2">
        <i class="fas fa-plus"></i>
        Nova Conta
    </button>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] tracking-widest">
            <tr>
                <th class="px-6 py-2">Código</th>
                <th class="px-6 py-2">Nome da Conta</th>
                <th class="px-6 py-2">Tipo</th>
                <th class="px-6 py-2 text-right">Total</th>
                <th class="px-6 py-2 text-right">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach($list as $item): ?>
            <tr class="hover:bg-gray-50 transition-colors group">
                <td class="px-6 py-2">
                    <span class="font-mono text-xs text-slate-600 font-bold"><?= htmlspecialchars($item['codigo'] ?: '-') ?></span>
                </td>
                <td class="px-6 py-2">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fas fa-university text-[10px]"></i>
                        </div>
                        <span class="font-bold text-gray-700 text-sm"><?= htmlspecialchars($item['descricao']) ?></span>
                    </div>
                </td>
                <td class="px-6 py-2">
                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-[9px] font-bold uppercase tracking-wider"><?= htmlspecialchars($item['tipo'] ?: 'S/T') ?></span>
                </td>
                <td class="px-6 py-2 text-right">
                    <span class="text-xs font-bold <?= ($item['total'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                        R$ <?= number_format($item['total'] ?? 0, 2, ',', '.') ?>
                    </span>
                </td>
                <td class="px-6 py-2 text-right">
                    <div class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick='openModal(<?= json_encode(["id" => $item["id"], "name" => $item["descricao"], "codigo" => $item["codigo"], "tipo" => $item["tipo"]]) ?>)' class="w-7 h-7 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all">
                            <i class="fas fa-edit text-xs"></i>
                        </button>
                        <form method="post" class="inline" onsubmit="return confirm('Deseja realmente excluir esta conta?')">
                            <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                            <button class="w-7 h-7 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-all">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$list): ?>
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        <div class="text-gray-300 mb-2"><i class="fas fa-folder-open text-4xl"></i></div>
                        <p class="text-gray-400">Nenhuma conta encontrada.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Create/Edit -->
<div id="modal-form" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 id="modal-title" class="font-bold text-lg text-gray-800">Nova Conta</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl font-light">×</button>
        </div>
        <form method="post" class="p-6">
            <input type="hidden" name="id" id="field-id">
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Código</label>
                    <input type="text" name="codigo" id="field-codigo" class="w-full border border-gray-200 p-3 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-300" placeholder="001">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tipo</label>
                    <select name="tipo" id="field-tipo" required class="w-full border border-gray-200 p-3 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 bg-white appearance-none">
                        <option value="Analitica">Analítica</option>
                        <option value="Sintética">Sintética</option>
                    </select>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nome da Conta</label>
                <input type="text" name="name" id="field-name" required class="w-full border border-gray-200 p-3 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-300" placeholder="Ex: Conta Corrente Itaú">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition-colors">Cancelar</button>
                <button class="flex-1 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">Salvar Conta</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(data = null) {
    const modal = document.getElementById('modal-form');
    const title = document.getElementById('modal-title');
    const fieldId = document.getElementById('field-id');
    const fieldName = document.getElementById('field-name');
    const fieldCodigo = document.getElementById('field-codigo');
    const fieldTipo = document.getElementById('field-tipo');

    if (data) {
        title.innerText = 'Editar Conta';
        fieldId.value = data.id;
        fieldName.value = data.name;
        fieldCodigo.value = data.codigo || '';
        fieldTipo.value = data.tipo || '';
    } else {
        title.innerText = 'Nova Conta';
        fieldId.value = '';
        fieldName.value = '';
        fieldCodigo.value = '';
        fieldTipo.value = 'Analitica';
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-form').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
