<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/billing.php';

$company_id = $_SESSION['company_id'];
$status = checkBillingStatus($company_id);

if ($status === 'active') {
    header('Location: dashboard.php');
    exit;
}

// Fetch any pending invoices
$stmt = $pdo->prepare("SELECT * FROM saas_invoices WHERE company_id = ? AND status = 'PENDING' ORDER BY due_date ASC");
$stmt->execute([$company_id]);
$invoices = $stmt->fetchAll();

?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Acesso Bloqueado - ShopBarber</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

<div class="max-w-md w-full bg-white rounded-3xl shadow-2xl shadow-gray-200 overflow-hidden border border-gray-100 italic-hover">
    <div class="p-8 text-center space-y-6">
        <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
            <i class="fas fa-lock text-3xl"></i>
        </div>
        
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Assinatura Pendente</h1>
            <p class="text-gray-500 mt-2 text-sm">O acesso ao sistema ShopBarber está temporariamente suspenso devido a pendências financeiras ou expiração do período de teste.</p>
        </div>

        <?php if($invoices): ?>
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-left">
                <p class="text-amber-800 font-bold text-sm mb-3">Faturas em Aberto:</p>
                <div class="space-y-3">
                    <?php foreach($invoices as $inv): ?>
                        <div class="flex items-center justify-between text-xs bg-white p-3 rounded-xl border border-amber-200 shadow-sm">
                            <div>
                                <span class="block font-bold text-gray-700"><?= $inv['type'] === 'SETUP' ? 'Taxa de Implantação' : 'Mensalidade' ?></span>
                                <span class="text-gray-400">Vencimento: <?= date('d/m/Y', strtotime($inv['due_date'])) ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block font-bold text-rose-600 text-sm">R$ <?= number_format($inv['amount'], 2, ',', '.') ?></span>
                                <a href="<?= $inv['invoice_url'] ?>" target="_blank" class="text-blue-600 hover:underline font-bold">Pagar Agora</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
             <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 text-center">
                <p class="text-indigo-800 font-bold text-sm">Nenhuma fatura encontrada.</p>
                <p class="text-indigo-600 text-xs mt-1">Sua conta pode ter sido desativada pelo administrador.</p>
            </div>
        <?php endif; ?>

        <div class="pt-4 space-y-3">
            <a href="billing.php" class="block w-full py-3.5 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                Gerenciar Assinatura
            </a>
            <a href="logout.php" class="block w-full py-3 bg-white text-gray-500 rounded-2xl font-bold hover:bg-gray-50 border border-gray-200 transition-colors">
                Sair
            </a>
        </div>
        
        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">ShopBarber SaaS</p>
    </div>
</div>

</body>
</html>
