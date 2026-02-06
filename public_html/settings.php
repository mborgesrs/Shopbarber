<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__ . '/../db.php';

$company_id = $_SESSION['company_id'];
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $company_name = $_POST['company_name'];
    $fantasy_name = $_POST['fantasy_name'] ?? null;
    $phone = $_POST['phone'];
    $email = $_POST['email'] ?? null;
    $address = $_POST['address'];
    $cnpj = $_POST['cnpj'] ?? null;
    $cpf = $_POST['cpf'] ?? null;
    $cep = $_POST['cep'] ?? null;
    $logradouro = $_POST['logradouro'] ?? null;
    $numero = $_POST['numero'] ?? null;
    $complemento = $_POST['complemento'] ?? null;
    $bairro = $_POST['bairro'] ?? null;
    $cidade = $_POST['cidade'] ?? null;
    $estado = $_POST['estado'] ?? null;
    
    // Update text data in settings table
    $stmt = $pdo->prepare("UPDATE settings SET 
        company_name=?, fantasy_name=?, phone=?, email=?, address=?, 
        pix_key_type=?, pix_key=?, pix_merchant_name=?, pix_merchant_city=?, pix_bank_id=?,
        cnpj=?, cpf=?, cep=?, logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, estado=?
        WHERE company_id=?");
    $stmt->execute([
        $company_name, $fantasy_name, $phone, $email, $address, 
        $_POST['pix_key_type'], $_POST['pix_key'], $_POST['pix_merchant_name'], $_POST['pix_merchant_city'], $_POST['pix_bank_id'],
        $cnpj, $cpf, $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado,
        $company_id
    ]);

    // Also sync basic info with companies table
    $stmt = $pdo->prepare("UPDATE companies SET name=?, fantasy_name=? WHERE id=?");
    $stmt->execute([$company_name, $fantasy_name, $company_id]);
    
    // Handle Logo Upload
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK){
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png','webp'])){
            $filename = 'logo_' . $company_id . '_' . time() . '.' . $ext;
            $upload_dir = __DIR__ . '/uploads/logos';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $dest = $upload_dir . '/' . $filename;
            if(move_uploaded_file($_FILES['logo']['tmp_name'], $dest)){
                $pdo->prepare("UPDATE settings SET logo_path=? WHERE company_id=?")->execute(["uploads/logos/$filename", $company_id]);
            }
        }
    }
    
    $msg = 'Configurações atualizadas com sucesso!';
}

