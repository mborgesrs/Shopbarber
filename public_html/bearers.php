<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$company_id = $_SESSION['company_id'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['delete_id'])){
        $stmt = $pdo->prepare('DELETE FROM bearers WHERE id = ? AND company_id = ?');
        $stmt->execute([$_POST['delete_id'], $company_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO bearers (name, company_id) VALUES (?, ?)');
        $stmt->execute([$_POST['name'], $company_id]);
    }
    header('Location: bearers.php'); exit;
}

$stmt = $pdo->prepare('SELECT * FROM bearers WHERE company_id = ? ORDER BY name');
$stmt->execute([$company_id]);
$list = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold">Portadores</h2>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-1">
        <form method="post" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="font-bold mb-4">Novo Portador</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Portador</label>
                <input type="text" name="name" required class="w-full border border-gray-200 p-2 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: José Carlos">
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
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($list as $item): ?>
                    <tr>
                        <td class="px-4 py-2 font-medium"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="px-4 py-2 text-right">
                            <form method="post" class="inline" onsubmit="return confirm('Excluir?')">
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(!$list): ?>
                        <tr><td colspan="2" class="px-4 py-8 text-center text-gray-400">Nenhum portador cadastrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
