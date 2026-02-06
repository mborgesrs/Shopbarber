<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';

$id = $_GET['id'] ?? null;
$data = [];

if($id) {
    $stmt = $pdo->prepare("SELECT * FROM contas WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    if(!$data) die("Registro não encontrado.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo']);
    $descricao = trim($_POST['descricao']);
    $tipo = $_POST['tipo'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    if(empty($descricao)) {
        $error = "Descrição é obrigatória.";
    } else {
        try {
            if($id) {
                $stmt = $pdo->prepare("UPDATE contas SET codigo = ?, descricao = ?, tipo = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$codigo, $descricao, $tipo, $ativo, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO contas (codigo, descricao, tipo, ativo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$codigo, $descricao, $tipo, $ativo]);
            }
            header("Location: financial_accounts.php");
            exit;
        } catch(Exception $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    }
}
include __DIR__ . '/../views/header.php';
?>
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold text-slate-800 mb-6"><?= $id ? 'Editar' : 'Nova' ?> Conta</h2>
    
    <form method="post" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <?php if(isset($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm border border-red-200"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Código</label>
            <input name="codigo" value="<?= htmlspecialchars($data['codigo'] ?? '') ?>" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" placeholder="Ex: 01.01.01">
        </div>

        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Descrição *</label>
            <input name="descricao" value="<?= htmlspecialchars($data['descricao'] ?? '') ?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" placeholder="Ex: Receitas de Serviços">
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block mb-1 text-sm font-medium text-slate-700">Tipo *</label>
                <select name="tipo" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" required>
                    <option value="Analitica" <?= ($data['tipo'] ?? 'Analitica') == 'Analitica' ? 'selected' : '' ?>>Analítica</option>
                    <option value="Sintetica" <?= ($data['tipo'] ?? '') == 'Sintetica' ? 'selected' : '' ?>>Sintética</option>
                </select>
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="ativo" value="1" <?= ($data['ativo'] ?? 1) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm font-medium text-slate-700">Conta Ativa</span>
                </label>
            </div>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="financial_accounts.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors font-medium">Voltar</a>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow-sm transition-colors">Salvar Conta</button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
