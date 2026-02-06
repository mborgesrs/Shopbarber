<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/billing.php';

if(!isSuperAdmin()){ 
    echo "Acesso negado. Apenas para Super Administradores.";
    exit; 
}

// Stats
$total_companies = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$active_companies = $pdo->query("SELECT COUNT(*) FROM companies WHERE subscription_status = 'active'")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(amount) FROM saas_invoices WHERE status = 'PAID'")->fetchColumn() ?: 0;
$pending_invoices = $pdo->query("SELECT SUM(amount) FROM saas_invoices WHERE status = 'PENDING'")->fetchColumn() ?: 0;

// All companies with their details
$companies = $pdo->query("SELECT c.*, 
    (SELECT MAX(payment_date) FROM saas_invoices WHERE company_id = c.id AND status = 'PAID') as last_payment 
    FROM companies c ORDER BY created_at DESC")->fetchAll();

include __DIR__ . '/../views/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Painel SaaS</h2>
            <p class="text-gray-500 mt-1">Gerenciamento global de clientes e faturamento.</p>
        </div>
        <div class="flex gap-2">
            <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-100 flex items-center gap-2 hover:bg-indigo-700 transition-all">
                <i class="fas fa-plus"></i> Nova Unidade
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-building text-xl"></i>
            </div>
            <span class="block text-2xl font-bold text-gray-800"><?= $total_companies ?></span>
            <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Empresas Totais</span>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-check-double text-xl"></i>
            </div>
            <span class="block text-2xl font-bold text-gray-800"><?= $active_companies ?></span>
            <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Contas Ativas</span>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
            <span class="block text-2xl font-bold text-gray-800">R$ <?= number_format($total_revenue, 2, ',', '.') ?></span>
            <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Receita Total</span>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <span class="block text-2xl font-bold text-gray-800">R$ <?= number_format($pending_invoices, 2, ',', '.') ?></span>
            <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">Em Aberto</span>
        </div>
    </div>

    <!-- Companies List -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Minhas Unidades SaaS</h3>
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                <input type="text" placeholder="Buscar empresa..." class="pl-11 pr-4 py-2 bg-gray-50 border-transparent rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all outline-none">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50/50 text-gray-400 uppercase text-[10px] font-bold tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Empresa / Documento</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Plano</th>
                        <th class="px-6 py-4">Vencimento</th>
                        <th class="px-6 py-4">Último Pagamento</th>
                        <th class="px-6 py-4">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach($companies as $c): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div>
                                        <span class="block font-bold text-gray-800"><?= htmlspecialchars($c['name']) ?></span>
                                        <span class="text-[10px] text-gray-400"><?= $c['document'] ?: 'Sem documento' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                    $badge = 'bg-gray-100 text-gray-600';
                                    if($c['subscription_status'] === 'active') $badge = 'bg-emerald-50 text-emerald-600';
                                    if($c['subscription_status'] === 'overdue') $badge = 'bg-rose-50 text-rose-600';
                                    if($c['subscription_status'] === 'trialing') $badge = 'bg-indigo-50 text-indigo-600';
                                ?>
                                <span class="px-2 py-1 rounded-lg text-[10px] font-bold uppercase <?= $badge ?>">
                                    <?= $c['subscription_status'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="block font-bold text-gray-700"><?= $c['plan_id'] ?></span>
                                <span class="text-[10px] text-gray-400"><?= ucfirst($c['billing_cycle']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                <?php 
                                    if($c['subscription_status'] === 'trialing' && $c['trial_ends_at']) {
                                        echo '<span class="text-indigo-600 font-bold">Trial até ' . date('d/m/Y', strtotime($c['trial_ends_at'])) . '</span>';
                                    } elseif($c['next_due_date']) {
                                        echo date('d/m/Y', strtotime($c['next_due_date']));
                                    } else {
                                        echo '--';
                                    }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500 italic">
                                <?= $c['last_payment'] ? date('d/m/Y', strtotime($c['last_payment'])) : 'Nenhum' ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="company_edit.php?id=<?= $c['id'] ?>" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="p-2 text-gray-400 hover:text-rose-600 transition-colors" title="Bloquear">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
