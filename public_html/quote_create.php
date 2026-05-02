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
  
  try {
      $pdo->beginTransaction();
      
      $stmt=$pdo->prepare('INSERT INTO quotes (client_id,professional_id,date_time,end_time,total,status,notes,duration,company_id) VALUES (?,?,?,?,?,?,?,?,?)');
      $stmt->execute([$client_id,$professional_id,$date_time,$endStr,$total,'Confirmado',$notes, $duration, $company_id]);
      $id = $pdo->lastInsertId();
      
      foreach($items_array as $it){
        $st=$pdo->prepare('INSERT INTO quote_items (quote_id,product_id,quantity,price,total,duration) VALUES (?,?,?,?,?,?)');
        $d = $prodMap[$it['product_id']] ?? 30;
        $st->execute([$id,$it['product_id'],$it['qty'],$it['price'],($it['price']*$it['qty']),$d]);
        
        // --- INVENTORY CONTROL ---
        // Fetch product type to see if we should deduct stock
        $pst = $pdo->prepare("SELECT type FROM products WHERE id = ?");
        $pst->execute([$it['product_id']]);
        $pType = $pst->fetchColumn();
        
        if ($pType === 'Ativo') {
            // 1. Deduct from balance
            $upd = $pdo->prepare("UPDATE products SET balance = balance - ? WHERE id = ?");
            $upd->execute([$it['qty'], $it['product_id']]);
            
            // 2. Record movement
            $mov = $pdo->prepare("INSERT INTO inventory_movements (company_id, product_id, date, supplier, quantity, price, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $mov->execute([$company_id, $it['product_id'], date('Y-m-d'), 'Atendimento #' . $id, $it['qty'], $it['price'], 'Saída (Atendimento)']);
        }
      }
      
      $pdo->commit();
  } catch (Exception $e) {
      $pdo->rollBack();
      echo "<script>alert('Erro ao gravar agendamento: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
      exit;
  }
  
  $send = isset($_POST['send_sms']);
  if($send){
    // Documentação: Busca os dados de contato do cliente
    $c = $pdo->prepare('SELECT phone,name FROM clients WHERE id=? AND company_id=?'); 
    $c->execute([$client_id, $company_id]); 
    $client = $c->fetch();
    
    $phone = preg_replace('/\D/','',$client['phone']);
    $msg_text = "Olá {$client['name']}, seu agendamento está confirmado em " . date('d/m/Y H:i', strtotime($date_time)) . ". Obrigado!";
    
    if($phone){
        // Documentação: Busca as configurações da Zenvia API no banco de dados
        $setStmt = $pdo->prepare('SELECT zenvia_api_token, zenvia_sender_id, zenvia_active FROM settings WHERE company_id=?');
        $setStmt->execute([$company_id]);
        $apiSettings = $setStmt->fetch();

        // Documentação: Se o Zenvia API estiver ativo e o token configurado, dispara o SMS
        if (!empty($apiSettings['zenvia_active']) && !empty($apiSettings['zenvia_api_token'])) {
            require_once __DIR__ . '/../lib/ZenviaAPI.php';
            $zenvia = new ZenviaAPI($apiSettings['zenvia_api_token'], $apiSettings['zenvia_sender_id']);
            
            // Documentação: Executa o envio
            $zenvia->sendSms($phone, $msg_text);
        }
        // Diferente da versão antiga do WhatsApp, se a integração de SMS estiver desligada, 
        // o envio é apenas ignorado, sem fallback para a interface web.
    }
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
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<div class="w-full max-w-5xl mx-auto">
    <div class="flex items-center gap-3 mb-4">
        <?php 
        $backUrl = ($from_calendar ? 'calendar.php' : 'quotes.php');
        if ($from_calendar && $month_cal && $year_cal) $backUrl .= "?month=$month_cal&year=$year_cal";
        ?>
        <a href="<?= $backUrl ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
          <i class="fas fa-arrow-left text-xs"></i>
      </a>
        <h2 class="text-xl font-bold text-gray-800">Novo Agendamento</h2>
    </div>

    <form method="post" id="quoteForm" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
      <input type="hidden" name="from" value="<?= $from_calendar ? 'calendar' : '' ?>">
      <input type="hidden" name="month" value="<?= htmlspecialchars($month_cal) ?>">
      <input type="hidden" name="year" value="<?= htmlspecialchars($year_cal) ?>">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
          <div class="md:col-span-4">
              <label class="block text-xs font-bold text-gray-700 mb-1">Pessoa</label>
              <select name="client_id" id="client_id" class="w-full border border-gray-200 p-2 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none" required>
                  <option value="">Selecione uma pessoa...</option>
                  <?php foreach($clients as $c): ?>
                      <option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?> (<?=$c['phone']?>)</option>
                  <?php endforeach; ?>
              </select>
          </div>
          <div class="md:col-span-3">
              <label class="block text-xs font-bold text-gray-700 mb-1">Profissional</label>
              <select name="professional_id" id="professional_id" class="w-full border border-gray-200 p-2 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none" required onchange="checkAvailability()">
                  <option value="">Selecione um profissional...</option>
                  <?php foreach($professionals as $p): ?>
                      <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
                  <?php endforeach; ?>
              </select>
          </div>
          <div class="md:col-span-2">
              <label class="block text-xs font-bold text-gray-700 mb-1">Data</label>
              <input type="date" id="date_select" value="<?= $pre_date ?: date('Y-m-d') ?>" class="w-full border border-gray-200 p-2 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none" required onchange="checkAvailability()">
          </div>
          <div class="md:col-span-3">
              <label class="block text-xs font-bold text-gray-700 mb-1">Horários Disponíveis</label>
              <select id="time_select" class="w-full border border-gray-200 p-2 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none" required onchange="updateDateTime()">
                  <option value="">Selecione data/prof...</option>
              </select>
          </div>
          <input type="hidden" name="date_time" id="date_time_input" value="<?= $default_datetime ?>">
      </div>

      <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-shopping-basket text-purple-500 text-xs"></i>
                Serviços / Produtos
            </h3>
        </div>
        
        <div class="bg-gray-50/50 p-3 rounded-xl mb-3 border border-gray-100">
            <div class="flex gap-2">
                <select id="productSelect" class="flex-1 border border-gray-200 p-2 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">-- Selecionar produto/serviço --</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?=$p['id']?>|<?=$p['price']?>"><?=htmlspecialchars($p['name'])?> — R$ <?=number_format($p['price'],2,',','.')?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addItem" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm shadow-blue-100">
                    <i class="fas fa-plus"></i>
                    Add
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-[11px]" id="itemsTable">
                <thead class="text-gray-400 border-b border-gray-100">
                    <tr>
                        <th class="text-left py-1.5 font-medium">Item</th>
                        <th class="text-left py-1.5 font-medium">Qtd</th>
                        <th class="text-left py-1.5 font-medium">Preço</th>
                        <th class="text-left py-1.5 font-medium">Subtotal</th>
                        <th class="text-right py-1.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-gray-700">
                    <!-- Dinamicamente via app.js -->
                </tbody>
            </table>
        </div>
      </div>

      <div class="space-y-4">
          <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Observações</label>
              <textarea name="notes" rows="2" class="w-full border border-gray-200 p-2 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none placeholder-gray-300" placeholder="Algum detalhe importante?"></textarea>
          </div>
          <div class="bg-blue-50/30 p-4 rounded-xl border border-blue-100/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div class="flex items-center gap-2">
                  <span class="text-xs text-gray-600 font-medium">Total Geral:</span>
                  <span id="totalDisplay" class="text-xl font-bold text-blue-600 tracking-tight">R$ 0,00</span>
              </div>
              <label class="flex items-center gap-2 cursor-pointer group">
                  <input type="checkbox" name="send_sms" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                  <span class="text-[11px] text-gray-500 group-hover:text-blue-600 transition-colors">Enviar confirmação via SMS</span>
              </label>
          </div>
      </div>

      <input type="hidden" name="items" id="itemsInput">
      
      <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end gap-2">
          <a href="<?= $backUrl ?>" class="px-5 py-2 rounded-lg border border-gray-200 text-gray-600 font-bold text-xs hover:bg-gray-50 transition-colors">Cancelar</a>
          <button class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold text-xs hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all flex items-center gap-2">
              <i class="fas fa-check"></i>
              Gravar Agendamento
          </button>
      </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#client_id',{create: false});
    new TomSelect('#professional_id',{create: false});
    new TomSelect('#productSelect',{create: false});
});
</script>
<?php include __DIR__ . '/../views/footer.php'; ?>
