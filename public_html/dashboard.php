<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';

$company_id = $_SESSION['company_id'] ?? 1;

$start = date('Y-m-01 00:00:00');
$end = date('Y-m-t 23:59:59');

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quotes WHERE company_id = ? AND created_at BETWEEN ? AND ?"); 
$stmt->execute([$company_id, $start, $end]); 
$total = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE company_id = ? AND status='Atendido' AND created_at BETWEEN ? AND ?"); 
$stmt->execute([$company_id, $start, $end]); 
$attended = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE company_id = ? AND status='Cancelado' AND created_at BETWEEN ? AND ?"); 
$stmt->execute([$company_id, $start, $end]); 
$canceled = $stmt->fetchColumn();

// Hoje
$todayStart = date('Y-m-d 00:00:00'); 
$todayEnd = date('Y-m-d 23:59:59');
$stmt = $pdo->prepare('SELECT q.*, c.name as client_name FROM quotes q JOIN clients c ON c.id=q.client_id WHERE q.company_id = ? AND q.date_time BETWEEN ? AND ? ORDER BY q.date_time');
$stmt->execute([$company_id, $todayStart, $todayEnd]);
$today_rows = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800">Bem-vindo, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?>!</h2>
    <p class="text-gray-500">Aqui está o que está acontecendo na sua barbearia hoje.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Month -->
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-purple-600 p-6 rounded-2xl shadow-lg shadow-indigo-100 transition-transform hover:scale-[1.02]">
        <div class="relative z-10 flex justify-between items-start">
            <div>
                <p class="text-indigo-100 text-sm font-medium uppercase tracking-wider mb-1">Agendamentos / Mês</p>
                <h3 class="text-4xl font-bold text-white"><?= $total ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                <i class="fas fa-calendar-check text-white text-2xl"></i>
            </div>
        </div>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
    </div>

    <!-- Attended -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-400 to-teal-600 p-6 rounded-2xl shadow-lg shadow-emerald-100 transition-transform hover:scale-[1.02]">
        <div class="relative z-10 flex justify-between items-start">
            <div>
                <p class="text-emerald-50 text-sm font-medium uppercase tracking-wider mb-1">Concluídos / Mês</p>
                <h3 class="text-4xl font-bold text-white"><?= $attended ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                <i class="fas fa-check-circle text-white text-2xl"></i>
            </div>
        </div>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
    </div>

    <!-- Canceled -->
    <div class="relative overflow-hidden bg-gradient-to-br from-rose-400 to-red-600 p-6 rounded-2xl shadow-lg shadow-rose-100 transition-transform hover:scale-[1.02]">
        <div class="relative z-10 flex justify-between items-start">
            <div>
                <p class="text-rose-50 text-sm font-medium uppercase tracking-wider mb-1">Cancelados / Mês</p>
                <h3 class="text-4xl font-bold text-white"><?= $canceled ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                <i class="fas fa-times-circle text-white text-2xl"></i>
            </div>
        </div>
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Today's Appointments -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900 border-l-4 border-indigo-500 pl-4">Agendamentos de Hoje</h3>
            <a href="/calendar.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Ver Agenda completa</a>
        </div>
        
        <?php if(!$today_rows): ?>
            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i class="far fa-calendar text-2xl"></i>
                </div>
                <p>Nenhum agendamento para hoje.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach($today_rows as $r): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-white hover:shadow-md transition-all border border-transparent hover:border-gray-100 group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white rounded-lg flex flex-col items-center justify-center border border-gray-200">
                                <span class="text-xs font-bold text-indigo-600 uppercase"><?= date('H:i', strtotime($r['date_time'])) ?></span>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900"><?= htmlspecialchars($r['client_name']) ?></h4>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($r['notes']) ?: 'Sem observações' ?></p>
                            </div>
                        </div>
                        <div>
                            <?php 
                            $status_class = 'bg-gray-100 text-gray-600';
                            if($r['status']=='Confirmado') $status_class = 'bg-blue-100 text-blue-700';
                            elseif($r['status']=='Atendido') $status_class = 'bg-emerald-100 text-emerald-700';
                            elseif($r['status']=='Cancelado') $status_class = 'bg-rose-100 text-rose-700';
                            ?>
                            <span class="px-3 py-1 <?= $status_class ?> text-xs font-bold rounded-full uppercase">
                                <?= $r['status'] ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
        <h3 class="text-lg font-bold text-gray-900 mb-6 text-left">Ações Rápidas</h3>
        <div class="grid grid-cols-2 gap-4">
            <a href="/quote_create.php" class="flex flex-col items-center justify-center p-4 bg-indigo-50 rounded-2xl hover:bg-indigo-100 transition-colors group">
                <div class="w-10 h-10 bg-indigo-100 group-hover:bg-indigo-200 rounded-xl flex items-center justify-center mb-2 transition-colors">
                    <i class="fas fa-plus text-indigo-600"></i>
                </div>
                <span class="text-xs font-bold text-indigo-700">Novo Agendamento</span>
            </a>
            <a href="/client_create.php" class="flex flex-col items-center justify-center p-4 bg-emerald-50 rounded-2xl hover:bg-emerald-100 transition-colors group">
                <div class="w-10 h-10 bg-emerald-100 group-hover:bg-emerald-200 rounded-xl flex items-center justify-center mb-2 transition-colors">
                    <i class="fas fa-user-plus text-emerald-600"></i>
                </div>
                <span class="text-xs font-bold text-emerald-700">Novo Cliente</span>
            </a>
            <a href="/finance.php" class="flex flex-col items-center justify-center p-4 bg-amber-50 rounded-2xl hover:bg-amber-100 transition-colors group">
                <div class="w-10 h-10 bg-amber-100 group-hover:bg-amber-200 rounded-xl flex items-center justify-center mb-2 transition-colors">
                    <i class="fas fa-dollar-sign text-amber-600"></i>
                </div>
                <span class="text-xs font-bold text-amber-700">Financeiro</span>
            </a>
            <a href="/products.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-2xl hover:bg-purple-100 transition-colors group">
                <div class="w-10 h-10 bg-purple-100 group-hover:bg-purple-200 rounded-xl flex items-center justify-center mb-2 transition-colors">
                    <i class="fas fa-box text-purple-600"></i>
                </div>
                <span class="text-xs font-bold text-purple-700">Produtos</span>
            </a>
        </div>
        
        <div class="mt-8 p-4 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
            <p class="text-xs text-gray-400 font-medium italic">"O melhor marketing é a satisfação do seu cliente."</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