$stmt = $pdo->prepare("SELECT * FROM settings WHERE company_id = ? LIMIT 1");
$stmt->execute([$company_id]);
$settings = $stmt->fetch();
if(!$settings) {
    $pdo->prepare("INSERT INTO settings (company_id, company_name, phone, address) VALUES (?, 'Minha Empresa', '', '')")->execute([$company_id]);
    $stmt->execute([$company_id]);
    $settings = $stmt->fetch();
}
$banks = $pdo->query("SELECT * FROM banks ORDER BY name")->fetchAll();
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Configurações</h2>
            <p class="text-sm text-gray-500">Gerencie as informações da sua empresa.</p>
        </div>
    </div>
    
    <?php if($msg): ?>
        <div id="msgSuccess" class="bg-emerald-50 border border-emerald-100 text-emerald-700 p-4 rounded-2xl mb-6 flex items-center gap-3 animate-fade-in">
            <i class="fas fa-check-circle"></i>
            <span class="font-bold text-sm"><?= htmlspecialchars($msg) ?></span>
        </div>
        <script>
            setTimeout(function(){
                var msg = document.getElementById('msgSuccess');
                if(msg) msg.style.display = 'none';
            }, 3000);
        </script>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-6">
        <!-- Identidade Visual -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2 font-bold text-sm text-gray-700 uppercase tracking-wider">
                <i class="fas fa-image text-indigo-500"></i>
                Identidade Visual
            </div>
            <div class="p-6">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="relative group">
                        <div class="w-32 h-32 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden">
                            <?php if(!empty($settings['logo_path'])): ?>
                                <img src="/<?= $settings['logo_path'] ?>" alt="Logo" class="w-full h-full object-contain p-2">
                            <?php else: ?>
                                <i class="fas fa-camera text-3xl text-gray-300"></i>
                            <?php endif; ?>
                        </div>
                        <label for="logo-upload" class="absolute -bottom-2 -right-2 w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:bg-indigo-700 transition-colors">
                            <i class="fas fa-pencil-alt text-xs"></i>
                            <input type="file" id="logo-upload" name="logo" class="hidden">
                        </label>
                    </div>
                    <div class="flex-1 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">Nome Fantasia</label>
                                <input type="text" name="fantasy_name" value="<?= htmlspecialchars($settings['fantasy_name'] ?? '') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="Ex: Barbearia Estilo">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">Razão Social</label>
                                <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name']) ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" required>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400">O logomarca será exibido nos relatórios e PDF de agendamentos.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contato e Endereço -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2 font-bold text-sm text-gray-700 uppercase tracking-wider">
                <i class="fas fa-map-marker-alt text-rose-500"></i>
                Contato e Localização
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">WhatsApp / Telefone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($settings['phone']) ?>" class="mask-phone w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="(00) 00000-0000">
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs font-bold text-gray-500 uppercase">E-mail Comercial</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="contato@empresa.com.br">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">CEP</label>
                        <div class="relative">
                            <input type="text" name="cep" id="cep" value="<?= htmlspecialchars($settings['cep'] ?? '') ?>" maxlength="9" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="00000-000">
                        </div>
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs font-bold text-gray-500 uppercase">Logradouro</label>
                        <input type="text" name="logradouro" id="logradouro" value="<?= htmlspecialchars($settings['logradouro'] ?? '') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Número</label>
                        <input type="text" name="numero" id="numero" value="<?= htmlspecialchars($settings['numero'] ?? '') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Bairro</label>
                        <input type="text" name="bairro" id="bairro" value="<?= htmlspecialchars($settings['bairro'] ?? '') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Cidade</label>
                        <input type="text" name="cidade" id="cidade" value="<?= htmlspecialchars($settings['cidade'] ?? '') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Estado (UF)</label>
                        <input type="text" name="estado" id="estado" value="<?= htmlspecialchars($settings['estado'] ?? '') ?>" maxlength="2" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="SP">
                    </div>
                </div>
                
                <div class="space-y-1">
                     <label class="text-xs font-bold text-gray-500 uppercase">Endereço de Referência (Opcional)</label>
                     <input type="text" name="address" value="<?= htmlspecialchars($settings['address']) ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
            </div>
        </div>

        <!-- Pagamento (Pix) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2 font-bold text-sm text-gray-700 uppercase tracking-wider">
                <i class="fas fa-qrcode text-emerald-500"></i>
                Recebimentos Pix
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Tipo de Chave</label>
                        <select name="pix_key_type" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            <option value="cpf" <?= ($settings['pix_key_type']??'')=='cpf'?'selected':'' ?>>CPF</option>
                            <option value="cnpj" <?= ($settings['pix_key_type']??'')=='cnpj'?'selected':'' ?>>CNPJ</option>
                            <option value="email" <?= ($settings['pix_key_type']??'')=='email'?'selected':'' ?>>E-mail</option>
                            <option value="phone" <?= ($settings['pix_key_type']??'')=='phone'?'selected':'' ?>>Celular</option>
                            <option value="random" <?= ($settings['pix_key_type']??'')=='random'?'selected':'' ?>>Aleatória</option>
                        </select>
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs font-bold text-gray-500 uppercase">Chave Pix</label>
                        <input type="text" name="pix_key" value="<?= htmlspecialchars($settings['pix_key']??'') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Nome Beneficiário</label>
                        <input type="text" name="pix_merchant_name" value="<?= htmlspecialchars($settings['pix_merchant_name']??'') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Cidade</label>
                        <input type="text" name="pix_merchant_city" value="<?= htmlspecialchars($settings['pix_merchant_city']??'') ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Banco</label>
                        <select name="pix_bank_id" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            <option value="">Selecione...</option>
                            <?php foreach($banks as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($settings['pix_bank_id']??'')==$b['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($b['code'] . ' - ' . $b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assinatura e Plano -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2 font-bold text-sm text-gray-700 uppercase tracking-wider">
                    <i class="fas fa-credit-card text-blue-500"></i>
                    Assinatura e Plano
                </div>
                <a href="billing.php" class="text-xs font-bold text-indigo-600 hover:underline">Gerenciar</a>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-gray-800">Seu plano atual: <?= strtoupper($company['plan_id'] ?? 'Não definido') ?></p>
                        <p class="text-xs text-gray-500 mt-1">Status: <?= strtoupper($company['subscription_status'] ?? 'Desconhecido') ?></p>
                    </div>
                    <a href="billing.php" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-bold hover:bg-indigo-100 transition-colors">
                        Ver Faturas
                    </a>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pb-8">
            <a href="dashboard.php" class="px-6 py-2.5 bg-white text-gray-500 rounded-xl font-bold hover:bg-gray-50 border border-gray-200 transition-colors">Cancelar</a>
            <button class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">
                Salvar Alterações
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(r => r.json())
                .then(data => {
                    if(!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                        document.getElementById('numero').focus();
                    }
                });
            }
        });
    }
    
    // Preview logo
    const logoInput = document.getElementById('logo-upload');
    const logoPreview = logoInput.parentElement.parentElement.querySelector('img') || logoInput.parentElement.parentElement.querySelector('i');
    
    logoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const container = logoInput.parentElement.parentElement.querySelector('div');
                container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-contain p-2">`;
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
