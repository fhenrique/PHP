<?php
//profissionais.php
require_once __DIR__ . '/../config.php';
exigeLogin();
$spa_id = $_SESSION['spa_id'];
$hoje = date('Y-m-d');

// Excluir
if (isset($_GET['excluir'])) {
  $stmt = $pdo->prepare("UPDATE profissionais SET ativo=0 WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['excluir'], $spa_id]);
  header("Location: profissionais.php"); exit;
}

// Novo / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome']);
  $especialidade = trim($_POST['especialidade']);
  $telefone = trim($_POST['telefone']);
  $email = trim($_POST['email']);
  $comissao = str_replace(',', '.', $_POST['comissao']);
  $id = trim($_POST['id'] ?? '');

  // ✅ TRAVA DE SEGURANÇA: só faz UPDATE se o id for número válido e existir
  if ($id !== '' && ctype_digit($id)) {
    // Confere se esse id realmente existe antes de atualizar
    $check = $pdo->prepare("SELECT id, nome FROM profissionais WHERE id=? AND spa_id=? AND ativo=1");
    $check->execute([$id, $spa_id]);
    $existe = $check->fetch();
    if ($existe) {
      $stmt = $pdo->prepare("UPDATE profissionais SET nome=?, especialidade=?, telefone=?, email=?, comissao=? WHERE id=? AND spa_id=?");
      $stmt->execute([$nome, $especialidade, $telefone, $email, $comissao, $id, $spa_id]);
    }
  } else {
    $stmt = $pdo->prepare("INSERT INTO profissionais (spa_id, nome, especialidade, telefone, email, comissao) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$spa_id, $nome, $especialidade, $telefone, $email, $comissao]);
  }
  header("Location: profissionais.php"); exit;
}

// Filtros + Busca
$busca = trim($_GET['busca'] ?? '');
$filtro = $_GET['filtro'] ?? 'Todos';

$sql = "SELECT p.*,
        (SELECT COUNT(*) FROM agendamentos a WHERE a.profissional_id=p.id AND a.data=? AND a.status != 'Cancelado') as atend_hoje,
        (SELECT COALESCE(SUM(valor),0) FROM agendamentos a WHERE a.profissional_id=p.id AND a.data=? AND a.status != 'Cancelado') as fat_hoje,
        (SELECT COUNT(*) FROM agendamentos a WHERE a.profissional_id=p.id) as total_agend
        FROM profissionais p WHERE p.spa_id=? AND p.ativo=1";
$params = [$hoje, $hoje, $spa_id];
if ($busca) { $sql .= " AND (p.nome LIKE ? OR p.especialidade LIKE ? OR p.telefone LIKE ? OR p.email LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($filtro == 'Com agenda hoje') { $sql .= " AND EXISTS (SELECT 1 FROM agendamentos a WHERE a.profissional_id=p.id AND a.data=?)"; $params[] = $hoje; }
if ($filtro == 'Sem agenda hoje') { $sql .= " AND NOT EXISTS (SELECT 1 FROM agendamentos a WHERE a.profissional_id=p.id AND a.data=?)"; $params[] = $hoje; }
$sql .= " ORDER BY p.nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$profs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM profissionais WHERE spa_id=? AND ativo=1"); $stmt->execute([$spa_id]);
$total = $stmt->fetchColumn();

// ✅ 2 INICIAIS: inicial do nome + inicial do sobrenome (último nome)
// Ex: Alvaro Augustos = AA | Maria da Silva = MS | Ana = A
function inicialProf($nome){
  $nome = trim($nome);
  if ($nome === '') return '?';
  // Quebra o nome em partes
  $partes = preg_split('/\s+/', $nome);
  // Palavras que não são sobrenome: da, de, do, dos, das, e
  $ignorar = ['da','de','do','dos','das','e'];
  $validas = [];
  foreach ($partes as $p) {
    $p = trim($p);
    if ($p === '') continue;
    // Ignora número (ex: "Ana 1") e palavras como da/de/do
    if (!preg_match('/^\p{L}/u', $p)) continue;
    if (in_array(mb_strtolower($p, 'UTF-8'), $ignorar)) continue;
    $validas[] = $p;
  }
  if (empty($validas)) return '?';
  // Primeira letra do primeiro nome
  $primeira = mb_strtoupper(mb_substr($validas[0], 0, 1, 'UTF-8'), 'UTF-8');
  // Se só tem 1 nome, mostra só 1 letra
  if (count($validas) === 1) return $primeira;
  // Primeira letra do ÚLTIMO nome (sobrenome)
  $ultima = mb_strtoupper(mb_substr(end($validas), 0, 1, 'UTF-8'), 'UTF-8');
  return $primeira . $ultima;
}
// Cor de fundo diferente por ID, só pra não ficar tudo igual
function corAvatar($id){
  $cores = ['#F9D8E8','#E8D5F5','#D8E8F5','#FCE0D8','#D8F0E0'];
  return $cores[$id % 5];
}
function corEspecialidade($e){
  if(stripos($e,'Massoterapia')!==false) return '#D8C9F0';
  if(stripos($e,'Podologia')!==false) return '#C9D8F0';
  if(stripos($e,'Nail')!==false) return '#F0C9C9';
  if(stripos($e,'Manicure')!==false) return '#FCE0D8';
  return '#E8E0F0';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Profissionais</title>
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
  .card-prof{ border:none; border-radius:18px; background:#fff; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.05); transition:.2s; }
  .card-prof:hover{ transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.08); }
  /* ✅ Círculo com 2 iniciais no lugar da foto */
  .avatar-inicial{ width:84px; height:84px; border-radius:50%; margin:0 auto; border:3px solid #FCEFF8; display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:700; color:#7A4A7A; letter-spacing:1px; }
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
    <h4 class="fw-bold mb-0">Profissionais</h4>
    <form method="GET" class="search-box m-0"><i class="bi bi-search text-muted"></i><input name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar profissional ou especialidade..."></form>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d M Y') ?></span>
  </div>

  <h5 class="fw-bold mb-0">Profissionais • <?= $total ?> profissionais cadastrados</h5>
  <p class="text-muted small">Gerencie a equipe da clínica</p>

  <?php if($busca !== ''): ?>
    <div class="alert alert-light d-flex justify-content-between align-items-center" style="border-radius:12px;">
      <span>🔍 Mostrando <b><?= count($profs) ?></b> resultado(s) para: <b>"<?= htmlspecialchars($busca) ?>"</b></span>
      <a href="profissionais.php" class="btn btn-sm btn-light border">✕ Limpar busca</a>
    </div>
  <?php endif; ?>

  <form method="GET" class="d-flex gap-2 mb-4">
    <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
    <select name="filtro" class="form-select" style="border-radius:12px; background:#F8EEF8; border:none;" onchange="this.form.submit()">
      <option value="Todos" <?= $filtro=='Todos'?'selected':'' ?>>Status: Todos</option>
      <option value="Com agenda hoje" <?= $filtro=='Com agenda hoje'?'selected':'' ?>>Com agenda hoje</option>
      <option value="Sem agenda hoje" <?= $filtro=='Sem agenda hoje'?'selected':'' ?>>Sem agenda hoje</option>
    </select>
    <button type="button" class="btn btn-filtro"><i class="bi bi-funnel"></i> Filtros</button>
    <button type="button" class="btn btn-lilas" onclick="novoProf()">+ Novo Profissional</button>
  </form>

  <div class="row g-3">
    <?php foreach($profs as $p): ?>
    <div class="col-md-4">
      <div class="card card-prof">
        <div class="card-body text-center pt-4">
          <div class="avatar-inicial" style="background:<?= corAvatar($p['id']) ?>"><?= inicialProf($p['nome']) ?></div>
          <div class="mt-3"><span class="badge-nome"><?= htmlspecialchars($p['nome']) ?></span></div>
          <div class="mt-2"><span class="badge-tag" style="background:<?= corEspecialidade($p['especialidade']) ?>"><?= htmlspecialchars($p['especialidade']) ?></span></div>
          <div class="mt-3 small text-muted">
            <div><i class="bi bi-phone"></i> <?= htmlspecialchars($p['telefone'] ?: 'Sem telefone') ?></div>
            <div class="text-truncate"><i class="bi bi-envelope"></i> <?= htmlspecialchars($p['email'] ?: 'Sem e-mail') ?></div>
            <div class="text-muted" style="font-size:11px;">ID: <?= $p['id'] ?></div>
          </div>
          <div class="d-flex justify-content-center gap-2 mt-3">
            <span class="badge bg-light text-dark border py-2 px-3"><?= $p['atend_hoje'] ?> hoje</span>
            <span class="badge bg-light text-dark border py-2 px-3">R$ <?= number_format($p['fat_hoje'],0,',','.') ?> hoje</span>
            <span class="badge bg-light text-dark border py-2 px-3"><?= $p['comissao'] ?>% comissão</span>
          </div>
          <div class="small text-muted mt-2"><?= $p['total_agend'] ?> agendamentos no total</div>
          <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <a href="agenda.php?profissional=<?= $p['id'] ?>" class="btn btn-sm btn-light rounded-pill px-3">Ver Agenda</a>
            <div class="dropdown">
              <button class="btn btn-sm" data-bs-toggle="dropdown">•••</button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item btn-editar-prof" href="#" data-id="<?= $p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>" data-especialidade="<?= htmlspecialchars($p['especialidade'], ENT_QUOTES) ?>" data-telefone="<?= htmlspecialchars($p['telefone'], ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($p['email'], ENT_QUOTES) ?>" data-comissao="<?= $p['comissao'] ?>">Editar </a></li>
                <li><a class="dropdown-item" href="agenda.php?profissional=<?= $p['id'] ?>">Ver agendamentos</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="?excluir=<?= $p['id'] ?>" onclick="return confirm('Excluir este profissional?')">Excluir</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if(empty($profs)): ?><p class="text-center text-muted py-5"><?= $busca ? 'Nenhum profissional encontrado para "'.htmlspecialchars($busca).'".' : 'Nenhum profissional encontrado.' ?> Clique em + Novo Profissional.</p><?php endif; ?>
  <p class="text-muted small text-end mt-3">Exibindo <?= count($profs) ?> de <?= $total ?> profissionais • Atualizado hoje às <?= date('H:i') ?></p>
</div>

<!-- MODAL NOVO / EDITAR -->
<div class="modal fade" id="modalProf" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:18px;">
  <form method="POST" id="formProf" autocomplete="off">
  <div class="modal-header border-0"><h5 class="fw-bold" id="tituloModalProf">Novo Profissional</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="id" id="f_id">
    <label class="form-label small fw-bold">Nome completo</label><input name="nome" id="f_nome" class="form-control mb-3" required placeholder="Ex: Lucas Martins" autocomplete="off">
    <label class="form-label small fw-bold">Especialidade</label>
    <select name="especialidade" id="f_especialidade" class="form-select mb-3">
      <option value="Massoterapia • Ventosaterapia">Massoterapia • Ventosaterapia</option>
      <option value="Podologia">Podologia</option>
      <option value="Nail Designer">Nail Designer</option>
      <option value="Manicure • Pedicure">Manicure • Pedicure</option>
      <option value="Estética Facial">Estética Facial</option>
      <option value="Depilação">Depilação</option>
    </select>
    <label class="form-label small fw-bold">Telefone / WhatsApp</label><input name="telefone" id="f_telefone" class="form-control mb-3" placeholder="(87) 99999-0000" autocomplete="off">
    <label class="form-label small fw-bold">E-mail</label><input name="email" id="f_email" type="email" class="form-control mb-3" placeholder="profissional@email.com" autocomplete="off">
    <label class="form-label small fw-bold">Comissão (%)</label><input name="comissao" id="f_comissao" type="number" step="0.01" class="form-control" value="50" placeholder="Ex: 50">
    <div class="form-text">Comissão que o profissional recebe sobre cada atendimento.</div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lilas" id="btnSalvarProf">Salvar Profissional</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modalProfInstance = null;
function getModal(){ if(!modalProfInstance) modalProfInstance = new bootstrap.Modal(document.getElementById('modalProf')); return modalProfInstance; }

function setEspecialidade(valor){
  const sel = document.getElementById('f_especialidade');
  if (![...sel.options].some(o => o.value === valor)) {
    const opt = document.createElement('option');
    opt.value = valor; opt.textContent = valor;
    sel.appendChild(opt);
  }
  sel.value = valor;
}

function novoProf(){
  const form = document.getElementById('formProf');
  form.reset();
  document.getElementById('f_id').value = '';
  document.getElementById('f_comissao').value = 50;
  document.getElementById('tituloModalProf').textContent = 'Novo Profissional';
  document.getElementById('btnSalvarProf').textContent = 'Salvar Profissional';
  getModal().show();
}

document.querySelectorAll('.btn-editar-prof').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const id = btn.dataset.id;
    document.getElementById('f_id').value = id;
    document.getElementById('f_nome').value = btn.dataset.nome;
    setEspecialidade(btn.dataset.especialidade);
    document.getElementById('f_telefone').value = btn.dataset.telefone;
    document.getElementById('f_email').value = btn.dataset.email;
    document.getElementById('f_comissao').value = btn.dataset.comissao;
    document.getElementById('tituloModalProf').textContent = 'Editar Profissional';
    getModal().show();
  });
});

</script>
</body>
</html>