<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$id = $_GET['id'] ?? null; if(!$id) header('Location: quotes.php');
$stmt=$pdo->prepare('SELECT q.*, c.name as person_name, c.phone as person_phone, p.name as prof_name FROM quotes q JOIN clients c ON c.id=q.client_id LEFT JOIN professionals p ON p.id=q.professional_id WHERE q.id=?'); $stmt->execute([$id]); $q=$stmt->fetch(); if(!$q) header('Location: quotes.php');
$items = $pdo->prepare('SELECT qi.*, p.name as product_name FROM quote_items qi JOIN products p ON p.id=qi.product_id WHERE qi.quote_id=?'); $items->execute([$id]); $items = $items->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>
<div class="bg-white p-6 rounded shadow max-w-3xl mx-auto">
  <div class="flex justify-between items-center mb-4">
    <div>
      <h2 class="text-2xl font-bold">Agendamento #<?=$q['id']?></h2>
      <div class="text-sm text-gray-500"><?=htmlspecialchars($q['person_name'])?> — <?=htmlspecialchars($q['person_phone'])?></div>
      <?php if($q['prof_name']): ?>
        <div class="text-sm text-indigo-600 font-bold mt-1">Profissional: <?=htmlspecialchars($q['prof_name'])?></div>
      <?php endif; ?>
    </div>
    <div>
      <button onclick="window.print()" class="bg-gray-800 text-white px-3 py-1 rounded">Gerar PDF</button>
    </div>
  </div>
  <div class="mb-4">Data/Hora: <strong><?=date('d/m/Y H:i', strtotime($q['date_time']))?></strong></div>
  <table class="w-full mb-4">
    <thead><tr><th>Item</th><th>Qtd</th><th>Preço</th><th>Total</th></tr></thead>
    <tbody>
      <?php foreach($items as $it): ?>
      <tr class="border-t"><td class="p-2"><?=htmlspecialchars($it['product_name'])?></td><td class="p-2"><?=htmlspecialchars($it['quantity'])?></td><td class="p-2">R$ <?=number_format($it['price'],2,',','.')?></td><td class="p-2">R$ <?=number_format($it['total'],2,',','.')?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="text-right font-bold mb-4">Total: R$ <?=number_format($q['total'],2,',','.')?></div>
  <div class="text-sm text-gray-500">Observações: <?=htmlspecialchars($q['notes'])?></div>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
