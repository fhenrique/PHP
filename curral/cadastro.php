<?php require 'config.php';
//cadastro.php - SEM VALIDAÇÃO DE @ NO APELIDO
$erro='';
$sucesso = false;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $whatsapp = trim($_POST['whatsapp'] ?? '');
  $senha = $_POST['senha'] ?? '';
  if(!$nome || !$email || !$whatsapp || strlen($senha)<4){ $erro='Preencha Nome, Apelido, WhatsApp e senha com no mínimo 4 caracteres.'; }
  else {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email=?"); $stmt->execute([$email]);
    if($stmt->fetch()){ $erro='Este apelido já está cadastrado.'; }
    else {
      $hash = password_hash($senha, PASSWORD_DEFAULT);
      $pdo->prepare("INSERT INTO usuarios (nome,email,whatsapp,senha) VALUES (?,?,?,?)")->execute([$nome,$email,$whatsapp,$hash]);
      $novo_id = $pdo->lastInsertId();
      // LOGIN AUTOMÁTICO
      $_SESSION['usuario_id'] = $novo_id;
      $_SESSION['usuario_nome'] = $nome;
      $_SESSION['usuario_avatar'] = 'deputado';
      $_SESSION['usuario_cor'] = '#3498db';
      $sucesso = true;
    }
  }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cadastro - Conquista Política</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0f172a;color:#fff;min-height:100vh;display:flex;align-items:center}
.card-login{background:#1e293b;border:none;border-radius:16px;color:#fff}
.card-login .form-label{color:#cbd5e1;font-weight:600}
.card-login .form-control::placeholder{color:#94a3b8}
.btn-jogar{background:#22c55e;color:#000;font-weight:800}
.btn-jogar:hover{background:#16a34a;color:#000}
.modal-content{background:#1e293b;color:#fff;border:none;border-radius:16px}
.modal-content .btn-close{filter:invert(1)}
</style>
</head><body><div class="container"><div class="row justify-content-center"><div class="col-md-5">
<div class="text-center mb-3"><h2>🗳️ Criar Conta</h2></div>
<div class="card card-login p-4">
<?php if($erro): ?><div class="alert alert-danger"><?= $erro ?></div><?php endif; ?>
<form method="POST" novalidate>
<label class="form-label">Nome</label><input name="nome" class="form-control mb-2" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
<label class="form-label">Apelido</label><input name="email" type="text" class="form-control mb-2" placeholder="Ex: meuapelido" autocomplete="username" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
<label class="form-label">WhatsApp</label><input name="whatsapp" class="form-control mb-2" placeholder="(11) 99999-9999" value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>" required>
<label class="form-label">Senha</label><input name="senha" type="password" class="form-control mb-3" required>
<button class="btn btn-jogar w-100 py-2">CADASTRAR</button>
</form>
<div class="text-center mt-3"><a href="index.php" class="text-warning">Já tenho conta</a></div>
</div></div></div></div>

<!-- MODAL DE SUCESSO -->
<div class="modal fade" id="modalSucesso" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4">
      <div class="modal-body">
        <div style="font-size:60px">✅</div>
        <h4 class="fw-bold mt-2">Cadastro realizado com sucesso!</h4>
        <p class="text-secondary">Sua conta já está logada. Clique abaixo para começar a jogar.</p>
        <a href="lobby.php" class="btn btn-jogar w-100 py-2 mt-2">FAZER LOGIN →</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if($sucesso): ?>
<script>
  var modal = new bootstrap.Modal(document.getElementById('modalSucesso'));
  modal.show();
</script>
<?php endif; ?>
</body></html>