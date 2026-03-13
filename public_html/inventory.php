<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$company_id = $_SESSION['company_id'];

// Handle Movement Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move') {
    $product_id = $_POST['product_id'];
    $date = $_POST['date'];
    
    $supplier_raw = $_POST['supplier_id'] ?? '';
    $supplier_id = null;
    $supplier_type = 'company';
    if (!empty($supplier_raw) && strpos($supplier_raw, ':') !== false) {
        list($type_prefix, $id_val) = explode(':', $supplier_raw);
        $supplier_id = (int)$id_val;
        $supplier_type = ($type_prefix === 'clie') ? 'client' : 'company';
    }

    $invoice_number = $_POST['invoice_number'] ?? '';
    $invoice_series = $_POST['invoice_series'] ?? '';
    $quantity = $_POST['quantity'];
    $price = $_POST['price'] ?? 0;
    $total_price = $quantity * $price;
    $type = $_POST['type']; // especie: Entrada, Saída, Consumo...

    try {
        $pdo->beginTransaction();

        // 0. Fetch Current Product State
        $stmt = $pdo->prepare("SELECT balance, pr_medio FROM products WHERE id = ? AND company_id = ?");
        $stmt->execute([$product_id, $company_id]);
        $product = $stmt->fetch();

        if (!$product) throw new Exception("Produto não encontrado.");

        $old_balance = $product['balance'];
        $old_pr_medio = $product['pr_medio'];

        // 1. Record Movement
        $stmt = $pdo->prepare("INSERT INTO inventory_movements (company_id, product_id, date, supplier_id, supplier_type, quantity, price, total_price, type, invoice_number, invoice_series) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $product_id, $date, $supplier_id, $supplier_type, $quantity, $price, $total_price, $type, $invoice_number, $invoice_series]);

        // 2. Update Product Balance and Average Price
        $adj = ($type === 'Entrada') ? $quantity : -$quantity;
        $new_balance = $old_balance + $adj;
        
        $update_data = [$new_balance];
        $sql_update = "balance = ?";

        if ($type === 'Entrada') {
            // New Average Price: ((old_qty * old_pr_medio) + (new_qty * entry_price)) / (old_qty + new_qty)
            // But if old_balance + entry_qty <= 0 we shouldn't divide or just use the new entry price
            if ($new_balance > 0) {
                $new_pr_medio = (($old_balance * $old_pr_medio) + ($quantity * $price)) / $new_balance;
            } else {
                $new_pr_medio = $price;
            }
            $sql_update .= ", pr_medio = ?, pr_custo = ?";
            $update_data[] = $new_pr_medio;
            $update_data[] = $price;
        }

        $update_data[] = $product_id;
        $update_data[] = $company_id;

        $stmt = $pdo->prepare("UPDATE products SET $sql_update WHERE id = ? AND company_id = ?");
        $stmt->execute($update_data);

        $pdo->commit();
        $_SESSION['success'] = "Movimentação registrada com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erro ao registrar: " . $e->getMessage();
    }
    header('Location: inventory.php'); exit;
}

// Fetch Ativo Products for dropdown
$stmt = $pdo->prepare("SELECT id, name, unit, balance, pr_medio FROM products WHERE company_id = ? AND type = 'Ativo' ORDER BY name");
$stmt->execute([$company_id]);
$products = $stmt->fetchAll();

// Fetch Suppliers from BOTH Companies and Clients
$suppliers = [];

// From Companies
$stmt = $pdo->prepare("SELECT id, name, 'comp' as type_prefix FROM companies WHERE (id = ? OR parent_company_id = ?) AND division = 'Fornecedores' ORDER BY name");
$stmt->execute([$company_id, $company_id]);
$comp_suppliers = $stmt->fetchAll();

// From Clients
$stmt = $pdo->prepare("SELECT id, name, 'clie' as type_prefix FROM clients WHERE company_id = ? AND division = 'Fornecedores' ORDER BY name");
$stmt->execute([$company_id]);
$clie_suppliers = $stmt->fetchAll();

$suppliers = array_merge($comp_suppliers, $clie_suppliers);
// Sort merged array by name
usort($suppliers, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Fetch Recent Movements
$stmt = $pdo->prepare("
    SELECT m.*, p.name as product_name, p.unit, 
           CASE 
               WHEN m.supplier_type = 'client' THEN cli.name 
               ELSE comp.name 
           END as supplier_name
    FROM inventory_movements m 
    JOIN products p ON m.product_id = p.id 
    LEFT JOIN companies comp ON m.supplier_id = comp.id AND m.supplier_type = 'company'
    LEFT JOIN clients cli ON m.supplier_id = cli.id AND m.supplier_type = 'client'
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
    <a href="dashboard.php" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-50 transition-all shadow-sm">
        <i class="fas fa-arrow-left"></i>
        Voltar ao Início
    </a>
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
                        <input type="number" step="0.01" name="quantity" id="move_qty" required oninput="calcTotal()" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Preço Unit.</label>
                        <input type="number" step="0.01" name="price" id="move_price" value="0.00" oninput="calcTotal()" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Total do Item:</span>
                    <span id="move_total" class="text-sm font-bold text-slate-700">R$ 0,00</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">NF Número</label>
                        <input name="invoice_number" placeholder="000.000" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">NF Série</label>
                        <input name="invoice_series" placeholder="1" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Fornecedor</label>
                    <select name="supplier_id" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Selecione...</option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s['type_prefix'] ?>:<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                                <div class="text-[10px] text-gray-400">
                                    <span class="font-medium"><?= htmlspecialchars($m['supplier_name'] ?: '-') ?></span>
                                    <?php if($m['invoice_number']): ?>
                                        <span class="mx-1">•</span>
                                        <span>NF: <?= htmlspecialchars($m['invoice_number']) ?><?= $m['invoice_series'] ? '/'.$m['invoice_series'] : '' ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $m['type'] === 'Entrada' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                                    <?= $m['type'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-700">
                                <?= number_format($m['quantity'], 2, ',', '.') ?> <span class="text-[10px] font-normal text-gray-400"><?= $m['unit'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-gray-900 font-bold">R$ <?= number_format($m['total_price'], 2, ',', '.') ?></div>
                                <div class="text-[10px] text-gray-400">R$ <?= number_format($m['price'], 2, ',', '.') ?>/<?= $m['unit'] ?></div>
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
function calcTotal() {
    const qty = parseFloat(document.getElementById('move_qty').value) || 0;
    const price = parseFloat(document.getElementById('move_price').value) || 0;
    const total = qty * price;
    document.getElementById('move_total').innerText = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

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
