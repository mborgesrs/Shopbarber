<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$pre_date = $_GET['date'] ?? '';
$from_calendar = ($_GET['from'] ?? ($_POST['from'] ?? '')) === 'calendar';
$month_cal = $_GET['month'] ?? ($_POST['month'] ?? '');
$year_cal = $_GET['year'] ?? ($_POST['year'] ?? '');
$default_datetime = $pre_date ? $pre_date . 'T08:00' : date('Y-m-d\TH:i');

if($_SERVER['REQUEST_METHOD']==='POST'){
  $client_id = $_POST['client_id'];
  $professional_id = $_POST['professional_id'] ?: null;
  $date_time = $_POST['date_time'];
  $notes = $_POST['notes'];
  $items = $_POST['items'] ?? [];
  $total = 0;
  
  // Decoding JSON items if sent via JS (which seems to be the case based on app.js)
  $items_array = json_decode($_POST['items'], true) ?: [];
  
  foreach($items_array as $it){ $total += ($it['price'] * $it['qty']); }
  
  // Calculate total duration
  $duration = 0;
  $productIds = array_column($items_array, 'product_id');
  if($productIds){
      $in  = str_repeat('?,', count($productIds) - 1) . '?';
      $st = $pdo->prepare("SELECT id, duration FROM products WHERE id IN ($in)");
      $st->execute($productIds);
      $prodMap = $st->fetchAll(PDO::FETCH_KEY_PAIR);
      foreach($items_array as $it){ $duration += (($prodMap[$it['product_id']] ?? 30) * $it['qty']); }
  }
  if($duration === 0) $duration = 30;

  $company_id = $_SESSION['company_id'];
  $startObj = new DateTime($date_time);
  $endObj = clone $startObj; $endObj->modify("+$duration minutes");
  $startStr = $startObj->format('Y-m-d H:i:s');
  $endStr = $endObj->format('Y-m-d H:i:s');

  // Conflict Check
  if($professional_id){
      $sqlConflict = "SELECT count(*) FROM quotes WHERE status != 'Cancelado' AND professional_id = ? AND (
          (date_time < ? AND end_time > ?)
      )";
      $chk = $pdo->prepare($sqlConflict);
      $chk->execute([$professional_id, $endStr, $startStr]);
      if($chk->fetchColumn() > 0){
          echo "<script>alert('Conflito de Horário! O profissional já possui agendamento neste intervalo.'); window.history.back();</script>";
          exit;
      }
  }
  
  $stmt=$pdo->prepare('INSERT INTO quotes (client_id,professional_id,date_time,end_time,total,status,notes,duration,company_id) VALUES (?,?,?,?,?,?,?,?,?)');
  $stmt->execute([$client_id,$professional_id,$date_time,$endStr,$total,'Confirmado',$notes, $duration, $company_id]);
  $id = $pdo->lastInsertId();
  
  foreach($items_array as $it){
    $st=$pdo->prepare('INSERT INTO quote_items (quote_id,product_id,quantity,price,total,duration) VALUES (?,?,?,?,?,?)');
    $d = $prodMap[$it['product_id']] ?? 30;
    $st->execute([$id,$it['product_id'],$it['qty'],$it['price'],($it['price']*$it['qty']),$d]);
  }
  
  $send = isset($_POST['send_whatsapp']);
  if($send){
    $c = $pdo->prepare('SELECT phone,name FROM clients WHERE id=? AND company_id=?'); $c->execute([$client_id, $company_id]); $client = $c->fetch();
    $phone = preg_replace('/\D/','',$client['phone']);
    $msg = rawurlencode("Olá {$client['name']}, seu agendamento está confirmado em " . date('d/m/Y H:i', strtotime($date_time)) . ". Obrigado!");
    if($phone){ header('Location: https://wa.me/'.$phone.'?text='.$msg); exit; }
  }
  
  $redirect = ($_POST['from'] ?? '') === 'calendar' ? 'calendar.php' : 'quotes.php';
  if ($redirect === 'calendar.php' && $month_cal && $year_cal) {
      $redirect .= "?month=$month_cal&year=$year_cal";
  }
  header("Location: $redirect"); 
  exit;
}
$company_id = $_SESSION['company_id'];
$stmt = $pdo->prepare('SELECT id,name,phone FROM clients WHERE company_id=? ORDER BY name');
$stmt->execute([$company_id]);
$clients = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id,name FROM professionals WHERE company_id=? ORDER BY name');
$stmt->execute([$company_id]);
$professionals = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM products WHERE company_id=? ORDER BY name');
$stmt->execute([$company_id]);
$products = $stmt->fetchAll();
?>
<script>
async function checkAvailability() {
    const profId = document.getElementById('professional_id').value;
    const date = document.getElementById('date_select').value;
    const timeSelect = document.getElementById('time_select');
    
    if (!profId || !date) {
        timeSelect.innerHTML = '<option value="">Selecione data/prof...</option>';
        return;
    }
    
    timeSelect.innerHTML = '<option value="">Carregando...</option>';
    
    try {
        const response = await fetch(`api/check_availability.php?professional_id=${profId}&date=${date}`);
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
        
        let html = '<option value="">-- Selecione um horário --</option>';
        slots.forEach(slot => {
            const slotTime = new Date(`${date}T${slot}:00`);
            const isBooked = bookings.some(b => slotTime >= b.start && slotTime < b.end);
            
            if (!isBooked) {
                html += `<option value="${slot}">${slot}</option>`;
            } else {
                html += `<option value="${slot}" disabled class="bg-gray-100 text-gray-400">${slot} (Ocupado)</option>`;
            }
        });
        
        timeSelect.innerHTML = html;
        updateDateTime(); // Initial sync
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

// Automatically check if professional is already selected (e.g. from session or default)
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('professional_id').value) {
        checkAvailability();
    }
});
</script>

