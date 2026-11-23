<?php
//configuracoes.php
require_once __DIR__ . '/../config.php';
exigeLogin();
exigePerfil(['Administrador']);
$spa_id = $_SESSION['spa_id'];
$msg = '';

// Salvar dados da clínica - usa as colunas REAIS da sua tabela spas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao']=='clinica') {
  $nome = trim($_POST['nome']);
  $responsavel = trim($_POST['responsavel']);
  $telefone = trim($_POST['telefone']);
  $whatsapp = trim($_POST['whatsapp']);
  $email = trim($_POST['email']);
  $endereco = trim($_POST['endereco']);
  $cidade = trim($_POST['cidade']);
  $abertura = $_POST['horario_abertura'];
  $fechamento = $_POST['horario_fechamento'];
  $dias = trim($_POST['dias_funcionamento']);
  $insta = trim($_POST['instagram']);
  $boas = trim($_POST['mensagem_boas_vindas']);

  $stmt = $pdo->prepare("UPDATE spas SET nome=?, responsavel=?, telefone=?, whatsapp=?, email=?, endereco=?, cidade=?, horario_abertura=?, horario_fechamento=?, dias_funcionamento=?, instagram=?, mensagem_boas_vindas=? WHERE id=?");
  $stmt->execute([$nome, $responsavel, $telefone, $whatsapp, $email, $endereco, $cidade, $abertura, $fechamento, $dias, $insta, $boas, $spa_id]);
  $msg = '✅ Dados da clínica salvos com sucesso!';
}

// Trocar senha - CORRIGIDO: senha fica na tabela spas, não em usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao']=='senha') {
  $atual = $_POST['senha_atual'] ?? '';
  $nova = $_POST['nova_senha'] ?? '';
  $conf = $_POST['conf_senha'] ?? '';

  // Busca senha atual
  $stmt = $pdo->prepare("SELECT senha FROM spas WHERE id=?");
  $stmt->execute([$spa_id]);
  $senha_atual_hash = $stmt->fetchColumn();

  if (!password_verify($atual, $senha_atual_hash)) {
    $msg = '❌ Senha atual incorreta.';
  } elseif (strlen($nova) < 4) {
    $msg = '❌ A nova senha precisa ter pelo menos 4 caracteres.';
  } elseif ($nova !== $conf) {
    $msg = '❌ As senhas novas não conferem.';
  } else {
    $stmt = $pdo->prepare("UPDATE spas SET senha=? WHERE id=?");
    $stmt->execute([password_hash($nova, PASSWORD_DEFAULT), $spa_id]);
    $msg = '✅ Senha alterada com sucesso! Na próxima vez, use a nova senha para entrar.';
  }
}

// Busca dados atuais - colunas reais
$stmt = $pdo->prepare("SELECT * FROM spas WHERE id=?");
$stmt->execute([$spa_id]);
$spa = $stmt->fetch();
if (!$spa) $spa = ['nome'=>'','responsavel'=>'','telefone'=>'','whatsapp'=>'','email'=>'','endereco'=>'','cidade'=>'Garanhuns','horario_abertura'=>'08:00','horario_fechamento'=>'19:00','dias_funcionamento'=>'Seg a Sáb','instagram'=>'','mensagem_boas_vindas'=>'','plano'=>'teste','criado_em'=>''];

