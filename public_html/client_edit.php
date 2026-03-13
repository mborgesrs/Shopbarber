<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$id = $_GET['id'] ?? null;
$error = '';
$success = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  // accept id from POST (when form posts without querystring)
  $id = $_POST['id'] ?? $id;
  if(!$id){ header('Location: clients.php');exit; }
  $name = trim($_POST['name'] ?? '');
  if($name === ''){ $error = 'O nome é obrigatório.'; }
  else{
    try{
      $stmt = $pdo->prepare('UPDATE clients SET name=:name,email=:email,phone=:phone,company=:company,notes=:notes,date_nascto=:date_nascto,division=:division,cep=:cep,address=:address,number=:number,neighborhood=:neighborhood,city=:city,state=:state,cpf=:cpf,cnpj=:cnpj,person_type=:person_type WHERE id=:id');
      $date_nascto = !empty($_POST['date_nascto']) ? $_POST['date_nascto'] : null;
      $stmt->execute([
        ':name'=>$name,
        ':email'=>$_POST['email']?:null,
        ':phone'=>$_POST['phone']?:null,
        ':company'=>$_POST['company']??null,
        ':notes'=>$_POST['notes']?:null,
        ':date_nascto'=>$date_nascto,
        ':division'=>$_POST['division']??'Clientes',
        ':cep'=>$_POST['cep']??null,
        ':address'=>$_POST['address']??null,
        ':number'=>$_POST['number']??null,
        ':neighborhood'=>$_POST['neighborhood']??null,
        ':city'=>$_POST['city']??null,
        ':state'=>$_POST['state']??null,
        ':cpf'=>$_POST['cpf']??null,
        ':cnpj'=>$_POST['cnpj']??null,
        ':person_type'=>$_POST['person_type']??'Fisica',
        ':id'=>$id
      ]);
      $success = 'Pessoa atualizada com sucesso.';
    }catch(PDOException $e){
      $error = 'Erro ao atualizar: '.$e->getMessage();
    }
  }
}