<?php include __DIR__ . '/../views/header.php'; ?>
<div class="w-full">
    <div class="flex items-center gap-4 mb-6">
        <?php 
        $backUrl = ($from_calendar ? 'calendar.php' : 'quotes.php');
        if ($from_calendar && $month_cal && $year_cal) $backUrl .= "?month=$month_cal&year=$year_cal";
        ?>
        <a href="<?= $backUrl ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
          <i class="fas fa-arrow-left text-sm"></i>
      </a>
        <h2 class="text-2xl font-bold text-gray-800">Novo Agendamento</h2>
    </div>

    <form method="post" id="quoteForm" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
      <input type="hidden" name="from" value="<?= $from_calendar ? 'calendar' : '' ?>">
      <input type="hidden" name="month" value="<?= htmlspecialchars($month_cal) ?>">
      <input type="hidden" name="year" value="<?= htmlspecialchars($year_cal) ?>">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Pessoa</label>
              <select name="client_id" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" required>
                  <option value="">Selecione uma pessoa...</option>
                  <?php foreach($clients as $c): ?>
                      <option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?> (<?=$c['phone']?>)</option>
                  <?php endforeach; ?>
              </select>
          </div>
          <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Profissional</label>
              <select name="professional_id" id="professional_id" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" required onchange="checkAvailability()">
                  <option value="">Selecione um profissional...</option>
                  <?php foreach($professionals as $p): ?>
                      <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
                  <?php endforeach; ?>
              </select>
          </div>
          <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Data</label>
              <input type="date" id="date_select" value="<?= $pre_date ?: date('Y-m-d') ?>" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" required onchange="checkAvailability()">
          </div>
          <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Horários Disponíveis</label>
              <select id="time_select" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" required onchange="updateDateTime()">
                  <option value="">Selecione data/prof...</option>
              </select>
          </div>
          <input type="hidden" name="date_time" id="date_time_input" value="<?= $default_datetime ?>">
      </div>

      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-shopping-basket text-purple-500 text-sm"></i>
                Serviços / Produtos
            </h3>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-xl mb-4">
            <div class="flex gap-2">
                <select id="productSelect" class="flex-1 border border-gray-200 p-2 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">-- Selecionar produto/serviço --</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?=$p['id']?>|<?=$p['price']?>"><?=htmlspecialchars($p['name'])?> — R$ <?=number_format($p['price'],2,',','.')?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addItem" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Add
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="itemsTable">
                <thead class="text-gray-400 border-b border-gray-100">
                    <tr>
                        <th class="text-left py-2 font-medium">Item</th>
                        <th class="text-left py-2 font-medium">Qtd</th>
                        <th class="text-left py-2 font-medium">Preço</th>
                        <th class="text-left py-2 font-medium">Subtotal</th>
                        <th class="text-right py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <!-- Dinamicamente via app.js -->
                </tbody>
            </table>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
          <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Observações</label>
              <textarea name="notes" rows="3" class="w-full border border-gray-200 p-3 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none placeholder-gray-300" placeholder="Algum detalhe importante?"></textarea>
          </div>
          <div class="bg-blue-50/50 p-6 rounded-2xl border border-blue-100/50">
              <div class="flex justify-between items-center mb-4">
                  <span class="text-gray-600 font-medium">Total do Geral:</span>
                  <span id="totalDisplay" class="text-2xl font-bold text-blue-600 tracking-tight">R$ 0,00</span>
              </div>
              <label class="flex items-center gap-2 cursor-pointer group">
                  <input type="checkbox" name="send_whatsapp" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-600 group-hover:text-blue-600 transition-colors">Enviar confirmação via WhatsApp</span>
              </label>
          </div>
      </div>

      <input type="hidden" name="items" id="itemsInput">
      
      <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end gap-3">
          <a href="<?= $backUrl ?>" class="px-6 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition-colors">Cancelar</a>
          <button class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all flex items-center gap-2">
              <i class="fas fa-check"></i>
              Gravar Agendamento
          </button>
      </div>
    </form>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
