<?php
require_once __DIR__ . '/../config.php';
exigeLogin();
$spa_id = $_SESSION['spa_id'];

// Só Administrador pode gerenciar usuários
if (($_SESSION['perfil'] ?? 'Administrador') !== 'Administrador') {
  header("Location: dashboard.php?erro=sem_permissao"); exit;
}

// Excluir
if (isset($_GET['excluir'])) {
  $pdo->prepare("UPDATE usuarios SET ativo=0 WHERE id=? AND spa_id=?")->execute([$_GET['excluir'], $spa_id]);
  header("Location: usuarios.php"); exit;
}

// Ativar / Desativar
if (isset($_GET['toggle'])) {
  $pdo->prepare("UPDATE usuarios SET ativo = 1 - ativo WHERE id=? AND spa_id=?")->execute([$_GET['toggle'], $spa_id]);
  header("Location: usuarios.php"); exit;
}

// Salvar / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome']); $email = trim($_POST['email']);
  $perfil = $_POST['perfil']; $prof_id = $_POST['profissional_id'] ?: null;
  $senha = trim($_POST['senha']); $id = $_POST['id'] ?? '';

  if ($id) {
    if ($senha) {
      $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, perfil=?, profissional_id=?, senha=? WHERE id=? AND spa_id=?");
      $stmt->execute([$nome, $email, $perfil, $prof_id, password_hash($senha, PASSWORD_DEFAULT), $id, $spa_id]);
    } else {
      $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, perfil=?, profissional_id=? WHERE id=? AND spa_id=?");
      $stmt->execute([$nome, $email, $perfil, $prof_id, $id, $spa_id]);
    }
  } else {
    $stmt = $pdo->prepare("INSERT INTO usuarios (spa_id, nome, email, senha, perfil, profissional_id) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$spa_id, $nome, $email, password_hash($senha ?: '1234', PASSWORD_DEFAULT), $perfil, $prof_id]);
  }
  header("Location: usuarios.php"); exit;
}

$busca = trim($_GET['busca'] ?? '');
$sql = "SELECT u.*, p.nome as prof_nome FROM usuarios u LEFT JOIN profissionais p ON p.id=u.profissional_id WHERE u.spa_id=? AND u.ativo=1";
$params = [$spa_id];
if ($busca) { $sql .= " AND (u.nome LIKE ? OR u.email LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
$sql .= " ORDER BY FIELD(u.perfil,'Administrador','Profissional','Visualizador'), u.nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $usuarios = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM profissionais WHERE spa_id=? AND ativo=1 ORDER BY nome"); $stmt->execute([$spa_id]); $profs = $stmt->fetchAll();

