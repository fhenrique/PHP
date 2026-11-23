<?php
//estoque.php
require_once __DIR__ . '/../config.php';
exigeLogin();
$spa_id = $_SESSION['spa_id'];

// Excluir
if (isset($_GET['excluir'])) {
  $stmt = $pdo->prepare("UPDATE estoque SET ativo=0 WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['excluir'], $spa_id]);
  header("Location: estoque.php"); exit;
}

// Entrada / Saída rápida
if (isset($_GET['entrada'])) {
  $pdo->prepare("UPDATE estoque SET quantidade = quantidade + 1 WHERE id=? AND spa_id=?")->execute([$_GET['entrada'], $spa_id]);
  header("Location: estoque.php"); exit;
}
if (isset($_GET['saida'])) {
  $pdo->prepare("UPDATE estoque SET quantidade = GREATEST(0, quantidade - 1) WHERE id=? AND spa_id=?")->execute([$_GET['saida'], $spa_id]);
  header("Location: estoque.php"); exit;
}

// Novo / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome']); $categoria = trim($_POST['categoria']);
  $qtd = (int)$_POST['quantidade']; $minimo = (int)$_POST['minimo'];
  $custo = str_replace(',', '.', $_POST['preco_custo']); $venda = str_replace(',', '.', $_POST['preco_venda']);
  $unidade = $_POST['unidade']; $id = $_POST['id'] ?? '';
  if ($id) {
    $stmt = $pdo->prepare("UPDATE estoque SET nome=?, categoria=?, quantidade=?, minimo=?, preco_custo=?, preco_venda=?, unidade=? WHERE id=? AND spa_id=?");
    $stmt->execute([$nome, $categoria, $qtd, $minimo, $custo, $venda, $unidade, $id, $spa_id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO estoque (spa_id, nome, categoria, quantidade, minimo, preco_custo, preco_venda, unidade) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$spa_id, $nome, $categoria, $qtd, $minimo, $custo, $venda, $unidade]);
  }
  header("Location: estoque.php"); exit;
}

// Filtros
$busca = trim($_GET['busca'] ?? '');
$filtro = $_GET['filtro'] ?? 'Todos';
$sql = "SELECT * FROM estoque WHERE spa_id=? AND ativo=1";
$params = [$spa_id];
if ($busca) { $sql .= " AND (nome LIKE ? OR categoria LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($filtro == 'Estoque baixo') { $sql .= " AND quantidade <= minimo"; }
if ($filtro == 'Zerado') { $sql .= " AND quantidade = 0"; }
if ($filtro == 'OK') { $sql .= " AND quantidade > minimo"; }
$sql .= " ORDER BY quantidade ASC, nome";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$itens = $stmt->fetchAll();

// KPIs
$stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(quantidade),0), COALESCE(SUM(quantidade*preco_custo),0) FROM estoque WHERE spa_id=? AND ativo=1"); $stmt->execute([$spa_id]); $kpi = $stmt->fetch(PDO::FETCH_NUM);
$stmt = $pdo->prepare("SELECT COUNT(*) FROM estoque WHERE spa_id=? AND ativo=1 AND quantidade <= minimo"); $stmt->execute([$spa_id]); $baixo = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM estoque WHERE spa_id=? AND ativo=1 AND quantidade = 0"); $stmt->execute([$spa_id]); $zerado = $stmt->fetchColumn();

