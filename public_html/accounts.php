<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$company_id = $_SESSION['company_id'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['delete_id'])){
        $stmt = $pdo->prepare('DELETE FROM accounts WHERE id = ? AND company_id = ?');
        $stmt->execute([$_POST['delete_id'], $company_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO accounts (name, bank_id, company_id) VALUES (?, ?, ?)');
        $stmt->execute([$_POST['name'], $_POST['bank_id']?:null, $company_id]);
    }
    header('Location: accounts.php'); exit;
}

$stmt = $pdo->prepare('SELECT a.*, b.name as bank_name FROM accounts a LEFT JOIN banks b ON b.id = a.bank_id WHERE a.company_id = ? ORDER BY a.name');
$stmt->execute([$company_id]);
$list = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, name FROM banks WHERE company_id = ? ORDER BY name');
$stmt->execute([$company_id]);
$banks = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold">Contas Bancárias</h2>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-1">
        <form method="post" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="font-bold mb-4">Nova Conta</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Conta</label>
                <input type="text" name="name" required class="w-full border border-gray-200 p-2 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: Conta Corrente">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Banco (Opcional)</label>
                <select name="bank_id" class="w-full border border-gray-200 p-2 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Sem Banco --</option>
                    <?php foreach($banks as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <button class="w-full bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">Salvar</button>
        </form>
    </div>
    <div class="md:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 font-medium">
                    <tr>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Banco</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($list as $item): ?>
                    <tr>
                        <td class="px-4 py-2 font-medium"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="px-4 py-2 text-gray-500"><?= htmlspecialchars($item['bank_name'] ?: 'N/A') ?></td>
                        <td class="px-4 py-2 text-right">
                            <form method="post" class="inline" onsubmit="return confirm('Excluir?')">
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(!$list): ?>
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Nenhuma conta cadastrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
