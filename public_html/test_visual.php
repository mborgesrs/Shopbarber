<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
$start = date('Y-m-01 00:00:00');
$end = date('Y-m-t 23:59:59');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quotes WHERE created_at BETWEEN ? AND ?"); $stmt->execute([$start,$end]); $total = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE status='Atendido' AND created_at BETWEEN ? AND ?"); $stmt->execute([$start,$end]); $attended = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE status='Cancelado' AND created_at BETWEEN ? AND ?"); $stmt->execute([$start,$end]); $canceled = $stmt->fetchColumn();
?>
<?php include __DIR__ . '/../views/header.php'; ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <div class="p-4 bg-white rounded shadow">
    <div class="text-sm text-gray-500">Agendamentos este mês</div>
    <div class="text-3xl font-bold"><?= $total ?></div>
  </div>
  <div class="p-4 bg-white rounded shadow">
    <div class="text-sm text-gray-500">Atendidos</div>
    <div class="text-3xl font-bold text-green-600"><?= $attended ?></div>
  </div>
  <div class="p-4 bg-white rounded shadow">
    <div class="text-sm text-gray-500">Cancelados</div>
    <div class="text-3xl font-bold text-red-600"><?= $canceled ?></div>
  </div>
</div>

<div class="mt-6 bg-yellow-50 rounded shadow p-4 border border-yellow-200">
  <h3 class="font-bold mb-2">Agendamentos do Dia (Hoje) - TESTE VISUAL</h3>
  <?php
  $todayStart = date('Y-m-d 00:00:00'); $todayEnd = date('Y-m-d 23:59:59');
  $stmt = $pdo->prepare('SELECT q.*, c.name as client_name FROM quotes q JOIN clients c ON c.id=q.client_id WHERE q.date_time BETWEEN ? AND ? ORDER BY q.date_time');
  $stmt->execute([$todayStart,$todayEnd]);
  $rows = $stmt->fetchAll();
  if(!$rows) echo '<div class="text-gray-500">Nenhum agendamento hoje.</div>';
  ?>
  <ul class="divide-y mt-2">
    <?php foreach($rows as $r): ?>
      <li class="py-2 flex justify-between items-center">
        <div>
          <div class="font-medium">
              <span class="text-blue-800 font-bold">
                <?= date('H:i', strtotime($r['date_time'])) ?> - 
                <?= date('H:i', strtotime($r['date_time']) + ($r['duration']??30)*60) ?>
              </span>
              <span class="mx-1 text-gray-400">|</span>
              <?= htmlspecialchars($r['client_name']) ?>
          </div>
          <div class="text-sm text-gray-500"><?= htmlspecialchars($r['notes']) ?></div>
        </div>
        <div>
          <?php if($r['status']=='Confirmado'): ?><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded">Confirmado</span><?php elseif($r['status']=='Atendido'): ?><span class="px-2 py-1 bg-green-100 text-green-700 rounded">Atendido</span><?php else: ?><span class="px-2 py-1 bg-red-100 text-red-700 rounded">Cancelado</span><?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
