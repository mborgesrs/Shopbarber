<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/accounts.php';
require_once __DIR__ . '/../lib/accounts.php';

$company_id = $_SESSION['company_id'];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $type = $_POST['type'];
  $value = $_POST['value'];
  $saldo_field = $_POST['saldo'] ?: 0;
  
  // Business Rule: For 'Pagar' and 'Receber', saldo = value
  if($type == 'Pagar' || $type == 'Receber') {
    $saldo_to_save = $value;
  } else {
    $saldo_to_save = $saldo_field;
  }

  $stmt=$pdo->prepare('INSERT INTO finance (date,data_vencimento,client_id,observation,value,saldo,type,portador_id,conta_id,tipo_pagamento_id,company_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
  $stmt->execute([
    $_POST['date'], 
    $_POST['data_vencimento']?:null, 
    $_POST['client_id']?:null, 
    $_POST['observation'], 
    $value, 
    $saldo_to_save,
    $type, 
    $_POST['portador_id']?:null,
    $_POST['conta_id']?:null,
    $_POST['tipo_pagamento_id']?:null,
    $company_id
  ]);
  recalculateAccountTotals($company_id, $pdo);
  header('Location: finance.php');exit;
}

// Filters
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$type = $_GET['type'] ?? '';
$client_id = $_GET['client_id'] ?? '';

$sql = 'SELECT f.*, c.name as client_name FROM finance f LEFT JOIN clients c ON c.id=f.client_id WHERE f.company_id=?';
$params = [$company_id];

if($from) { $sql .= " AND f.date >= ?"; $params[] = $from; }
if($to) { $sql .= " AND f.date <= ?"; $params[] = $to; }
if($type) { $sql .= " AND f.type = ?"; $params[] = $type; }
if($client_id) { $sql .= " AND f.client_id = ?"; $params[] = $client_id; }

$sql .= ' ORDER BY date DESC';
$stmt=$pdo->prepare($sql);
$stmt->execute($params);
$rows=$stmt->fetchAll();

// Calculate Summary
$entradas = 0;
$saidas = 0;
foreach($rows as $r) {
    if($r['type'] == 'Receber' || $r['type'] == 'Entrada') {
        $entradas += $r['value'];
    } else {
        $saidas += $r['value'];
    }
}
$saldo = $entradas - $saidas;

$stmt=$pdo->prepare('SELECT id,name FROM clients WHERE company_id=? ORDER BY name');
$stmt->execute([$company_id]);
$clients = $stmt->fetchAll();

$portadores = $pdo->prepare('SELECT id,nome FROM portadores WHERE company_id = ? ORDER BY nome');
$portadores->execute([$company_id]);
$portadores = $portadores->fetchAll();

$contas = $pdo->prepare("SELECT id,codigo,descricao,tipo FROM contas WHERE company_id = ? AND ativo=1 ORDER BY codigo");
$contas->execute([$company_id]);
$contas = $contas->fetchAll();

$tipos_pagamento = $pdo->prepare('SELECT id,descricao FROM tipos_pagamento WHERE company_id = ? AND ativo=1 ORDER BY descricao');
$tipos_pagamento->execute([$company_id]);
$tipos_pagamento = $tipos_pagamento->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
  <div class="flex-shrink-0">
    <h2 class="text-2xl font-bold text-gray-800">Financeiro</h2>
    <p class="text-sm text-gray-500">Controle suas receitas e despesas.</p>
  </div>

  <div class="flex flex-wrap lg:flex-nowrap items-center gap-3">
    <!-- Compact Summary Cards -->
    <div class="bg-white px-4 py-2.5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center text-sm">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div>
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Entradas</p>
            <h3 class="text-sm font-bold text-emerald-600 leading-none">R$ <?= number_format($entradas, 2, ',', '.') ?></h3>
        </div>
    </div>
    
    <div class="bg-white px-4 py-2.5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-8 h-8 bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center text-sm">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div>
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Saídas</p>
            <h3 class="text-sm font-bold text-rose-600 leading-none">R$ <?= number_format($saidas, 2, ',', '.') ?></h3>
        </div>
    </div>

    <div class="bg-white px-4 py-2.5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3 border-l-4 <?= $saldo >= 0 ? 'border-l-indigo-500' : 'border-l-rose-500' ?>">
        <div class="w-8 h-8 <?= $saldo >= 0 ? 'bg-indigo-50 text-indigo-600' : 'bg-rose-50 text-rose-600' ?> rounded-lg flex items-center justify-center text-sm">
            <i class="fas fa-wallet"></i>
        </div>
        <div>
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Saldo</p>
            <h3 class="text-sm font-bold <?= $saldo >= 0 ? 'text-gray-900' : 'text-rose-600' ?> leading-none">R$ <?= number_format($saldo, 2, ',', '.') ?></h3>
        </div>
    </div>

    <button onclick="document.getElementById('form').classList.toggle('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-indigo-100 font-bold text-sm ml-2">
        <i class="fas fa-plus"></i> Novo
    </button>
  </div>
