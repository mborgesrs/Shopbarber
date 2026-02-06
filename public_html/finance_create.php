<?php
session_start(); 
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $type = $_POST['type'];
  
  // Business rule: Auto-set status based on type
  if($type == 'Entrada' || $type == 'Saida' || $type == 'cRecebido' || $type == 'dPago') {
    $status = 'Liquidado';
  } else {
    // Pagar or Receber
    $status = 'Aberto';
  }
  
  try {
    $stmt=$pdo->prepare('INSERT INTO finance (date,client_id,observation,value,saldo,type,portador_id,conta_id,tipo_pagamento_id,status,data_vencimento,data_pagamento,company_id,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
      $_POST['date'], 
      $_POST['client_id']?:null, 
      $_POST['observation'], 
      $_POST['value'],
      $_POST['value'], // saldo begins equal to value
      $type,
      $_POST['portador_id']?:null,
      $_POST['conta_id']?:null,
      $_POST['tipo_pagamento_id']?:null,
      $status,
      $_POST['data_vencimento']?:null,
      $_POST['data_pagamento']?:null,
      $_SESSION['company_id']
    ]);
    
    header('Location: finance.php');
    exit;
  } catch(PDOException $e) {
    $error = 'Erro ao criar registro: ' . $e->getMessage();
  }
}

// Get all reference data
$companyId = $_SESSION['company_id'];
$clients = $pdo->prepare('SELECT id,name,email,phone,company FROM clients WHERE company_id = ? ORDER BY name');
$clients->execute([$companyId]);
$clients = $clients->fetchAll();
$portadores = $pdo->query('SELECT id,nome FROM portadores ORDER BY nome')->fetchAll();
$contas = $pdo->query('SELECT id,codigo,descricao FROM contas WHERE ativo=1 ORDER BY codigo')->fetchAll();
$tipos_pagamento = $pdo->query('SELECT id,descricao FROM tipos_pagamento WHERE ativo=1 ORDER BY descricao')->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<style>
  .ts-control {
    border-color: #cbd5e1 !important; /* slate-300 */
    padding: 0.5rem !important;
    border-radius: 0.25rem !important;
  }
  .ts-wrapper.focus .ts-control {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5) !important; /* blue-500 ring */
    border-color: #3b82f6 !important;
  }
</style>

<div class="max-w-4xl mx-auto">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-slate-800">Novo Lançamento Financeiro</h2>
  </div>

  <?php if($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
      <p class="font-medium"><?= htmlspecialchars($error) ?></p>
    </div>
  <?php endif; ?>

  <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
    <form method="post" id="financeForm">
      <!-- Row 1: Datas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Data *</label>
          <input type="date" name="date" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Data Vencimento</label>
          <input type="date" name="data_vencimento" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Data Pagamento</label>
          <input type="date" name="data_pagamento" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
        </div>
      </div>

      <!-- Row 2: Tipo, Situação, Valor -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Tipo *</label>
          <select name="type" id="tipo" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" required onchange="updateSituacao()">
            <option value="">-- Selecione --</option>
            <option value="Pagar">Pagar (Despesa)</option>
            <option value="Receber">Receber (Receita)</option>
            <option value="Entrada">Entrada</option>
            <option value="Saida">Saída</option>
            <option value="cRecebido">Conta Recebida</option>
            <option value="dPago">Despesa Paga</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Situação</label>
          <input type="text" id="situacao" readonly class="w-full border border-slate-300 rounded p-2 bg-slate-100 text-slate-600 cursor-not-allowed" value="Aberto" placeholder="Automático">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Valor *</label>
          <input name="value" type="number" step="0.01" placeholder="0.00" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" required>
        </div>
      </div>

      <!-- Row 3: Cliente -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">Cliente / Fornecedor</label>
        <select name="client_id" id="client_id" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700" placeholder="Pesquisar por nome, email, telefone ou empresa...">
          <option value="">-- Selecione --</option>
          <?php foreach($clients as $c): ?>
            <option value="<?=$c['id']?>">
              <?=htmlspecialchars($c['name'])?> 
              <?= !empty($c['email']) ? ' - ' . htmlspecialchars($c['email']) : '' ?>
              <?= !empty($c['phone']) ? ' - ' . htmlspecialchars($c['phone']) : '' ?>
              <?= !empty($c['company']) ? ' (' . htmlspecialchars($c['company']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Row 4: Portador, Conta, Tipo Pagamento -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Portador</label>
          <select name="portador_id" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
            <option value="">-- Selecione --</option>
            <?php foreach($portadores as $p): ?>
              <option value="<?=$p['id']?>"><?=htmlspecialchars($p['nome'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Conta Contábil</label>
          <select name="conta_id" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
            <option value="">-- Selecione --</option>
            <?php foreach($contas as $c): ?>
              <option value="<?=$c['id']?>">
                <?=htmlspecialchars($c['codigo'])?> - <?=htmlspecialchars($c['descricao'])?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Pagamento</label>
          <select name="tipo_pagamento_id" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700">
            <option value="">-- Selecione --</option>
            <?php foreach($tipos_pagamento as $tp): ?>
              <option value="<?=$tp['id']?>"><?=htmlspecialchars($tp['descricao'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>


      <!-- Row 5: Observação -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-slate-700 mb-1">Observação</label>
        <textarea name="observation" rows="2" placeholder="Observações sobre este lançamento..." class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-slate-700"></textarea>
      </div>

      <!-- Buttons -->
      <div class="flex items-center justify-between pt-4 border-t border-slate-100">
        <a href="finance.php" class="bg-white border border-slate-300 text-slate-700 px-6 py-2 rounded hover:bg-slate-50 font-medium transition-colors">Voltar</a>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow-sm transition-colors">Salvar Lançamento</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  new TomSelect("#client_id", {
    create: false,
    sortField: {
      field: "text",
      direction: "asc"
    }
  });
});

function updateSituacao() {
  const tipo = document.getElementById('tipo').value;
  const situacao = document.getElementById('situacao');
  
  if (tipo === 'Entrada' || tipo === 'Saida' || tipo === 'cRecebido' || tipo === 'dPago') {
    situacao.value = 'Liquidado';
  } else if (tipo === 'Pagar' || tipo === 'Receber') {
    situacao.value = 'Aberto';
  } else {
    situacao.value = '';
  }
}
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