function corCategoria($c){
  if($c=='Massoterapia') return '#D8C9F0';
  if($c=='Nail Designer') return '#F0C9C9';
  if($c=='Podologia') return '#C9D8F0';
  if($c=='Estética') return '#FCE0D8';
  if($c=='Ambiente') return '#D8E8C9';
  return '#E8E0F0';
}
function emojiProduto($nome){
  $n = strtolower($nome);
  if(str_contains($n,'óleo')) return '🧴';
  if(str_contains($n,'creme')) return '🧴';
  if(str_contains($n,'esmalte')) return '💅';
  if(str_contains($n,'lixa')) return '🦶';
  if(str_contains($n,'vela')) return '🕯️';
  if(str_contains($n,'toalha')) return '🧺';
  return '📦';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Estoque</title>
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
  .card-kpi{ border:none; border-radius:18px; padding:18px; }
  .kpi-1{ background:#FFEFEF; } .kpi-2{ background:#FFF0E6; } .kpi-3{ background:#FFEAF6; } .kpi-4{ background:#F0EBFF; }
  .card-serenita{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.04); }
  .card-prod{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.05); transition:.2s; }
  .card-prod:hover{ transform:translateY(-3px); }
  .card-prod.baixo{ border:2px solid #F0A0A0; }
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
    <h4 class="fw-bold mb-0">Estoque</h4>
    <form method="GET" class="search-box m-0"><i class="bi bi-search text-muted"></i><input name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar produto..."></form>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d M Y') ?></span>
  </div>

  <h5 class="fw-bold mb-0">Estoque • <?= $kpi[0] ?> produtos</h5>
  <p class="text-muted small">Controle de produtos e materiais da clínica</p>

  <?php if($baixo > 0): ?>
  <div class="alert alert-danger d-flex align-items-center gap-2" style="border-radius:14px; border:none; background:#FFEFEF; color:#8A2A2A;"><i class="bi bi-exclamation-triangle-fill"></i><b>Atenção:</b> <?= $baixo ?> produto(s) com estoque baixo ou zerado. Repõe antes que acabe!</div>
  <?php endif; ?>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-kpi kpi-1"><small>📦 Total de produtos</small><h3 class="fw-bold mt-1 mb-0"><?= $kpi[0] ?></h3><small class="text-muted"><?= $kpi[1] ?> unidades em estoque</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-2"><small>⚠️ Estoque baixo</small><h3 class="fw-bold mt-1 mb-0 text-danger"><?= $baixo ?></h3><small class="text-muted">Precisam de reposição</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-3"><small>🚫 Zerados</small><h3 class="fw-bold mt-1 mb-0"><?= $zerado ?></h3><small class="text-muted">Sem unidade em estoque</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-4"><small>💰 Valor em estoque (custo)</small><h3 class="fw-bold mt-1 mb-0">R$ <?= number_format($kpi[2],2,',','.') ?></h3><small class="text-muted">Investimento parado</small></div></div>
  </div>

  <form method="GET" class="d-flex gap-2 mb-4">
    <select name="filtro" class="form-select" style="border-radius:12px; background:#F8EEF8; border:none; max-width:220px;" onchange="this.form.submit()">
      <option value="Todos" <?= $filtro=='Todos'?'selected':'' ?>>Status: Todos</option>
      <option value="Estoque baixo" <?= $filtro=='Estoque baixo'?'selected':'' ?>>Estoque baixo</option>
      <option value="Zerado" <?= $filtro=='Zerado'?'selected':'' ?>>Zerados</option>
      <option value="OK" <?= $filtro=='OK'?'selected':'' ?>>Estoque OK</option>
    </select>
    <button type="button" class="btn btn-filtro"><i class="bi bi-funnel"></i> Filtros</button>
    <button type="button" class="btn btn-lilas ms-auto" data-bs-toggle="modal" data-bs-target="#modalEstoque">+ Novo Produto</button>
  </form>

  <div class="row g-3">
    <?php foreach($itens as $it): $isBaixo = $it['quantidade'] <= $it['minimo']; ?>
    <div class="col-md-4">
      <div class="card card-prod <?= $isBaixo?'baixo':'' ?>">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div style="font-size:42px;"><?= emojiProduto($it['nome']) ?></div>
            <?php if($it['quantidade']==0): ?><span class="badge bg-danger rounded-pill">ZERADO</span>
            <?php elseif($isBaixo): ?><span class="badge bg-warning text-dark rounded-pill">ESTOQUE BAIXO</span>
            <?php else: ?><span class="badge bg-success rounded-pill">OK</span><?php endif; ?>
          </div>
          <h6 class="fw-bold mt-2 mb-1"><?= htmlspecialchars($it['nome']) ?></h6>
          <span class="badge-tag" style="background:<?= corCategoria($it['categoria']) ?>"><?= htmlspecialchars($it['categoria']) ?></span>
          <div class="d-flex justify-content-between mt-3">
            <div><div class="small text-muted">Quantidade</div><b style="font-size:22px; color:<?= $isBaixo?'#C0392B':'#333' ?>;"><?= $it['quantidade'] ?> <?= htmlspecialchars($it['unidade']) ?></b><div class="small text-muted">Mínimo: <?= $it['minimo'] ?></div></div>
            <div class="text-end"><div class="small text-muted">Custo / Venda</div><b>R$ <?= number_format($it['preco_venda'],2,',','.') ?></b><div class="small text-muted">Custo: R$ <?= number_format($it['preco_custo'],2,',','.') ?></div></div>
          </div>
          <div class="progress mt-2" style="height:7px; background:#F0E0F0;"><div class="progress-bar" style="width:<?= min(100, $it['quantidade'] / max(1,$it['minimo']*3) * 100) ?>%; background:<?= $isBaixo ? '#F0A0A0' : '#E3B8E3' ?>;"></div></div>
          <div class="d-flex gap-2 mt-3">
            <a href="?saida=<?= $it['id'] ?>" class="btn btn-sm btn-light flex-grow-1">− Usar 1</a>
            <a href="?entrada=<?= $it['id'] ?>" class="btn btn-sm btn-lilas flex-grow-1">+ Entrada</a>
            <div class="dropdown"><button class="btn btn-sm btn-light" data-bs-toggle="dropdown">•••</button><ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#" onclick="editarItem(<?= $it['id'] ?>,'<?= htmlspecialchars($it['nome'],ENT_QUOTES) ?>','<?= htmlspecialchars($it['categoria'],ENT_QUOTES) ?>',<?= $it['quantidade'] ?>,<?= $it['minimo'] ?>,<?= $it['preco_custo'] ?>,<?= $it['preco_venda'] ?>,'<?= $it['unidade'] ?>')">Editar</a></li>
              <li><a class="dropdown-item text-danger" href="?excluir=<?= $it['id'] ?>" onclick="return confirm('Excluir este produto?')">Excluir</a></li>
            </ul></div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if(empty($itens)): ?><p class="text-center text-muted py-5">Nenhum produto encontrado.</p><?php endif; ?>
  <p class="text-muted small text-end mt-3">Atualizado hoje às <?= date('H:i') ?></p>
</div>

<!-- MODAL NOVO / EDITAR -->
<div class="modal fade" id="modalEstoque" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:18px;">
  <form method="POST"><input type="hidden" name="id" id="f_id">
  <div class="modal-header border-0"><h5 class="fw-bold">Novo Produto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label small fw-bold">Nome do produto</label><input name="nome" id="f_nome" class="form-control mb-3" required placeholder="Ex: Óleo de Massagem">
    <label class="form-label small fw-bold">Categoria</label>
    <select name="categoria" id="f_categoria" class="form-select mb-3"><option>Massoterapia</option><option>Estética</option><option>Nail Designer</option><option>Podologia</option><option>Ambiente</option><option>Lavanderia</option><option>Geral</option></select>
    <div class="row"><div class="col-4"><label class="form-label small fw-bold">Quantidade</label><input name="quantidade" id="f_qtd" type="number" class="form-control mb-3" value="10"></div>
    <div class="col-4"><label class="form-label small fw-bold">Mínimo</label><input name="minimo" id="f_minimo" type="number" class="form-control mb-3" value="5"></div>
    <div class="col-4"><label class="form-label small fw-bold">Unidade</label><select name="unidade" id="f_unidade" class="form-select mb-3"><option value="un">un</option><option value="ml">ml</option><option value="L">L</option><option value="kg">kg</option><option value="cx">cx</option></select></div></div>
    <div class="row"><div class="col-6"><label class="form-label small fw-bold">Preço de custo (R$)</label><input name="preco_custo" id="f_custo" class="form-control mb-3" value="0,00"></div>
    <div class="col-6"><label class="form-label small fw-bold">Preço de venda (R$)</label><input name="preco_venda" id="f_venda" class="form-control mb-3" value="0,00"></div></div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lilas">Salvar Produto</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarItem(id, nome, cat, qtd, minimo, custo, venda, unidade){
  document.getElementById('f_id').value=id; document.getElementById('f_nome').value=nome; document.getElementById('f_categoria').value=cat;
  document.getElementById('f_qtd').value=qtd; document.getElementById('f_minimo').value=minimo;
  document.getElementById('f_custo').value=String(custo).replace('.',','); document.getElementById('f_venda').value=String(venda).replace('.',',');
  document.getElementById('f_unidade').value=unidade;
  new bootstrap.Modal(document.getElementById('modalEstoque')).show();
}
</script>
</body>
</html>