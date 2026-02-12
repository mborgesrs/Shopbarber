<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/billing.php';
require_once __DIR__ . '/../lib/helpers.php';


if (session_status() === PHP_SESSION_NONE) session_start();

$current_company_id = $_SESSION['company_id'] ?? null;
$company_brand_name = $app_name;
$company_logo_url = null;

// Billing Bypass for Super Admin or specific pages
$allowed_pages = ['billing.php', 'billing_blocked.php', 'logout.php', 'login.php', 'setup_payment.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_company_id && !isSuperAdmin() && !in_array($current_page, $allowed_pages)) {
    $status = checkBillingStatus($current_company_id);
    if ($status === 'blocked' || $status === 'inactive') {
        header('Location: /billing_blocked.php');
        exit;
    }
}

if($current_company_id) {
    $stmtBrand = $pdo->prepare("SELECT fantasy_name, logo_path FROM settings WHERE company_id = ? LIMIT 1");
    $stmtBrand->execute([$current_company_id]);
    $brandData = $stmtBrand->fetch();
    if($brandData) {
        if(!empty($brandData['fantasy_name'])) $company_brand_name = $brandData['fantasy_name'];
        if(!empty($brandData['logo_path'])) $company_logo_url = '/' . $brandData['logo_path'];
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlentities($company_brand_name) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 text-gray-600 font-normal antialiased">
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-72 bg-white border-r hidden md:block transition-all duration-300">
      <div class="p-8 pb-6">
        <div class="flex items-center gap-3">
          <?php if($company_logo_url): ?>
            <div class="w-10 h-10 rounded-xl overflow-hidden shadow-md">
                <img src="<?= $company_logo_url ?>" alt="Logo" class="w-full h-full object-contain bg-white">
            </div>
          <?php else: ?>
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
              <i class="fas fa-scissors text-white text-lg"></i>
            </div>
          <?php endif; ?>
          <div>
            <h1 class="font-bold text-sm text-gray-900 leading-tight tracking-tight"><?= htmlentities($company_brand_name) ?></h1>
            <p class="text-[9px] uppercase tracking-wider text-gray-400 font-bold">Sistema de Gestão</p>
          </div>
        </div>
      </div>
      
      <nav class="px-4 py-4 space-y-1">
        <a href="/dashboard.php" class="flex items-center gap-3 py-2.5 px-3 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
          <i class="fas fa-chart-pie text-blue-500 w-5"></i>
          <span class="font-medium text-sm">Dashboard</span>
        </a>

        <!-- Cadastros -->
        <div class="pt-2">
            <button onclick="toggleMenu('menu-cadastros')" class="w-full flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors group">
                <div class="flex items-center gap-3">
                    <i class="fas fa-folder text-indigo-500 w-5"></i>
                    <span class="font-medium text-sm">Cadastros</span>
                </div>
                <i id="arrow-menu-cadastros" class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform"></i>
            </button>
            <div id="menu-cadastros" class="hidden pl-8 space-y-1 mt-1">
                <a href="/clients.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-indigo-600 rounded-lg hover:bg-gray-50 transition-colors">Pessoas</a>
                <a href="/products.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-indigo-600 rounded-lg hover:bg-gray-50 transition-colors">Produtos</a>
            </div>
        </div>

        <!-- Agendamentos -->
        <div class="pt-1">
            <button onclick="toggleMenu('menu-agendamentos')" class="w-full flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors group">
                <div class="flex items-center gap-3">
                    <i class="fas fa-calendar-alt text-amber-500 w-5"></i>
                    <span class="font-medium text-sm">Agendamentos</span>
                </div>
                <i id="arrow-menu-agendamentos" class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform"></i>
            </button>
            <div id="menu-agendamentos" class="hidden pl-8 space-y-1 mt-1">
                <a href="/calendar.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-amber-600 rounded-lg hover:bg-gray-50 transition-colors">Agenda (Calendário)</a>
                <a href="/quotes.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-amber-600 rounded-lg hover:bg-gray-50 transition-colors">Lista de Agendamentos</a>
            </div>
        </div>

        <!-- Gestão Financeira -->
        <div class="pt-1">
            <button onclick="toggleMenu('menu-financeiro')" class="w-full flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors group">
                <div class="flex items-center gap-3">
                    <i class="fas fa-wallet text-emerald-500 w-5"></i>
                    <span class="font-medium text-sm">Gestão Financeira</span>
                </div>
                <i id="arrow-menu-financeiro" class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform"></i>
            </button>
            <div id="menu-financeiro" class="hidden pl-8 space-y-1 mt-1">
                <a href="/finance.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-emerald-600 rounded-lg hover:bg-gray-50 transition-colors">Movimentações</a>
                <a href="/banks.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-emerald-600 rounded-lg hover:bg-gray-50 transition-colors">Bancos</a>
                <a href="/contas.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-emerald-600 rounded-lg hover:bg-gray-50 transition-colors">Contas</a>
                <a href="/portadores.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-emerald-600 rounded-lg hover:bg-gray-50 transition-colors">Portadores</a>
                <a href="/tipos_pagamento.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-emerald-600 rounded-lg hover:bg-gray-50 transition-colors">Tipos de Pagamento</a>
            </div>
        </div>

        <?php if(isSuperAdmin()): ?>
        <!-- SaaS Admin -->
        <div class="pt-1">
            <a href="/saas_admin.php" class="flex items-center gap-3 py-2.5 px-3 rounded-lg hover:bg-indigo-50 text-indigo-700 transition-colors">
                <i class="fas fa-server w-5"></i>
                <span class="font-bold text-sm">Painel SaaS</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Configurações -->
        <div class="pt-1">
            <button onclick="toggleMenu('menu-config')" class="w-full flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors group">
                <div class="flex items-center gap-3">
                    <i class="fas fa-cog text-gray-500 w-5"></i>
                    <span class="font-medium text-sm">Configurações</span>
                </div>
                <i id="arrow-menu-config" class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform"></i>
            </button>
            <div id="menu-config" class="hidden pl-8 space-y-1 mt-1">
                <a href="/settings.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Minha Empresa</a>
                <a href="/users.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Usuários</a>
                <a href="/user_create.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Novo Usuário</a>
                <a href="/companies.php" class="block py-2 px-3 text-sm text-gray-500 hover:text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Lista de Empresas</a>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100">
          <a href="/logout.php" class="flex items-center gap-3 py-2.5 px-3 rounded-lg hover:bg-red-50 text-gray-500 hover:text-red-600 transition-colors">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span class="font-medium text-sm">Sair</span>
          </a>
        </div>
      </nav>

      <script>
        function toggleMenu(id) {
            const menu = document.getElementById(id);
            const arrow = document.getElementById('arrow-' + id);
            const isHidden = menu.classList.contains('hidden');
            
            // Close all other menus if you want (optional)
            // document.querySelectorAll('[id^="menu-"]').forEach(el => el.classList.add('hidden'));
            // document.querySelectorAll('[id^="arrow-"]').forEach(el => el.classList.remove('rotate-180'));

            if (isHidden) {
                menu.classList.remove('hidden');
                arrow.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }
        
        // Auto-open menu based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            if (currentPath.includes('clients') || currentPath.includes('products')) toggleMenu('menu-cadastros');
            if (currentPath.includes('calendar') || currentPath.includes('quotes')) toggleMenu('menu-agendamentos');
            if (currentPath.includes('finance') || currentPath.includes('banks') || currentPath.includes('contas') || currentPath.includes('portadores') || currentPath.includes('tipos_pagamento')) toggleMenu('menu-financeiro');
            if (currentPath.includes('companies') || currentPath.includes('user') || currentPath.includes('settings')) toggleMenu('menu-config');
        });
      </script>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
      <!-- Main Header -->
      <header class="bg-white border-b border-gray-100 flex-shrink-0">
        <div class="px-8 py-4 flex items-center justify-between">
          <div>
            <!-- Breadcrumb or Title placeholder if needed -->
          </div>
          <div class="flex items-center gap-4">
            <div class="h-8 w-px bg-gray-100 mx-2"></div>
            <div class="flex items-center gap-3">
              <div class="text-right">
                <p class="text-sm font-bold text-gray-900 leading-tight"><?php if(session_status()===PHP_SESSION_ACTIVE && isset($_SESSION['user_name'])) echo htmlspecialchars($_SESSION['user_name']); ?></p>
                <p class="text-[10px] text-gray-400 font-medium">Operador</p>
              </div>
              <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 border-2 border-white shadow-sm">
                <i class="fas fa-user-circle text-xl"></i>
              </div>
            </div>
          </div>
        </div>
      </header>
      
      <!-- Main Content Area -->
      <main class="flex-1 overflow-y-auto p-8">