</div>

<div id="form" class="hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 animate-fade-in-down">
  <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
    <i class="fas fa-edit text-indigo-600"></i> Adicionar Lançamento
  </h3>
  <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Data Lançamento</label>
        <input type="date" name="date" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" value="<?=date('Y-m-d')?>">
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Vencimento</label>
        <input type="date" name="data_vencimento" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" value="<?=date('Y-m-d')?>">
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Pessoa / Fornecedor</label>
        <select name="client_id" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
          <option value="">-- Selecione --</option>
          <?php foreach($clients as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Tipo</label>
        <select name="type" id="main_type" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" onchange="syncSaldo()">
          <option>Pagar</option><option>Receber</option><option>Entrada</option><option>Saida</option>
        </select>
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Valor</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">R$</span>
            <input name="value" id="main_value" placeholder="0,00" class="w-full border-gray-200 border p-2.5 pl-10 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" oninput="syncSaldo()">
        </div>
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Saldo</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">R$</span>
            <input name="saldo" id="main_saldo" placeholder="0,00" class="w-full border-gray-200 border p-2.5 pl-10 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
        </div>
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Portador</label>
        <select name="portador_id" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
          <option value="">-- Selecione --</option>
          <?php foreach($portadores as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['nome'])?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Conta Contábil</label>
        <select name="conta_id" id="main_conta_id" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" onchange="checkContaTipo(this)">
          <option value="">-- Selecione --</option>
          <?php foreach($contas as $c): ?>
            <option value="<?=$c['id']?>" data-tipo="<?=$c['tipo']?>" class="<?= $c['tipo'] == 'Sintetica' ? 'font-bold bg-gray-50' : '' ?>">
                <?=htmlspecialchars($c['codigo'])?> - <?=htmlspecialchars($c['descricao'])?> (<?= $c['tipo'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Tipo Pagamento</label>
        <select name="tipo_pagamento_id" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
          <option value="">-- Selecione --</option>
          <?php foreach($tipos_pagamento as $tp): ?><option value="<?=$tp['id']?>"><?=htmlspecialchars($tp['descricao'])?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="space-y-1">
        <label class="text-xs font-bold text-gray-500 uppercase">Observação</label>
        <input name="observation" placeholder="Ex: Pagamento de luz, Venda de produto..." class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
    </div>
    <div class="col-span-1 md:col-span-3 pt-2 flex gap-2">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 w-full md:w-auto">
            Salvar Lançamento
        </button>
        <button type="button" onclick="document.getElementById('form').classList.add('hidden')" class="px-6 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-200 transition-colors flex items-center gap-2 border border-gray-200">
            Voltar
        </button>
    </div>
  </form>
</div>

<!-- Filters -->
<div class="mb-6 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
  <form method="get" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
    <div class="space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">De</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
    </div>
    <div class="space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">Até</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
    </div>
    <div class="space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">Tipo</label>
        <select name="type" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all bg-white">
            <option value="">Todos os Tipos</option>
            <option value="Pagar" <?= $type=='Pagar'?'selected':'' ?>>Pagar</option>
            <option value="Receber" <?= $type=='Receber'?'selected':'' ?>>Receber</option>
            <option value="Entrada" <?= $type=='Entrada'?'selected':'' ?>>Entrada</option>
            <option value="Saida" <?= $type=='Saida'?'selected':'' ?>>Saída</option>
        </select>
    </div>
    <div class="space-y-1">
        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">Pessoa</label>
        <select name="client_id" class="w-full border-gray-100 border p-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all bg-white">
            <option value="">Todas as Pessoas</option>
            <?php foreach($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $client_id==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-100">
            <i class="fas fa-filter mr-1"></i> Filtrar
        </button>
        <?php if($from || $to || $type || $client_id): ?>
            <a href="finance.php" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors flex items-center justify-center border border-gray-200" title="Limpar">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
    </div>
  </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between font-bold text-sm text-gray-700 uppercase tracking-wider">
    <span>Histórico de Movimentações</span>
    <i class="fas fa-list text-gray-400"></i>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50/50 border-b border-gray-100 text-[11px] uppercase text-gray-400 tracking-widest font-bold">
        <tr>
          <th class="px-6 py-2 text-left">Data</th>
          <th class="px-6 py-2 text-left">Vencimento</th>
          <th class="px-6 py-2 text-left">Pessoa / Descrição</th>
          <th class="px-6 py-2 text-left">Tipo</th>
          <th class="px-6 py-2 text-left">Status</th>
          <th class="px-6 py-2 text-left">Valor</th>
          <th class="px-6 py-2 text-left">Saldo</th>
          <th class="px-6 py-2 text-right">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
      <?php foreach($rows as $r): ?>
        <tr class="hover:bg-gray-50/80 transition-colors group">
          <td class="px-6 py-2">
            <div class="flex flex-col">
                <span class="text-gray-900 font-bold text-xs"><?=date('d/m/Y', strtotime($r['date']))?></span>
                <span class="text-[9px] text-gray-400 font-medium"><?= date('H:i', strtotime($r['created_at'])) ?></span>
            </div>
          </td>
          <td class="px-6 py-2">
            <div class="flex flex-col">
                <span class="text-gray-900 font-bold text-xs"><?= $r['data_vencimento'] ? date('d/m/Y', strtotime($r['data_vencimento'])) : '-' ?></span>
            </div>
          </td>
          <td class="px-6 py-2">
            <div class="flex flex-col">
                <span class="text-gray-800 font-bold text-xs"><?= $r['client_name'] ? htmlspecialchars($r['client_name']) : '<span class="text-gray-400 font-normal italic">Sem pessoa</span>' ?></span>
                <span class="text-[11px] text-gray-500 line-clamp-1"><?=htmlspecialchars($r['observation'] ?? '')?></span>
            </div>
          </td>
          <td class="px-6 py-2">
            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider <?= $r['type'] == 'Pagar' || $r['type'] == 'Saida' ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' ?>">
              <i class="fas <?= $r['type'] == 'Pagar' || $r['type'] == 'Saida' ? 'fa-minus-circle' : 'fa-plus-circle' ?> mr-1"></i>
              <?=htmlspecialchars($r['type'])?>
            </span>
          </td>
          <td class="px-6 py-2">
            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider <?= $r['status'] == 'Pago' ? 'bg-emerald-50 text-emerald-600' : ($r['status'] == 'Cancelado' ? 'bg-gray-50 text-gray-600' : 'bg-amber-50 text-amber-600') ?>">
              <?=htmlspecialchars($r['status'])?>
            </span>
          </td>
          <td class="px-6 py-2">
            <div class="flex flex-col">
                <span class="text-sm font-bold <?= $r['type'] == 'Pagar' || $r['type'] == 'Saida' ? 'text-rose-600' : 'text-emerald-600' ?>">
                    R$ <?=number_format($r['value'],2,',','.')?>
                </span>
                <?php if($r['status'] !== 'Pago' && ($r['type'] == 'Pagar' || $r['type'] == 'Receber')): ?>
                    <?php 
                        $today = date('Y-m-d');
                        $is_overdue = $r['data_vencimento'] < $today;
                    ?>
                    <span class="text-[9px] font-bold uppercase <?= $is_overdue ? 'text-rose-600' : 'text-amber-600' ?>">
                        (<?= $is_overdue ? 'Titulo Vencido' : 'Titulo Vencer' ?>)
                    </span>
                <?php endif; ?>
            </div>
          </td>
          <td class="px-6 py-2">
            <span class="text-sm font-bold text-blue-600">
                R$ <?=number_format($r['saldo'] ?? 0,2,',','.')?>
            </span>
          </td>
          <td class="px-6 py-2 text-right">
            <div class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
              <a title="Editar" class="w-7 h-7 flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm" href="finance_edit.php?id=<?=$r['id']?>">
                <i class="fas fa-edit text-[10px]"></i>
              </a>
              <a title="Excluir" class="w-7 h-7 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm" href="finance_delete.php?id=<?=$r['id']?>" onclick="return confirm('Excluir?')">
                <i class="fas fa-trash text-[10px]"></i>
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function syncSaldo() {
    const type = document.getElementById('main_type').value;
    const value = document.getElementById('main_value').value;
    const saldoField = document.getElementById('main_saldo');
    
    if (type === 'Pagar' || type === 'Receber') {
        // Replace comma with dot to ensure it's a valid number for JS, though browser might handle it
        saldoField.value = value;
        saldoField.readOnly = true;
        saldoField.classList.add('bg-gray-50', 'text-gray-500');
    } else {
        saldoField.readOnly = false;
        saldoField.classList.remove('bg-gray-50', 'text-gray-500');
    }
}

function checkContaTipo(select) {
    const selectedOption = select.options[select.selectedIndex];
    const tipo = selectedOption.getAttribute('data-tipo');
    
    if (tipo === 'Sintetica') {
        Swal.fire({
            title: 'Atenção!',
            text: 'Você só pode selecionar uma conta ANALÍTICA dentro do grupo.',
            icon: 'warning',
            confirmButtonText: 'Entendi',
            confirmButtonColor: '#4f46e5'
        });
        select.value = '';
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', syncSaldo);
</script>
