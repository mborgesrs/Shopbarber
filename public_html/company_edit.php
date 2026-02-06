<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$id = $_GET['id'] ?? null;
if(!$id){ header('Location: companies.php'); exit; }

// Users can only edit their own company
if($id != $_SESSION['company_id']){
    header('Location: companies.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
$stmt->execute([$id]);
$company = $stmt->fetch();
if(!$company){ header('Location: companies.php'); exit; }

    // Get settings info for this company
    $stmtS = $pdo->prepare("SELECT * FROM settings WHERE company_id = ? LIMIT 1");
    $stmtS->execute([$id]);
    $settings = $stmtS->fetch();
    
    if($_SERVER['REQUEST_METHOD']==='POST'){
      $name = $_POST['name'];
      $fantasy = $_POST['fantasy_name'];
      $doc = $_POST['document'];
      $status = $_POST['status'];
      
      // Update companies table
      $stmt = $pdo->prepare('UPDATE companies SET name=?, fantasy_name=?, document=?, status=? WHERE id=?');
      $stmt->execute([$name, $fantasy, $doc, $status, $id]);
      
      // Update settings table
      $phone = $_POST['phone'] ?? '';
      $email = $_POST['email'] ?? '';
      
      if($settings){
          $stmt = $pdo->prepare("UPDATE settings SET company_name=?, fantasy_name=?, phone=?, email=? WHERE company_id=?");
          $stmt->execute([$name, $fantasy, $phone, $email, $id]);
      } else {
          $stmt = $pdo->prepare("INSERT INTO settings (company_id, company_name, fantasy_name, phone, email, address) VALUES (?, ?, ?, ?, ?, '')");
          $stmt->execute([$id, $name, $fantasy, $phone, $email]);
      }
      
      // Handle Logo Upload
      if(isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK){
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['jpg','jpeg','png','webp'])){
            $filename = 'logo_' . $id . '_' . time() . '.' . $ext;
            $upload_dir = __DIR__ . '/uploads/logos';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $dest = $upload_dir . '/' . $filename;
            if(move_uploaded_file($_FILES['logo']['tmp_name'], $dest)){
                $pdo->prepare("UPDATE settings SET logo_path=? WHERE company_id=?")->execute(["uploads/logos/$filename", $id]);
            }
        }
      }
      
      header('Location: companies.php');exit;
    }
    ?>
    <?php include __DIR__.'/../views/header.php'; ?>
    
    <div class="max-w-xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Editar Empresa</h2>
                <p class="text-sm text-gray-500">Ajuste as informações básicas e identidade.</p>
            </div>
            <a href="companies.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    
        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2 font-bold text-sm text-gray-700 uppercase tracking-wider">
                    <i class="fas fa-info-circle text-indigo-500"></i> Informações Gerais
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Razão Social</label>
                            <input name="name" value="<?=htmlspecialchars($company['name'])?>" required class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Nome Fantasia</label>
                            <input name="fantasy_name" value="<?=htmlspecialchars($company['fantasy_name'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                    </div>
    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Documento (CNPJ/CPF)</label>
                            <input name="document" id="document" value="<?=htmlspecialchars($company['document'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Status do Sistema</label>
                            <select name="status" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                <option value="active" <?= $company['status']=='active'?'selected':'' ?>>Ativo</option>
                                <option value="inactive" <?= $company['status']=='inactive'?'selected':'' ?>>Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Contact & Visual -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2 font-bold text-sm text-gray-700 uppercase tracking-wider">
                    <i class="fas fa-id-card text-emerald-500"></i> Contato e Identidade
                </div>
                <div class="p-6 space-y-6">
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <div class="w-24 h-24 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden">
                                <?php if(!empty($settings['logo_path'])): ?>
                                    <img src="/<?= $settings['logo_path'] ?>" id="logo-preview-img" class="w-full h-full object-contain p-2">
                                <?php else: ?>
                                    <i class="fas fa-camera text-2xl text-gray-300"></i>
                                <?php endif; ?>
                            </div>
                            <label for="logo-upload" class="absolute -bottom-2 -right-2 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:bg-indigo-700 transition-colors">
                                <i class="fas fa-pencil-alt text-[10px]"></i>
                                <input type="file" id="logo-upload" name="logo" class="hidden">
                            </label>
                        </div>
                        <div class="flex-1 space-y-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">WhatsApp / Celular</label>
                                <input name="phone" value="<?=htmlspecialchars($settings['phone'] ?? '')?>" class="mask-phone w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="(00) 00000-0000">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">E-mail Comercial</label>
                                <input type="email" name="email" value="<?=htmlspecialchars($settings['email'] ?? '')?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="contato@empresa.com.br">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="flex items-center justify-end gap-3 pt-4">
                <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Document Mask
        const docInput = document.getElementById('document');
        const applyMask = (v) => {
            v = v.replace(/\D/g, '');
            if(v.length <= 11) return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4").substring(0, 14);
            return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5").substring(0, 18);
        };
        docInput.value = applyMask(docInput.value);
        docInput.addEventListener('input', e => e.target.value = applyMask(e.target.value));
    
        // Logo Preview
        const logoInput = document.getElementById('logo-upload');
        logoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const container = logoInput.parentElement.parentElement.querySelector('div');
                    container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-contain p-2">`;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    </script>

<?php include __DIR__.'/../views/footer.php'; ?>
