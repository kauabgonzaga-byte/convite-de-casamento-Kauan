<?php
declare(strict_types=1);

require __DIR__ . '/../includes/functions.php';

if (is_admin()) {
    redirect('index.php');
}

$error = flash('admin_error');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf($_POST['csrf'] ?? null)) {
        $error = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } else {
        $username = clean_text($_POST['username'] ?? '', 80);
        $password = (string) ($_POST['password'] ?? '');
        $config = config();
        if (hash_equals($config['admin_username'], $username) && password_verify($password, $config['admin_password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['wedding_admin'] = true;
            redirect('index.php');
        }
        $error = 'Usuário ou senha incorretos.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel administrativo</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-login-body">
  <main class="login-card">
    <p class="admin-kicker">Kauã + Débora</p>
    <h1>Painel administrativo</h1>
    <p>Use as credenciais configuradas para administrar a lista e as confirmações.</p>
    <?php if ($error): ?><p class="admin-notice error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <label for="username">Usuário</label>
      <input id="username" name="username" autocomplete="username" required autofocus>
      <label for="password">Senha</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button type="submit">Entrar</button>
    </form>
  </main>
</body>
</html>
