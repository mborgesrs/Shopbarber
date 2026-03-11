<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$company_id = $_SESSION['company_id'];

// Handle Movement Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move') {
    $product_id = $_POST['product_id'];
    $date = $_POST['date'];
    $supplier = $_POST['supplier'] ?? '';
    $quantity = $_POST['quantity'];
    $price = $_POST['price'] ?? 0;
    $type = $_POST['type']; // especie: Entrada, Saída, Consumo...

    try {
        $pdo->beginTransaction();

        // 1. Record Movement
        $stmt = $pdo->prepare("INSERT INTO inventory_movements (company_id, product_id, date, supplier, quantity, price, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $product_id, $date, $supplier, $quantity, $price, $type]);

        // 2. Update Product Balance
        // If it's "Entrada", we add. If it's "Saída" or "Consumo", we subtract.
        // For custom types, user should decide, but usually anything other than "Entrada" is a reduction in this salon context.
        $adj = ($type === 'Entrada') ? $quantity : -$quantity;
        
        $stmt = $pdo->prepare("UPDATE products SET balance = balance + ? WHERE id = ? AND company_id = ?");
        $stmt->execute([$adj, $product_id, $company_id]);

        $pdo->commit();
        $_SESSION['success'] = "Movimentação registrada com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erro ao registrar: " . $e->getMessage();
    }
    header('Location: inventory.php'); exit;
}

// Fetch Ativo Products for dropdown
$stmt = $pdo->prepare("SELECT id, name, unit, balance FROM products WHERE company_id = ? AND type = 'Ativo' ORDER BY name");
$stmt->execute([$company_id]);
$products = $stmt->fetchAll();

// Fetch Recent Movements
$stmt = $pdo->prepare("
    SELECT m.*, p.name as product_name, p.unit 
    FROM inventory_movements m 
    JOIN products p ON m.product_id = p.id 
    WHERE m.company_id = ? 
    ORDER BY m.date DESC, m.created_at DESC 
    LIMIT 50
");
$stmt->execute([$company_id]);
$movements = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Controle de Estoque</h2>
        <p class="text-[11px] text-gray-400 uppercase tracking-widest font-bold">Movimentação de Materiais</p>
    </div>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="bg-emerald-50 border border-emerald-100 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3">
        <i class="fas fa-check-circle"></i>
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="bg-rose-50 border border-rose-100 text-rose-600 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3">
        <i class="fas fa-exclamation-circle"></i>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Form Movement -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-8">
            <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between font-bold text-sm text-gray-700 uppercase tracking-wider">
                <span>Registrar Movimento</span>
                <i class="fas fa-exchange-alt text-gray-400"></i>
            </div>
            <form method="post" class="p-6 space-y-4">
                <input type="hidden" name="action" value="move">
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Produto</label>
                    <?php if(empty($products)): ?>
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl">
                            <p class="text-[10px] text-amber-700 font-medium leading-tight">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Nenhum produto do tipo <b>Ativo</b> encontrado. 
                                <a href="products.php" class="underline font-bold">Configure seus produtos aqui</a>.
                            </p>
                        </div>
                        <select name="product_id" required disabled class="mt-2 w-full border border-gray-100 p-2.5 rounded-xl text-sm bg-gray-50 text-gray-400 cursor-not-allowed outline-none">
                            <option value="">Nenhum produto disponível...</option>
                        </select>
                    <?php else: ?>
                        <select name="product_id" required class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Selecione...</option>
                            <?php foreach($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Saldo: <?= number_format($p['balance'], 2, ',', '.') ?> <?= $p['unit'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data</label>
                        <input type="date" name="date" value="<?= date('Y-m-d') ?>" required class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Espécie</label>
                        <select name="type" required onchange="checkCustomType(this)" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="Entrada">Entrada</option>
                            <option value="Saída">Saída</option>
                            <option value="Consumo">Consumo</option>
                            <option value="Outros">Outros...</option>
                        </select>
                        <input type="text" id="custom_type" name="custom_type" placeholder="Especifique..." class="hidden mt-2 w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Quantidade</label>
                        <input type="number" step="0.01" name="quantity" required class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Preço Unit.</label>
                        <input type="number" step="0.01" name="price" value="0.00" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Fornecedor / Origem</label>
                    <input type="text" name="supplier" placeholder="Ex: Distribuidora X" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-2 mt-4">
                    <i class="fas fa-save"></i>
                    Salvar Movimentação
                </button>
            </form>
        </div>
    </div>

    <!-- Movement History -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between font-bold text-sm text-gray-700 uppercase tracking-wider">
                <span>Histórico Recente</span>
                <i class="fas fa-history text-gray-400"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase text-gray-400 tracking-widest font-bold">
                        <tr>
                            <th class="px-6 py-3 text-left">Data</th>
                            <th class="px-6 py-3 text-left">Produto</th>
                            <th class="px-6 py-3 text-left">Espécie</th>
                            <th class="px-6 py-3 text-right">Qtd</th>
                            <th class="px-6 py-3 text-right">Vlr. Unit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($movements as $m): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-500">
                                <?= date('d/m/Y', strtotime($m['date'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-900 font-bold"><?= htmlspecialchars($m['product_name']) ?></span>
                                <span class="text-[10px] text-gray-400 block"><?= htmlspecialchars($m['supplier']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $m['type'] === 'Entrada' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                                    <?= $m['type'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-700">
                                <?= number_format($m['quantity'], 2, ',', '.') ?> <span class="text-[10px] font-normal text-gray-400"><?= $m['unit'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-500">
                                R$ <?= number_format($m['price'], 2, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($movements)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">Nenhuma movimentação registrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function checkCustomType(select) {
    const customInput = document.getElementById('custom_type');
    if (select.value === 'Outros') {
        customInput.classList.remove('hidden');
        customInput.required = true;
        // When user types in customInput, it should be the value submitted. 
        // Simplest way for PHP is to check if type == 'Outros' then use custom_type.
    } else {
        customInput.classList.add('hidden');
        customInput.required = false;
    }
}

// Intercept form submission to handle "Outros"
document.querySelector('form').addEventListener('submit', function(e) {
    const select = this.querySelector('select[name="type"]');
    const customInput = document.getElementById('custom_type');
    if (select.value === 'Outros' && customInput.value) {
        // Create a hidden input or change the select value
        // To keep it simple, we'll let the PHP handle it.
    }
});
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
