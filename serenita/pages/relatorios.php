<?php
//relatorios.php
require_once __DIR__ . '/../config.php';
exigeLogin();
exigePerfil(['Administrador']);
$spa_id = $_SESSION['spa_id'];
$hoje = date('Y-m-d');

// Filtro de período
$periodo = $_GET['periodo'] ?? 'mes';
if ($periodo == 'hoje') { $ini = $hoje; $fim = $hoje; $label = 'Hoje'; }
elseif ($periodo == 'semana') { $ini = date('Y-m-d', strtotime('monday this week')); $fim = date('Y-m-d', strtotime('sunday this week')); $label = 'Esta semana'; }
elseif ($periodo == 'ano') { $ini = date('Y-01-01'); $fim = date('Y-12-31'); $label = 'Este ano'; }
else { $ini = date('Y-m-01'); $fim = date('Y-m-t'); $label = 'Este mês'; }

// KPIs do período
$stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0), COUNT(*) FROM agendamentos WHERE spa_id=? AND data BETWEEN ? AND ? AND status != 'Cancelado'"); $stmt->execute([$spa_id, $ini, $fim]); $r = $stmt->fetch(PDO::FETCH_NUM); $fat = $r[0]; $qtd = $r[1];
$ticket = $qtd > 0 ? $fat / $qtd : 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE spa_id=? AND DATE(criado_em) BETWEEN ? AND ?"); $stmt->execute([$spa_id, $ini, $fim]); $clientes_novos = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE spa_id=? AND data BETWEEN ? AND ? AND status='Cancelado'"); $stmt->execute([$spa_id, $ini, $fim]); $cancelados = $stmt->fetchColumn();
$taxa_cancel = ($qtd + $cancelados) > 0 ? $cancelados / ($qtd + $cancelados) * 100 : 0;

// Gráfico 1 - Faturamento por dia (últimos 14 dias)
$stmt = $pdo->prepare("SELECT data, COALESCE(SUM(valor),0) as total FROM agendamentos WHERE spa_id=? AND data BETWEEN ? AND ? AND status != 'Cancelado' GROUP BY data ORDER BY data");
$stmt->execute([$spa_id, date('Y-m-d', strtotime('-13 days')), $hoje]); $raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$labels14 = []; $valores14 = [];
for($i=13;$i>=0;$i--){ $d = date('Y-m-d', strtotime("-$i days")); $labels14[] = date('d/m', strtotime($d)); $valores14[] = (float)($raw[$d] ?? 0); }

