<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';

$id = $_GET['id'] ?? null;
$data = [];

if($id) {
    $stmt = $pdo->prepare("SELECT * FROM portadores WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    if(!$data) die("Registro não encontrado.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $conta = trim($_POST['conta']);
    $agencia = trim($_POST['agencia']);
    $numero = trim($_POST['numero']);
    
    if(empty($nome)) {
        $error = "Nome é obrigatório.";
    } else {
        try {
            if($id) {
                $stmt = $pdo->prepare("UPDATE portadores SET nome = ?, conta = ?, agencia = ?, numero = ? WHERE id = ?");
                $stmt->execute([$nome, $conta, $agencia, $numero, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO portadores (nome, conta, agencia, numero) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $conta, $agencia, $numero]);
            }
            header("Location: bearers.php");
            exit;
        } catch(Exception $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    }
}
include __DIR__ . '/../views/header.php';
?>
<div class="max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold text-slate-800 mb-6"><?= $id ? 'Editar' : 'Novo' ?> Portador</h2>
    
    <form method="post" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <?php if(isset($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm border border-red-200"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-slate-700">Nome *</label>
            <input name="nome" value="<?= htmlspecialchars($data['nome'] ?? '') ?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block mb-1 text-sm font-medium text-slate-700">Conta</label>
                <input name="conta" value="<?= htmlspecialchars($data['conta'] ?? '') ?>" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" placeholder="Ex: 12345-6">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-slate-700">Agência</label>
                <input name="agencia" value="<?= htmlspecialchars($data['agencia'] ?? '') ?>" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" placeholder="Ex: 0001">
            </div>
        </div>

        <div class="mb-6">
            <label class="block mb-1 text-sm font-medium text-slate-700">Número</label>
            <input name="numero" value="<?= htmlspecialchars($data['numero'] ?? '') ?>" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" placeholder="Ex: 001">
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="bearers.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded hover:bg-slate-50 transition-colors font-medium">Voltar</a>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow-sm transition-colors">Salvar Portador</button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
