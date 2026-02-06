<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$company_id = $_SESSION['company_id'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['delete_id'])){
        $stmt = $pdo->prepare('DELETE FROM banks WHERE id = ? AND company_id = ?');
        $stmt->execute([$_POST['delete_id'], $company_id]);
    } elseif (isset($_POST['id']) && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE banks SET name = ?, code = ? WHERE id = ? AND company_id = ?');
        $stmt->execute([$_POST['name'], $_POST['code'], $_POST['id'], $company_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO banks (name, code, company_id) VALUES (?, ?, ?)');
        $stmt->execute([$_POST['name'], $_POST['code'], $company_id]);
    }
    header('Location: banks.php'); exit;
}

$stmt = $pdo->prepare('SELECT * FROM banks WHERE company_id = ? ORDER BY name');
$stmt->execute([$company_id]);
$list = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="finance.php" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors shadow-sm" title="Voltar">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Bancos</h2>
    </div>
    <button onclick="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center gap-2">
        <i class="fas fa-plus"></i>
        Novo Banco
    </button>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] tracking-widest">
            <tr>
                <th class="px-6 py-2">Código</th>
                <th class="px-6 py-2">Nome do Banco</th>
                <th class="px-6 py-2 text-right">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach($list as $item): ?>
            <tr class="hover:bg-gray-50 transition-colors group">
                <td class="px-6 py-2 font-mono text-xs text-gray-600 font-bold"><?= htmlspecialchars($item['code']) ?></td>
                <td class="px-6 py-2">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center">
                            <i class="fas fa-university text-[10px]"></i>
                        </div>
                        <span class="font-bold text-gray-700 text-sm"><?= htmlspecialchars($item['name']) ?></span>
                    </div>
                </td>
                <td class="px-6 py-2 text-right">
                    <div class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick='openModal(<?= json_encode($item) ?>)' class="w-7 h-7 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all">
                            <i class="fas fa-edit text-xs"></i>
                        </button>
                        <form method="post" class="inline" onsubmit="return confirm('Deseja realmente excluir este banco?')">
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
                    <td colspan="3" class="px-6 py-12 text-center">
                        <div class="text-gray-300 mb-2"><i class="fas fa-landmark text-4xl"></i></div>
                        <p class="text-gray-400">Nenhum banco encontrado.</p>
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
            <h3 id="modal-title" class="font-bold text-lg text-gray-800">Novo Banco</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl font-light">×</button>
        </div>
        <form method="post" class="p-6">
            <input type="hidden" name="id" id="field-id">
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Código do Banco</label>
                <input type="text" name="code" id="field-code" class="w-full border border-gray-200 p-3 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-300" placeholder="Ex: 001">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nome do Banco</label>
                <input type="text" name="name" id="field-name" required class="w-full border border-gray-200 p-3 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-300" placeholder="Ex: Banco do Brasil">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition-colors">Cancelar</button>
                <button class="flex-1 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">Salvar Banco</button>
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
    const fieldCode = document.getElementById('field-code');

    if (data) {
        title.innerText = 'Editar Banco';
        fieldId.value = data.id;
        fieldName.value = data.name;
        fieldCode.value = data.code;
    } else {
        title.innerText = 'Novo Banco';
        fieldId.value = '';
        fieldName.value = '';
        fieldCode.value = '';
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-form').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