// Gráfico 2 - Faturamento por mês (últimos 6 meses)
$labelsMes = []; $valoresMes = [];
for($i=5;$i>=0;$i--){ $m = date('Y-m', strtotime("-$i months")); $labelsMes[] = date('M/Y', strtotime($m.'-01')); $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM agendamentos WHERE spa_id=? AND DATE_FORMAT(data,'%Y-%m')=? AND status != 'Cancelado'"); $stmt->execute([$spa_id, $m]); $valoresMes[] = (float)$stmt->fetchColumn(); }

// Top Serviços
$stmt = $pdo->prepare("SELECT s.nome, COUNT(a.id) as qtd, COALESCE(SUM(a.valor),0) as total FROM agendamentos a JOIN servicos s ON s.id=a.servico_id WHERE a.spa_id=? AND a.data BETWEEN ? AND ? AND a.status != 'Cancelado' GROUP BY s.id ORDER BY total DESC LIMIT 5"); $stmt->execute([$spa_id, $ini, $fim]); $topServ = $stmt->fetchAll();

// Por Profissional
$stmt = $pdo->prepare("SELECT p.nome, COUNT(a.id) as qtd, COALESCE(SUM(a.valor),0) as total FROM agendamentos a JOIN profissionais p ON p.id=a.profissional_id WHERE a.spa_id=? AND a.data BETWEEN ? AND ? AND a.status != 'Cancelado' GROUP BY p.id ORDER BY total DESC"); $stmt->execute([$spa_id, $ini, $fim]); $porProf = $stmt->fetchAll();

// Status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as qtd FROM agendamentos WHERE spa_id=? AND data BETWEEN ? AND ? GROUP BY status"); $stmt->execute([$spa_id, $ini, $fim]); $porStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Top Clientes
$stmt = $pdo->prepare("SELECT c.nome, COUNT(a.id) as qtd, COALESCE(SUM(a.valor),0) as total FROM agendamentos a JOIN clientes c ON c.id=a.cliente_id WHERE a.spa_id=? AND a.data BETWEEN ? AND ? AND a.status != 'Cancelado' GROUP BY c.id ORDER BY total DESC LIMIT 5"); $stmt->execute([$spa_id, $ini, $fim]); $topClientes = $stmt->fetchAll();

function corServico($nome){
  if(stripos($nome,'Manicure')!==false) return '#E8C6E8';
  if(stripos($nome,'Massoterapia')!==false) return '#D8C9F0';
  if(stripos($nome,'Podologia')!==false) return '#C9D8F0';
  if(stripos($nome,'Ventosa')!==false) return '#F0D8C9';
  if(stripos($nome,'Nail')!==false) return '#F0C9C9';
  return '#E8E0F0';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenità - Relatórios</title>
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
  .user-box{ display:flex; align-items:center; gap:10px; background:#fff; padding:10px; border-radius:14px; margin-top:20px; }
  .user-box img{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
  @media(max-width:900px){ .sidebar{ display:none; } .main{ margin-left:0; } }
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <div class="d-flex align-items-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">Relatórios</h4>
    <div class="search-box"><i class="bi bi-search text-muted"></i><input placeholder="Buscar..."></div>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= $label ?> • <?= date('d/m/Y', strtotime($ini)) ?> — <?= date('d/m/Y', strtotime($fim)) ?></span>
  </div>

  <h5 class="fw-bold mb-0">Relatórios • <?= $label ?></h5>
  <p class="text-muted small">Visão completa do desempenho da clínica</p>

  <form method="GET" class="d-flex gap-2 mb-4">
    <select name="periodo" class="form-select" style="border-radius:12px; background:#F8EEF8; border:none; max-width:200px;" onchange="this.form.submit()">
      <option value="hoje" <?= $periodo=='hoje'?'selected':'' ?>>Hoje</option>
      <option value="semana" <?= $periodo=='semana'?'selected':'' ?>>Esta semana</option>
      <option value="mes" <?= $periodo=='mes'?'selected':'' ?>>Este mês</option>
      <option value="ano" <?= $periodo=='ano'?'selected':'' ?>>Este ano</option>
    </select>
    <button type="button" class="btn btn-light border" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
    <a href="faturamento.php" class="btn btn-lilas ms-auto">Ver Faturamento →</a>
  </form>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-kpi kpi-1"><small>💵 Faturamento</small><h3 class="fw-bold mt-1 mb-0">R$ <?= number_format($fat,2,',','.') ?></h3><small class="text-muted"><?= $qtd ?> agendamentos no período</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-2"><small>📈 Ticket médio</small><h3 class="fw-bold mt-1 mb-0">R$ <?= number_format($ticket,2,',','.') ?></h3><small class="text-muted">por agendamento</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-3"><small>👩 Clientes novos</small><h3 class="fw-bold mt-1 mb-0"><?= $clientes_novos ?></h3><small class="text-muted">cadastrados no período</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-4"><small>❌ Cancelamentos</small><h3 class="fw-bold mt-1 mb-0"><?= $cancelados ?> (<?= number_format($taxa_cancel,1,',','.') ?>%)</h3><small class="text-muted">taxa de cancelamento</small></div></div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card card-serenita p-3 mb-3">
        <h6 class="fw-bold mb-2">Faturamento — Últimos 14 dias</h6>
        <canvas id="grafDia" height="130"></canvas>
      </div>
      <div class="card card-serenita p-3 mb-3">
        <h6 class="fw-bold mb-2">Faturamento — Últimos 6 meses</h6>
        <canvas id="grafMes" height="130"></canvas>
      </div>
      <div class="card card-serenita p-3">
        <h6 class="fw-bold mb-3">Top Serviços — <?= $label ?></h6>
        <?php foreach($topServ as $s): ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between"><b style="font-size:14px;"><?= htmlspecialchars($s['nome']) ?></b><b>R$ <?= number_format($s['total'],2,',','.') ?> • <?= $s['qtd'] ?>x</b></div>
          <div class="progress mt-1" style="height:7px; background:#F0E0F0;"><div class="progress-bar" style="width:<?= $fat>0 ? $s['total']/$fat*100 : 0 ?>%; background:<?= corServico($s['nome']) ?>; border:1px solid #E0C0E0;"></div></div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($topServ)): ?><p class="text-muted small">Sem dados neste período.</p><?php endif; ?>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card card-serenita p-3 mb-3">
        <h6 class="fw-bold mb-2">Status dos Agendamentos</h6>
        <canvas id="grafStatus" height="160"></canvas>
        <div class="mt-3">
          <?php foreach($porStatus as $st=>$qt): ?><div class="d-flex justify-content-between py-1 border-bottom"><span><?= htmlspecialchars($st) ?></span><b><?= $qt ?></b></div><?php endforeach; ?>
        </div>
      </div>

      <div class="card card-serenita p-3 mb-3">
        <h6 class="fw-bold mb-3">Por Profissional</h6>
        <?php foreach($porProf as $p): ?>
        <div class="d-flex justify-content-between py-2 border-bottom"><span><?= htmlspecialchars($p['nome']) ?><br><small class="text-muted"><?= $p['qtd'] ?> atendimentos</small></span><b>R$ <?= number_format($p['total'],2,',','.') ?></b></div>
        <?php endforeach; ?>
        <?php if(empty($porProf)): ?><p class="text-muted small">Sem dados.</p><?php endif; ?>
      </div>

      <div class="card card-serenita p-3">
        <h6 class="fw-bold mb-3">🏆 Top Clientes — <?= $label ?></h6>
        <?php foreach($topClientes as $i=>$c): ?>
        <div class="d-flex justify-content-between py-2 border-bottom"><span><b><?= $i+1 ?>.</b> <?= htmlspecialchars($c['nome']) ?><br><small class="text-muted"><?= $c['qtd'] ?> visitas</small></span><b>R$ <?= number_format($c['total'],2,',','.') ?></b></div>
        <?php endforeach; ?>
        <?php if(empty($topClientes)): ?><p class="text-muted small">Sem dados.</p><?php endif; ?>
      </div>
    </div>
  </div>
  <p class="text-muted small text-end mt-3">Relatório gerado em <?= date('d/m/Y H:i') ?></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
new Chart(document.getElementById('grafDia'), { type:'bar', data:{ labels:<?= json_encode($labels14) ?>, datasets:[{ label:'Faturamento R$', data:<?= json_encode($valores14) ?>, backgroundColor:'#E3B8E3', borderRadius:8 }] }, options:{ plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true }, x:{ grid:{display:false} } } } });
new Chart(document.getElementById('grafMes'), { type:'line', data:{ labels:<?= json_encode($labelsMes) ?>, datasets:[{ label:'Faturamento R$', data:<?= json_encode($valoresMes) ?>, borderColor:'#C795D0', backgroundColor:'rgba(227,184,227,.25)', fill:true, tension:.4, pointBackgroundColor:'#C795D0' }] }, options:{ plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true } } } });
new Chart(document.getElementById('grafStatus'), { type:'doughnut', data:{ labels:<?= json_encode(array_keys($porStatus)) ?>, datasets:[{ data:<?= json_encode(array_values($porStatus)) ?>, backgroundColor:['#A8E0A8','#E3B8E3','#F0D8A0','#C9D8F0','#F0A0A0'] }] }, options:{ plugins:{ legend:{ position:'bottom' } } } });
</script>
</body>
</html>