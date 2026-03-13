<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../db.php';

$company_id = $_SESSION['company_id'];
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (!is_uploaded_file($file)) {
        $error = "Por favor, selecione um arquivo.";
    } else {
        // Detect if it's a binary file (like .xlsx)
        $fh = fopen($file, 'rb');
        $header = fread($fh, 4);
        fclose($fh);
        
        if (strpos($header, "PK\x03\x04") === 0) {
            $error = "O arquivo enviado parece ser um Excel (.xlsx). Por favor, salve-o como **CSV (Separado por ponto e vírgula)** no Excel e tente novamente.";
        } else {
            $handle = fopen($file, "r");
            
            // Check for BOM and skip it
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Skip header
            fgetcsv($handle, 1000, ";");

            $imported = 0;
            $categories_map = [];
            
            // Fetch existing categories
            $stmt_cat = $pdo->prepare("SELECT id, name FROM categories WHERE company_id = ?");
            $stmt_cat->execute([$company_id]);
            while ($row = $stmt_cat->fetch()) {
                $categories_map[mb_strtolower($row['name'])] = $row['id'];
            }

            try {
                $pdo->beginTransaction();
                
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    // Force UTF-8 conversion if not already (Excel PT-BR defaults to Windows-1252)
                    foreach ($data as $key => $value) {
                        if (!empty($value) && !mb_check_encoding($value, 'UTF-8')) {
                            // Try Windows-1252 first as it's the Excel standard in Brazil
                            $data[$key] = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
                        }
                    }

                    if (count($data) < 2) continue;
                    
                    $cat_name = trim($data[0] ?? '');
                    $prod_name = trim($data[1] ?? '');
                    $unit = trim($data[2] ?? 'un');
                    $balance = (float)str_replace(',', '.', trim($data[3] ?? '0'));
                    $pr_custo = (float)str_replace(',', '.', trim($data[4] ?? '0'));
                    $price = (float)str_replace(',', '.', trim($data[5] ?? '0'));
                    
                    if (empty($prod_name)) continue;

                    $cat_id = $categories_map[mb_strtolower($cat_name)] ?? null;

                    $stmt_check = $pdo->prepare("SELECT id FROM products WHERE company_id = ? AND name = ?");
                    $stmt_check->execute([$company_id, $prod_name]);
                    $existing = $stmt_check->fetch();

                    if ($existing) {
                        $stmt_upd = $pdo->prepare("UPDATE products SET category_id = ?, unit = ?, balance = ?, min_stock = ?, pr_custo = ?, pr_medio = ?, price = ? WHERE id = ?");
                        $stmt_upd->execute([$cat_id, $unit, $balance, $balance, $pr_custo, $pr_custo, $price, $existing['id']]);
                    } else {
                        $stmt_ins = $pdo->prepare("INSERT INTO products (company_id, category_id, name, unit, type, balance, min_stock, pr_custo, pr_medio, price) VALUES (?, ?, ?, ?, 'Ativo', ?, ?, ?, ?, ?)");
                        $stmt_ins->execute([$company_id, $cat_id, $prod_name, $unit, $balance, $balance, $pr_custo, $pr_custo, $price]);
                    }
                    $imported++;
                }
                
                $pdo->commit();
                $msg = "Importação concluída! $imported itens processados.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erro na importação: " . $e->getMessage();
            }
            
            fclose($handle);
        }
    }
}
?>

<?php include __DIR__ . '/../views/header.php'; ?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center gap-4 mb-8">
        <a href="products.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Importar Produtos</h2>
            <p class="text-sm text-gray-400">Suba sua planilha CSV para atualizar o catálogo.</p>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <span class="font-bold text-sm"><?= htmlspecialchars($msg) ?></span>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="bg-rose-50 border border-rose-100 text-rose-700 p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <div class="flex flex-col">
                <span class="font-bold text-sm">Atenção!</span>
                <p class="text-sm"><?= $error ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <h3 class="font-bold text-gray-700 mb-4 uppercase text-xs tracking-wider">Instruções do Arquivo</h3>
            <div class="space-y-4 text-sm text-gray-600 mb-8">
                <p>O arquivo deve ser um **CSV (Separado por ponto e vírgula)**.</p>
                <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                    <p class="font-bold text-indigo-700 mb-2 italic">Como salvar no Excel:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs text-indigo-600">
                        <li>Vá em **Arquivo > Salvar Como**</li>
                        <li>Escolha o formato **CSV (Separado por ponto e vírgula) (*.csv)**</li>
                        <li>Clique em Salvar</li>
                    </ol>
                </div>
                <p>Colunas necessárias:</p>
                <ul class="list-disc list-inside space-y-1 bg-gray-50 p-4 rounded-xl border border-gray-100 font-medium font-mono text-[11px]">
                    <li>Categoria</li>
                    <li>Produto</li>
                    <li>Unidade</li>
                    <li>Estoque Mínimo (será saldo atual)</li>
                    <li>Preço Custo (R$)</li>
                    <li>Preço Venda (R$)</li>
                </ul>
            </div>

            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center hover:border-indigo-300 transition-all group cursor-pointer relative" id="drop-zone">
                    <input type="file" name="csv_file" accept=".csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="updateFileName(this)">
                    <div class="space-y-3">
                        <i class="fas fa-file-csv text-4xl text-gray-300 group-hover:text-indigo-400"></i>
                        <p class="text-gray-500 group-hover:text-gray-700 font-medium" id="file-label">Clique para selecionar seu arquivo CSV</p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest px-4 py-1 bg-gray-50 inline-block rounded-full">Apenas arquivos .csv</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="products.php" class="px-6 py-2.5 bg-white text-gray-500 rounded-xl font-bold hover:bg-gray-50 border border-gray-200 transition-colors">Cancelar</a>
                    <button class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">
                        Iniciar Importação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateFileName(input) {
    const label = document.getElementById('file-label');
    if (input.files && input.files[0]) {
        label.innerHTML = `Arquivo selecionado: <b class="text-indigo-600">${input.files[0].name}</b>`;
        label.parentElement.parentElement.classList.add('border-indigo-400');
    }
}
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>
