<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';
$return_url = $_GET['return_url'] ?? 'clients.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $stmt = $pdo->prepare('INSERT INTO clients (name,email,phone,company,notes,date_nascto,division,cep,address,number,neighborhood,city,state,cpf,cnpj,person_type,company_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $stmt->execute([
    strtoupper($_POST['name']),
    $_POST['email'],
    $_POST['phone'],
    $_POST['company']??null,
    $_POST['notes'],
    $_POST['date_nascto']?:null,
    $_POST['division']??'Clientes',
    $_POST['cep']??null,
    $_POST['address']??null,
    $_POST['number']??null,
    $_POST['neighborhood']??null,
    $_POST['city']??null,
    $_POST['state']??null,
    $_POST['cpf']??null,
    $_POST['cnpj']??null,
    $_POST['person_type']??'Fisica',
    $_SESSION['company_id']
  ]);
  header('Location: ' . $return_url);exit;
}
?>
<?php include __DIR__.'/../views/header.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-2">
    <div class="flex items-center gap-3 mb-3">
        <a href="<?= htmlspecialchars($return_url) ?>" class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <h2 class="text-lg font-bold text-gray-800">Nova Pessoa</h2>
    </div>

    <form method="post" action="client_create.php?return_url=<?= urlencode($return_url) ?>" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3 mb-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome Completo</label>
                <input name="name" id="name" required placeholder="Ex: JOÃO SILVA" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm uppercase">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo de Pessoa</label>
                <select name="person_type" id="person_type" onchange="togglePersonType()" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                    <option value="Fisica">Física</option>
                    <option value="Juridica">Jurídica</option>
                </select>
            </div>

            <div id="div_cpf">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">CPF</label>
                <div class="flex gap-2">
                    <input name="cpf" id="cpf" placeholder="000.000.000-00" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm" onkeyup="handleCpf(event)">
                    <button type="button" onclick="lookupCpf()" class="bg-indigo-50 text-indigo-700 px-4 rounded-xl border border-indigo-100 hover:bg-indigo-100 transition-all font-bold text-xs whitespace-nowrap">
                        Consultar
                    </button>
                </div>
            </div>

            <div id="div_cnpj" class="hidden">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">CNPJ</label>
                <div class="flex gap-2">
                    <input name="cnpj" id="cnpj" placeholder="00.000.000/0000-00" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm" onkeyup="handleCnpj(event)">
                    <button type="button" onclick="lookupCnpj()" class="bg-indigo-50 text-indigo-700 px-4 rounded-xl border border-indigo-100 hover:bg-indigo-100 transition-all font-bold text-xs whitespace-nowrap">
                        Consultar
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Divisão</label>
                <select name="division" id="division" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                    <option value="Clientes">Clientes</option>
                    <option value="Fornecedores">Fornecedores</option>
                    <option value="Profissionais">Profissionais</option>
                    <option value="Outros">Outros</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">CEP</label>
                <div class="flex gap-2">
                    <input name="cep" id="cep" placeholder="00000-000" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                    <button type="button" onclick="lookupCep()" class="bg-slate-50 text-slate-600 px-4 rounded-xl border border-slate-200 hover:bg-slate-100 transition-all text-sm">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Endereço</label>
                    <input name="address" id="address" placeholder="Rua, Av..." class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nº</label>
                    <input name="number" id="number" placeholder="123" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:col-span-2">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bairro</label>
                    <input name="neighborhood" id="neighborhood" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cidade</label>
                    <input name="city" id="city" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">UF</label>
                    <input name="state" id="state" maxlength="2" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 uppercase text-sm text-center">
                </div>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">E-mail</label>
                    <input name="email" id="email" type="email" placeholder="email@exemplo.com" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Telefone</label>
                    <input name="phone" id="phone" placeholder="(00) 00000-0000" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm" maxlength="15" onkeyup="handlePhone(event)">
                </div>
                <div>
                    <label id="lbl_date_nascto" class="block text-xs font-bold text-slate-500 uppercase mb-1">Nascimento</label>
                    <input type="date" name="date_nascto" class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 text-sm">
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Observações</label>
                <textarea name="notes" placeholder="Observações..." class="w-full border border-slate-300 rounded-xl p-2 focus:ring-1 focus:ring-blue-500 transition-all outline-none text-slate-700 min-h-[60px] text-sm"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-between pt-5 border-t border-slate-100">
            <a href="<?= htmlspecialchars($return_url) ?>" class="bg-white border border-slate-200 text-slate-600 px-5 py-2 rounded-xl hover:bg-slate-50 font-bold transition-colors text-sm">Voltar</a>
            <button class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 text-sm">Cadastrar Pessoa</button>
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
    // apply mask on load if there is a value
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

function handleCnpj(event) {
    let input = event.target;
    input.value = cnpjMask(input.value);
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