function badgePerfil($p){
  if($p=='Administrador') return '<span class="badge rounded-pill" style="background:#E3B8E3; color:#5A2A5A;">👑 Administrador</span>';
  if($p=='Profissional') return '<span class="badge rounded-pill" style="background:#C9D8F0; color:#2A4A6A;">💅 Profissional</span>';
  return '<span class="badge rounded-pill" style="background:#F0D8C9; color:#6A4A2A;">👁️ Visualizador</span>';
}
function descPerfil($p){
  if($p=='Administrador') return 'Acesso total: tudo, incluindo usuários e configurações';
  if($p=='Profissional') return 'Vê agenda, pacientes e seus atendimentos. Não exclui, não vê faturamento total';
  return 'Só visualiza: agenda, pacientes, serviços. Não edita nada';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Usuários</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body{ background:#FBF7FB; font-family:'Segoe UI',system-ui,sans-serif; }
  .sidebar{ background:linear-gradient(180deg,#FCEFF8 0%, #E9D5F5 100%); min-height:100vh; width:260px; position:fixed; left:0; top:0; padding:24px 18px; }
  .sidebar .logo{ font-size:26px; font-weight:700; color:#7A4A7A; }
  .sidebar .logo small{ display:block; font-size:10px; letter-spacing:3px; color:#B08AB0; font-weight:400; }
  .menu-item{ display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; color:#5A3A5A; text-decoration:none; font-size:14.5px; margin-bottom:4px; }
  .menu-item:hover{ background:#F3DDF3; }
  .menu-item.active{ background:#E3B8E3; color:#fff; font-weight:600; }
  .main{ margin-left:260px; padding:22px 28px; }
  .search-box{ flex:1; background:#fff; border-radius:12px; padding:10px 14px; border:1px solid #F0E0F0; display:flex; align-items:center; gap:8px; }
  .search-box input{ border:none; outline:none; width:100%; font-size:14px; }
  .card-serenita{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.04); }
  .btn-lilas{ background:#D8A8E0; color:#fff; border:none; border-radius:10px; padding:8px 16px; font-weight:600; }
  .btn-lilas:hover{ background:#C795D0; color:#fff; }
  .user-box{ display:flex; align-items:center; gap:10px; background:#fff; padding:10px; border-radius:14px; margin-top:20px; }
  .user-box img{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
  @media(max-width:900px){ .sidebar{ display:none; } .main{ margin-left:0; } }
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <div class="d-flex align-items-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">Usuários</h4>
    <form method="GET" class="search-box m-0"><i class="bi bi-search text-muted"></i><input name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por nome ou e-mail..."></form>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d M Y') ?></span>
  </div>

  <h5 class="fw-bold mb-0">Usuários • <?= count($usuarios) ?> ativos</h5>
  <p class="text-muted small">Quem pode entrar no Serenitá e o que cada um pode fazer</p>

  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card card-serenita p-3" style="background:#FCEFF8;"><small>👑 Administrador</small><p class="small mb-0 mt-1">Acesso total. Pode criar usuários, excluir, ver faturamento e configurações.</p></div></div>
    <div class="col-md-4"><div class="card card-serenita p-3" style="background:#EBF0FF;"><small>💅 Profissional</small><p class="small mb-0 mt-1">Vê agenda e pacientes. Vincula com o cadastro em Profissionais pra ver só a própria agenda.</p></div></div>
    <div class="col-md-4"><div class="card card-serenita p-3" style="background:#FFF0E6;"><small>👁️ Visualizador</small><p class="small mb-0 mt-1">Só olha. Ideal pra recepcionista nova ou sócia que só acompanha.</p></div></div>
  </div>

  <div class="d-flex mb-3"><button class="btn btn-lilas ms-auto" data-bs-toggle="modal" data-bs-target="#modalUsuario">+ Novo Usuário</button></div>

  <div class="card card-serenita p-3">
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Usuário</th><th>Perfil</th><th>Vinculado a</th><th>Criado em</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
      <?php foreach($usuarios as $u): ?>
      <tr>
        <td><b><?= htmlspecialchars($u['nome']) ?></b><br><small class="text-muted"><?= htmlspecialchars($u['email']) ?></small></td>
        <td><?= badgePerfil($u['perfil']) ?><br><small class="text-muted"><?= descPerfil($u['perfil']) ?></small></td>
        <td><?= $u['prof_nome'] ? '💅 '.htmlspecialchars($u['prof_nome']) : '<span class="text-muted">—</span>' ?></td>
        <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
        <td class="text-end">
          <button class="btn btn-sm btn-light" onclick="editarUsuario(<?= $u['id'] ?>,'<?= htmlspecialchars($u['nome'],ENT_QUOTES) ?>','<?= htmlspecialchars($u['email'],ENT_QUOTES) ?>','<?= $u['perfil'] ?>','<?= $u['profissional_id'] ?>')">Editar</button>
          <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm btn-light">Desativar</a>
          <a href="?excluir=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este usuário?')">Excluir</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php if(empty($usuarios)): ?><p class="text-muted text-center py-4">Nenhum usuário. Crie o primeiro.</p><?php endif; ?>
  </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:18px;">
  <form method="POST"><input type="hidden" name="id" id="f_id">
  <div class="modal-header border-0"><h5 class="fw-bold">Novo Usuário</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label small fw-bold">Nome</label><input name="nome" id="f_nome" class="form-control mb-3" required placeholder="Ex: Camila Rocha">
    <label class="form-label small fw-bold">E-mail (login)</label><input name="email" id="f_email" type="email" class="form-control mb-3" required placeholder="camila@serenita.com">
    <label class="form-label small fw-bold">Senha <small class="text-muted">(deixe em branco pra manter a atual ao editar)</small></label><input name="senha" id="f_senha" type="password" class="form-control mb-3" placeholder="Mínimo 4 caracteres">
    <label class="form-label small fw-bold">Perfil</label><select name="perfil" id="f_perfil" class="form-select mb-3" onchange="mostraProf(this.value)"><option>Administrador</option><option>Profissional</option><option>Visualizador</option></select>
    <div id="box_prof" style="display:none;"><label class="form-label small fw-bold">Vincular à profissional (pra ver só a própria agenda)</label><select name="profissional_id" id="f_prof" class="form-select mb-3"><option value="">Sem vínculo</option><?php foreach($profs as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lilas">Salvar Usuário</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function mostraProf(v){ document.getElementById('box_prof').style.display = (v=='Profissional') ? 'block' : 'none'; }
function editarUsuario(id,nome,email,perfil,prof){
  document.getElementById('f_id').value=id; document.getElementById('f_nome').value=nome; document.getElementById('f_email').value=email;
  document.getElementById('f_perfil').value=perfil; document.getElementById('f_prof').value=prof||''; document.getElementById('f_senha').value='';
  mostraProf(perfil);
  new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}
</script>
</body>
</html>