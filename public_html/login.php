<?php
session_start();
require_once __DIR__ . '/../db.php';

// Fetch active companies for the dropdown
$companies = $pdo->query("SELECT id, name FROM companies WHERE status = 'active' ORDER BY name")->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $company_id = $_POST['company_id'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND company_id = ? LIMIT 1');
    $stmt->execute([$username, $company_id]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['company_id'] = $user['company_id'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        header('Location: dashboard.php');exit;
    }
    $error = 'Usuário, senha ou empresa inválidos';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - <?= htmlentities($app_name) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 antialiased">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center shadow-xl shadow-blue-200 mx-auto mb-4">
                <i class="fas fa-scissors text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight"><?= htmlentities($app_name) ?></h1>
            <p class="text-slate-500 mt-2">Acesse sua conta para gerenciar sua barbearia</p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200 border border-slate-100 overflow-hidden">
            <div class="p-8">
                <?php if(!empty($error)): ?>
                    <div class="flex items-center gap-3 p-4 bg-red-50 text-red-700 rounded-2xl mb-6 text-sm font-medium border border-red-100 animate-pulse">
                        <i class="fas fa-exclamation-circle"></i>
                        <?=htmlspecialchars($error)?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Empresa</label>
                        <div class="relative">
                            <select name="company_id" class="w-full bg-slate-50 border border-slate-200 px-4 py-3 rounded-2xl text-slate-700 text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all appearance-none" required>
                                <option value="">Selecione sua empresa...</option>
                                <?php foreach($companies as $comp): ?>
                                    <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-4 text-slate-300 pointer-events-none text-xs"></i>
                            <i class="fas fa-building absolute left-4 hidden"></i> <!-- Space for icon if needed -->
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Usuário</label>
                        <div class="relative">
                            <input name="username" class="w-full bg-slate-50 border border-slate-200 pl-11 pr-4 py-3 rounded-2xl text-slate-700 text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all" placeholder="Digite seu usuário" required>
                            <i class="fas fa-user absolute left-4 top-4 text-slate-300"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Senha</label>
                        <div class="relative">
                            <input type="password" name="password" class="w-full bg-slate-50 border border-slate-200 pl-11 pr-4 py-3 rounded-2xl text-slate-700 text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all" placeholder="••••••••" required>
                            <i class="fas fa-lock absolute left-4 top-4 text-slate-300"></i>
                        </div>
                    </div>

                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-2xl shadow-lg shadow-blue-200 transition-all transform hover:scale-[1.02] active:scale-[0.98] mt-2">
                        Entrar no Sistema
                    </button>
                </form>
            </div>
            <div class="bg-slate-50 p-6 text-center border-t border-slate-100">
                <p class="text-xs text-slate-400 font-medium">© <?= date('Y') ?> <?= htmlentities($app_name) ?> — Gestão Profissional</p>
            </div>
        </div>
    </div>
</body>
</html>
<?php exit; ?>
