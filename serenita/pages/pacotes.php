<?php
require_once __DIR__ . '/../config.php';
exigeLogin();
$spa_id = $_SESSION['spa_id'];
$hoje = date('Y-m-d');

// Excluir pacote
if (isset($_GET['excluir'])) {
  $stmt = $pdo->prepare("UPDATE pacotes SET ativo=0 WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['excluir'], $spa_id]);
  header("Location: pacotes.php"); exit;
}

// Usar 1 sessão
if (isset($_GET['usar'])) {
  $stmt = $pdo->prepare("UPDATE pacotes_vendas SET sessoes_usadas = sessoes_usadas + 1 WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['usar'], $spa_id]);
  // Se completou, marca como Concluído
  $pdo->prepare("UPDATE pacotes_vendas SET status='Concluído' WHERE sessoes_usadas >= sessoes_total AND id=?")->execute([$_GET['usar']]);
  header("Location: pacotes.php"); exit;
}

// Cancelar venda
if (isset($_GET['cancelar_venda'])) {
  $stmt = $pdo->prepare("UPDATE pacotes_vendas SET status='Cancelado' WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['cancelar_venda'], $spa_id]);
  header("Location: pacotes.php"); exit;
}

// Novo / Editar pacote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao']=='pacote') {
  $nome = trim($_POST['nome']); $descricao = trim($_POST['descricao']);
  $preco = str_replace(',', '.', $_POST['preco']); $sessoes = (int)$_POST['sessoes']; $validade = (int)$_POST['validade_dias'];
  $id = $_POST['id'] ?? '';
  if ($id) {
    $stmt = $pdo->prepare("UPDATE pacotes SET nome=?, descricao=?, preco=?, sessoes=?, validade_dias=? WHERE id=? AND spa_id=?");
    $stmt->execute([$nome, $descricao, $preco, $sessoes, $validade, $id, $spa_id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO pacotes (spa_id, nome, descricao, preco, sessoes, validade_dias, imagem) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$spa_id, $nome, $descricao, $preco, $sessoes, $validade, 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&q=80']);
  }
  header("Location: pacotes.php"); exit;
}

// Vender pacote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao']=='vender') {
  $cliente_id = $_POST['cliente_id']; $pacote_id = $_POST['pacote_id'];
  $stmt = $pdo->prepare("SELECT * FROM pacotes WHERE id=? AND spa_id=?"); $stmt->execute([$pacote_id, $spa_id]);
  $pac = $stmt->fetch();
  $venc = date('Y-m-d', strtotime("+{$pac['validade_dias']} days"));
  $stmt = $pdo->prepare("INSERT INTO pacotes_vendas (spa_id, cliente_id, pacote_id, sessoes_total, valor_pago, data_compra, data_vencimento) VALUES (?,?,?,?,?,?,?)");
  $stmt->execute([$spa_id, $cliente_id, $pacote_id, $pac['sessoes'], $pac['preco'], $hoje, $venc]);
  header("Location: pacotes.php"); exit;
}

// Busca
$busca = trim($_GET['busca'] ?? '');
$sql = "SELECT * FROM pacotes WHERE spa_id=? AND ativo=1";
$params = [$spa_id];
if ($busca) { $sql .= " AND nome LIKE ?"; $params[] = "%$busca%"; }
$sql .= " ORDER BY nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$pacotes = $stmt->fetchAll();

// Pacotes ativos das pacientes
$stmt = $pdo->prepare("SELECT pv.*, c.nome as cliente, c.telefone, p.nome as pacote_nome, p.preco FROM pacotes_vendas pv JOIN clientes c ON c.id=pv.cliente_id JOIN pacotes p ON p.id=pv.pacote_id WHERE pv.spa_id=? AND pv.status='Ativo' ORDER BY pv.data_vencimento ASC");
$stmt->execute([$spa_id]); $ativos = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE spa_id=? AND ativo=1 ORDER BY nome"); $stmt->execute([$spa_id]); $clientes = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(valor_pago),0) FROM pacotes_vendas WHERE spa_id=? AND status != 'Cancelado'"); $stmt->execute([$spa_id]); $total_vendido = $stmt->fetchColumn();
function corPacote($nome){
  if(stripos($nome,'Relax')!==false) return '#D8C9F0';
  if(stripos($nome,'Pés')!==false || stripos($nome,'Beleza')!==false) return '#FCE0D8';
  if(stripos($nome,'Nail')!==false) return '#F0C9C9';
  return '#E8E0F0';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Pacotes</title>
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
  .card-pacote{ border:none; border-radius:18px; background:#fff; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.05); transition:.2s; }
  .card-pacote:hover{ transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.08); }
  .card-pacote img{ width:100%; height:160px; object-fit:cover; }
  .badge-nome{ background:#F9D8E8; color:#5A2A4A; padding:7px 14px; border-radius:20px; font-weight:700; font-size:14px; display:inline-block; }
  .badge-tag{ background:#EDE0F5; color:#6A3A6A; padding:4px 12px; border-radius:20px; font-size:11.5px; font-weight:600; display:inline-block; }
  .btn-lilas{ background:#D8A8E0; color:#fff; border:none; border-radius:10px; padding:8px 16px; font-weight:600; }
  .btn-lilas:hover{ background:#C795D0; color:#fff; }
  .btn-filtro{ background:#FCEAF6; border:none; border-radius:10px; padding:8px 16px; font-weight:600; color:#5A3A5A; }
  .card-serenita{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.04); }
  .user-box{ display:flex; align-items:center; gap:10px; background:#fff; padding:10px; border-radius:14px; margin-top:20px; }
  .user-box img{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
  @media(max-width:900px){ .sidebar{ display:none; } .main{ margin-left:0; } }
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <div class="d-flex align-items-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">Pacotes</h4>
    <form method="GET" class="search-box m-0"><i class="bi bi-search text-muted"></i><input name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar pacote..."></form>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d M Y') ?></span>
  </div>

  <h5 class="fw-bold mb-0">Pacotes • <?= count($pacotes) ?> pacotes à venda • <?= count($ativos) ?> ativos</h5>
  <p class="text-muted small">Total já vendido em pacotes: <b>R$ <?= number_format($total_vendido,2,',','.') ?></b></p>

  <div class="d-flex gap-2 mb-4">
    <button class="btn btn-filtro"><i class="bi bi-funnel"></i> Filtros</button>
    <button class="btn btn-lilas" data-bs-toggle="modal" data-bs-target="#modalVender">💰 Vender Pacote</button>
    <button class="btn btn-lilas ms-auto" data-bs-toggle="modal" data-bs-target="#modalPacote">+ Novo Pacote</button>
  </div>

  <!-- PACOTES À VENDA -->
  <h6 class="fw-bold mb-3">Pacotes à Venda</h6>
  <div class="row g-3 mb-4">
    <?php foreach($pacotes as $p): ?>
    <div class="col-md-4">
      <div class="card card-pacote">
        <img src="<?= htmlspecialchars($p['imagem'] ?: 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&q=80') ?>">
        <div class="card-body text-center">
          <span class="badge-nome"><?= htmlspecialchars($p['nome']) ?></span><br>
          <span class="badge-tag mt-2"><?= $p['sessoes'] ?> sessões • <?= $p['validade_dias'] ?> dias de validade</span>
          <p class="small text-muted mt-2 mb-1"><?= htmlspecialchars($p['descricao']) ?></p>
          <div class="fw-bold fs-5">R$ <?= number_format($p['preco'],2,',','.') ?> <small class="text-muted fw-normal" style="font-size:12px;">(R$ <?= number_format($p['preco']/$p['sessoes'],2,',','.') ?> / sessão)</small></div>
          <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <button class="btn btn-sm btn-light rounded-pill px-3" onclick="venderRapido(<?= $p['id'] ?>)">Vender</button>
            <div class="dropdown"><button class="btn btn-sm" data-bs-toggle="dropdown">•••</button><ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#" onclick="editarPacote(<?= $p['id'] ?>,'<?= htmlspecialchars($p['nome'],ENT_QUOTES) ?>','<?= htmlspecialchars($p['descricao'],ENT_QUOTES) ?>',<?= $p['preco'] ?>,<?= $p['sessoes'] ?>,<?= $p['validade_dias'] ?>)">Editar</a></li>
              <li><a class="dropdown-item text-danger" href="?excluir=<?= $p['id'] ?>" onclick="return confirm('Excluir este pacote?')">Excluir</a></li>
            </ul></div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- PACOTES ATIVOS -->
  <div class="card card-serenita p-3">
    <div class="d-flex justify-content-between align-items-center mb-3"><h6 class="fw-bold mb-0">Pacotes Ativos das Pacientes</h6><span class="badge bg-light text-dark border"><?= count($ativos) ?> ativos</span></div>
    <?php if(empty($ativos)): ?><p class="text-muted text-center py-4">Nenhum pacote ativo. Clique em Vender Pacote para vender o primeiro.</p><?php endif; ?>
    <?php foreach($ativos as $a): $perc = $a['sessoes_total']>0 ? ($a['sessoes_usadas']/$a['sessoes_total']*100) : 0; $dias = (strtotime($a['data_vencimento']) - strtotime($hoje))/86400; ?>
    <div class="mb-3 pb-3 border-bottom">
      <div class="d-flex justify-content-between flex-wrap"><b><?= htmlspecialchars($a['pacote_nome']) ?> — <?= htmlspecialchars($a['cliente']) ?></b><span class="small"><?= round($perc) ?>% • Vence em <?= $dias ?> dias (<?= date('d/m/Y', strtotime($a['data_vencimento'])) ?>)</span></div>
      <div class="small text-muted"><?= $a['sessoes_usadas'] ?> de <?= $a['sessoes_total'] ?> sessões utilizadas • R$ <?= number_format($a['valor_pago'],2,',','.') ?> • <?= htmlspecialchars($a['telefone']) ?></div>
      <div class="d-flex gap-2 align-items-center mt-2">
        <div class="progress flex-grow-1" style="height:8px; background:#F0E0F0;"><div class="progress-bar" style="width:<?= $perc ?>%; background:<?= $dias<=7 ? '#F0A0A0' : '#E3B8E3' ?>;"></div></div>
        <a href="?usar=<?= $a['id'] ?>" class="btn btn-sm btn-lilas" onclick="return confirm('Marcar 1 sessão como utilizada?')">+ Usar Sessão</a>
        <a href="?cancelar_venda=<?= $a['id'] ?>" class="btn btn-sm btn-light" onclick="return confirm('Cancelar este pacote?')">Cancelar</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="text-muted small text-end mt-3">Atualizado hoje às <?= date('H:i') ?></p>
</div>

<!-- MODAL NOVO PACOTE -->
<div class="modal fade" id="modalPacote" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:18px;">
  <form method="POST"><input type="hidden" name="acao" value="pacote"><input type="hidden" name="id" id="f_id">
  <div class="modal-header border-0"><h5 class="fw-bold">Novo Pacote</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label small fw-bold">Nome do pacote</label><input name="nome" id="f_nome" class="form-control mb-3" required placeholder="Ex: Pacote Relax Spa">
    <label class="form-label small fw-bold">Descrição</label><textarea name="descricao" id="f_desc" class="form-control mb-3" rows="2" placeholder="O que está incluso?"></textarea>
    <div class="row"><div class="col-6"><label class="form-label small fw-bold">Preço total (R$)</label><input name="preco" id="f_preco" class="form-control mb-3" value="499,00"></div>
    <div class="col-3"><label class="form-label small fw-bold">Sessões</label><input name="sessoes" id="f_sessoes" type="number" class="form-control mb-3" value="5"></div>
    <div class="col-3"><label class="form-label small fw-bold">Validade (dias)</label><input name="validade_dias" id="f_validade" type="number" class="form-control mb-3" value="60"></div></div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lilas">Salvar Pacote</button></div>
  </form>
</div></div></div>

<!-- MODAL VENDER -->
<div class="modal fade" id="modalVender" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:18px;">
  <form method="POST"><input type="hidden" name="acao" value="vender">
  <div class="modal-header border-0"><h5 class="fw-bold">Vender Pacote</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label small fw-bold">Paciente</label><select name="cliente_id" class="form-select mb-3" required><option value="">Selecione...</option><?php foreach($clientes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option><?php endforeach; ?></select>
    <label class="form-label small fw-bold">Pacote</label><select name="pacote_id" id="f_vender_pacote" class="form-select mb-3" required><option value="">Selecione...</option><?php foreach($pacotes as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> — R$ <?= number_format($p['preco'],2,',','.') ?> (<?= $p['sessoes'] ?> sessões)</option><?php endforeach; ?></select>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lilas">Confirmar Venda</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarPacote(id, nome, desc, preco, sessoes, validade){
  document.getElementById('f_id').value=id; document.getElementById('f_nome').value=nome; document.getElementById('f_desc').value=desc;
  document.getElementById('f_preco').value=String(preco).replace('.',','); document.getElementById('f_sessoes').value=sessoes; document.getElementById('f_validade').value=validade;
  new bootstrap.Modal(document.getElementById('modalPacote')).show();
}
function venderRapido(id){
  document.getElementById('f_vender_pacote').value=id;
  new bootstrap.Modal(document.getElementById('modalVender')).show();
}
</script>
</body>
</html>