if(!$id){ header('Location: clients.php');exit; }
$stmt = $pdo->prepare('SELECT * FROM clients WHERE id=?'); $stmt->execute([$id]); $client = $stmt->fetch();
if(!$client){ header('Location: clients.php');exit; }
?>
<?php include __DIR__.'/../views/header.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-4">
    <div class="flex items-center gap-4 mb-6">
        <a href="clients.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Editar Pessoa</h2>
    </div>

    <?php if($error): ?>
        <div class="bg-red-50 border border-red-100 text-red-700 p-4 rounded-2xl mb-6 flex items-center gap-3 animate-fade-in">
            <i class="fas fa-exclamation-circle"></i>
            <span class="text-sm"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 p-4 rounded-2xl mb-6 flex items-center gap-3 animate-fade-in">
            <i class="fas fa-check-circle"></i>
            <span class="text-sm"><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
        <input type="hidden" name="id" value="<?=htmlspecialchars($id)?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome Completo / Razão Social</label>
                <input name="name" id="name" value="<?=htmlspecialchars($client['name'] ?? '')?>" required class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Pessoa</label>
                <select name="person_type" id="person_type" onchange="togglePersonType()" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                    <option value="Fisica" <?= ($client['person_type'] ?? '') === 'Fisica' ? 'selected' : '' ?>>Física</option>
                    <option value="Juridica" <?= ($client['person_type'] ?? '') === 'Juridica' ? 'selected' : '' ?>>Jurídica</option>
                </select>
            </div>

            <div id="div_cpf" class="<?= ($client['person_type'] ?? 'Fisica') === 'Juridica' ? 'hidden' : '' ?>">
                <label class="block text-sm font-medium text-slate-700 mb-2">CPF</label>
                <div class="flex gap-2">
                    <input name="cpf" id="cpf" value="<?=htmlspecialchars($client['cpf'] ?? '')?>" placeholder="000.000.000-00" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700" onkeyup="handleCpf(event)">
                    <button type="button" onclick="lookupCpf()" class="bg-indigo-100 text-indigo-700 px-4 rounded-xl border border-indigo-200 hover:bg-indigo-200 transition-all font-bold text-xs whitespace-nowrap">
                        <i class="fas fa-search mr-1"></i> Consultar
                    </button>
                </div>
            </div>

            <div id="div_cnpj" class="<?= ($client['person_type'] ?? 'Fisica') === 'Fisica' ? 'hidden' : '' ?>">
                <label class="block text-sm font-medium text-slate-700 mb-2">CNPJ</label>
                <div class="flex gap-2">
                    <input name="cnpj" id="cnpj" value="<?=htmlspecialchars($client['cnpj'] ?? '')?>" placeholder="00.000.000/0000-00" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700" onkeyup="handleCnpj(event)">
                    <button type="button" onclick="lookupCnpj()" class="bg-indigo-100 text-indigo-700 px-4 rounded-xl border border-indigo-200 hover:bg-indigo-200 transition-all font-bold text-xs whitespace-nowrap">
                        <i class="fas fa-search mr-1"></i> Consultar
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Divisão</label>
                <select name="division" id="division" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                    <option value="Clientes" <?= ($client['division'] ?? '') === 'Clientes' ? 'selected' : '' ?>>Clientes</option>
                    <option value="Fornecedores" <?= ($client['division'] ?? '') === 'Fornecedores' ? 'selected' : '' ?>>Fornecedores</option>
                    <option value="Profissionais" <?= ($client['division'] ?? '') === 'Profissionais' ? 'selected' : '' ?>>Profissionais</option>
                    <option value="Outros" <?= ($client['division'] ?? '') === 'Outros' ? 'selected' : '' ?>>Outros</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">CEP</label>
                <div class="flex gap-2">
                    <input name="cep" id="cep" value="<?=htmlspecialchars($client['cep'] ?? '')?>" placeholder="00000-000" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                    <button type="button" onclick="lookupCep()" class="bg-slate-100 text-slate-600 px-4 rounded-xl border border-slate-200 hover:bg-slate-200 transition-all">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Endereço</label>
                    <input name="address" id="address" value="<?=htmlspecialchars($client['address'] ?? '')?>" placeholder="Rua, Av..." class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nº</label>
                    <input name="number" id="number" value="<?=htmlspecialchars($client['number'] ?? '')?>" placeholder="123" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:col-span-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Bairro</label>
                    <input name="neighborhood" id="neighborhood" value="<?=htmlspecialchars($client['neighborhood'] ?? '')?>" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Cidade</label>
                    <input name="city" id="city" value="<?=htmlspecialchars($client['city'] ?? '')?>" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">UF</label>
                    <input name="state" id="state" value="<?=htmlspecialchars($client['state'] ?? '')?>" maxlength="2" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700 uppercase">
                </div>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">E-mail</label>
                    <input name="email" id="email" value="<?=htmlspecialchars($client['email'] ?? '')?>" type="email" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Telefone</label>
                    <input name="phone" id="phone" value="<?=htmlspecialchars($client['phone'] ?? '')?>" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700" maxlength="15" onkeyup="handlePhone(event)">
                </div>
                <div>
                    <label id="lbl_date_nascto" class="block text-sm font-medium text-slate-700 mb-2"><?= ($client['person_type'] ?? '') === 'Juridica' ? 'Data de Fundação' : 'Data de Nascimento' ?></label>
                    <input type="date" name="date_nascto" value="<?=htmlspecialchars($client['date_nascto'] ?? '')?>" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700">
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Observações</label>
                <textarea name="notes" class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none text-slate-700 min-h-[60px]"><?=htmlspecialchars($client['notes'] ?? '')?></textarea>
            </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
            <a href="clients.php" class="bg-white border border-slate-300 text-slate-700 px-6 py-2.5 rounded-xl hover:bg-slate-50 font-medium transition-colors">Voltar</a>
            <button class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">Salvar Alterações</button>
        </div>
    </form>
</div>

<?php include __DIR__.'/../views/footer.php'; ?>

<script>
function togglePersonType() {
    const type = document.getElementById('person_type').value;
    const divCpf = document.getElementById('div_cpf');
    const divCnpj = document.getElementById('div_cnpj');
    const lblDateNascto = document.getElementById('lbl_date_nascto');
    
    if (type === 'Fisica') {
        divCpf.classList.remove('hidden');
        divCnpj.classList.add('hidden');
        lblDateNascto.innerText = 'Data de Nascimento';
    } else {
        divCpf.classList.add('hidden');
        divCnpj.classList.remove('hidden');
        lblDateNascto.innerText = 'Data de Fundação';
    }
}

