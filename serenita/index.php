<?php
require_once 'config.php';
if (estaLogado()) { header("Location: pages/dashboard.php"); exit; }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  $stmt = $pdo->prepare("SELECT * FROM spas WHERE email = ?");
  $stmt->execute([$email]);
  $spa = $stmt->fetch();
  if ($spa && password_verify($senha, $spa['senha'])) {
    $_SESSION['spa_id'] = $spa['id'];
    $_SESSION['spa_nome'] = $spa['nome'];
    header("Location: pages/dashboard.php");
    exit;
  } else {
    $erro = 'E-mail ou senha inválidos.';
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenitá - Entrar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-bg">
<div class="container d-flex align-items-center justify-content-center min-vh-100">
  <div class="card login-card border-0 shadow-lg" style="max-width:420px; width:100%">
    <div class="card-body p-4 p-md-5 text-center">
      <div class="logo-circle mb-3">🌿</div>
      <h1 class="brand-title">Serenitá</h1>
      <p class="text-muted small mb-4">A calma da sua agenda, a beleza do seu negócio</p>
      <?php if ($erro): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
      <form method="POST" class="text-start">
        <label class="form-label small fw-bold">E-mail</label>
        <input type="email" name="email" class="form-control mb-3" required placeholder="seu@spa.com.br" value="teste@serenita.com">
        <label class="form-label small fw-bold">Senha</label>
        <input type="password" name="senha" class="form-control mb-3" required placeholder="Sua senha" value="123456">
        <button class="btn btn-serenita w-100 py-2 fw-bold">Entrar</button>
      </form>
      <p class="small text-muted mt-3 mb-0">Teste: teste@serenita.com / 123456</p>
    </div>
  </div>
</div>
</body>
</html>