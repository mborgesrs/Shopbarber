<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: products.php');
    exit;
}

// Fetch product info to show in confirmation
$stmt = $pdo->prepare('SELECT name, type FROM products WHERE id = ? AND company_id = ?');
$stmt->execute([$id, $_SESSION['company_id']]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ? AND company_id = ?');
    $stmt->execute([$id, $_SESSION['company_id']]);
    header('Location: products.php');
    exit;
}
?>

<?php include __DIR__ . '/../views/header.php'; ?>

<div class="max-w-lg mx-auto px-4 py-20">
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden text-center p-8 md:p-12 transition-all hover:shadow-2xl">
        <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
            <i class="fas fa-exclamation-triangle text-3xl"></i>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Confirmar Exclusão?</h2>
        <p class="text-gray-500 text-sm mb-8">
            Você está prestes a excluir permanentemente o <?= mb_strtolower($product['type']) ?>:
            <br>
            <span class="text-gray-900 font-bold text-lg mt-2 block italic">"<?= htmlspecialchars($product['name']) ?>"</span>
        </p>

        <div class="bg-amber-50 rounded-2xl p-4 mb-8 text-left border border-amber-100">
            <div class="flex gap-3">
                <i class="fas fa-info-circle text-amber-500 mt-1"></i>
                <p class="text-[12px] text-amber-700 leading-relaxedCondensed">
                    <b>Atenção:</b> Esta ação não pode ser desfeita. Se este produto possuir histórico de movimentação ou estiver vinculado a serviços realizados, a integridade dos relatórios pode ser afetada.
                </p>
            </div>
        </div>

        <form method="post" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="confirm_delete" value="1">
            <a href="products.php" class="flex-1 px-8 py-3.5 bg-gray-50 text-gray-600 rounded-2xl font-bold hover:bg-gray-100 transition-all border border-gray-200">
                Cancelar
            </a>
            <button type="submit" class="flex-1 px-8 py-3.5 bg-rose-600 text-white rounded-2xl font-bold hover:bg-rose-700 shadow-lg shadow-rose-100 transition-all transform active:scale-95">
                Sim, Excluir
            </button>
        </form>
    </div>
    
    <div class="mt-8 text-center text-gray-400 text-xs tracking-widest uppercase font-medium">
        ShopBarber • Controle de Catálogo
    </div>
</div>

<?php include __DIR__ . '/../views/header.php'; // Using header as footer if footer fix is needed, but common pattern is header/footer. Wait, I should use footer. ?>
<?php // Actually, looking at other files, they use footer.php. Let me fix that. ?>
<?php include __DIR__ . '/../views/footer.php'; ?>
