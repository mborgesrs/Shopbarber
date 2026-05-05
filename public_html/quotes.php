<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$professional_id = $_GET['professional_id'] ?? '';
$client_id = $_GET['client_id'] ?? '';
$company_id = $_SESSION['company_id'];

$sql = 'SELECT q.*, c.name as client_name, p.name as prof_name 
        FROM quotes q 
        JOIN clients c ON c.id=q.client_id 
        LEFT JOIN professionals p ON p.id=q.professional_id 
        WHERE q.company_id=?';
$params = [$company_id];

if($from) { $sql .= " AND DATE(q.date_time) >= ?"; $params[] = $from; }
if($to) { $sql .= " AND DATE(q.date_time) <= ?"; $params[] = $to; }
if($status) { $sql .= " AND q.status = ?"; $params[] = $status; }
if($professional_id) { $sql .= " AND q.professional_id = ?"; $params[] = $professional_id; }
if($client_id) { $sql .= " AND q.client_id = ?"; $params[] = $client_id; }

$sql .= ' ORDER BY q.date_time';
$stmt=$pdo->prepare($sql);
$stmt->execute($params); 
$list=$stmt->fetchAll();

// Stats for the filtered selection
$totalCount = count($list);
$attendedCount = 0;
$canceledCount = 0;
$confirmedCount = 0;
foreach($list as $q) {
    if($q['status'] == 'Atendido') $attendedCount++;
    elseif($q['status'] == 'Cancelado') $canceledCount++;
    elseif($q['status'] == 'Confirmado') $confirmedCount++;
}

// Fetch clients and professionals for filters
$clients = $pdo->prepare("SELECT id, name FROM clients WHERE company_id=? ORDER BY name");
$clients->execute([$company_id]);
$clients = $clients->fetchAll();

$professionals = $pdo->prepare("SELECT id, name FROM professionals WHERE company_id=? ORDER BY name");
$professionals->execute([$company_id]);
$professionals = $professionals->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-8">
  <div>
    <h2 class="text-2xl font-bold text-gray-800">Agendamentos</h2>
    <p class="text-sm text-gray-500">
        <?php if($from == $to): ?>
            Listagem do dia <?= date('d/m/Y', strtotime($from)) ?>.
        <?php else: ?>
            Período de <?= date('d/m/Y', strtotime($from)) ?> até <?= date('d/m/Y', strtotime($to)) ?>.
        <?php endif; ?>
    </p>
  </div>
  <a href="quote_create.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl flex items-center gap-2 transition-colors shadow-lg shadow-indigo-100 font-bold text-sm">
    <i class="fas fa-plus"></i> Novo Agendamento
  </a>
</div>

<!-- Stats for the filtered selection -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total</p>
        <h3 class="text-xl font-bold text-gray-900"><?= $totalCount ?></h3>
    </div>
    <div class="bg-emerald-50 p-4 rounded-2xl shadow-sm border border-emerald-100">
        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1">Atendidos</p>
        <h3 class="text-xl font-bold text-emerald-700"><?= $attendedCount ?></h3>
    </div>
    <div class="bg-blue-50 p-4 rounded-2xl shadow-sm border border-blue-100">
        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Confirmados</p>
        <h3 class="text-xl font-bold text-blue-700"><?= $confirmedCount ?></h3>
    </div>
    <div class="bg-rose-50 p-4 rounded-2xl shadow-sm border border-rose-100">
        <p class="text-[10px] font-bold text-rose-600 uppercase tracking-widest mb-1">Cancelados</p>
        <h3 class="text-xl font-bold text-rose-700"><?= $canceledCount ?></h3>
    </div>
</div>

<div class="mb-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
  <form method="get" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
    <div class="md:col-span-2 space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">De</label>
        <input type="date" name="from" value="<?=htmlspecialchars($from)?>" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
    </div>
    <div class="md:col-span-2 space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">Até</label>
        <input type="date" name="to" value="<?=htmlspecialchars($to)?>" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
    </div>
    <div class="md:col-span-2 space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">Status</label>
        <select name="status" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all bg-white">
            <option value="">Status...</option>
            <option value="Confirmado" <?= $status=='Confirmado'?'selected':'' ?>>Confirmado</option>
            <option value="Atendido" <?= $status=='Atendido'?'selected':'' ?>>Atendido</option>
            <option value="Cancelado" <?= $status=='Cancelado'?'selected':'' ?>>Cancelado</option>
        </select>
    </div>
    <div class="md:col-span-2 space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">Profissional</label>
        <select name="professional_id" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all bg-white">
            <option value="">Todos...</option>
            <?php foreach($professionals as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $professional_id==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="md:col-span-2 space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">Cliente</label>
        <select name="client_id" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all bg-white">
            <option value="">Todos...</option>
            <?php foreach($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $client_id==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="md:col-span-2 flex gap-1.5">
        <button type="submit" class="flex-1 px-3 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-100">
            Filtrar
        </button>
        <?php if($from !== date('Y-m-d') || $to !== date('Y-m-d') || $status || $professional_id || $client_id): ?>
            <a href="quotes.php" class="px-3 py-2 bg-gray-50 text-gray-400 rounded-xl text-xs hover:bg-rose-50 hover:text-rose-600 transition-colors flex items-center justify-center border border-gray-100" title="Limpar">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
    </div>
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
                    <th class="px-6 py-3 text-left">Data/Hora</th>
                    <th class="px-6 py-3 text-left">Pessoa</th>
                    <th class="px-6 py-3 text-left">Serviços</th>
                    <th class="px-6 py-3 text-left">Profissional</th>
                    <th class="px-6 py-3 text-left">Valor</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-right">Ações</th>
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
                        <td class="px-6 py-2">
                            <div class="flex flex-col">
                                <span class="text-gray-900 font-bold text-xs"><?=date('d/m/Y', strtotime($q['date_time']))?></span>
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-lg text-[10px] font-bold shadow-sm shadow-indigo-100 w-fit mt-1">
                                    <i class="far fa-clock mr-1"></i>
                                    <?=date('H:i', strtotime($q['date_time']))?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-2">
                            <div class="flex flex-col">
                                <span class="text-gray-900 font-bold"><?=htmlspecialchars($q['client_name'])?></span>
                                <span class="text-xs text-gray-400"><?=htmlspecialchars($q['notes']) ?: 'Sem observações'?></span>
                            </div>
                        </td>
                        <td class="px-6 py-2">
                            <span class="text-xs text-gray-600"><?= htmlspecialchars($servicesText) ?: '<span class="text-gray-300 italic">Nenhum</span>' ?></span>
                        </td>
                        <td class="px-6 py-2">
                            <span class="text-gray-600 font-medium"><?=htmlspecialchars($q['prof_name'] ?: 'Não definido')?></span>
                        </td>
                        <td class="px-6 py-2">
                           <span class="font-bold text-gray-700">R$ <?= number_format($q['total'], 2, ',', '.') ?></span>
                        </td>
                        <td class="px-6 py-2">
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
                        <td class="px-6 py-2 text-right">
                            <div class="flex justify-end gap-2 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                                <a title="Editar" href="quote_edit.php?id=<?=$q['id']?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                <a title="Ver / PDF" href="quote_view.php?id=<?=$q['id']?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-file-pdf text-xs"></i>
                                </a>
                                <a title="Excluir" href="quote_delete.php?id=<?=$q['id']?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm" onclick="confirmAction(event, this.href, 'Excluir?')">
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
