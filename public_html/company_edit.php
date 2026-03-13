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
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <a href="companies.php" class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
                    <i class="fas fa-arrow-left text-xs"></i>
                </a>
                <h2 class="text-lg font-bold text-gray-800">Editar Empresa / Parceiro</h2>
            </div>
            <a href="companies.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    
        <form method="post" enctype="multipart/form-data" class="space-y-2">
            <!-- Basic Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-1.5 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2 font-bold text-[8px] text-gray-400 uppercase tracking-widest">
                    <i class="fas fa-info-circle text-indigo-500"></i> Informações Gerais
                </div>
                <div class="p-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-1.5">
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Divisão</label>
                            <select name="division" required class="w-full border-gray-200 border p-2 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                                <option value="Clientes" <?= $company['division']=='Clientes'?'selected':'' ?>>Clientes</option>
                                <option value="Fornecedores" <?= $company['division']=='Fornecedores'?'selected':'' ?>>Fornecedores</option>
                                <option value="Profissionais" <?= $company['division']=='Profissionais'?'selected':'' ?>>Profissionais</option>
                                <option value="Outros" <?= $company['division']=='Outros'?'selected':'' ?>>Outros</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Status</label>
                            <select name="status" class="w-full border-gray-200 border p-2 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                                <option value="active" <?= $company['status']=='active'?'selected':'' ?>>Ativo</option>
                                <option value="inactive" <?= $company['status']=='inactive'?'selected':'' ?>>Inativo</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Tipo de Pessoa</label>
                            <select name="person_type" id="person_type" class="w-full border-gray-200 border p-2 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                                <option value="Juridica" <?= $company['person_type']=='Juridica'?'selected':'' ?>>Jurídica</option>
                                <option value="Fisica" <?= $company['person_type']=='Fisica'?'selected':'' ?>>Física</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-1.5">
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Razão Social / Nome</label>
                            <div class="relative">
                                <input name="name" id="name" value="<?=htmlspecialchars($company['name'])?>" required class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                                <button type="button" id="btn-cnpj" class="absolute right-1 top-1 bg-blue-50 text-blue-600 px-2 py-0.5 rounded-lg text-[10px] font-bold hover:bg-blue-100 transition-colors">CNPJ</button>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Doc (CNPJ)</label>
                            <input name="document" id="document" value="<?=htmlspecialchars($company['document'])?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-1.5">
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Nome Fantasia</label>
                            <input name="fantasy_name" id="fantasy_name" value="<?=htmlspecialchars($company['fantasy_name'])?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                        </div>
                        <div>
                            <label id="lbl_date_nascto" class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block"><?= $company['person_type'] == 'Fisica' ? 'Nascimento' : 'Fundação' ?></label>
                            <input type="date" name="date_nascto" value="<?= $company['date_nascto'] ?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">CPF</label>
                            <div class="flex gap-1.5">
                                <input name="cpf" id="cpf" value="<?=htmlspecialchars($company['cpf'])?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                                <button type="button" id="btn-cpf" class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded-xl border border-indigo-100 hover:bg-indigo-100 transition-all font-bold text-[9px] whitespace-nowrap">CPF</button>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-2">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-1.5">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">CEP</label>
                                <div class="relative">
                                    <input name="cep" id="cep" value="<?=htmlspecialchars($company['cep'])?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                                    <i id="cep-loading" class="fas fa-spinner fa-spin absolute right-2.5 top-2.5 text-slate-400 hidden"></i>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Endereço</label>
                                <input name="address" id="address" value="<?=htmlspecialchars($company['address'])?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Número</label>
                                <input name="number" id="number" value="<?=htmlspecialchars($company['number'])?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Bairro</label>
                                <input name="neighborhood" id="neighborhood" value="<?=htmlspecialchars($company['neighborhood'])?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">Cidade</label>
                                <input name="city" id="city" value="<?=htmlspecialchars($company['city'])?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-0.5 block">UF</label>
                                <input name="state" id="state" value="<?=htmlspecialchars($company['state'])?>" maxlength="2" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs text-center">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
    
            <!-- Contact & Visual -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-1.5 border-b border-gray-50 bg-gray-50/50 flex items-center gap-2 font-bold text-[8px] text-gray-400 uppercase tracking-widest">
                    <i class="fas fa-id-card text-emerald-500"></i> Contato e Identidade
                </div>
                <div class="p-3">
                    <div class="flex items-center gap-3">
                        <div class="relative group">
                            <div class="w-12 h-12 bg-gray-50 rounded-2xl border border-dashed border-gray-200 flex items-center justify-center overflow-hidden">
                                <?php if(!empty($settings['logo_path'])): ?>
                                    <img src="/<?= $settings['logo_path'] ?>" id="logo-preview-img" class="w-full h-full object-contain p-1">
                                <?php else: ?>
                                    <i class="fas fa-camera text-lg text-gray-200"></i>
                                <?php endif; ?>
                            </div>
                            <label for="logo-upload" class="absolute -bottom-1 -right-1 w-5 h-5 bg-indigo-600 text-white rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:bg-indigo-700 transition-colors">
                                <i class="fas fa-pencil-alt text-[7px]"></i>
                                <input type="file" id="logo-upload" name="logo" class="hidden">
                            </label>
                        </div>
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight block mb-0.5">WhatsApp</label>
                                <input name="phone" value="<?=htmlspecialchars($settings['phone'] ?? '')?>" class="mask-phone w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs" placeholder="(00) 00000-0000">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight block mb-0.5">E-mail</label>
                                <input type="email" name="email" value="<?=htmlspecialchars($settings['email'] ?? '')?>" class="w-full border-gray-200 border p-1.5 rounded-xl focus:ring-1 focus:ring-indigo-500 outline-none transition-all text-xs" placeholder="contato@empresa.com.br">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="w-full py-2 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all text-sm">
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
