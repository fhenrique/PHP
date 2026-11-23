<?php
require_once __DIR__ . '/../config.php';
exigeLogin();
$spa_id = $_SESSION['spa_id'];

// Excluir
if (isset($_GET['excluir'])) {
  $stmt = $pdo->prepare("UPDATE clientes SET ativo=0 WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['excluir'], $spa_id]);
  header("Location: clientes.php"); exit;
}

// Novo / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome']);
  $telefone = trim($_POST['telefone']);
  $email = trim($_POST['email']);
  $obs = trim($_POST['obs'] ?? '');
  $id = $_POST['id'] ?? '';

  if ($id) {
    $stmt = $pdo->prepare("UPDATE clientes SET nome=?, telefone=?, email=? WHERE id=? AND spa_id=?");
    $stmt->execute([$nome, $telefone, $email, $id, $spa_id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO clientes (spa_id, nome, telefone, email) VALUES (?,?,?,?)");
    $stmt->execute([$spa_id, $nome, $telefone, $email]);
  }
  header("Location: clientes.php"); exit;
}

// Filtros + Busca
$busca = trim($_GET['busca'] ?? '');
$filtro = $_GET['filtro'] ?? 'Todos';

$sql = "SELECT c.*, (SELECT COUNT(*) FROM agendamentos a WHERE a.cliente_id=c.id) as total_agend, (SELECT MAX(data) FROM agendamentos a WHERE a.cliente_id=c.id) as ultimo_agend FROM clientes c WHERE c.spa_id=? AND c.ativo=1";
$params = [$spa_id];
if ($busca) { $sql .= " AND (c.nome LIKE ? OR c.telefone LIKE ? OR c.email LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($filtro == 'Com agendamento') { $sql .= " AND EXISTS (SELECT 1 FROM agendamentos a WHERE a.cliente_id=c.id)"; }
if ($filtro == 'Sem agendamento') { $sql .= " AND NOT EXISTS (SELECT 1 FROM agendamentos a WHERE a.cliente_id=c.id)"; }
$sql .= " ORDER BY c.nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$clientes = $stmt->fetchAll();

// ✅ NOVO: Se for AJAX do Dashboard, devolve só JSON e para aqui
if (!empty($_GET['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_map(fn($c) => ['id' => $c['id'], 'nome' => $c['nome'], 'telefone' => $c['telefone']], array_slice($clientes, 0, 5)));
  exit;
}

// Total para o título
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE spa_id=? AND ativo=1"); $stmt->execute([$spa_id]);
$total = $stmt->fetchColumn();

function avatarPaciente($c){
  if(!empty($c['foto'])) return $c['foto'];
  $nome = urlencode($c['nome']);
  return "https://ui-avatars.com/api/?name=$nome&background=FCEFF8&color=7A4A7A&size=200&bold=true";
}
function iniciais($nome){
  $p = explode(' ', trim($nome));
  return strtoupper(substr($p[0],0,1) . (isset($p[1]) ? substr($p[1],0,1) : ''));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Pacientes</title>
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
  .card-paciente{ border:none; border-radius:18px; background:#fff; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.05); transition:.2s; }
  .card-paciente:hover{ transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.08); }
  .pac-avatar{ width:78px; height:78px; border-radius:50%; object-fit:cover; margin:0 auto; border:3px solid #FCEFF8; }
  .badge-nome{ background:#F9D8E8; color:#5A2A4A; padding:7px 14px; border-radius:20px; font-weight:700; font-size:14px; display:inline-block; }
  .badge-tag{ background:#EDE0F5; color:#6A3A6A; padding:4px 12px; border-radius:20px; font-size:11.5px; font-weight:600; display:inline-block; }
  .btn-lilas{ background:#D8A8E0; color:#fff; border:none; border-radius:10px; padding:8px 16px; font-weight:600; }
  .btn-lilas:hover{ background:#C795D0; color:#fff; }
  .btn-filtro{ background:#FCEAF6; border:none; border-radius:10px; padding:8px 16px; font-weight:600; color:#5A3A5A; }
  .user-box{ display:flex; align-items:center; gap:10px; background:#fff; padding:10px; border-radius:14px; margin-top:20px; }
  .user-box img{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
  @media(max-width:900px){ .sidebar{ display:none; } .main{ margin-left:0; } }
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <div class="d-flex align-items-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">Pacientes</h4>
    <form method="GET" class="search-box m-0"><i class="bi bi-search text-muted"></i><input name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar paciente, telefone ou e-mail..."></form>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d M Y') ?></span>
  </div>

  <h5 class="fw-bold mb-0">Pacientes • <?= $total ?> pacientes cadastrados</h5>
  <p class="text-muted small">Gerencie as pacientes da clínica</p>

  <?php if($busca !== ''): ?>
    <div class="alert alert-light d-flex justify-content-between align-items-center" style="border-radius:12px;">
      <span>🔍 Mostrando <b><?= count($clientes) ?></b> resultado(s) para: <b>"<?= htmlspecialchars($busca) ?>"</b></span>
      <a href="clientes.php" class="btn btn-sm btn-light border">✕ Limpar busca</a>
    </div>
  <?php endif; ?>

  <form method="GET" class="d-flex gap-2 mb-4">
    <!-- ✅ CORRIGIDO: mantém a busca quando troca o filtro -->
    <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
    <select name="filtro" class="form-select" style="border-radius:12px; background:#F8EEF8; border:none;" onchange="this.form.submit()">
      <option value="Todos" <?= $filtro=='Todos'?'selected':'' ?>>Status: Todos</option>
      <option value="Com agendamento" <?= $filtro=='Com agendamento'?'selected':'' ?>>Com agendamento</option>
      <option value="Sem agendamento" <?= $filtro=='Sem agendamento'?'selected':'' ?>>Sem agendamento</option>
    </select>
    <button type="button" class="btn btn-filtro"><i class="bi bi-funnel"></i> Filtros</button>
    <button type="button" class="btn btn-lilas" data-bs-toggle="modal" data-bs-target="#modalPaciente">+ Novo Paciente</button>
  </form>

  <div class="row g-3">
    <?php foreach($clientes as $c): ?>
    <div class="col-md-4">
      <div class="card card-paciente">
        <div class="card-body text-center pt-4">
          <img src="<?= avatarPaciente($c) ?>" class="pac-avatar" alt="<?= htmlspecialchars($c['nome']) ?>">
          <div class="mt-3"><span class="badge-nome"><?= htmlspecialchars($c['nome']) ?></span></div>
          <div class="mt-2"><span class="badge-tag"><?= $c['total_agend'] > 0 ? $c['total_agend'].' agendamentos' : 'Nova paciente' ?></span></div>
          <div class="mt-2 small text-muted">
            <div><i class="bi bi-phone"></i> <?= htmlspecialchars($c['telefone'] ?: 'Sem telefone') ?></div>
            <div class="text-truncate"><i class="bi bi-envelope"></i> <?= htmlspecialchars($c['email'] ?: 'Sem e-mail') ?></div>
            <?php if($c['ultimo_agend']): ?><div><i class="bi bi-calendar-check"></i> Último: <?= date('d/m/Y', strtotime($c['ultimo_agend'])) ?></div><?php endif; ?>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <a href="agenda.php?cliente=<?= $c['id'] ?>" class="btn btn-sm btn-light rounded-pill px-3">+ Agendar</a>
            <div class="dropdown">
              <button class="btn btn-sm" data-bs-toggle="dropdown">•••</button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="editarPaciente(<?= $c['id'] ?>,'<?= htmlspecialchars($c['nome'],ENT_QUOTES) ?>','<?= htmlspecialchars($c['telefone'],ENT_QUOTES) ?>','<?= htmlspecialchars($c['email'],ENT_QUOTES) ?>')">Editar</a></li>
                <li><a class="dropdown-item" href="agenda.php?cliente=<?= $c['id'] ?>">Ver agendamentos</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="?excluir=<?= $c['id'] ?>" onclick="return confirm('Excluir esta paciente?')">Excluir</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if(empty($clientes)): ?>
    <p class="text-center text-muted py-5">
      <?= $busca ? 'Nenhuma paciente encontrada para "'.htmlspecialchars($busca).'".' : 'Nenhuma paciente encontrada.' ?> Clique em + Novo Paciente.
    </p>
  <?php endif; ?>
  <p class="text-muted small text-end mt-3">Exibindo <?= count($clientes) ?> de <?= $total ?> pacientes • Atualizado hoje às <?= date('H:i') ?></p>
</div>

<!-- MODAL NOVO / EDITAR -->
<div class="modal fade" id="modalPaciente" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:18px;">
  <form method="POST">
  <div class="modal-header border-0"><h5 class="fw-bold">Novo Paciente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="id" id="f_id">
    <label class="form-label small fw-bold">Nome completo</label><input name="nome" id="f_nome" class="form-control mb-3" required placeholder="Ex: Maria Fernanda">
    <label class="form-label small fw-bold">Telefone / WhatsApp</label><input name="telefone" id="f_telefone" class="form-control mb-3" placeholder="(87) 99999-0000">
    <label class="form-label small fw-bold">E-mail</label><input name="email" id="f_email" type="email" class="form-control mb-3" placeholder="paciente@email.com">
    <label class="form-label small fw-bold">Observações</label><textarea name="obs" class="form-control" rows="2" placeholder="Alergias, preferências, etc."></textarea>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lilas">Salvar Paciente</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarPaciente(id, nome, telefone, email){
  document.getElementById('f_id').value = id;
  document.getElementById('f_nome').value = nome;
  document.getElementById('f_telefone').value = telefone;
  document.getElementById('f_email').value = email;
  new bootstrap.Modal(document.getElementById('modalPaciente')).show();
}
</script>
</body>
</html>