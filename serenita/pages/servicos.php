<?php
require_once __DIR__ . '/../config.php';
exigeLogin();
exigePerfil(['Administrador']);
$spa_id = $_SESSION['spa_id'];

// Excluir
if (isset($_GET['excluir'])) {
  $stmt = $pdo->prepare("UPDATE servicos SET ativo=0 WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['excluir'], $spa_id]);
  header("Location: servicos.php"); exit;
}

// Novo / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome']);
  $categoria = trim($_POST['categoria']);
  $duracao = (int)$_POST['duracao_min'];
  $preco = str_replace(',', '.', $_POST['preco']);
  $prof_id = $_POST['profissional_id'] ?: null;
  $id = $_POST['id'] ?? '';

  if ($id) {
    $stmt = $pdo->prepare("UPDATE servicos SET nome=?, categoria=?, duracao_min=?, preco=?, profissional_id=? WHERE id=? AND spa_id=?");
    $stmt->execute([$nome, $categoria, $duracao, $preco, $prof_id, $id, $spa_id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO servicos (spa_id, nome, categoria, duracao_min, preco, profissional_id, imagem) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$spa_id, $nome, $categoria, $duracao, $preco, $prof_id, 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&q=80']);
  }
  header("Location: servicos.php"); exit;
}

// Filtros
$categoria_filtro = $_GET['categoria'] ?? 'Todas';
$busca = trim($_GET['busca'] ?? '');

$sql = "SELECT s.*, p.nome as profissional FROM servicos s LEFT JOIN profissionais p ON p.id = s.profissional_id WHERE s.spa_id=? AND s.ativo=1";
$params = [$spa_id];
if ($categoria_filtro !== 'Todas') { $sql .= " AND s.categoria=?"; $params[] = $categoria_filtro; }
if ($busca) { $sql .= " AND (s.nome LIKE ? OR s.categoria LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
$sql .= " ORDER BY s.categoria, s.nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$servicos = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT DISTINCT categoria FROM servicos WHERE spa_id=? AND ativo=1"); $stmt->execute([$spa_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT * FROM profissionais WHERE spa_id=? AND ativo=1"); $stmt->execute([$spa_id]);
$profs = $stmt->fetchAll();

function imgServico($s){
  if(!empty($s['imagem'])) return $s['imagem'];
  $n = strtolower($s['nome']);
  if(str_contains($n,'ventosa')) return 'https://images.unsplash.com/photo-1519823551278-64ac92734fb1?w=600&q=80';
  if(str_contains($n,'podologia') || str_contains($n,'pedicure')) return 'https://images.unsplash.com/photo-1604654894610-df63bc536371?w=600&q=80';
  if(str_contains($n,'nail') || str_contains($n,'manicure')) return 'https://images.unsplash.com/photo-1604654894610-df63bc536371?w=600&q=80';
  return 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&q=80';
}
function corCategoria($c){
  if($c=='Massoterapia') return '#F3D1E8';
  if($c=='Ventosaterapia') return '#F5C8C8';
  if($c=='Podologia') return '#D8C9F0';
  if($c=='Nail Designer') return '#F9D8E8';
  if($c=='Manicure / Pedicure') return '#FCE0D8';
  return '#E8E0F0';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Serviços</title>
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
  .card-servico{ border:none; border-radius:18px; background:#fff; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.05); transition:.2s; }
  .card-servico:hover{ transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.08); }
  .card-servico img{ width:100%; height:180px; object-fit:cover; }
  .badge-nome{ background:#F9D8E8; color:#5A2A4A; padding:7px 14px; border-radius:20px; font-weight:700; font-size:14px; display:inline-block; }
  .badge-cat{ background:#EDE0F5; color:#6A3A6A; padding:4px 12px; border-radius:20px; font-size:11.5px; font-weight:600; display:inline-block; }
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
    <h4 class="fw-bold mb-0">Serviços</h4>
    <form method="GET" class="search-box m-0"><i class="bi bi-search text-muted"></i><input name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar serviço, profissional ou categoria..."></form>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d M Y') ?></span>
  </div>

  <h5 class="fw-bold mb-0">Serviços • <?= count($servicos) ?> serviços cadastrados</h5>
  <p class="text-muted small">Gerencie os serviços oferecidos na clínica</p>

  <form method="GET" class="d-flex gap-2 mb-4">
    <select name="categoria" class="form-select" style="border-radius:12px; background:#F8EEF8; border:none;" onchange="this.form.submit()">
      <option value="Todas">Categoria: Todas</option>
      <?php foreach($categorias as $c): ?>
      <option value="<?= htmlspecialchars($c) ?>" <?= $categoria_filtro==$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="btn btn-filtro"><i class="bi bi-funnel"></i> Filtros</button>
    <button type="button" class="btn btn-lilas" data-bs-toggle="modal" data-bs-target="#modalServico">+ Novo Serviço</button>
  </form>

  <div class="row g-3">
    <?php foreach($servicos as $s): ?>
    <div class="col-md-4">
      <div class="card card-servico">
        <img src="<?= imgServico($s) ?>" alt="<?= htmlspecialchars($s['nome']) ?>">
        <div class="card-body text-center">
          <span class="badge-nome"><?= htmlspecialchars($s['nome']) ?></span><br>
          <span class="badge-cat mt-2"><?= htmlspecialchars($s['categoria']) ?></span>
          <div class="mt-2 fw-bold"><i class="bi bi-clock"></i> <?= $s['duracao_min'] ?>min • R$ <?= number_format($s['preco'],2,',','.') ?></div>
          <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <span class="small text-muted"><img src="https://i.pravatar.cc/40?img=<?= $s['profissional_id']+10 ?>" style="width:22px; height:22px; border-radius:50%;"> Profissional: <?= htmlspecialchars($s['profissional'] ?? 'A definir') ?></span>
            <div class="dropdown">
              <button class="btn btn-sm" data-bs-toggle="dropdown">•••</button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="editarServico(<?= $s['id'] ?>,'<?= htmlspecialchars($s['nome'],ENT_QUOTES) ?>','<?= htmlspecialchars($s['categoria'],ENT_QUOTES) ?>',<?= $s['duracao_min'] ?>,<?= $s['preco'] ?>,<?= $s['profissional_id']?:'null' ?>)">Editar</a></li>
                <li><a class="dropdown-item text-danger" href="?excluir=<?= $s['id'] ?>" onclick="return confirm('Excluir este serviço?')">Excluir</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if(empty($servicos)): ?><p class="text-center text-muted py-5">Nenhum serviço encontrado. Clique em + Novo Serviço.</p><?php endif; ?>
  <p class="text-muted small text-end mt-3">Exibindo <?= count($servicos) ?> de <?= count($servicos) ?> serviços • Atualizado hoje às <?= date('H:i') ?></p>
</div>

<!-- MODAL NOVO / EDITAR -->
<div class="modal fade" id="modalServico" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:18px;">
  <form method="POST">
  <div class="modal-header border-0"><h5 class="fw-bold">Novo Serviço</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="id" id="f_id">
    <label class="form-label small fw-bold">Nome do serviço</label><input name="nome" id="f_nome" class="form-control mb-3" required placeholder="Ex: Massoterapia Relaxante">
    <label class="form-label small fw-bold">Categoria</label>
    <select name="categoria" id="f_categoria" class="form-select mb-3"><option>Massoterapia</option><option>Ventosaterapia</option><option>Podologia</option><option>Nail Designer</option><option>Manicure / Pedicure</option></select>
    <div class="row"><div class="col-6"><label class="form-label small fw-bold">Duração (min)</label><input name="duracao_min" id="f_duracao" type="number" class="form-control mb-3" value="60"></div>
    <div class="col-6"><label class="form-label small fw-bold">Preço (R$)</label><input name="preco" id="f_preco" class="form-control mb-3" value="120,00"></div></div>
    <label class="form-label small fw-bold">Profissional</label>
    <select name="profissional_id" id="f_prof" class="form-select"><option value="">A definir</option><?php foreach($profs as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lilas">Salvar Serviço</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarServico(id, nome, cat, dur, preco, prof){
  document.getElementById('f_id').value = id;
  document.getElementById('f_nome').value = nome;
  document.getElementById('f_categoria').value = cat;
  document.getElementById('f_duracao').value = dur;
  document.getElementById('f_preco').value = String(preco).replace('.',',');
  document.getElementById('f_prof').value = prof ?? '';
  new bootstrap.Modal(document.getElementById('modalServico')).show();
}
</script>
</body>
</html>