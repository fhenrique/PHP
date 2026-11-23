<?php
require_once __DIR__ . '/config.php';

// Se já está logado, vai direto pro dashboard
if (!empty($_SESSION['spa_id'])) {
  header("Location: pages/dashboard.php"); exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';

  // 1º tenta em usuarios (Administrador / Profissional / Visualizador)
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email=? AND ativo=1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if ($u && password_verify($senha, $u['senha'])) {
    $_SESSION['spa_id'] = $u['spa_id'];
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['nome'] = $u['nome'];
    $_SESSION['email'] = $u['email'];
    $_SESSION['perfil'] = $u['perfil'];
    $_SESSION['profissional_id'] = $u['profissional_id'];
    header("Location: pages/dashboard.php"); exit;
  }

  // 2º fallback: login da clínica (tabela spas) = Administrador
  $stmt = $pdo->prepare("SELECT * FROM spas WHERE email=?");
  $stmt->execute([$email]);
  $spa = $stmt->fetch();

  if ($spa && password_verify($senha, $spa['senha'])) {
    $_SESSION['spa_id'] = $spa['id'];
    $_SESSION['user_id'] = $spa['id'];
    $_SESSION['nome'] = $spa['responsavel'] ?: $spa['nome'];
    $_SESSION['email'] = $spa['email'];
    $_SESSION['perfil'] = 'Administrador';
    $_SESSION['profissional_id'] = null;
    header("Location: pages/dashboard.php"); exit;
  }

  $erro = 'E-mail ou senha incorretos. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Entrar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body{ background:linear-gradient(135deg,#FCEFF8 0%, #E9D5F5 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',system-ui,sans-serif; }
  .card-login{ background:#fff; border-radius:24px; box-shadow:0 20px 50px rgba(122,74,122,.18); overflow:hidden; max-width:860px; width:100%; }
  .lado-esq{ background:linear-gradient(180deg,#FCEFF8,#E9D5F5); padding:28px; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; }
  /* ✅ NOVO: logo no lugar do nome Serenità SPA & ESTÉTICA */
  .logo-login{ width:100%; max-width:320px; height:auto; border-radius:18px; display:block; }
  .btn-lilas{ background:#D8A8E0; color:#fff; border:none; border-radius:12px; padding:12px; font-weight:700; width:100%; }
  .btn-lilas:hover{ background:#C795D0; color:#fff; }
  .form-control{ border-radius:12px; padding:12px 14px; }
  @media(max-width:768px){ .lado-esq{ display:none; } }
</style>
</head>
<body>
<div class="container d-flex justify-content-center"><div class="card-login row g-0 mx-auto">
  <div class="col-md-5 lado-esq">
    <!-- ✅ ANTES: 🌸 Serenità / SPA & ESTÉTICA | AGORA: sua logo -->
    <img src="imagens/logo_v1.png" alt="Serenità - Beleza & Estética" class="logo-login">
    <p class="text-muted mt-3 mb-0">Sua clínica organizada, bonita e sem papelada.</p>
    <div class="mt-4 small text-start">
      <p class="mb-1">✅ Agenda, pacientes e faturamento</p>
      <p class="mb-1">✅ Pacotes e estoque</p>
      <p class="mb-0">✅ Relatórios em tempo real</p>
    </div>
  </div>
  <div class="col-md-7 p-4 p-md-5">
    <h4 class="fw-bold mb-1">Que bom que você voltou! 💜</h4>
    <p class="text-muted small mb-4">Entre com seu e-mail e senha do Serenitá</p>

    <?php if($erro): ?><div class="alert alert-danger" style="border-radius:12px;"><?= $erro ?></div><?php endif; ?>

    <form method="POST">
      <label class="form-label small fw-bold">E-mail</label>
      <input name="email" type="email" class="form-control mb-3" placeholder="seu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      <label class="form-label small fw-bold">Senha</label>
      <div class="input-group mb-2"><input name="senha" id="senha" type="password" class="form-control" placeholder="Sua senha" required><button type="button" class="btn btn-light border" onclick="var s=document.getElementById('senha'); s.type = s.type=='password'?'text':'password';">👁️</button></div>
      <button class="btn btn-lilas mt-3">Entrar →</button>
    </form>

    <p class="text-muted small text-center mt-3 mb-0">🌸 • Serenità v1.0 • 🌸</p>
  </div>
</div></div>
</body>
</html>