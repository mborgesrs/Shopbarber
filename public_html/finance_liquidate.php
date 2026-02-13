<?php
session_start(); 
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/accounts.php';

$id = $_GET['id'] ?? null;
if(!$id){ header('Location: finance.php'); exit; }

// Fetch original finance record with joined details
$stmt = $pdo->prepare('
    SELECT f.*, 
           c.name as client_name,
           p.nome as portador_nome,
           ct.descricao as conta_descricao,
           ct.codigo as conta_codigo
    FROM finance f 
    LEFT JOIN clients c ON c.id=f.client_id 
    LEFT JOIN portadores p ON p.id=f.portador_id
    LEFT JOIN contas ct ON ct.id=f.conta_id
    WHERE f.id = ? AND f.company_id = ?
');
$stmt->execute([$id, $_SESSION['company_id']]);
$original = $stmt->fetch();

if(!$original){ 
    header('Location: finance.php'); 
    exit; 
}

// Validate that it's a Pagar or Receber with status Aberto and saldo > 0
$currentStatus = trim($original['status'] ?? '');
if(empty($currentStatus) || $currentStatus == 'Pendente') $currentStatus = 'Aberto';

if(!in_array($original['type'], ['Pagar', 'Receber', 'dPago', 'cRecebido']) || ($currentStatus != 'Aberto' && $original['saldo'] <= 0)){
    // Allow if status is Aberto OR if it has balance, even if technically 'liquidated' but maybe partial?
    // Actually the logic above was strict. Let's stick to Pagar/Receber mostly, but user might be editing.
    // However, liquidation usually applies to Aberto items.
    // If user clicked liquidate on a list item, it should be valid.
    
    // The original check was:
    // (!in_array($original['type'], ['Pagar', 'Receber']) || $currentStatus != 'Aberto' || $original['saldo'] <= 0)
    
    // If we want to allow re-liquidating or fixing, maybe relax. But for now, just ensure type Pagar/Receber as per standard flow.
    // AND ensure balance > 0.
    
    if($original['saldo'] <= 0) {
         header('Location: finance.php?error=already_liquidated');
         exit;
    }
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $liquidatedAmount = (float)$_POST['amount'];
    
    if($liquidatedAmount <= 0){
        $error = "O valor deve ser maior que zero.";
    } elseif($liquidatedAmount > $original['saldo']){
        $error = "O valor de liquidação não pode ser maior que o saldo atual (R$ ".number_format($original['saldo'], 2, ',', '.').").";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Determine new type based on original
            $newType = ($original['type'] == 'Receber') ? 'Entrada' : 'Saida';
            
            // Create liquidation record (copy of original with modifications)
            $stmt = $pdo->prepare('INSERT INTO finance (date, client_id, observation, value, saldo, type, portador_id, conta_id, tipo_pagamento_id, status, data_vencimento, data_pagamento, company_id, parent_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $_POST['payment_date'] ?: date('Y-m-d'),
                $original['client_id'],
                'LIQUIDAÇÃO - ' . ($original['observation'] ?: 'Pagamento'),
                $liquidatedAmount,
                0, // Liquidation record saldo is 0 (it's done)
                $newType,
                $original['portador_id'],
                $original['conta_id'],
                $_POST['tipo_pagamento_id'] ?: $original['tipo_pagamento_id'],
                'Liquidado',
                $original['data_vencimento'],
                $_POST['payment_date'] ?: date('Y-m-d'),
                $_SESSION['company_id'],
                $id // Store parent_id
            ]);
            
            $liquidacaoId = $pdo->lastInsertId();
            
            // Update original record balance
            $newBalance = $original['saldo'] - $liquidatedAmount;
            
            // Logic for status and type update
            $newStatus = 'Aberto';
            // Default to original type if not changing
            $newTypeOriginal = !empty($original['type']) ? $original['type'] : 'Pagar'; 
            
            // Debug if needed: error_log("Original type: " . $original['type'] . ", Balance: " . $newBalance);

            // Check if fully liquidated (using small epsilon for float comparison safety)
            if ($newBalance <= 0.001) {
                 $newBalance = 0; // Force zero
                 
                 // DEBUG LOG
                 file_put_contents(__DIR__ . '/debug_liquidate.txt', "Liquidating ID: $id. Original Type: {$original['type']}. New Balance: $newBalance\n", FILE_APPEND);

                 // For Receivables, switch to cRecebido and mark as Liquidado
                 if ($original['type'] == 'Receber') {
                     $newTypeOriginal = 'cRecebido';
                     $newStatus = 'Liquidado';
                     file_put_contents(__DIR__ . '/debug_liquidate.txt', "-> Set to cRecebido/Liquidado\n", FILE_APPEND);
                 }
                 // For Payables (Pagar), switch to dPago and mark as Liquidado
                 // Note: trim() to be super safe
                 elseif (trim($original['type']) == 'Pagar') {
                     $newTypeOriginal = 'dPago';
                     $newStatus = 'Liquidado';
                     file_put_contents(__DIR__ . '/debug_liquidate.txt', "-> Set to dPago/Liquidado\n", FILE_APPEND);
                 }
                 // Generic fallback
                 else {
                     $newStatus = 'Liquidado';
                     file_put_contents(__DIR__ . '/debug_liquidate.txt', "-> Fallback to Liquidado. Type was: '{$original['type']}'\n", FILE_APPEND);
                 }
            } else {
                 file_put_contents(__DIR__ . '/debug_liquidate.txt', "Partial liquidation. ID: $id. Balance: $newBalance\n", FILE_APPEND);
            }
            
            $stmt = $pdo->prepare('UPDATE finance SET saldo = ?, status = ?, liquidacao_id = ?, type = ?, data_pagamento = ? WHERE id = ?');
            $stmt->execute([
                $newBalance, 
                $newStatus, 
                $liquidacaoId, 
                $newTypeOriginal, 
                $_POST['payment_date'] ?: date('Y-m-d'),
                $id
            ]);
            
            recalculateAccountTotals($_SESSION['company_id'], $pdo);
            
            $pdo->commit();
            
            header('Location: finance.php?msg=liquidated');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erro na liquidação: " . $e->getMessage();
        }
    }
}

