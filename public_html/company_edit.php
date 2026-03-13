<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$id = $_GET['id'] ?? null;
if(!$id){ header('Location: companies.php'); exit; }

// Security: User can edit their own company or any company where parent_company_id = their company_id
$stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
$stmt->execute([$id]);
$company = $stmt->fetch();
if(!$company){ header('Location: companies.php'); exit; }

if($id != $_SESSION['company_id'] && $company['parent_company_id'] != $_SESSION['company_id']){
    header('Location: companies.php');
    exit;
}

    // Get settings info for this company
    $stmtS = $pdo->prepare("SELECT * FROM settings WHERE company_id = ? LIMIT 1");
    $stmtS->execute([$id]);
    $settings = $stmtS->fetch();
    
    if($_SERVER['REQUEST_METHOD']==='POST'){
      $name = $_POST['name'];
      $fantasy = $_POST['fantasy_name'];
      $doc = $_POST['document'];
      $status = $_POST['status'];
      $division = $_POST['division'] ?? 'Outros';
      $cep = $_POST['cep'] ?? null;
      $address = $_POST['address'] ?? null;
      $number = $_POST['number'] ?? null;
      $neighborhood = $_POST['neighborhood'] ?? null;
      $city = $_POST['city'] ?? null;
      $state = $_POST['state'] ?? null;
      $cpf = $_POST['cpf'] ?? null;
      $person_type = $_POST['person_type'] ?? 'Juridica';
      $date_nascto = $_POST['date_nascto'] ?: null;
      
      // Update companies table
      $stmt = $pdo->prepare('UPDATE companies SET name=?, fantasy_name=?, document=?, status=?, division=?, cep=?, address=?, number=?, neighborhood=?, city=?, state=?, cpf=?, person_type=?, date_nascto=? WHERE id=?');
      $stmt->execute([$name, $fantasy, $doc, $status, $division, $cep, $address, $number, $neighborhood, $city, $state, $cpf, $person_type, $date_nascto, $id]);
      
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
    
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Editar Empresa / Parceiro</h2>
                <p class="text-sm text-gray-500">Ajuste as informações cadastrais e de localização.</p>
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
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Divisão</label>
                            <select name="division" required class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                <option value="Clientes" <?= $company['division']=='Clientes'?'selected':'' ?>>Clientes</option>
                                <option value="Fornecedores" <?= $company['division']=='Fornecedores'?'selected':'' ?>>Fornecedores</option>
                                <option value="Profissionais" <?= $company['division']=='Profissionais'?'selected':'' ?>>Profissionais</option>
                                <option value="Outros" <?= $company['division']=='Outros'?'selected':'' ?>>Outros</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Status do Sistema</label>
                            <select name="status" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                <option value="active" <?= $company['status']=='active'?'selected':'' ?>>Ativo</option>
                                <option value="inactive" <?= $company['status']=='inactive'?'selected':'' ?>>Inativo</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Razão Social</label>
                            <div class="relative">
                                <input name="name" id="name" value="<?=htmlspecialchars($company['name'])?>" required class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                <button type="button" id="btn-cnpj" class="absolute right-2 top-1.5 bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-xs font-bold hover:bg-blue-100 transition-colors">Consultar CNPJ</button>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Nome Fantasia</label>
                            <input name="fantasy_name" id="fantasy_name" value="<?=htmlspecialchars($company['fantasy_name'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">CNPJ</label>
                            <input name="document" id="document" value="<?=htmlspecialchars($company['document'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">CPF</label>
                            <div class="flex gap-2">
                                <input name="cpf" id="cpf" value="<?=htmlspecialchars($company['cpf'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                <button type="button" id="btn-cpf" class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-xl border border-indigo-200 hover:bg-indigo-200 transition-all font-bold text-xs whitespace-nowrap">Consultar CPF</button>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Tipo de Pessoa</label>
                            <select name="person_type" id="person_type" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                <option value="Juridica" <?= $company['person_type']=='Juridica'?'selected':'' ?>>Jurídica</option>
                                <option value="Fisica" <?= $company['person_type']=='Fisica'?'selected':'' ?>>Física</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label id="lbl_date_nascto" class="text-xs font-bold text-gray-500 uppercase"><?= $company['person_type'] == 'Fisica' ? 'Data de Nascimento' : 'Data de Fundação' ?></label>
                            <input type="date" name="date_nascto" value="<?= $company['date_nascto'] ?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-6 mb-6">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Endereço</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">CEP</label>
                                <div class="relative">
                                    <input name="cep" id="cep" value="<?=htmlspecialchars($company['cep'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                    <i id="cep-loading" class="fas fa-spinner fa-spin absolute right-3 top-3 text-slate-400 hidden"></i>
                                </div>
                            </div>
                            <div class="md:col-span-2 space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">Endereço</label>
                                <input name="address" id="address" value="<?=htmlspecialchars($company['address'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">Número</label>
                                <input name="number" id="number" value="<?=htmlspecialchars($company['number'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">Bairro</label>
                                <input name="neighborhood" id="neighborhood" value="<?=htmlspecialchars($company['neighborhood'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="col-span-2 space-y-1">
                                    <label class="text-xs font-bold text-gray-500 uppercase">Cidade</label>
                                    <input name="city" id="city" value="<?=htmlspecialchars($company['city'])?>" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-gray-500 uppercase">UF</label>
                                    <input name="state" id="state" value="<?=htmlspecialchars($company['state'])?>" maxlength="2" class="w-full border-gray-200 border p-2.5 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-center">
                                </div>
                            </div>
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
        // Person Type Logic
        const personTypeSelect = document.getElementById('person_type');
        const lblDateNascto = document.getElementById('lbl_date_nascto');

        personTypeSelect.addEventListener('change', function() {
            if (this.value === 'Fisica') {
                lblDateNascto.innerText = 'DATA DE NASCIMENTO';
            } else {
                lblDateNascto.innerText = 'DATA DE FUNDAÇÃO';
            }
        });

        // Document Mask
        const docInput = document.getElementById('document');
        const applyMask = (v) => {
            v = v.replace(/\D/g, '');
            if(v.length <= 11) return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4").substring(0, 14);
            return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5").substring(0, 18);
        };
        docInput.value = applyMask(docInput.value);
        docInput.addEventListener('input', e => e.target.value = applyMask(e.target.value));

        // CNPJ Lookup logic
        const btnCnpj = document.getElementById('btn-cnpj');
        btnCnpj.addEventListener('click', async function() {
            const cnpj = docInput.value.replace(/\D/g, '');
            if (cnpj.length !== 14) {
                alert('CNPJ inválido');
                return;
            }
            btnCnpj.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btnCnpj.disabled = true;
            
            try {
                const resp = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpj}`);
                if (resp.ok) {
                    const data = await resp.json();
                    document.getElementById('name').value = data.razao_social;
                    document.getElementById('fantasy_name').value = data.nome_fantasia || '';
                    document.getElementById('cep').value = applyCepMask(data.cep);
                    document.getElementById('address').value = data.logradouro;
                    document.getElementById('number').value = data.numero;
                    document.getElementById('neighborhood').value = data.bairro;
                    document.getElementById('city').value = data.municipio;
                    document.getElementById('state').value = data.uf;
                } else {
                    alert('CNPJ não encontrado');
                }
            } catch (e) {
                console.error(e);
                alert('Erro ao consultar CNPJ');
            } finally {
                btnCnpj.innerHTML = 'Consultar CNPJ';
                btnCnpj.disabled = false;
            }
        });
    
        // CPF Mask and Lookup
        const cpfInput = document.getElementById('cpf');
        const btnCpf = document.getElementById('btn-cpf');

        const applyCpfMask = (v) => {
            v = v.replace(/\D/g, '');
            return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4").substring(0, 14);
        };

        cpfInput.addEventListener('input', e => e.target.value = applyCpfMask(e.target.value));

        btnCpf.addEventListener('click', async function() {
            const cpf = cpfInput.value.replace(/\D/g, '');
            if (cpf.length !== 11) {
                alert('CPF inválido');
                return;
            }
            btnCpf.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btnCpf.disabled = true;
            
            try {
                const resp = await fetch(`api/cpf_lookup.php?cpf=${cpf}`);
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.nome) {
                        document.getElementById('name').value = data.nome;
                    } else if (data.message) {
                        alert(data.message);
                    }
                } else {
                    const errorData = await resp.json();
                    alert(errorData.message || 'CPF não encontrado');
                }
            } catch (e) {
                console.error(e);
                alert('Erro ao consultar CPF');
            } finally {
                btnCpf.innerHTML = 'Consultar CPF';
                btnCpf.disabled = false;
            }
        });

        // CEP Mask and Lookup
        const cepInput = document.getElementById('cep');
        const cepLoading = document.getElementById('cep-loading');

        function applyCepMask(v) {
            v = v.replace(/\D/g, '');
            return v.replace(/^(\d{5})(\d)/, '$1-$2').substring(0, 9);
        }

        cepInput.addEventListener('input', function(e) {
            e.target.value = applyCepMask(e.target.value);
            if (e.target.value.replace(/\D/g, '').length === 8) {
                lookupCep(e.target.value.replace(/\D/g, ''));
            }
        });

        cepInput.value = applyCepMask(cepInput.value);

        async function lookupCep(cep) {
            cepLoading.classList.remove('hidden');
            try {
                const resp = await fetch(`https://brasilapi.com.br/api/cep/v1/${cep}`);
                if (resp.ok) {
                    const data = await resp.json();
                    document.getElementById('address').value = data.street;
                    document.getElementById('neighborhood').value = data.neighborhood;
                    document.getElementById('city').value = data.city;
                    document.getElementById('state').value = data.state;
                    document.getElementById('number').focus();
                }
            } catch (e) {
                console.error(e);
            } finally {
                cepLoading.classList.add('hidden');
            }
        }

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
