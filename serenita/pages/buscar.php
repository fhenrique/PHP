<?php
//buscar.php
require_once __DIR__ . '/../config.php';
exigeLogin();
$spa_id = $_SESSION['spa_id'];
$busca = trim($_GET['busca'] ?? '');

// ✅ Se for AJAX (sugestão ao vivo do Dashboard), devolve JSON com os 3 tipos
if (!empty($_GET['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $out = [];
  if ($busca !== '') {
    $like = "%$busca%";
    // Pacientes
    $s = $pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE spa_id=? AND ativo=1 AND (nome LIKE ? OR telefone LIKE ? OR email LIKE ?) LIMIT 4");
    $s->execute([$spa_id, $like, $like, $like]);
    foreach ($s->fetchAll() as $c) $out[] = ['tipo' => 'Paciente', 'icone' => '👩', 'nome' => $c['nome'], 'detalhe' => $c['telefone'], 'link' => 'clientes.php?busca=' . urlencode($c['nome'])];

    // Profissionais
    $s = $pdo->prepare("SELECT id, nome, especialidade FROM profissionais WHERE spa_id=? AND ativo=1 AND (nome LIKE ? OR especialidade LIKE ?) LIMIT 4");
    $s->execute([$spa_id, $like, $like]);
    foreach ($s->fetchAll() as $p) $out[] = ['tipo' => 'Profissional', 'icone' => '💆', 'nome' => $p['nome'], 'detalhe' => $p['especialidade'], 'link' => 'profissionais.php?busca=' . urlencode($p['nome'])];

    // Serviços
    $s = $pdo->prepare("SELECT id, nome, preco FROM servicos WHERE spa_id=? AND ativo=1 AND nome LIKE ? LIMIT 4");
    $s->execute([$spa_id, $like]);
    foreach ($s->fetchAll() as $sv) $out[] = ['tipo' => 'Serviço', 'icone' => '✨', 'nome' => $sv['nome'], 'detalhe' => 'R$ ' . number_format($sv['preco'], 2, ',', '.'), 'link' => 'servicos.php?busca=' . urlencode($sv['nome'])];
  }
  echo json_encode($out); exit;
}

// Página normal de resultados
$pacientes = $profissionais = $servicos = [];
if ($busca !== '') {
  $like = "%$busca%";
  $s = $pdo->prepare("SELECT * FROM clientes WHERE spa_id=? AND ativo=1 AND (nome LIKE ? OR telefone LIKE ? OR email LIKE ?) ORDER BY nome LIMIT 20");
  $s->execute([$spa_id, $like, $like, $like]); $pacientes = $s->fetchAll();

  $s = $pdo->prepare("SELECT * FROM profissionais WHERE spa_id=? AND ativo=1 AND (nome LIKE ? OR especialidade LIKE ?) ORDER BY nome LIMIT 20");
  $s->execute([$spa_id, $like, $like]); $profissionais = $s->fetchAll();

  $s = $pdo->prepare("SELECT * FROM servicos WHERE spa_id=? AND ativo=1 AND nome LIKE ? ORDER BY nome LIMIT 20");
  $s->execute([$spa_id, $like]); $servicos = $s->fetchAll();
}
$total = count($pacientes) + count($profissionais) + count($servicos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Busca: <?= htmlspecialchars($busca) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
  body{ background:#FBF7FB; font-family:'Segoe UI',system-ui,sans-serif; }
  /* ✅ CORRIGIDO: CSS da sidebar igual ao Dashboard - era isso que faltava */
  .sidebar{ background: linear-gradient(180deg,#FCEFF8 0%, #F5EAF6 100%); min-height:100vh; width:260px; position:fixed; left:0; top:0; padding:24px 18px; border-right:1px solid #F0DDF0; }
  .sidebar .logo{ font-size:26px; font-weight:700; color:#7A4A7A; letter-spacing:.5px; }
  .sidebar .logo small{ display:block; font-size:10px; letter-spacing:3px; color:#B08AB0; font-weight:400; }
  .menu-item{ display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; color:#5A3A5A; text-decoration:none; font-size:14.5px; margin-bottom:4px; }
  .menu-item:hover{ background:#F3DDF3; color:#7A4A7A; }
  .menu-item.active{ background:#E3B8E3; color:#fff; font-weight:600; box-shadow:0 4px 14px rgba(227,184,227,.5); }
  .user-box{ display:flex; align-items:center; gap:10px; background:#fff; padding:10px; border-radius:14px; margin-top:20px; }
  .user-box img{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
  .main{ margin-left:260px; padding:22px 28px; }
  .card-serenita{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.04); }
  .item-busca{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:12px; text-decoration:none; color:#333; }
  .item-busca:hover{ background:#FBF7FB; }
  .badge-tipo{ font-size:11px; padding:4px 10px; border-radius:20px; font-weight:700; }
  @media(max-width:900px){ .sidebar{ display:none; } .main{ margin-left:0; } }
</style>

</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main">
  <h4 class="fw-bold">🔍 Resultados para "<?= htmlspecialchars($busca) ?>"</h4>
  <p class="text-muted small"><?= $total ?> resultado(s) encontrado(s) • <a href="dashboard.php">Voltar ao Dashboard</a></p>

  <form action="buscar.php" method="GET" class="d-flex gap-2 mb-4">
    <input name="busca" value="<?= htmlspecialchars($busca) ?>" class="form-control" style="border-radius:12px;" placeholder="Buscar paciente, serviço ou profissional..." autofocus>
    <button class="btn px-4" style="background:#D8A8E0; color:#fff; border-radius:12px; font-weight:600;">Buscar</button>
  </form>

  <?php if($busca === ''): ?><div class="alert alert-light">Digite algo para buscar. Ex: <b>Lucas Martins</b>, <b>Manicure</b> ou <b>Camila</b></div><?php endif; ?>

  <?php if($total === 0 && $busca !== ''): ?><div class="alert alert-light">Nenhum resultado para "<b><?= htmlspecialchars($busca) ?></b>". Tente outro termo.</div><?php endif; ?>

  <?php if($pacientes): ?>
  <div class="card card-serenita p-3 mb-3"><h6 class="fw-bold mb-2">👩 Pacientes (<?= count($pacientes) ?>)</h6>
    <?php foreach($pacientes as $c): ?><a href="clientes.php?busca=<?= urlencode($c['nome']) ?>" class="item-busca"><span style="font-size:22px;">👩</span><div><b><?= htmlspecialchars($c['nome']) ?></b><div class="small text-muted"><?= htmlspecialchars($c['telefone'] ?: $c['email']) ?></div></div><span class="badge-tipo ms-auto" style="background:#FCEFF8; color:#7A4A7A;">Paciente</span></a><?php endforeach; ?>
  </div><?php endif; ?>

  <?php if($profissionais): ?>
  <div class="card card-serenita p-3 mb-3"><h6 class="fw-bold mb-2">💆 Profissionais (<?= count($profissionais) ?>)</h6>
    <?php foreach($profissionais as $p): ?><a href="profissionais.php?busca=<?= urlencode($p['nome']) ?>" class="item-busca"><span style="font-size:22px;">💆</span><div><b><?= htmlspecialchars($p['nome']) ?></b><div class="small text-muted"><?= htmlspecialchars($p['especialidade']) ?></div></div><span class="badge-tipo ms-auto" style="background:#F0EBFF; color:#6A3A6A;">Profissional</span></a><?php endforeach; ?>
  </div><?php endif; ?>

  <?php if($servicos): ?>
  <div class="card card-serenita p-3 mb-3"><h6 class="fw-bold mb-2">✨ Serviços (<?= count($servicos) ?>)</h6>
    <?php foreach($servicos as $sv): ?><a href="servicos.php?busca=<?= urlencode($sv['nome']) ?>" class="item-busca"><span style="font-size:22px;">✨</span><div><b><?= htmlspecialchars($sv['nome']) ?></b><div class="small text-muted"><?= htmlspecialchars($sv['duracao'] ?? '') ?> min • R$ <?= number_format($sv['preco'],2,',','.') ?></div></div><span class="badge-tipo ms-auto" style="background:#FFF0E6; color:#8A5A2A;">Serviço</span></a><?php endforeach; ?>
  </div><?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>