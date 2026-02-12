<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$id = $_GET['id'] ?? null;
$from_calendar = ($_GET['from'] ?? ($_POST['from'] ?? '')) === 'calendar';
$month_cal = $_GET['month'] ?? ($_POST['month'] ?? '');
$year_cal = $_GET['year'] ?? ($_POST['year'] ?? '');
if(!$id){ header('Location: quotes.php'); exit; }

// Fetch the quote
$stmt = $pdo->prepare('SELECT * FROM quotes WHERE id = ? AND company_id = ?');
$stmt->execute([$id, $_SESSION['company_id']]);
$quote = $stmt->fetch();
if(!$quote){ header('Location: quotes.php'); exit; }

$msg = '';
$error = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $client_id = $_POST['client_id'];
  $professional_id = $_POST['professional_id'] ?? null;
  $date_time = $_POST['date_time'];
  $notes = $_POST['notes'];
  $action = $_POST['action'] ?? 'save';
  
  $items = json_decode($_POST['items'] ?? '[]', true);
  if(!is_array($items)) $items = [];
  
  $total = 0;
  $durationCtx = 0;
  $itemNames = [];

  // Calculate Total & Duration
  $productIds = array_column($items, 'product_id');
  if($productIds){
      $in  = str_repeat('?,', count($productIds) - 1) . '?';
      $sql = "SELECT id, name, duration FROM products WHERE id IN ($in)";
      $st = $pdo->prepare($sql);
      $st->execute($productIds);
      $prodMap = $st->fetchAll(PDO::FETCH_ASSOC); // id -> row
      $pDict = []; foreach($prodMap as $r) $pDict[$r['id']] = $r;

      foreach($items as $it){
          $pid = $it['product_id'];
          if(isset($pDict[$pid])){
            $d = $pDict[$pid]['duration'] ?? 30;
            $durationCtx += ($d * $it['qty']);
            $total += ($it['price'] * $it['qty']);
            $itemNames[] = $pDict[$pid]['name'] . ($it['qty']>1 ? " (x{$it['qty']})" : "");
          }
      }
  }
  if($durationCtx === 0) $durationCtx = 30;

  // Conflict Check (if changing time or professional)
  $startObj = new DateTime($date_time);
  $endObj = clone $startObj; $endObj->modify("+$durationCtx minutes");
  $startStr = $startObj->format('Y-m-d H:i:s');
  $endStr = $endObj->format('Y-m-d H:i:s');

  $conflictError = false;
  // Only check conflict if not cancelling
  if($action !== 'cancel' && $professional_id){
      $sqlConflict = "SELECT count(*) FROM quotes WHERE id != ? AND status != 'Cancelado' AND professional_id = ? AND (
          (date_time < ? AND end_time > ?)
      )";
      $chk = $pdo->prepare($sqlConflict);
      $chk->execute([$id, $professional_id, $endStr, $startStr]);
      if($chk->fetchColumn() > 0){
          $error = "Conflito de Horário! O profissional já possui agendamento neste intervalo.";
          $conflictError = true;
      }
  }

  if(!$conflictError){
      try {
          $pdo->beginTransaction();
          
          $status = $quote['status'];
          if($action === 'complete') $status = 'Concluido'; // or Atendido
          
          // Update Quote
          $sqlUpd = 'UPDATE quotes SET client_id=?, professional_id=?, date_time=?, end_time=?, total=?, notes=?, duration=?, status=? WHERE id=?';
          $stmt=$pdo->prepare($sqlUpd);
          $stmt->execute([$client_id, $professional_id, $date_time, $endStr, $total, $notes, $durationCtx, $status, $id]);
          
          // Update Items
          $pdo->prepare('DELETE FROM quote_items WHERE quote_id=?')->execute([$id]);
          $stmtItem=$pdo->prepare('INSERT INTO quote_items (quote_id,product_id,quantity,price,total,duration) VALUES (?,?,?,?,?,?)');
          foreach($items as $it){
            $pid = $it['product_id'];
            $price = $it['price'];
            $qty = $it['qty'];
            $lineTotal = $price * $qty;
            $lineDur = $pDict[$pid]['duration'] ?? 30;
            $stmtItem->execute([$id, $pid, $qty, $price, $lineTotal, $lineDur]);
          }

          // Generate Finance if completing
          if($action === 'complete'){
              $obs = "Ref. Agendamento #$id - Serviços: " . implode(", ", $itemNames);
              // Check if already exists to avoid duplication? (Ideally)
              // For now, simpler to just insert.
              $sqlFin = "INSERT INTO finance (date, client_id, observation, value, type, status, created_at, company_id) VALUES (?, ?, ?, ?, 'Entrada', 'Pago', NOW(), ?)";
              $finStmt = $pdo->prepare($sqlFin);
              $finStmt->execute([date('Y-m-d'), $client_id, $obs, $total, $_SESSION['company_id']]);
          }

          $pdo->commit();
          
          if($action === 'complete'){
             $redirect = $from_calendar ? 'calendar.php' : 'quotes.php?msg=completed';
             if ($from_calendar && $month_cal && $year_cal) {
                 $redirect .= (strpos($redirect, '?') === false ? '?' : '&') . "month=$month_cal&year=$year_cal";
             }
             header("Location: $redirect"); exit;
          } else {
             $msg = "Agendamento atualizado com sucesso!";
             // Refresh quote data
             $stmt = $pdo->prepare('SELECT * FROM quotes WHERE id = ? AND company_id = ?');
             $stmt->execute([$id, $_SESSION['company_id']]);
             $quote = $stmt->fetch();
          }

      } catch (Exception $e) {
          $pdo->rollBack();
          $error = "Erro ao salvar: " . $e->getMessage();
      }
  }
}

