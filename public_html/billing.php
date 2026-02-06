<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/Asaas.php';
require_once __DIR__ . '/../lib/billing.php';

$company_id = $_SESSION['company_id'];
$company_name = $_SESSION['company_name'] ?? 'Empresa';

// Fetch company billing details
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

// Fetch invoices
$stmt = $pdo->prepare("SELECT * FROM saas_invoices WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$company_id]);
$invoices = $stmt->fetchAll();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'select_plan') {
        $plan_id = $_POST['plan_id'];
        $cycle = $_POST['billing_cycle']; // monthly, yearly
        
        $pdo->prepare("UPDATE companies SET plan_id = ?, billing_cycle = ? WHERE id = ?")
            ->execute([$plan_id, $cycle, $company_id]);
            
        $msg = "Plano atualizado com sucesso!";
        // Refresh company data
        $stmt->execute([$company_id]);
        $company = $stmt->fetch();
    }
}

// Logic to create setup charge if not paid and not exists
if ($company['setup_paid'] == 0) {
    $stmt = $pdo->prepare("SELECT id FROM saas_invoices WHERE company_id = ? AND type = 'SETUP' AND status = 'PENDING'");
    $stmt->execute([$company_id]);
    if (!$stmt->fetch()) {
        // Need to create Asaas Charge here... 
        // This would require the company to have CNPJ/CPF and Email in settings
    }
}

include __DIR__ . '/../views/header.php';
?>

<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Minha Assinatura</h2>
        <p class="text-sm text-gray-500">Gerencie seus pagamentos e plano do ShopBarber.</p>
    </div>

    <?php if($msg): ?>
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <span class="font-bold text-sm"><?= $msg ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Status Card -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-4">Status Atual</p>
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 <?= $company['subscription_status'] === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' ?> rounded-2xl flex items-center justify-center">
                        <i class="fas <?= $company['subscription_status'] === 'active' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> text-xl"></i>
                    </div>
                    <div>
                        <span class="block text-lg font-bold text-gray-800"><?= ucfirst($company['subscription_status']) ?></span>
                        <span class="text-xs text-gray-400">Desde <?= date('d/m/Y', strtotime($company['created_at'])) ?></span>
                    </div>
                </div>
                
                <div class="space-y-4 pt-4 border-t border-gray-50">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Próximo Vencimento</span>
                        <span class="font-bold text-gray-700"><?= $company['next_due_date'] ? date('d/m/Y', strtotime($company['next_due_date'])) : 'N/A' ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Plano</span>
                        <span class="font-bold text-gray-700"><?= $company['plan_id'] ?> (<?= ucfirst($company['billing_cycle']) ?>)</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Taxa Implantação</span>
                        <span class="<?= $company['setup_paid'] ? 'text-emerald-600' : 'text-rose-600' ?> font-bold"><?= $company['setup_paid'] ? 'Paga' : 'Pendente' ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-3xl shadow-xl p-6 text-white overflow-hidden relative">
                <i class="fas fa-headset absolute -right-4 -bottom-4 text-8xl text-indigo-500/20"></i>
                <h4 class="font-bold mb-2">Precisa de ajuda?</h4>
                <p class="text-indigo-100 text-xs mb-4">Fale com nosso suporte sobre cobranças ou planos personalizados.</p>
                <a href="#" class="inline-block px-4 py-2 bg-white text-indigo-600 rounded-xl text-xs font-bold hover:bg-indigo-50 transition-colors">Contatar Suporte</a>
            </div>
        </div>

        <!-- Plans & Invoices -->
        <div class="md:col-span-2 space-y-6">
            <!-- Plan Selection -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">Planos Disponíveis</h3>
                    <div class="flex bg-gray-50 p-1 rounded-xl">
                        <button class="px-4 py-1.5 text-xs font-bold rounded-lg bg-white shadow-sm text-gray-700">Mensal</button>
                        <button class="px-4 py-1.5 text-xs font-bold text-gray-400 hover:text-gray-600 transition-colors">Anual <span class="bg-emerald-100 text-emerald-600 px-1 rounded">-20%</span></button>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border-2 <?= $company['plan_id'] === 'monthly_79' ? 'border-indigo-500 bg-indigo-50/20' : 'border-gray-50' ?> rounded-2xl p-4 relative">
                        <?php if($company['plan_id'] === 'monthly_79'): ?>
                            <span class="absolute -top-3 -right-3 bg-indigo-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg">ATUAL</span>
                        <?php endif; ?>
                        <h4 class="font-bold text-gray-800">Essencial</h4>
                        <p class="text-xs text-gray-400 mb-4">Ideal para barbeiros individuais.</p>
                        <div class="flex items-baseline gap-1 mb-4">
                            <span class="text-sm font-bold text-gray-400 italic">R$</span>
                            <span class="text-3xl font-bold text-gray-800">79</span>
                            <span class="text-sm text-gray-400">/mês</span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="select_plan">
                            <input type="hidden" name="plan_id" value="monthly_79">
                            <input type="hidden" name="billing_cycle" value="monthly">
                            <button class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all">Selecionar</button>
                        </form>
                    </div>

                    <div class="border-2 <?= $company['plan_id'] === 'monthly_149' ? 'border-indigo-500 bg-indigo-50/20' : 'border-gray-50' ?> rounded-2xl p-4 relative">
                        <?php if($company['plan_id'] === 'monthly_149'): ?>
                            <span class="absolute -top-3 -right-3 bg-indigo-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg">ATUAL</span>
                        <?php endif; ?>
                        <h4 class="font-bold text-gray-800">Professional</h4>
                        <p class="text-xs text-gray-400 mb-4">Para equipes e barbearias maiores.</p>
                        <div class="flex items-baseline gap-1 mb-4">
                            <span class="text-sm font-bold text-gray-400 italic">R$</span>
                            <span class="text-3xl font-bold text-gray-800">149</span>
                            <span class="text-sm text-gray-400">/mês</span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="select_plan">
                            <input type="hidden" name="plan_id" value="monthly_149">
                            <input type="hidden" name="billing_cycle" value="monthly">
                            <button class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-indigo-100">Atualizar</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50">
                    <h3 class="font-bold text-gray-800">Histórico de Faturas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/50 text-gray-400 uppercase text-[10px] font-bold tracking-widest">
                            <tr>
                                <th class="px-6 py-4">Data</th>
                                <th class="px-6 py-4">Descrição</th>
                                <th class="px-6 py-4">Valor</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach($invoices as $inv): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($inv['created_at'])) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-gray-700"><?= $inv['type'] === 'SETUP' ? 'Implantação' : 'Mensalidade' ?></span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-700">R$ <?= number_format($inv['amount'], 2, ',', '.') ?></td>
                                    <td class="px-6 py-4">
                                        <?php if($inv['status'] === 'PAID'): ?>
                                            <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-bold">PAGO</span>
                                        <?php elseif($inv['status'] === 'OVERDUE'): ?>
                                            <span class="px-2 py-1 bg-rose-50 text-rose-600 rounded-lg text-[10px] font-bold">VENCIDO</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-amber-50 text-amber-600 rounded-lg text-[10px] font-bold">PENDENTE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($inv['status'] !== 'PAID'): ?>
                                            <a href="<?= $inv['invoice_url'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold">Pagar</a>
                                        <?php else: ?>
                                            <span class="text-gray-300">Concluído</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($invoices)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">Nenhuma fatura encontrada.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
