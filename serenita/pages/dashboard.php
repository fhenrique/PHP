<?php
// dashboard.php - Layout Serenitá com Sidebar
require_once __DIR__ . '/../config.php';
exigeLogin();
$spa_id = $_SESSION['spa_id'];
$hoje = date('Y-m-d');
$hoje_br = date('d/m/Y');

// Faturamento do dia
$stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) as total, COUNT(*) as qtd FROM agendamentos WHERE spa_id=? AND data=?");
$stmt->execute([$spa_id, $hoje]);
$dia = $stmt->fetch();
$faturamento = $dia['total'];
$qtd_hoje = $dia['qtd'];

// Agenda do dia
$stmt = $pdo->prepare("
  SELECT a.*, c.nome as cliente, p.nome as profissional, s.nome as servico
  FROM agendamentos a
  JOIN clientes c ON c.id = a.cliente_id
  JOIN profissionais p ON p.id = a.profissional_id
  JOIN servicos s ON s.id = a.servico_id
  WHERE a.spa_id=? AND a.data=? ORDER BY a.hora ASC
");
$stmt->execute([$spa_id, $hoje]);
$agenda = $stmt->fetchAll();

// Profissionais
$stmt = $pdo->prepare("SELECT * FROM profissionais WHERE spa_id=? AND ativo=1");
$stmt->execute([$spa_id]);
$profs = $stmt->fetchAll();

function corStatus($s){
  if($s=='Confirmado') return 'bg-success';
  if($s=='Em andamento') return 'bg-primary';
  if($s=='Pendente') return 'bg-warning text-dark';
  return 'bg-secondary';
}
function corServico($nome){
  if(stripos($nome,'Manicure')!==false) return '#E8C6E8';
  if(stripos($nome,'Massoterapia')!==false) return '#D8C9F0';
  if(stripos($nome,'Podologia')!==false) return '#C9D8F0';
  if(stripos($nome,'Ventosa')!==false) return '#F0D8C9';
  if(stripos($nome,'Nail')!==false) return '#F0C9C9';
  if(stripos($nome,'Pedicure')!==false) return '#F9E0C9';
  return '#E8E0F0';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenitá - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body{ background:#FBF7FB; font-family:'Segoe UI',system-ui,sans-serif; }
  .sidebar{ background: linear-gradient(180deg,#FCEFF8 0%, #F5EAF6 100%); min-height:100vh; width:260px; position:fixed; left:0; top:0; padding:24px 18px; border-right:1px solid #F0DDF0; }
  .sidebar .logo{ font-size:26px; font-weight:700; color:#7A4A7A; letter-spacing:.5px; }
  .sidebar .logo small{ display:block; font-size:10px; letter-spacing:3px; color:#B08AB0; font-weight:400; }
  .menu-item{ display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; color:#5A3A5A; text-decoration:none; font-size:14.5px; margin-bottom:4px; }
  .menu-item:hover{ background:#F3DDF3; color:#7A4A7A; }
  .menu-item.active{ background:#E3B8E3; color:#fff; font-weight:600; box-shadow:0 4px 14px rgba(227,184,227,.5); }
  .main{ margin-left:260px; padding:22px 28px; }
  .topbar{ display:flex; align-items:center; gap:16px; margin-bottom:22px; }
  .search-box{ flex:1; background:#fff; border-radius:12px; padding:10px 14px; border:1px solid #F0E0F0; display:flex; align-items:center; gap:8px; position:relative; }
  .search-box input{ border:none; outline:none; width:100%; font-size:14px; }
  .search-resultados{ position:absolute; top:100%; left:0; right:0; background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.12); z-index:1000; overflow:hidden; display:none; margin-top:6px; }
  .search-resultados a{ display:flex; gap:10px; align-items:center; padding:10px 14px; text-decoration:none; color:#333; font-size:14px; }
  .search-resultados a:hover{ background:#FBF7FB; }
  .card-kpi{ border:none; border-radius:18px; padding:18px; }
  .kpi-1{ background:#FFEFEF; } .kpi-2{ background:#F0EBFF; } .kpi-3{ background:#FFEAF6; } .kpi-4{ background:#FFF0E6; }
  .card-serenita{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.04); }
  .badge-servico{ padding:6px 12px; border-radius:20px; font-size:12.5px; font-weight:600; color:#4A2A4A; }
  .prof-card{ text-align:center; padding:14px 6px; }
  .prof-avatar{ width:68px; height:68px; border-radius:50%; object-fit:cover; background:#FCEFF8; display:flex; align-items:center; justify-content:center; font-size:30px; margin:0 auto 8px; }
  .progress-serenita{ height:7px; border-radius:10px; background:#F0E0F0; }
  .user-box{ display:flex; align-items:center; gap:10px; background:#fff; padding:10px; border-radius:14px; margin-top:20px; }
  .user-box img{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
  a.kpi-link{ text-decoration:none; color:inherit; display:block; }
  a.kpi-link:hover{ opacity:.92; }
  @media(max-width:900px){ .sidebar{ display:none; } .main{ margin-left:0; } }
</style>
</head>
<body>

<!-- SIDEBAR -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- CONTEÚDO -->
<div class="main">

  <div class="topbar">
    <h4 class="fw-bold mb-0">Dashboard</h4>
    <!-- ✅ CORRIGIDO: vai para buscar.php (global) e não mais para clientes.php -->
    <form action="buscar.php" method="GET" class="search-box m-0" id="formBuscaDash" autocomplete="off">
      <i class="bi bi-search text-muted"></i>
      <input id="inputBuscaDash" name="busca" placeholder="Buscar paciente, serviço ou profissional..." autocomplete="off">
      <div id="resultadosBusca" class="search-resultados"></div>
    </form>
    <i class="bi bi-bell fs-5"></i>
    <i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= $hoje_br ?></span>
  </div>

  <h5 class="fw-bold">Olá, Dra. Camila — aqui está o resumo de hoje, <?= date('d \d\e F \d\e Y') ?></h5>
  <p class="text-muted small">Você tem <?= $qtd_hoje ?> agendamentos para hoje e 6 pacotes ativos.</p>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-md-3"><a href="faturamento.php" class="kpi-link"><div class="card card-kpi kpi-1"><small>💵 Faturamento do dia</small><h3 class="fw-bold mt-1 mb-0">R$ <?= number_format($faturamento,2,',','.') ?></h3><small class="text-success">+12% em relação a ontem ↑</small><div class="small mt-2" style="color:#7A4A7A; font-weight:600;">Ver faturamento →</div></div></a></div>
    <div class="col-md-3"><a href="agenda.php" class="kpi-link"><div class="card card-kpi kpi-2"><small>📅 Agendamentos</small><h3 class="fw-bold mt-1 mb-0"><?= $qtd_hoje ?></h3><small class="text-muted">+3 agendamentos hoje</small><div class="small mt-2" style="color:#7A4A7A; font-weight:600;">Ver agenda →</div></div></a></div>
    <div class="col-md-3"><div class="card card-kpi kpi-3"><small>📦 Pacotes Ativos</small><h3 class="fw-bold mt-1 mb-0">6</h3><small class="text-danger">2 pacotes expiram em breve</small></div></div>
    <div class="col-md-3"><div class="card card-kpi kpi-4"><small>📈 Taxa de ocupação</small><h3 class="fw-bold mt-1 mb-0">78%</h3><small class="text-success">+5% vs média da semana</small></div></div>
  </div>

  <div class="row g-3">
    <!-- AGENDA DO DIA -->
    <div class="col-lg-8">
      <div class="card card-serenita p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0">Agenda do Dia — <?= $hoje_br ?></h6>
          <a href="agenda.php" class="btn btn-sm" style="background:#E3B8E3; color:#fff; border-radius:10px;">Novo Agendamento +</a>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <tbody>
              <?php foreach($agenda as $a): ?>
              <tr>
                <td class="fw-bold" style="width:60px;"><?= substr($a['hora'],0,5) ?></td>
                <td><span class="badge-servico" style="background:<?= corServico($a['servico']) ?>"><?= htmlspecialchars($a['servico']) ?></span></td>
                <td class="small"><?= htmlspecialchars($a['profissional']) ?> • <?= htmlspecialchars($a['cliente']) ?></td>
                <td class="text-end"><span class="badge <?= corStatus($a['status']) ?> rounded-pill px-3 py-2"><?= htmlspecialchars($a['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($agenda)): ?><tr><td colspan="4" class="text-center text-muted py-4">Nenhum agendamento para hoje. Clique em Novo Agendamento.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <h6 class="fw-bold mt-4 mb-3">Profissionais</h6>
        <div class="row g-2">
          <?php foreach($profs as $p): ?>
          <div class="col-6 col-md-3"><div class="card border-0" style="background:#FDF7FD; border-radius:14px;"><div class="prof-card">
            <div class="prof-avatar">👩</div>
            <div class="fw-bold" style="font-size:13px;"><?= htmlspecialchars($p['nome']) ?></div>
            <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($p['especialidade']) ?></div>
            <div class="small text-muted mt-1">4 atendimentos hoje</div>
          </div></div></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- LADO DIREITO -->
    <div class="col-lg-4">
      <div class="card card-serenita p-3 mb-3">
        <h6 class="fw-bold mb-3">Próximos Agendamentos</h6>
        <?php foreach(array_slice($agenda, 5) as $a): ?>
        <div class="d-flex gap-3 mb-3">
          <span class="badge bg-light text-dark border py-2 px-2"><?= substr($a['hora'],0,5) ?></span>
          <div><div class="fw-semibold" style="font-size:14px;"><?= htmlspecialchars($a['servico']) ?></div><div class="text-muted small"><?= htmlspecialchars($a['cliente']) ?></div></div>
        </div>
        <?php endforeach; ?>
        <?php if(count($agenda) <= 5): ?>
          <p class="text-muted small">Seus próximos agendamentos aparecem aqui.</p>
        <?php endif; ?>
      </div>

      <div class="card card-serenita p-3">
        <h6 class="fw-bold mb-3">Pacotes Ativos</h6>
        <div class="mb-3">
          <div class="d-flex justify-content-between"><b style="font-size:14px;">Pacote Relax Spa</b><span class="small">60%</span></div>
          <div class="small text-muted">3 de 5 sessões utilizadas</div>
          <div class="progress progress-serenita mt-1"><div class="progress-bar" style="width:60%; background:#E3B8E3;"></div></div>
          <div class="small text-muted mt-1">Vence em 12 dias • 60% concluído</div>
        </div>
        <div class="mb-2">
          <div class="d-flex justify-content-between"><b style="font-size:14px;">Pacote Beleza Pés & Mãos</b><span class="small">50%</span></div>
          <div class="small text-muted">2 de 4 sessões utilizadas</div>
          <div class="progress progress-serenita mt-1"><div class="progress-bar" style="width:50%; background:#F0A0A0;"></div></div>
          <div class="small text-muted mt-1">Vence em 5 dias • 50% concluído</div>
        </div>
      </div>
      <p class="text-muted small text-center mt-3">Atualizado hoje às <?= date('H:i') ?> • Dados atualizados em tempo real</p>
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ✅ PESQUISA GLOBAL - Paciente + Profissional + Serviço via buscar.php
const inputBusca = document.getElementById('inputBuscaDash');
const boxResultados = document.getElementById('resultadosBusca');
let timerBusca;

if (inputBusca) {
  inputBusca.addEventListener('input', () => {
    clearTimeout(timerBusca);
    const q = inputBusca.value.trim();
    if (q.length < 2) { boxResultados.style.display = 'none'; return; }
    timerBusca = setTimeout(async () => {
      try {
        const res = await fetch('buscar.php?ajax=1&busca=' + encodeURIComponent(q));
        const dados = await res.json();
        if (!dados.length) {
          boxResultados.innerHTML = '<div class="p-3 small text-muted">Nenhum resultado para "' + q + '". Aperte Enter para ver tudo.</div>';
        } else {
          boxResultados.innerHTML = dados.map(d =>
            '<a href="' + d.link + '">' + d.icone + ' <div style="line-height:1.2;"><b>' + d.nome + '</b><div class="small text-muted">' + (d.detalhe || '') + '</div></div><span class="badge bg-light text-dark border ms-auto" style="font-size:11px;">' + d.tipo + '</span></a>'
          ).join('') + '<a href="buscar.php?busca=' + encodeURIComponent(q) + '" style="background:#FBF7FB; font-weight:600;">🔍 Ver todos os resultados para "' + q + '" →</a>';
        }
        boxResultados.style.display = 'block';
      } catch(e) { boxResultados.style.display = 'none'; }
    }, 250);
  });
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#formBuscaDash')) boxResultados.style.display = 'none';
  });
}
</script>
</body>
</html>