// Load Data for View
$companyId = $_SESSION['company_id'];
$clients = $pdo->prepare('SELECT id,name,phone FROM clients WHERE company_id = ? ORDER BY name'); $clients->execute([$companyId]); $clients = $clients->fetchAll();
$professionals = $pdo->prepare('SELECT * FROM professionals WHERE active=1 AND company_id = ? ORDER BY name'); $professionals->execute([$companyId]); $professionals = $professionals->fetchAll();
$products = $pdo->prepare('SELECT * FROM products WHERE company_id = ? ORDER BY name'); $products->execute([$companyId]); $products = $products->fetchAll();
$userSettings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();

// Prepare Items for JS
// If POST failed, use POSTed items, else DB items
if($_SERVER['REQUEST_METHOD'] === 'POST' && $error){
    // Use $items from POST logic above, already prepared somewhat, but need to ensure it has 'name' for JS
    // (Re-using logic from fetching product names)
    $jsItems = [];
    foreach($items as $it){
        $pid = $it['product_id'];
        // Need name from products list
        $pName = 'Produto'; 
        foreach($products as $p){ if($p['id']==$pid) $pName=$p['name']; }
        $jsItems[] = [
            'product_id' => $pid,
            'name' => $pName,
            'qty' => $it['qty'],
            'price' => $it['price'],
            'duration' => 30 // Approximate if we don't look up again
        ];
    }
} else {
    $existingItems = $pdo->prepare('SELECT p.id as product_id, p.name, qi.quantity as qty, qi.price, qi.duration FROM quote_items qi JOIN products p ON p.id=qi.product_id WHERE qi.quote_id=?');
    $existingItems->execute([$id]);
    $jsItems = $existingItems->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php include __DIR__ . '/../views/header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
/* Hide scrollbars for the whole page and forms */
main::-webkit-scrollbar { display: none !important; }
main { scrollbar-width: none !important; -ms-overflow-style: none !important; }
.scrollbar-hide::-webkit-scrollbar { display: none !important; }
.scrollbar-hide { scrollbar-width: none !important; -ms-overflow-style: none !important; }
</style>

<div class="w-full py-8">
    <div class="flex items-center gap-4 mb-6">
        <?php 
        $backUrl = ($from_calendar ? 'calendar.php' : 'quotes.php');
        if ($from_calendar && $month_cal && $year_cal) $backUrl .= (strpos($backUrl, '?') === false ? '?' : '&') . "month=$month_cal&year=$year_cal";
        ?>
        <a href="<?= $backUrl ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Editar Agendamento</h2>
    </div>
        <?php if($quote['status'] == 'Concluido'): ?>
            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold text-sm">Concluído</span>
        <?php endif; ?>
    </div>

    <?php if($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <p class="font-bold">Erro</p>
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>
    <?php if($msg): ?>
        <div id="successMsg" class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Sucesso</p>
            <p><?= htmlspecialchars($msg) ?></p>
        </div>
        <script>setTimeout(()=>document.getElementById('successMsg').style.display='none', 3000);</script>
    <?php endif; ?>

    <form method="post" id="quoteForm" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
        <input type="hidden" name="from" value="<?= $from_calendar ? 'calendar' : '' ?>">
        <input type="hidden" name="month" value="<?= htmlspecialchars($month_cal) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year_cal) ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Pessoa</label>
                <select name="client_id" id="client_id" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <?php foreach($clients as $c): ?>
                        <option value="<?=$c['id']?>" <?= $c['id'] == $quote['client_id'] ? 'selected' : '' ?>>
                            <?=htmlspecialchars($c['name'])?> (<?=htmlspecialchars($c['phone'])?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Profissional</label>
                <select name="professional_id" id="professional_id" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required onchange="checkAvailability()">
                    <option value="">Selecione...</option>
                    <?php foreach($professionals as $p): ?>
                        <option value="<?=$p['id']?>" <?= $p['id'] == $quote['professional_id'] ? 'selected' : '' ?>>
                            <?=htmlspecialchars($p['name'])?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Data</label>
                    <input type="date" id="date_select" value="<?= date('Y-m-d', strtotime($quote['date_time'])) ?>" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" required onchange="checkAvailability()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Horário</label>
                    <select id="time_select" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" required onchange="updateDateTime()">
                        <?php $currentTime = date('H:i', strtotime($quote['date_time'])); ?>
                        <option value="<?= $currentTime ?>"><?= $currentTime ?></option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="date_time" id="date_time_input" value="<?= date('Y-m-d\TH:i', strtotime($quote['date_time'])) ?>">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Observações</label>
                <textarea name="notes" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700 scrollbar-hide" rows="3"><?= htmlspecialchars($quote['notes']) ?></textarea>
            </div>
        </div>

        <div class="mb-8 p-4 bg-slate-50 rounded border border-slate-100">
            <div class="flex items-center justify-between mb-3">
                <div class="font-semibold text-slate-700">Serviços / Produtos</div>
                <div class="text-sm text-slate-500">Adicione os serviços realizados</div>
            </div>
            
            <table class="w-full mb-4 text-sm text-left text-slate-600" id="itemsTable">
                <thead class="text-xs text-slate-700 uppercase bg-slate-200">
                    <tr>
                        <th class="px-4 py-2 rounded-l">Serviço</th>
                        <th class="px-4 py-2">Qtd</th>
                        <th class="px-4 py-2">Preço</th>
                        <th class="px-4 py-2">Subtotal</th>
                        <th class="px-4 py-2 rounded-r"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <!-- Items injected by JS -->
                </tbody>
            </table>

            <div class="flex gap-2">
                <select id="productSelect" class="flex-1 border border-slate-300 rounded p-2 text-sm">
                    <option value="">-- Selecionar Serviço --</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?=$p['id']?>|<?=$p['price']?>|<?=$p['duration'] ?? 30?>">
                            <?=htmlspecialchars($p['name'])?> (<?=$p['duration']??30?> min) — R$ <?=number_format($p['price'],2,',','.')?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addItem" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">Adicionar</button>
            </div>
        </div>

        <div class="flex flex-col items-end mb-6 space-y-2">
            <div class="text-lg">Tempo Estimado: <span id="totalTimeDisplay" class="font-bold text-slate-800">0 min</span></div>
            <div class="text-xl">Total: <span id="totalDisplay" class="font-bold text-emerald-600">R$ 0,00</span></div>
        </div>
        
        <input type="hidden" name="items" id="itemsInput">
        <input type="hidden" name="action" id="formAction" value="save">

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
            <a href="<?= $backUrl ?>" class="bg-white border border-slate-300 text-slate-700 px-6 py-2.5 rounded hover:bg-slate-50 font-medium transition-colors">Voltar</a>
            
            <div class="flex gap-4">
                <?php if($quote['status'] != 'Concluido'): ?>
                    <button type="submit" onclick="document.getElementById('formAction').value='save'" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded font-medium shadow-sm transition-colors">Salvar Alterações</button>
                    
                    <button type="submit" onclick="if(confirm('Confirmar atendimento e gerar financeiro?')) { document.getElementById('formAction').value='complete'; return true; } else { return false; }" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded font-medium shadow-sm transition-colors flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Concluir Agendamento
                    </button>
                <?php else: ?>
                    <div class="text-sm text-green-600 font-medium flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Atendimento Finalizado
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
// Data from PHP
const currentQuoteId = <?= json_encode($id) ?>;
const initialDateTime = <?= json_encode($quote['date_time']) ?>;
const initialTime = new Date(initialDateTime).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});

async function checkAvailability() {
    const profId = document.getElementById('professional_id').value;
    const date = document.getElementById('date_select').value;
    const timeSelect = document.getElementById('time_select');
    
    if (!profId || !date) {
        timeSelect.innerHTML = '<option value="">Selecione...</option>';
        return;
    }
    
    const originalValue = timeSelect.value;
    timeSelect.innerHTML = '<option value="">Carregando...</option>';
    
    try {
        const response = await fetch(`api/check_availability.php?professional_id=${profId}&date=${date}&exclude_id=${currentQuoteId}`);
        const data = await response.json();
        
        if (data.error) {
            alert(data.error);
            return;
        }
        
        // Generate slots 08:00 to 20:00 every 30 mins
        const slots = [];
        for (let h = 8; h <= 20; h++) {
            for (let m = 0; m < 60; m += 30) {
                if (h === 20 && m > 0) break;
                slots.push(`${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`);
            }
        }
        
        const bookings = (data.bookings || []).map(b => {
            const start = new Date(b.date_time);
            const duration = parseInt(b.duration || 30);
            const end = new Date(start.getTime() + duration * 60000);
            return { start, end };
        });
        
        // Ensure the current time is also in the slots if not already
        if (date === initialDateTime.split(' ')[0] && !slots.includes(initialTime)) {
            slots.push(initialTime);
            slots.sort();
        }

        let html = '';
        slots.forEach(slot => {
            const slotTime = new Date(`${date}T${slot}:00`);
            const isBooked = bookings.some(b => slotTime >= b.start && slotTime < b.end);
            const isInitial = (date === initialDateTime.split(' ')[0] && slot === initialTime);
            
            const selected = (slot === originalValue || (originalValue === '' && isInitial)) ? 'selected' : '';

            if (!isBooked || isInitial) {
                html += `<option value="${slot}" ${selected}>${slot}</option>`;
            } else {
                html += `<option value="${slot}" disabled class="bg-gray-100 text-gray-400">${slot} (Ocupado)</option>`;
            }
        });
        
        timeSelect.innerHTML = html;
        updateDateTime();
    } catch (e) {
        console.error(e);
        timeSelect.innerHTML = '<option value="">Erro ao carregar</option>';
    }
}

function updateDateTime() {
    const date = document.getElementById('date_select').value;
    const time = document.getElementById('time_select').value;
    const input = document.getElementById('date_time_input');
    if (date && time) {
        input.value = `${date}T${time}`;
    }
}

// Initial check
document.addEventListener('DOMContentLoaded', () => {
    checkAvailability();
});

const itemsData = <?= json_encode($jsItems) ?>;
const itemsInput = document.getElementById('itemsInput');
const itemsTable = document.getElementById('itemsTable').querySelector('tbody');
const totalDisplay = document.getElementById('totalDisplay');
const totalTimeDisplay = document.getElementById('totalTimeDisplay');
let currentItems = itemsData || [];

function formatMoney(amount) {
    return 'R$ ' + amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}

function renderItems() {
    itemsTable.innerHTML = '';
    let total = 0;
    let time = 0;
    
    currentItems.forEach((item, index) => {
        const subtotal = item.qty * item.price;
        total += subtotal;
        time += (item.qty * (item.duration || 30));
        
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 transition-colors';
        tr.innerHTML = `
            <td class="px-4 py-3 font-medium text-slate-800">${item.name}</td>
            <td class="px-4 py-3">
                <input type="number" min="1" value="${item.qty}" onchange="updateQty(${index}, this.value)" class="w-16 border rounded p-1 text-center">
            </td>
            <td class="px-4 py-3">${formatMoney(parseFloat(item.price))}</td>
            <td class="px-4 py-3 font-medium text-slate-700">${formatMoney(subtotal)}</td>
            <td class="px-4 py-3 text-right">
                <button type="button" onclick="removeItem(${index})" class="text-red-500 hover:text-red-700 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </td>
        `;
        itemsTable.appendChild(tr);
    });
    
    totalDisplay.textContent = formatMoney(total);
    totalTimeDisplay.textContent = time + ' min';
    itemsInput.value = JSON.stringify(currentItems);
}

window.updateQty = function(index, qty) {
    if(qty < 1) qty = 1;
    currentItems[index].qty = parseInt(qty);
    renderItems();
};

window.removeItem = function(index) {
    currentItems.splice(index, 1);
    renderItems();
};

document.getElementById('addItem').addEventListener('click', function() {
    const sel = document.getElementById('productSelect');
    if(!sel.value) return;
    
    const [id, price, duration] = sel.value.split('|');
    const text = sel.options[sel.selectedIndex].text.split('—')[0].trim();
    
    // Check if exists
    const exists = currentItems.find(i => i.product_id == id);
    if(exists) {
        exists.qty++;
    } else {
        currentItems.push({
            product_id: id,
            name: text,
            qty: 1,
            price: parseFloat(price),
            duration: parseInt(duration)
        });
    }
    renderItems();
    sel.value = '';
});

// Init
renderItems();

// TomSelects
new TomSelect('#client_id',{create: false, sortField: {field: "text", direction: "asc"}});
new TomSelect('#professional_id',{create: false, sortField: {field: "text", direction: "asc"}});

</script>
<?php include __DIR__ . '/../views/footer.php'; ?>
