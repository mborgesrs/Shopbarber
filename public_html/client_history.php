<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$id = $_GET['id'] ?? null; if(!$id) header('Location: clients.php');
$stmt = $pdo->prepare('SELECT * FROM clients WHERE id=?'); $stmt->execute([$id]); $client=$stmt->fetch(); if(!$client) header('Location: clients.php');
$stmt = $pdo->prepare('SELECT * FROM quotes WHERE client_id=? ORDER BY date_time DESC'); $stmt->execute([$id]); $quotes = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-8">
  <div>
    <h2 class="text-2xl font-bold text-gray-800">Histórico do Cliente</h2>
    <p class="text-sm text-gray-500"><?= htmlspecialchars($client['name']) ?></p>
  </div>
  <a href="clients.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl flex items-center gap-2 transition-colors border border-gray-200 font-bold text-sm">
    <i class="fas fa-arrow-left"></i> Voltar
  </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between font-bold text-sm text-gray-700 uppercase tracking-wider">
    <span>Todos os Agendamentos</span>
    <i class="fas fa-history text-gray-400"></i>
  </div>
  
  <?php if(!$quotes): ?>
    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-calendar-times text-2xl"></i>
        </div>
        <p>Nenhum agendamento encontrado para este cliente.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase text-gray-400 tracking-widest font-bold">
                <tr>
                    <th class="px-6 py-4 text-left">Data / Hora</th>
                    <th class="px-6 py-4 text-left">Descrição / Notas</th>
                    <th class="px-6 py-4 text-left">Status</th>
                    <th class="px-6 py-4 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach($quotes as $q): ?>
                    <tr class="hover:bg-gray-50/80 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-gray-900 font-bold"><?=date('d/m/Y', strtotime($q['date_time']))?></span>
                                <span class="text-xs text-indigo-600 font-bold"><?=date('H:i', strtotime($q['date_time']))?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-700"><?=htmlspecialchars($q['notes']) ?: '<span class="text-gray-400 italic">Sem observações</span>'?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                            $status_class = 'bg-gray-100 text-gray-600';
                            if($q['status']=='Confirmado') $status_class = 'bg-blue-100 text-blue-700';
                            elseif($q['status']=='Atendido') $status_class = 'bg-emerald-100 text-emerald-700';
                            elseif($q['status']=='Cancelado') $status_class = 'bg-rose-100 text-rose-700';
                            ?>
                            <span class="px-3 py-1 <?= $status_class ?> text-[10px] font-bold rounded-full uppercase tracking-wider">
                                <?= $q['status'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a title="Ver Detalhes" href="quote_view.php?id=<?=$q['id']?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
