<?php
//faturamento.php
require_once __DIR__ . '/../config.php';
exigeLogin();
exigePerfil(['Administrador']);

$spa_id = $_SESSION['spa_id'];

// Filtros
$periodo = $_GET['periodo'] ?? 'mes';
$filtro_prof = $_GET['profissional'] ?? 'Todos';
$busca = trim($_GET['busca'] ?? '');

// Define datas
$hoje = date('Y-m-d');
if ($periodo == 'hoje') { $data_ini = $hoje; $data_fim = $hoje; }
elseif ($periodo == 'semana') { $data_ini = date('Y-m-d', strtotime('monday this week')); $data_fim = date('Y-m-d', strtotime('sunday this week')); }
elseif ($periodo == 'ano') { $data_ini = date('Y-01-01'); $data_fim = date('Y-12-31'); }
else { $data_ini = date('Y-m-01'); $data_fim = date('Y-m-t'); } // mês

// KPIs
function totalPeriodo($pdo, $spa_id, $ini, $fim, $status = null){
  $sql = "SELECT COALESCE(SUM(valor),0), COUNT(*) FROM agendamentos WHERE spa_id=? AND data BETWEEN ? AND ? AND status != 'Cancelado'";
  $params = [$spa_id, $ini, $fim];
  if($status){ $sql .= " AND status=?"; $params[] = $status; }
  $stmt = $pdo->prepare($sql); $stmt->execute($params);
  return $stmt->fetch(PDO::FETCH_NUM);
}
list($fat_periodo, $qtd_periodo) = totalPeriodo($pdo, $spa_id, $data_ini, $data_fim);
list($fat_hoje, $qtd_hoje) = totalPeriodo($pdo, $spa_id, $hoje, $hoje);
list($fat_concluido, $qtd_concluido) = totalPeriodo($pdo, $spa_id, $data_ini, $data_fim, 'Concluído');
list($fat_pendente, $qtd_pendente) = totalPeriodo($pdo, $spa_id, $data_ini, $data_fim, 'Confirmado');

// Ticket médio
$ticket = $qtd_periodo > 0 ? $fat_periodo / $qtd_periodo : 0;

// Gráfico - últimos 14 dias
$stmt = $pdo->prepare("SELECT data, COALESCE(SUM(valor),0) as total FROM agendamentos WHERE spa_id=? AND data BETWEEN ? AND ? AND status != 'Cancelado' GROUP BY data ORDER BY data");
$stmt->execute([$spa_id, date('Y-m-d', strtotime('-13 days')), $hoje]);
$grafico_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$labels = []; $valores = [];
for($i=13;$i>=0;$i--){ $d = date('Y-m-d', strtotime("-$i days")); $labels[] = date('d/m', strtotime($d)); $valores[] = (float)($grafico_raw[$d] ?? 0); }

// Faturamento por profissional
$stmt = $pdo->prepare("SELECT p.nome, COUNT(a.id) as qtd, COALESCE(SUM(a.valor),0) as total, COALESCE(SUM(a.valor * p.comissao / 100),0) as comissao FROM agendamentos a JOIN profissionais p ON p.id=a.profissional_id WHERE a.spa_id=? AND a.data BETWEEN ? AND ? AND a.status != 'Cancelado' GROUP BY p.id ORDER BY total DESC");
$stmt->execute([$spa_id, $data_ini, $data_fim]);
$por_prof = $stmt->fetchAll();

