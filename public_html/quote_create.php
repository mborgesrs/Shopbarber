<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$pre_date = $_GET['date'] ?? '';
$default_datetime = $pre_date ? $pre_date . 'T08:00' : date('Y-m-d\TH:i');

if($_SERVER['REQUEST_METHOD']==='POST'){
  $client_id = $_POST['client_id'];
  $date_time = $_POST['date_time'];
  $notes = $_POST['notes'];
  $items = $_POST['items'] ?? [];
  $total = 0;
  
  // Decoding JSON items if sent via JS (which seems to be the case based on app.js)
  $items_array = json_decode($_POST['items'], true) ?: [];
  
  foreach($items_array as $it){ $total += ($it['price'] * $it['qty']); }
  
  // Fetch duration (default 30 if not specified by items)
  $duration = 30; // Could be summed from items if products had duration
  $company_id = $_SESSION['company_id'];
  
  $stmt=$pdo->prepare('INSERT INTO quotes (client_id,date_time,total,status,notes,duration,company_id) VALUES (?,?,?,?,?,?,?)');
  $stmt->execute([$client_id,$date_time,$total,'Confirmado',$notes, $duration, $company_id]);
  $quote_id = $pdo->lastInsertId();
  
  $stmtItem=$pdo->prepare('INSERT INTO quote_items (quote_id,product_id,quantity,price,total) VALUES (?,?,?,?,?)');
  foreach($items_array as $it){
    $t = $it['price'] * $it['qty'];
    $stmtItem->execute([$quote_id,$it['product_id'],$it['qty'],$it['price'],$t]);
  }
  
  $send = isset($_POST['send_whatsapp']);
  if($send){
    $c = $pdo->prepare('SELECT phone,name FROM clients WHERE id=? AND company_id=?'); $c->execute([$client_id, $company_id]); $client = $c->fetch();
    $phone = preg_replace('/\D/','',$client['phone']);
    $msg = rawurlencode("Olá {$client['name']}, seu agendamento está confirmado em {$date_time}. Obrigado!");
    if($phone){ header('Location: https://wa.me/'.$phone.'?text='.$msg); exit; }
  }
  
  header('Location: calendar.php'); exit;
}
$company_id = $_SESSION['company_id'];
$stmt = $pdo->prepare('SELECT id,name,phone FROM clients WHERE company_id=? ORDER BY name');
$stmt->execute([$company_id]);
$clients = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM products WHERE company_id=? ORDER BY name');
$stmt->execute([$company_id]);
$products = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>
<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="quotes.php" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Novo Agendamento</h2>
    </div>

    <form method="post" id="quoteForm" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Cliente</label>
              <select name="client_id" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" required>
                  <option value="">Selecione um cliente...</option>
                  <?php foreach($clients as $c): ?>
                      <option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?> (<?=$c['phone']?>)</option>
                  <?php endforeach; ?>
              </select>
          </div>
          <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Data e Hora</label>
              <input type="datetime-local" name="date_time" value="<?= $default_datetime ?>" class="w-full border border-gray-200 p-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" required>
          </div>
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
          <a href="quotes.php" class="px-6 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition-colors">Cancelar</a>
          <button class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all flex items-center gap-2">
              <i class="fas fa-check"></i>
              Gravar Agendamento
          </button>
      </div>
    </form>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