// Estatísticas rápidas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE spa_id=? AND ativo=1"); $stmt->execute([$spa_id]); $total_clientes = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM profissionais WHERE spa_id=? AND ativo=1"); $stmt->execute([$spa_id]); $total_profs = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE spa_id=? AND ativo=1"); $stmt->execute([$spa_id]); $total_serv = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE spa_id=?"); $stmt->execute([$spa_id]); $total_agend = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Configurações</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body{ background:#FBF7FB; font-family:'Segoe UI',system-ui,sans-serif; }
  .sidebar{ background:linear-gradient(180deg,#FCEFF8 0%, #E9D5F5 100%); min-height:100vh; width:260px; position:fixed; left:0; top:0; padding:24px 18px; }
  .sidebar .logo{ font-size:26px; font-weight:700; color:#7A4A7A; }
  .sidebar .logo small{ display:block; font-size:10px; letter-spacing:3px; color:#B08AB0; font-weight:400; }
  .menu-item{ display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; color:#5A3A5A; text-decoration:none; font-size:14.5px; margin-bottom:4px; }
  .menu-item:hover{ background:#F3DDF3; }
  .menu-item.active{ background:#E3B8E3; color:#fff; font-weight:600; box-shadow:0 4px 14px rgba(227,184,227,.5); }
  .main{ margin-left:260px; padding:22px 28px; }
  .search-box{ flex:1; background:#fff; border-radius:12px; padding:10px 14px; border:1px solid #F0E0F0; display:flex; align-items:center; gap:8px; }
  .search-box input{ border:none; outline:none; width:100%; font-size:14px; }
  .card-serenita{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.04); }
  .btn-lilas{ background:#D8A8E0; color:#fff; border:none; border-radius:10px; padding:8px 16px; font-weight:600; }
  .btn-lilas:hover{ background:#C795D0; color:#fff; }
  .user-box{ display:flex; align-items:center; gap:10px; background:#fff; padding:10px; border-radius:14px; margin-top:20px; }
  .user-box img{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
  .preview-card{ background:linear-gradient(135deg,#FCEFF8,#E9D5F5); border-radius:18px; padding:20px; text-align:center; }
  @media(max-width:900px){ .sidebar{ display:none; } .main{ margin-left:0; } }
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <div class="d-flex align-items-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">Configurações</h4>
    <div class="search-box"><i class="bi bi-search text-muted"></i><input placeholder="Buscar configuração..."></div>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d M Y') ?></span>
  </div>

  <h5 class="fw-bold mb-0">Configurações da Clínica</h5>
  <p class="text-muted small">Conta: <b><?= htmlspecialchars($spa['email']) ?></b> • Plano: <b><?= htmlspecialchars($spa['plano']) ?></b> • Desde: <?= date('d/m/Y', strtotime($spa['criado_em'])) ?></p>

  <?php if($msg): ?><div class="alert alert-light border" style="border-radius:14px;"><?= $msg ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card card-serenita p-4 mb-3">
        <h6 class="fw-bold mb-3">🏥 Dados da Clínica</h6>
        <form method="POST"><input type="hidden" name="acao" value="clinica">
          <label class="form-label small fw-bold">Nome da clínica</label><input name="nome" class="form-control mb-3" value="<?= htmlspecialchars($spa['nome']) ?>" required>
          <label class="form-label small fw-bold">Responsável</label><input name="responsavel" class="form-control mb-3" value="<?= htmlspecialchars($spa['responsavel']) ?>" placeholder="Ex: Maria">
          <div class="row"><div class="col-6"><label class="form-label small fw-bold">Telefone</label><input name="telefone" class="form-control mb-3" value="<?= htmlspecialchars($spa['telefone']) ?>" placeholder="(87) 99999-9999"></div>
          <div class="col-6"><label class="form-label small fw-bold">WhatsApp</label><input name="whatsapp" class="form-control mb-3" value="<?= htmlspecialchars($spa['whatsapp'] ?? '') ?>" placeholder="(87) 99999-9999"></div></div>
          <div class="row"><div class="col-6"><label class="form-label small fw-bold">E-mail de login</label><input name="email" type="email" class="form-control mb-3" value="<?= htmlspecialchars($spa['email']) ?>" required></div>
          <div class="col-6"><label class="form-label small fw-bold">Instagram</label><input name="instagram" class="form-control mb-3" value="<?= htmlspecialchars($spa['instagram'] ?? '') ?>" placeholder="@serenita.spa"></div></div>
          <label class="form-label small fw-bold">Endereço</label><input name="endereco" class="form-control mb-3" value="<?= htmlspecialchars($spa['endereco'] ?? '') ?>" placeholder="Rua, número, bairro">
          <div class="row"><div class="col-6"><label class="form-label small fw-bold">Cidade</label><input name="cidade" class="form-control mb-3" value="<?= htmlspecialchars($spa['cidade'] ?? 'Garanhuns') ?>"></div>
          <div class="col-6"><label class="form-label small fw-bold">Dias de funcionamento</label><input name="dias_funcionamento" class="form-control mb-3" value="<?= htmlspecialchars($spa['dias_funcionamento'] ?? 'Seg a Sáb') ?>"></div></div>
          <div class="row"><div class="col-6"><label class="form-label small fw-bold">Abertura</label><input name="horario_abertura" type="time" class="form-control mb-3" value="<?= $spa['horario_abertura'] ?? '08:00' ?>"></div>
          <div class="col-6"><label class="form-label small fw-bold">Fechamento</label><input name="horario_fechamento" type="time" class="form-control mb-3" value="<?= $spa['horario_fechamento'] ?? '19:00' ?>"></div></div>
          <label class="form-label small fw-bold">Mensagem de boas-vindas (WhatsApp)</label><textarea name="mensagem_boas_vindas" class="form-control mb-3" rows="2"><?= htmlspecialchars($spa['mensagem_boas_vindas'] ?? '') ?></textarea>
          <button class="btn btn-lilas">💾 Salvar Dados da Clínica</button>
        </form>
      </div>

      <div class="card card-serenita p-4">
        <h6 class="fw-bold mb-3">🔒 Segurança — Trocar Senha</h6>
        <form method="POST"><input type="hidden" name="acao" value="senha">
          <label class="form-label small fw-bold">Senha atual</label><input name="senha_atual" type="password" class="form-control mb-3" required placeholder="Sua senha atual">
          <div class="row"><div class="col-6"><label class="form-label small fw-bold">Nova senha</label><input name="nova_senha" type="password" class="form-control mb-3" required></div>
          <div class="col-6"><label class="form-label small fw-bold">Confirmar nova senha</label><input name="conf_senha" type="password" class="form-control mb-3" required></div></div>
          <button class="btn btn-lilas">Alterar Senha</button>
        </form>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="preview-card mb-3">
        <div style="font-size:48px;">🌸</div>
        <h5 class="fw-bold mb-1"><?= htmlspecialchars($spa['nome']) ?></h5>
        <p class="small mb-1">Responsável: <?= htmlspecialchars($spa['responsavel'] ?: '-') ?> • Plano: <?= htmlspecialchars($spa['plano']) ?></p>
        <p class="small mb-1">📞 <?= htmlspecialchars($spa['telefone'] ?: 'Sem telefone') ?></p>
        <p class="small mb-1">🕐 <?= substr($spa['horario_abertura'] ?? '08:00',0,5) ?> — <?= substr($spa['horario_fechamento'] ?? '19:00',0,5) ?> • <?= htmlspecialchars($spa['dias_funcionamento'] ?? '') ?></p>
        <p class="small mb-0">📍 <?= htmlspecialchars($spa['endereco'] ?? 'Endereço não informado') ?> • <?= htmlspecialchars($spa['cidade'] ?? '') ?></p>
        <?php if(!empty($spa['instagram'])): ?><p class="small mt-2 mb-0">📸 <?= htmlspecialchars($spa['instagram']) ?></p><?php endif; ?>
      </div>

      <div class="card card-serenita p-3 mb-3">
        <h6 class="fw-bold mb-3">📊 Resumo do Sistema</h6>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>Pacientes</span><b><?= $total_clientes ?></b></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>Profissionais</span><b><?= $total_profs ?></b></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>Serviços</span><b><?= $total_serv ?></b></div>
        <div class="d-flex justify-content-between py-2"><span>Agendamentos</span><b><?= $total_agend ?></b></div>
      </div>

      <div class="card card-serenita p-3">
        <h6 class="fw-bold mb-3">🔗 Atalhos Rápidos</h6>
        <a href="servicos.php" class="btn btn-light w-100 mb-2 text-start">⭐ Gerenciar Serviços</a>
        <a href="profissionais.php" class="btn btn-light w-100 mb-2 text-start">👩 Gerenciar Profissionais</a>
        <a href="faturamento.php" class="btn btn-light w-100 mb-2 text-start">💰 Ver Faturamento</a>
        <a href="../logout.php" class="btn btn-outline-danger w-100 text-start">🚪 Sair do Sistema</a>
        <p class="text-muted small mt-3 mb-0">Serenità v1.0 • Spa ID #<?= $spa_id ?> • <?= htmlspecialchars($spa['nome']) ?></p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>