<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/accounts.php';

$company_id = $_SESSION['company_id'];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $type = $_POST['type'];
  $value = str_replace(',', '.', $_POST['value']); // Ensure numeric format
  $saldo_field = str_replace(',', '.', $_POST['saldo'] ?: 0);
  
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

// Fetch data for form
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

include __DIR__ . '/../views/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Novo Lançamento</h2>
            <p class="text-sm text-gray-500">Insira as informações do novo movimento financeiro.</p>
        </div>
        <a href="finance.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl flex items-center gap-2 transition-colors font-bold text-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8">
        <form method="post" id="financeForm" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Data Lançamento</label>
                <input type="date" name="date" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" value="<?=date('Y-m-d')?>" required>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Vencimento</label>
                <input type="date" name="data_vencimento" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" value="<?=date('Y-m-d')?>">
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Pessoa / Fornecedor</label>
                <div class="flex gap-2">
                    <select name="client_id" class="flex-1 border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm">
                        <option value="">-- Selecione --</option>
                        <?php foreach($clients as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?>
                    </select>
                    <a href="client_create.php?return_url=finance_create.php" class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100" title="Nova Pessoa">
                        <i class="fas fa-plus text-xs"></i>
                    </a>
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Tipo</label>
                <select name="type" id="main_type" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" onchange="syncSaldo()" required>
                    <option value="Pagar">Pagar</option>
                    <option value="Receber">Receber</option>
                    <option value="Entrada">Entrada</option>
                    <option value="Saida">Saída</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Valor</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">R$</span>
                    <input name="value" id="main_value" placeholder="0,00" class="w-full border-gray-200 border p-2.5 pl-10 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" oninput="syncSaldo()" required>
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Saldo</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">R$</span>
                    <input name="saldo" id="main_saldo" placeholder="0,00" class="w-full border-gray-200 border p-2.5 pl-10 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Portador</label>
                <div class="flex gap-2">
                    <select name="portador_id" class="flex-1 border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm">
                        <option value="">-- Selecione --</option>
                        <?php foreach($portadores as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['nome'])?></option><?php endforeach; ?>
                    </select>
                    <a href="portadores.php?return_url=finance_create.php" class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100" title="Novo Portador">
                        <i class="fas fa-plus text-xs"></i>
                    </a>
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Conta Contábil</label>
                <div class="flex gap-2">
                    <select name="conta_id" id="main_conta_id" class="flex-1 border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm" onchange="checkContaTipo(this)" required>
                        <option value="">-- Selecione --</option>
                        <?php foreach($contas as $c): ?>
                            <option value="<?=$c['id']?>" data-tipo="<?=$c['tipo']?>" class="<?= $c['tipo'] == 'Sintetica' ? 'font-bold bg-gray-50' : '' ?>">
                                <?=htmlspecialchars($c['codigo'])?> - <?=htmlspecialchars($c['descricao'])?> (<?= $c['tipo'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="contas.php?return_url=finance_create.php" class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100" title="Nova Conta">
                        <i class="fas fa-plus text-xs"></i>
                    </a>
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Tipo Pagamento</label>
                <div class="flex gap-2">
                    <select name="tipo_pagamento_id" class="flex-1 border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm">
                        <option value="">-- Selecione --</option>
                        <?php foreach($tipos_pagamento as $tp): ?><option value="<?=$tp['id']?>"><?=htmlspecialchars($tp['descricao'])?></option><?php endforeach; ?>
                    </select>
                    <a href="tipos_pagamento.php?return_url=finance_create.php" class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100" title="Novo Tipo de Pagamento">
                        <i class="fas fa-plus text-xs"></i>
                    </a>
                </div>
            </div>
            <div class="col-span-1 md:col-span-3 space-y-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Observação</label>
                <textarea name="observation" id="main_observation" rows="3" placeholder="Ex: Pagamento de luz, Venda de produto..." class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"></textarea>
            </div>
            <div class="col-span-1 md:col-span-3 pt-4">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 flex items-center justify-center gap-2">
                    <i class="fas fa-check"></i> Salvar Lançamento
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// State Persistence Logic
const FORM_ID = 'finance_create_state';
const form = document.getElementById('financeForm');

function saveState() {
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    sessionStorage.setItem(FORM_ID, JSON.stringify(data));
}

function restoreState() {
    const savedData = sessionStorage.getItem(FORM_ID);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const field = form.elements[key];
            if (field) {
                field.value = data[key];
            }
        });
        syncSaldo(); // Re-trigger dependent field logic
    }
}

// Attach event listeners for saving
form.querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('input', saveState);
    el.addEventListener('change', saveState);
});

// Clear state on submit
form.addEventListener('submit', () => {
    sessionStorage.removeItem(FORM_ID);
});

function syncSaldo() {
    const type = document.getElementById('main_type').value;
    const value = document.getElementById('main_value').value;
    const saldoField = document.getElementById('main_saldo');
    
    if (type === 'Pagar' || type === 'Receber') {
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
        saveState();
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    restoreState();
    syncSaldo();
});
</script>