async function lookupCpf() {
    const cpf = document.getElementById('cpf').value.replace(/\D/g, '');
    if (cpf.length !== 11) {
        alert('CPF deve ter 11 dígitos');
        return;
    }
    
    const btn = document.querySelector('button[onclick="lookupCpf()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    try {
        const response = await fetch(`api/cpf_lookup.php?cpf=${cpf}`);
        if (!response.ok) {
            const errorData = await response.json();
            alert(errorData.message || 'Erro na consulta');
            return;
        }
        const data = await response.json();
        
        if (data.nome) {
            document.getElementById('name').value = data.nome;
            if (data.nascimento) {
                // Ensure format YYYY-MM-DD
                let dob = data.nascimento;
                if (dob.includes('/')) {
                    const parts = dob.split('/');
                    if (parts[0].length === 4) dob = parts.join('-');
                    else dob = `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
                document.querySelector('input[name="date_nascto"]').value = dob;
            }
        } else if (data.message) {
            alert(data.message);
        }
    } catch (error) {
        console.error('Erro ao buscar CPF:', error);
        alert('Erro ao conectar com a API de CPF');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function lookupCnpj() {
    const cnpj = document.getElementById('cnpj').value.replace(/\D/g, '');
    if (cnpj.length !== 14) {
        alert('CNPJ deve ter 14 dígitos');
        return;
    }
    
    const btn = document.querySelector('button[onclick="lookupCnpj()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    try {
        const response = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpj}`);
        if (!response.ok) {
            alert('CNPJ não encontrado ou erro na consulta');
            return;
        }
        const data = await response.json();
        
        if (data.razao_social) {
            document.getElementById('name').value = data.razao_social;
            document.getElementById('address').value = data.logradouro;
            document.getElementById('neighborhood').value = data.bairro;
            document.getElementById('city').value = data.municipio;
            document.getElementById('state').value = data.uf;
            document.getElementById('number').value = data.numero;
            document.getElementById('cep').value = data.cep;
            document.getElementById('email').value = data.email || '';
            document.getElementById('phone').value = (data.ddd_telefone_1 || '').replace(/\D/g, '');
            handlePhone({target: document.getElementById('phone')});
        }
    } catch (error) {
        console.error('Erro ao buscar CNPJ:', error);
        alert('Erro ao conectar com a API de CNPJ');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function lookupCep() {
    const cepInput = document.getElementById('cep');
    const cep = cepInput.value.replace(/\D/g, '');
    if (cep.length !== 8) return;

    try {
        const response = await fetch(`https://brasilapi.com.br/api/cep/v1/${cep}`);
        const data = await response.json();

        if (data.street) {
            document.getElementById('address').value = data.street;
            document.getElementById('neighborhood').value = data.neighborhood;
            document.getElementById('city').value = data.city;
            document.getElementById('state').value = data.state;
            document.getElementById('number').focus();
            cepInput.value = applyCepMask(data.cep);
        }
    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
    }
}

function applyCepMask(v) {
    if (!v) return "";
    v = v.replace(/\D/g, "");
    return v.replace(/^(\d{5})(\d)/, "$1-$2").substring(0, 9);
}

document.addEventListener('DOMContentLoaded', function() {
    const cepInput = document.getElementById('cep');
    cepInput.addEventListener('input', function(e) {
        e.target.value = applyCepMask(e.target.value);
    });
    cepInput.value = applyCepMask(cepInput.value);
});

function handlePhone(event) {
    let input = event.target;
    input.value = phoneMask(input.value);
}

function handleCpf(event) {
    let input = event.target;
    input.value = cpfMask(input.value);
}

function phoneMask(value) {
    if (!value) return "";
    value = value.replace(/\D/g, "");
    if (value.length > 11) value = value.slice(0, 11);
    value = value.replace(/(\d{2})(\d)/, "($1) $2");
    value = value.replace(/(\d{5})(\d)/, "$1-$2");
    return value;
}

function cpfMask(value) {
    if (!value) return "";
    value = value.replace(/\D/g, "");
    if (value.length > 11) value = value.slice(0, 11);
    value = value.replace(/(\d{3})(\d)/, "$1.$2");
    value = value.replace(/(\d{3})(\d)/, "$1.$2");
    value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    return value;
}

function cnpjMask(value) {
    if (!value) return "";
    value = value.replace(/\D/g, "");
    if (value.length > 14) value = value.slice(0, 14);
    value = value.replace(/^(\d{2})(\d)/, "$1.$2");
    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
    value = value.replace(/\.(\d{3})(\d)/, ".$1/$2");
    value = value.replace(/(\d{4})(\d)/, "$1-$2");
    return value;
}
</script>
