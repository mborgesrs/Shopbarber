<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/PixPayload.php';

$id = $_GET['id'] ?? null;
if(!$id){ header('Location: quotes.php'); exit; }

$stmt = $pdo->prepare("SELECT q.*, c.name as client_name FROM quotes q JOIN clients c ON c.id=q.client_id WHERE q.id=?");
$stmt->execute([$id]);
$quote = $stmt->fetch();

if(!$quote || $quote['status'] === 'Atendido'){
    header('Location: quotes.php'); exit;
}

// Get Pix Settings
$stmtS = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmtS->fetch();

$pixPayload = '';
$hasPix = !empty($settings['pix_key']);

if($hasPix){
    $ob = new PixPayload();
    $ob->setPixKey($settings['pix_key'])
       ->setDescription('Pagamento Pedido #'.$id)
       ->setMerchantName($settings['pix_merchant_name'])
       ->setMerchantCity($settings['pix_merchant_city'])
       ->setAmount($quote['total'])
       ->setTxid('PEDIDO'.$id); // TXID must be simple
    
    $pixPayload = $ob->getPayload();
}
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="max-w-lg mx-auto bg-white p-6 rounded shadow mt-10">
    <h2 class="text-2xl font-bold mb-4 text-center">Finalizar Agendamento #<?= $quote['id'] ?></h2>
    
    <div class="bg-gray-50 p-4 rounded mb-6 text-center">
        <p class="text-gray-600">Cliente</p>
        <p class="font-bold text-lg"><?= htmlspecialchars($quote['client_name']) ?></p>
        <div class="my-2 border-t border-gray-200"></div>
        <p class="text-gray-600">Total a Pagar</p>
        <p class="font-bold text-3xl text-green-600">R$ <?= number_format($quote['total'], 2, ',', '.') ?></p>
    </div>

    <form action="quote_complete.php" method="POST" id="payForm">
        <input type="hidden" name="id" value="<?= $quote['id'] ?>">
        
        <label class="block font-medium mb-2">Forma de Pagamento:</label>
        <select name="payment_method" id="payment_method" class="w-full border p-3 rounded mb-6 text-lg" onchange="togglePix()">
            <option value="Dinheiro">Dinheiro</option>
            <option value="Cartão">Cartão</option>
            <?php if($hasPix): ?><option value="Pix">Pix</option><?php endif; ?>
        </select>

        <!-- Pix Area -->
        <div id="pixArea" class="hidden mb-6 text-center">
            <div class="mb-4 flex justify-center">
                 <div id="qrcode"></div>
            </div>
            <p class="text-sm text-gray-500 mb-2">Escaneie o QR Code acima ou copie a chave abaixo:</p>
            <div class="flex gap-2">
                <input type="text" id="pixCopy" value="<?= htmlspecialchars($pixPayload) ?>" readonly class="w-full border p-2 rounded bg-gray-100 text-xs">
                <button type="button" onclick="copyPix()" class="bg-gray-200 px-3 py-1 rounded hover:bg-gray-300">Copiar</button>
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition">
            Confirmar Pagamento e Concluir
        </button>
        <a href="quotes.php" class="block text-center text-gray-500 mt-4 hover:underline">Cancelar</a>
    </form>
</div>

<!-- QR Code Lib -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    var pixPayload = "<?= htmlspecialchars($pixPayload) ?>";
    var qrCodeObj = null;

    function togglePix(){
        var sel = document.getElementById('payment_method').value;
        var area = document.getElementById('pixArea');
        if(sel === 'Pix'){
            area.classList.remove('hidden');
            if(!qrCodeObj && pixPayload){
                qrCodeObj = new QRCode(document.getElementById("qrcode"), {
                    text: pixPayload,
                    width: 200,
                    height: 200
                });
            }
        } else {
            area.classList.add('hidden');
        }
    }

    function copyPix(){
        var copyText = document.getElementById("pixCopy");
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(copyText.value).then(function(){
            alert("Código Pix copiado!");
        }, function(){
            alert("Falha ao copiar. Tente selecionar e copiar manualmente.");
        });
    }
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
