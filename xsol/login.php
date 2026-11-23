<link rel="icon" type="image/png" href="favicon.png">

<?php
//login.php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
  header("Location: pages/dashboard.php"); exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';

  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email=? AND ativo=1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if ($u && password_verify($senha, $u['senha'])) {
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['nome'] = $u['nome'];
    $_SESSION['email'] = $u['email'];
    $_SESSION['perfil'] = $u['perfil'];
    header("Location: pages/dashboard.php"); exit;
  }

  $erro = 'E-mail ou senha incorretos. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>XSOL - Sistema Cloud</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{ background:linear-gradient(135deg,#FFF7E0 0%, #FFE9A8 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',system-ui,sans-serif; }
  .card-login{ background:#fff; border-radius:24px; box-shadow:0 20px 50px rgba(180,120,0,.18); overflow:hidden; max-width:860px; width:100%; }
  .lado-esq{ background:linear-gradient(180deg,#FFF7E0,#FFE9A8); padding:36px; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; }
  .logo-login{ max-width: 240px; width: 100%; height: auto; display: block; margin: 0 auto; }
  .btn-sol{ background:#F59E0B; color:#fff; border:none; border-radius:12px; padding:12px; font-weight:700; width:100%; }
  .btn-sol:hover{ background:#E08E00; color:#fff; }
  .form-control{ border-radius:12px; padding:12px 14px; }
  @media(max-width:768px){ .lado-esq{ display:none; } }
</style>
</head>
<body>
<div class="container d-flex justify-content-center"><div class="card-login row g-0 mx-auto">
  <div class="col-md-5 lado-esq">
    <img src="imagens/logo_v1.png" alt="XSOL - Energia Solar" class="logo-login">
    <p class="text-muted mt-3">Gestão de instalação e manutenção de equipamentos à energia solar.</p>
  </div>
  <div class="col-md-7 p-4 p-md-5">
    <h4 class="fw-bold mb-1">Bem-vindo à XSOL!️</h4>
    <p class="text-muted small mb-4">Entre com seu e-mail e senha</p>

    <?php if($erro): ?><div class="alert alert-danger" style="border-radius:12px;"><?= $erro ?></div><?php endif; ?>

    <form method="POST">
      <label class="form-label small fw-bold">E-mail</label>
      <input name="email" type="email" class="form-control mb-3" placeholder="seu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      <label class="form-label small fw-bold">Senha</label>
      <div class="input-group mb-2"><input name="senha" id="senha" type="password" class="form-control" placeholder="Sua senha" required><button type="button" class="btn btn-light border" onclick="var s=document.getElementById('senha'); s.type = s.type=='password'?'text':'password';">👁️</button></div>
      <button class="btn btn-sol mt-3">Entrar →</button>
    </form>

    <p class="text-muted small text-center mt-3 mb-0"> • XSOL Cloud v1.0 • ️</p>
	
  </div>
</div></div>
</body>
</html>