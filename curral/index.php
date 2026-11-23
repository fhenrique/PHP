<?php require 'config.php';
//index.php - VIDEO GRANDE PREENCHENDO O CENTRO + SEM ROLAR
if(estaLogado()){ header("Location: lobby.php"); exit; }
$erro = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email=?");
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if($u && password_verify($senha, $u['senha'])){
    $_SESSION['usuario_id'] = $u['id'];
    $_SESSION['usuario_nome'] = $u['nome'];
    $_SESSION['usuario_avatar'] = $u['avatar'];
    $_SESSION['usuario_cor'] = $u['cor'];
    header("Location: lobby.php"); exit;
  } else $erro = 'Apelido ou senha inválidos.';
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Curral - Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
html,body{height:100%;margin:0;overflow:hidden}
body{background:#0f172a;color:#fff;height:100dvh;display:flex;flex-direction:column}
.wrap{flex:1;display:flex;flex-direction:column;width:100%;max-width:480px;margin:0 auto;padding:10px 12px 10px;box-sizing:border-box;height:100dvh;overflow:hidden;gap:10px}
.img-jogo{width:100%;flex:1;min-height:0;object-fit:cover;border-radius:14px;border:2px solid #334155;box-shadow:0 8px 30px rgba(0,0,0,.45);display:block}
.titulo{flex-shrink:0}
.titulo h1{font-size:26px;line-height:1.15;margin:0}
.titulo p{font-size:13px;margin:4px 0 0}
.card-login{background:#1e293b;border:none;border-radius:16px;color:#fff;flex-shrink:0}
.card-login h4{color:#fff;font-weight:800;font-size:18px}
.card-login .form-label{color:#cbd5e1;font-weight:600;font-size:14px;margin-bottom:3px}
.card-login .form-control{padding:7px 10px;font-size:15px}
.btn-jogar{background:#f59e0b;color:#000;font-weight:800}
.btn-jogar:hover{background:#d97706;color:#000}
</style>
</head><body>
<div class="wrap">
  <!-- VIDEO PREENCHE TODO O ESPAÇO VAZIO DO CENTRO - era <img src="galinhas.mp4"> que não toca video -->
  <video src="galinhas.mp4" class="img-jogo" autoplay loop muted playsinline preload="auto"></video>
  <div class="titulo text-center"><h1>🗳️ CURRAL <br>Tema: Políticos </h1><p class="text-secondary">Conquiste o território. Corte o rastro. Sobreviva.</p></div>
  <div class="card card-login p-3">
    <h4 class="text-center mb-2">Entrar</h4>
    <?php if($erro): ?><div class="alert alert-danger py-2 mb-2"><?= $erro ?></div><?php endif; ?>
    <form method="POST" novalidate>
      <label class="form-label">Apelido</label><input name="email" type="text" class="form-control mb-2" required>
      <label class="form-label">Senha</label><input name="senha" type="password" class="form-control mb-2" required>
      <button class="btn btn-jogar w-100 py-2">ENTRAR NO JOGO →</button>
    </form>
    <div class="text-center mt-2"><a href="cadastro.php" class="text-warning">Ainda não tem conta? Cadastre-se</a></div>
  </div>
</div>
</body></html>