// Lista detalhada
$sql = "SELECT a.*, c.nome as cliente, p.nome as profissional, s.nome as servico FROM agendamentos a JOIN clientes c ON c.id=a.cliente_id JOIN profissionais p ON p.id=a.profissional_id JOIN servicos s ON s.id=a.servico_id WHERE a.spa_id=? AND a.data BETWEEN ? AND ?";
$params = [$spa_id, $data_ini, $data_fim];
if ($filtro_prof !== 'Todos') { $sql .= " AND a.profissional_id=?"; $params[] = $filtro_prof; }
if ($busca) { $sql .= " AND (c.nome LIKE ? OR s.nome LIKE ? OR p.nome LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
$sql .= " ORDER BY a.data DESC, a.hora DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$lista = $stmt->fetchAll();

// Lista profs para filtro
$stmt = $pdo->prepare("SELECT * FROM profissionais WHERE spa_id=? AND ativo=1 ORDER BY nome"); $stmt->execute([$spa_id]); $profs = $stmt->fetchAll();

function corStatus($s){
  if($s=='Confirmado') return 'bg-success';
  if($s=='Em andamento') return 'bg-primary';
  if($s=='Concluído') return 'bg-secondary';
  if($s=='Pendente') return 'bg-warning text-dark';
  if($s=='Cancelado') return 'bg-danger';
  return 'bg-secondary';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Faturamento</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
  .kpi-1{ background:#FFEFEF; } .kpi-2{ background:#F0EBFF; } .kpi-3{ background:#FFEAF6; } .kpi-4{ background:#FFF0E6; }
  .card-serenita{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.04); }
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
    <h4 class="fw-bold mb-0">Faturamento</h4>
    <form method="GET" class="search-box m-0"><input type="hidden" name="periodo" value="<?= $periodo ?>"><input type="hidden" name="profissional" value="<?= $filtro_prof ?>"><i class="bi bi-search text-muted"></i><input name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar paciente, serviço ou profissional..."></form>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($data_ini)) ?> — <?= date('d/m/Y', strtotime($data_fim)) ?></span>
  </div>

  <h5 class="fw-bold mb-0">Faturamento • <?= $periodo=='hoje'?'Hoje':($periodo=='semana'?'Esta semana':($periodo=='ano'?'Este ano':'Este mês')) ?></h5>
  <p class="text-muted small">Acompanhe o faturamento da clínica em tempo real</p>

  <!-- FILTROS -->
  <form method="GET" class="d-flex gap-2 mb-4 flex-wrap">
    <select name="periodo" class="form-select" style="border-radius:12px; background:#F8EEF8; border:none; max-width:200px;" onchange="this.form.submit()">
      <option value="hoje" <?= $periodo=='hoje'?'selected':'' ?>>Hoje</option>
      <option value="semana" <?= $periodo=='semana'?'selected':'' ?>>Esta semana</option>
      <option value="mes" <?= $periodo=='mes'?'selected':'' ?>>Este mês</option>
      <option value="ano" <?= $periodo=='ano'?'selected':'' ?>>Este ano</option>
    </select>
    <select name="profissional" class="form-select" style="border-radius:12px; background:#F8EEF8; border:none; max-width:220px;" onchange="this.form.submit()">
      <option value="Todos">Profissional: Todos</option>
      <?php foreach($profs as $p): ?><option value="<?= $p['id'] ?>" <?= $filtro_prof==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nome']) ?></option><?php endforeach; ?>
    </select>
    <button type="button" class="btn btn-filtro" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
    <a href="agenda.php" class="btn btn-lilas ms-auto">+ Novo Agendamento</a>
  </form>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-kpi kpi-1"><small>💵 Faturamento no período</small><h3 class="fw-bold mt-1 mb-0">R$ <?= number_format($fat_periodo,2,',','.') ?></h3><small class="text-muted"><?= $qtd_periodo ?> agendamentos</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-2"><small>📅 Faturamento de hoje</small><h3 class="fw-bold mt-1 mb-0">R$ <?= number_format($fat_hoje,2,',','.') ?></h3><small class="text-muted"><?= $qtd_hoje ?> agendamentos hoje</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-3"><small>✅ Já recebido (Concluído)</small><h3 class="fw-bold mt-1 mb-0">R$ <?= number_format($fat_concluido,2,',','.') ?></h3><small class="text-muted"><?= $qtd_concluido ?> concluídos</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-4"><small>📈 Ticket médio</small><h3 class="fw-bold mt-1 mb-0">R$ <?= number_format($ticket,2,',','.') ?></h3><small class="text-muted">por agendamento</small></div></div>
  </div>

  <div class="row g-3">
    <!-- GRÁFICO -->
    <div class="col-lg-8">
      <div class="card card-serenita p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0">Faturamento — Últimos 14 dias</h6><span class="badge bg-light text-dark border">R$ <?= number_format(array_sum($valores),2,',','.') ?> no período</span></div>
        <canvas id="graficoFat" height="140"></canvas>
      </div>

      <!-- LISTA DETALHADA -->
      <div class="card card-serenita p-3">
        <div class="d-flex justify-content-between align-items-center mb-3"><h6 class="fw-bold mb-0">Lançamentos — <?= count($lista) ?> registros</h6><span class="small text-muted">Total: R$ <?= number_format(array_sum(array_column($lista,'valor')),2,',','.') ?></span></div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Data</th><th>Paciente</th><th>Serviço</th><th>Profissional</th><th>Status</th><th class="text-end">Valor</th></tr></thead>
            <tbody>
              <?php foreach($lista as $a): ?>
              <tr>
                <td><b><?= date('d/m/Y', strtotime($a['data'])) ?></b><br><small class="text-muted"><?= substr($a['hora'],0,5) ?></small></td>
                <td><?= htmlspecialchars($a['cliente']) ?></td>
                <td><?= htmlspecialchars($a['servico']) ?></td>
                <td><?= htmlspecialchars($a['profissional']) ?></td>
                <td><span class="badge <?= corStatus($a['status']) ?> rounded-pill px-3 py-1"><?= htmlspecialchars($a['status']) ?></span></td>
                <td class="text-end fw-bold">R$ <?= number_format($a['valor'],2,',','.') ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($lista)): ?><tr><td colspan="6" class="text-center text-muted py-4">Nenhum lançamento neste período.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- POR PROFISSIONAL -->
    <div class="col-lg-4">
      <div class="card card-serenita p-3 mb-3">
        <h6 class="fw-bold mb-3">Faturamento por Profissional</h6>
        <?php foreach($por_prof as $p): ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between"><b style="font-size:14px;"><?= htmlspecialchars($p['nome']) ?></b><b>R$ <?= number_format($p['total'],2,',','.') ?></b></div>
          <div class="small text-muted"><?= $p['qtd'] ?> atendimentos • Comissão: R$ <?= number_format($p['comissao'],2,',','.') ?></div>
          <div class="progress mt-1" style="height:7px; background:#F0E0F0;"><div class="progress-bar" style="width:<?= $fat_periodo>0 ? ($p['total']/$fat_periodo*100) : 0 ?>%; background:#E3B8E3;"></div></div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($por_prof)): ?><p class="text-muted small">Sem dados neste período.</p><?php endif; ?>
      </div>

      <div class="card card-serenita p-3">
        <h6 class="fw-bold mb-3">Resumo</h6>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>A receber (Confirmado)</span><b>R$ <?= number_format($fat_pendente,2,',','.') ?></b></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>Já recebido</span><b class="text-success">R$ <?= number_format($fat_concluido,2,',','.') ?></b></div>
        <div class="d-flex justify-content-between py-2"><span>Total no período</span><b>R$ <?= number_format($fat_periodo,2,',','.') ?></b></div>
        <p class="text-muted small mt-3 mb-0">Atualizado hoje às <?= date('H:i') ?> • Marque o agendamento como Concluído na Agenda para ele entrar em Já recebido.</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('graficoFat');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{ label: 'Faturamento R$', data: <?= json_encode($valores) ?>, backgroundColor: '#E3B8E3', borderRadius: 8, maxBarThickness: 22 }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'R$ ' + v } }, x: { grid: { display: false } } } }
});
</script>
</body>
</html>