<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

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
  $parent_id = $_SESSION['company_id']; // The current tenant is the parent of this new record
  
  // Define 7 days trial
  $trial_ends_at = date('Y-m-d H:i:s', strtotime('+7 days'));
  $sub_status = ($status === 'active') ? 'trialing' : 'inactive';

  $stmt = $pdo->prepare('INSERT INTO companies (name,fantasy_name,document,status,subscription_status,trial_ends_at,division,cep,address,number,neighborhood,city,state,cpf,person_type,date_nascto,parent_company_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $stmt->execute([$name, $fantasy, $doc, $status, $sub_status, $trial_ends_at, $division, $cep, $address, $number, $neighborhood, $city, $state, $cpf, $person_type, $date_nascto, $parent_id]);
  header('Location: companies.php');exit;
}
?>
<?php include __DIR__.'/../views/header.php'; ?>
<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="companies.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Nova Empresa / Parceiro</h2>
    </div>

    <form method="post" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block mb-1 text-sm font-medium text-slate-700">Divisão</label>
                    <select name="division" required class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                        <option value="Clientes">Clientes</option>
                        <option value="Fornecedores">Fornecedores</option>
                        <option value="Profissionais">Profissionais</option>
                        <option value="Outros">Outros</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-slate-700">Status</label>
                    <select name="status" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-slate-700">Razão Social</label>
                    <div class="relative">
                        <input name="name" id="name" required class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                        <button type="button" id="btn-cnpj" class="absolute right-2 top-1.5 bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-xs font-bold hover:bg-blue-100 transition-colors">Consultar CNPJ</button>
                    </div>
                </div>
                
                <div>
                    <label class="block mb-1 text-sm font-medium text-slate-700">Nome Fantasia</label>
                    <input name="fantasy_name" id="fantasy_name" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-slate-700">CNPJ</label>
                    <input name="document" id="document" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700" placeholder="00.000.000/0000-00">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-slate-700">Tipo de Pessoa</label>
                    <select name="person_type" id="person_type" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                        <option value="Juridica">Jurídica</option>
                        <option value="Fisica">Física</option>
                    </select>
                </div>

                <div>
                    <label id="lbl_date_nascto" class="block mb-1 text-sm font-medium text-slate-700">Data de Fundação</label>
                    <input type="date" name="date_nascto" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-slate-700">CPF</label>
                    <div class="flex gap-2">
                        <input name="cpf" id="cpf" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700" placeholder="000.000.000-00">
                        <button type="button" id="btn-cpf" class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-xl border border-indigo-200 hover:bg-indigo-200 transition-all font-bold text-xs whitespace-nowrap">Consultar CPF</button>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-6 mb-6">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Endereço</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-slate-700">CEP</label>
                        <div class="relative">
                            <input name="cep" id="cep" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                            <i id="cep-loading" class="fas fa-spinner fa-spin absolute right-3 top-3 text-slate-400 hidden"></i>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-medium text-slate-700">Endereço</label>
                        <input name="address" id="address" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-slate-700">Número</label>
                        <input name="number" id="number" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-slate-700">Bairro</label>
                        <input name="neighborhood" id="neighborhood" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block mb-1 text-sm font-medium text-slate-700">Cidade</label>
                            <input name="city" id="city" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-slate-700">UF</label>
                            <input name="state" id="state" maxlength="2" class="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 text-center">
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-100">Salvar Registro</button>
            </div>
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
            lblDateNascto.innerText = 'Data de Nascimento';
        } else {
            lblDateNascto.innerText = 'Data de Fundação';
        }
    });

    // CNPJ Mask and Lookup
    const docInput = document.getElementById('document');
    const btnCnpj = document.getElementById('btn-cnpj');

    docInput.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '');
        v = v.replace(/^(\d{2})(\d)/, '$1.$2');
        v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
        e.target.value = v.substring(0, 18);
    });

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

    cpfInput.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = v.substring(0, 14);
    });

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
});
</script>

<?php include __DIR__.'/../views/footer.php'; ?>

<?php include __DIR__.'/../views/footer.php'; ?>
