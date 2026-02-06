<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';

$id = $_GET['id'] ?? null;
$data = [];

if($id) {
    $stmt = $pdo->prepare("SELECT * FROM tipos_pagamento WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    if(!$data) die("Registro não encontrado.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    if(empty($descricao)) {
        $error = "Descrição é obrigatória.";
    } else {
        try {
            if($id) {
                $stmt = $pdo->prepare("UPDATE tipos_pagamento SET descricao = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$descricao, $ativo, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO tipos_pagamento (descricao, ativo) VALUES (?, ?)");
                $stmt->execute([$descricao, $ativo]);
            }
            header("Location: payment_types.php");
            exit;
        } catch(Exception $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    }
}
include __DIR__ . '/../views/header.php';
?>
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold text-slate-800 mb-6"><?= $id ? 'Editar' : 'Novo' ?> Tipo de Pagamento</h2>
    
    <form method="post" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <?php if(isset($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm border border-red-200"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Descrição *</label>
            <input name="descricao" value="<?= htmlspecialchars($data['descricao'] ?? '') ?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" placeholder="Ex: Dinheiro, PIX, Cartão">
        </div>
        
        <div class="mb-6">
            <label class="flex items-center cursor-pointer select-none">
                <input type="checkbox" name="ativo" value="1" <?= ($data['ativo'] ?? 1) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="ml-2 text-sm font-medium text-slate-700">Tipo Ativo</span>
            </label>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="payment_types.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors font-medium">Voltar</a>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow-sm transition-colors">Salvar Tipo</button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