// Reference data for payment types
$tipos_pagamento = $pdo->query('SELECT id,descricao FROM tipos_pagamento WHERE ativo=1 ORDER BY descricao')->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-800">Liquidar Lançamento #<?= $id ?></h2>
        <p class="text-slate-500">Registrar pagamento parcial ou total</p>
    </div>

    <?php if($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-medium"><?= $error ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="p-6 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b pb-2">Dados do Lançamento de Origem</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-6 gap-x-8">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Cliente / Fornecedor</label>
                    <p class="text-base font-bold text-slate-800"><?= htmlspecialchars($original['client_name'] ?: '-') ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Tipo</label>
                    <p class="text-base font-bold text-slate-800"><?= $original['type'] ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Data do Lançamento</label>
                    <p class="text-base font-bold text-slate-800"><?= date('d/m/Y', strtotime($original['date'])) ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Valor Original</label>
                    <p class="text-base font-bold text-slate-800">R$ <?= number_format($original['value'], 2, ',', '.') ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Saldo em Aberto</label>
                    <p class="text-lg font-black text-blue-600">R$ <?= number_format($original['saldo'], 2, ',', '.') ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Vencimento</label>
                    <p class="text-base font-bold text-slate-800"><?= $original['data_vencimento'] ? date('d/m/Y', strtotime($original['data_vencimento'])) : '-' ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Portador</label>
                    <p class="text-base font-medium text-slate-700"><?= htmlspecialchars($original['portador_nome'] ?: '-') ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Conta Contábil</label>
                    <p class="text-base font-medium text-slate-700"><?= $original['conta_codigo'] ? ($original['conta_codigo'] . ' - ' . htmlspecialchars($original['conta_descricao'])) : '-' ?></p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 uppercase">Observação</label>
                    <p class="text-sm text-slate-600 italic"><?= htmlspecialchars($original['observation'] ?: '-') ?></p>
                </div>
            </div>
        </div>

        <form method="post" class="p-8 space-y-8 bg-white">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2 border-b pb-2 text-emerald-600">Dados da Liquidação (Pagamento)</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-4">
                <div class="bg-emerald-50 p-4 rounded-lg border border-emerald-100">
                    <label class="block text-sm font-bold text-emerald-800 mb-2">Valor a Liquidar (R$) *</label>
                    <input type="number" step="0.01" name="amount" value="<?= $original['saldo'] ?>" 
                           max="<?= $original['saldo'] ?>" min="0.01"
                           class="w-full border-2 border-emerald-200 rounded-lg p-3 focus:outline-none focus:border-emerald-500 text-slate-800 font-black text-2xl shadow-inner" required>
                    <p class="text-xs text-emerald-600 mt-2 font-medium">Permite liquidação parcial ou total.</p>
                </div>

                <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Data do Pagamento *</label>
                    <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" 
                           class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 text-slate-700 font-bold" required>
                </div>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                <label class="block text-sm font-bold text-slate-700 mb-2">Meio de Pagamento / Tipo</label>
                <select name="tipo_pagamento_id" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 text-slate-700 font-semibold" required>
                    <option value="">-- Selecione o meio de pagamento --</option>
                    <?php foreach($tipos_pagamento as $tp): ?>
                        <option value="<?= $tp['id'] ?>" <?= $original['tipo_pagamento_id'] == $tp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tp['descricao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center justify-between pt-8 border-t border-slate-100">
                <a href="finance.php" class="bg-slate-100 text-slate-600 px-6 py-3 rounded-lg hover:bg-slate-200 font-bold transition-all flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Cancelar
                </a>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-10 py-3 rounded-lg font-black text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all inline-flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Confirmar Liquidação
                </button>
            </div>
        </form>
    </div>

</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
