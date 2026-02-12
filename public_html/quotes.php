<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$day = $_GET['day'] ?? date('Y-m-d');
$company_id = $_SESSION['company_id'];

$stmt=$pdo->prepare('SELECT q.*, c.name as client_name, p.name as prof_name 
             FROM quotes q 
             JOIN clients c ON c.id=q.client_id 
             LEFT JOIN professionals p ON p.id=q.professional_id 
             WHERE q.company_id=? AND DATE(q.date_time)=? 
             ORDER BY q.date_time');
$stmt->execute([$company_id, $day]); 
$list=$stmt->fetchAll();

// Stats for the day
$totalDay = count($list);
$attendedDay = 0;
$canceledDay = 0;
$confirmedDay = 0;
foreach($list as $q) {
    if($q['status'] == 'Atendido') $attendedDay++;
    elseif($q['status'] == 'Cancelado') $canceledDay++;
    elseif($q['status'] == 'Confirmado') $confirmedDay++;
}
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-8">
  <div>
    <h2 class="text-2xl font-bold text-gray-800">Agendamentos</h2>
    <p class="text-sm text-gray-500">Gerencie os horários do dia <?= date('d/m/Y', strtotime($day)) ?>.</p>
  </div>
  <a href="quote_create.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl flex items-center gap-2 transition-colors shadow-lg shadow-indigo-100 font-bold text-sm">
    <i class="fas fa-plus"></i> Novo Agendamento
  </a>
</div>

<!-- Stats for the selected day -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total</p>
        <h3 class="text-xl font-bold text-gray-900"><?= $totalDay ?></h3>
    </div>
    <div class="bg-emerald-50 p-4 rounded-2xl shadow-sm border border-emerald-100">
        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1">Atendidos</p>
        <h3 class="text-xl font-bold text-emerald-700"><?= $attendedDay ?></h3>
    </div>
    <div class="bg-blue-50 p-4 rounded-2xl shadow-sm border border-blue-100">
        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Confirmados</p>
        <h3 class="text-xl font-bold text-blue-700"><?= $confirmedDay ?></h3>
    </div>
    <div class="bg-rose-50 p-4 rounded-2xl shadow-sm border border-rose-100">
        <p class="text-[10px] font-bold text-rose-600 uppercase tracking-widest mb-1">Cancelados</p>
        <h3 class="text-xl font-bold text-rose-700"><?= $canceledDay ?></h3>
    </div>
</div>

<div class="mb-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
  <form method="get" class="flex items-end gap-4">
    <div class="space-y-1 flex-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Filtrar por data</label>
        <input type="date" name="day" value="<?=htmlspecialchars($day)?>" onchange="this.form.submit()" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
    </div>
    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 p-2.5 rounded-xl px-4 transition-colors font-bold text-sm">
        <i class="fas fa-search mr-2"></i> Filtrar
    </button>
  </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between font-bold text-sm text-gray-700 uppercase tracking-wider">
    <span>Lista de Horários</span>
    <i class="fas fa-clock text-gray-400"></i>
  </div>
  <?php if(!$list): ?>
    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
            <i class="far fa-calendar-times text-2xl"></i>
        </div>
        <p>Nenhum agendamento para este dia.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase text-gray-400 tracking-widest font-bold">
                <tr>
                    <th class="px-6 py-4 text-left">Hora</th>
                    <th class="px-6 py-4 text-left">Pessoa</th>
                    <th class="px-6 py-4 text-left">Serviços</th>
                    <th class="px-6 py-4 text-left">Profissional</th>
                    <th class="px-6 py-4 text-left">Valor</th>
                    <th class="px-6 py-4 text-left">Status</th>
                    <th class="px-6 py-4 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach($list as $q): ?>
                    <?php
                        // Fetch services for this quote
                        $itemStmt = $pdo->prepare('SELECT p.name FROM quote_items qi JOIN products p ON p.id=qi.product_id WHERE qi.quote_id=?');
                        $itemStmt->execute([$q['id']]);
                        $serviceNames = $itemStmt->fetchAll(PDO::FETCH_COLUMN);
                        $servicesText = implode(', ', $serviceNames);
                    ?>
                    <tr class="hover:bg-gray-50/80 transition-colors group">
                        <td class="px-6 py-4">
                            <span class="bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg text-sm font-bold shadow-sm shadow-indigo-100">
                                <i class="far fa-clock mr-1"></i>
                                <?=date('H:i', strtotime($q['date_time']))?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-gray-900 font-bold"><?=htmlspecialchars($q['client_name'])?></span>
                                <span class="text-xs text-gray-400"><?=htmlspecialchars($q['notes']) ?: 'Sem observações'?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs text-gray-600"><?= htmlspecialchars($servicesText) ?: '<span class="text-gray-300 italic">Nenhum</span>' ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-600 font-medium"><?=htmlspecialchars($q['prof_name'] ?: 'Não definido')?></span>
                        </td>
                        <td class="px-6 py-4">
                           <span class="font-bold text-gray-700">R$ <?= number_format($q['total'], 2, ',', '.') ?></span>
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
                                <a title="Editar" href="quote_edit.php?id=<?=$q['id']?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                <a title="Ver / PDF" href="quote_view.php?id=<?=$q['id']?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-file-pdf text-xs"></i>
                                </a>
                                <a title="Excluir" href="quote_delete.php?id=<?=$q['id']?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm" onclick="return confirm('Excluir?')">
                                    <i class="fas fa-trash text-xs"></i>
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
