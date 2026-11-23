<?php
//dashboard.php
require_once __DIR__ . '/../config.php';
exigirLogin();

$hoje = date('Y-m-d');
$hoje_br = date('d/m/Y');

// Dia da semana SEMPRE em português - não usa strftime (que vem em inglês no servidor)
$dias_semana_pt = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$dia_semana_pt = $dias_semana_pt[(int)date('w', strtotime($hoje))];

// --- CARD PRINCIPAL: Atendimentos do dia corrente ---
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM atendimentos WHERE data_serv = ?");
$stmt->execute([$hoje]);
$atendimentos_hoje = (int)($stmt->fetch()['total'] ?? 0);

// Detalhe do dia: quantos ABERTO / BAIXADO / CANCELADO hoje
$stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM atendimentos WHERE data_serv = ? GROUP BY status");
$stmt->execute([$hoje]);
$por_status_hoje = ['ABERTO' => 0, 'BAIXADO' => 0, 'CANCELADO' => 0];
foreach ($stmt->fetchAll() as $r) {
  $por_status_hoje[strtoupper($r['status'] ?? '')] = (int)$r['total'];
}

// Por período hoje: MANHA / TARDE / INTEGRAL
$stmt = $pdo->prepare("SELECT periodo, COUNT(*) as total FROM atendimentos WHERE data_serv = ? GROUP BY periodo");
$stmt->execute([$hoje]);
$por_periodo_hoje = [];
foreach ($stmt->fetchAll() as $r) {
  $por_periodo_hoje[strtoupper($r['periodo'] ?? '')] = (int)$r['total'];
}

// Lista dos atendimentos de hoje
$stmt = $pdo->prepare("
  SELECT a.*, c.nome as cliente_nome, c.codigo as cliente_codigo, t.nome as tecnico_nome
  FROM atendimentos a
  LEFT JOIN clientes c ON c.id = a.cliente_id
  LEFT JOIN tecnicos t ON t.id = a.tecnico_id
  WHERE a.data_serv = ?
  ORDER BY a.periodo ASC, a.os ASC
");
$stmt->execute([$hoje]);
$lista_hoje = $stmt->fetchAll();

// --- Outros cards (resumo geral) ---
$total_clientes = (int)($pdo->query("SELECT COUNT(*) as t FROM clientes WHERE arquivo_morto=0")->fetch()['t'] ?? 0);
$total_tecnicos = (int)($pdo->query("SELECT COUNT(*) as t FROM tecnicos")->fetch()['t'] ?? 0);
$atend_abertos = (int)($pdo->query("SELECT COUNT(*) as t FROM atendimentos WHERE status='ABERTO'")->fetch()['t'] ?? 0);
$atend_mes = (int)($pdo->query("SELECT COUNT(*) as t FROM atendimentos WHERE YEAR(data_serv)=YEAR(CURDATE()) AND MONTH(data_serv)=MONTH(CURDATE())")->fetch()['t'] ?? 0);

$pagina_ativa = 'dashboard';
require_once __DIR__ . '/../sidebar.php';
?>
<style>
  .card-dash{ border:0; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.06); background:#fff; padding:16px 18px; height:100%; }
  .card-dash .numero{ font-size:42px; font-weight:900; line-height:1; color:#111; }
  .card-dash .rotulo{ font-size:12px; font-weight:700; text-transform:uppercase; color:#6B7280; letter-spacing:.5px; }
  .card-dash .sub{ font-size:12px; color:#6B7280; }
  .card-hoje{ background:#FEF3C7; border:2px solid #F59E0B; }
  .card-hoje .numero{ color:#92400E; font-size:56px; }
  .card-hoje .rotulo{ color:#92400E; }
  .badge-periodo{ font-size:11px; padding:3px 8px; border-radius:999px; background:#fff; border:1px solid #F59E0B; font-weight:700; color:#92400E; }
  .tabela-hoje th{ font-size:11px; color:#6B7280; border-bottom:1px solid #eee; padding:6px 4px; text-align:left; }
  .tabela-hoje td{ font-size:12px; padding:8px 4px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
  .btn-ver{ background:#F59E0B; color:#000; font-weight:700; font-size:12px; padding:7px 14px; border-radius:6px; text-decoration:none; display:inline-block; }
  .btn-ver:hover{ color:#000; background:#FBBF24; }
  .status-aberto{ background:#BFDBFE; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; }
  .status-baixado{ background:#86EFAC; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; }
  .status-cancelado{ background:#FECACA; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; }
</style>

<div>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="fw-bold mb-0">Dashboard</h4>
      <small class="text-muted">Hoje: <?= $hoje_br ?> - <?= $dia_semana_pt ?></small>
    </div>
    <a href="atendimentos.php" class="btn btn-light border" style="font-size:12px;">Ir para Atendimentos →</a>
  </div>

  <div class="row g-3">
    <!-- CARD PRINCIPAL: ATENDIMENTOS DO DIA -->
    <div class="col-lg-4">
      <div class="card-dash card-hoje">
        <div class="rotulo">📅 Atendimentos de Hoje</div>
        <div class="numero mt-2"><?= $atendimentos_hoje ?></div>
        <div class="sub mt-1">atendimento(s) agendado(s) para <?= $hoje_br ?></div>
        <hr style="border-color:#F59E0B;">
        <div class="d-flex gap-2 flex-wrap" style="font-size:12px;">
          <span><b>ABERTO:</b> <?= $por_status_hoje['ABERTO'] ?></span>
          <span><b>BAIXADO:</b> <?= $por_status_hoje['BAIXADO'] ?></span>
          <span><b>CANCELADO:</b> <?= $por_status_hoje['CANCELADO'] ?></span>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2">
          <span class="badge-periodo">☀️ MANHÃ: <?= $por_periodo_hoje['MANHA'] ?? 0 ?></span>
          <span class="badge-periodo">🌤️ TARDE: <?= $por_periodo_hoje['TARDE'] ?? 0 ?></span>
          <span class="badge-periodo">🕐 INTEGRAL: <?= $por_periodo_hoje['INTEGRAL'] ?? 0 ?></span>
        </div>
        <a href="atendimentos.php?todos=1" class="btn-ver mt-3 w-100 text-center">Ver Atendimentos</a>
      </div>
    </div>

    <!-- OUTROS CARDS -->
    <div class="col-lg-8">
      <div class="row g-3">
        <div class="col-md-4"><div class="card-dash"><div class="rotulo">👥 Clientes Ativos</div><div class="numero mt-2"><?= $total_clientes ?></div><div class="sub">sem Arquivo Morto</div></div></div>
        <div class="col-md-4"><div class="card-dash"><div class="rotulo">🔧 Técnicos</div><div class="numero mt-2"><?= $total_tecnicos ?></div><div class="sub">cadastrados</div></div></div>
        <div class="col-md-4"><div class="card-dash"><div class="rotulo">📂 OS em Aberto</div><div class="numero mt-2"><?= $atend_abertos ?></div><div class="sub">todos os anos</div></div></div>
        <div class="col-md-4"><div class="card-dash"><div class="rotulo">📆 Atendimentos do Mês</div><div class="numero mt-2"><?= $atend_mes ?></div><div class="sub"><?= date('m/Y') ?></div></div></div>
        
		<div class="col-md-8">
          <div class="card-dash d-flex flex-column justify-content-center">
            <div class="rotulo">Atalhos</div>
            <div class="d-flex gap-2 mt-2 flex-wrap">
              <a href="atendimentos.php?modo=incluir" class="btn btn-success btn-sm">+ Novo Atendimento</a>
              <a href="atendimentos.php" class="btn btn-light border btn-sm">Atendimentos</a>
            </div>
          </div>
        </div>
		
		
      </div>
    </div>
  </div>

  <!-- LISTA DE HOJE -->
  <div class="card-dash mt-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="fw-bold mb-0">Atendimentos de Hoje - <?= $hoje_br ?> (<?= $atendimentos_hoje ?>)</h6>
      <a href="atendimentos.php?todos=1" style="font-size:12px;">Ver todos →</a>
    </div>
    <table class="w-100 tabela-hoje">
      <thead><tr><th style="width:60px;">OS</th><th>Cliente</th><th>Técnico</th><th>Serviço</th><th style="width:90px;">Período</th><th style="width:100px;">Status</th><th style="width:90px;">Valor</th><th style="width:70px;"></th></tr></thead>
      <tbody>
        <?php foreach($lista_hoje as $a): ?>
        <tr>
          <td><b><?= $a['os'] ?></b></td>
          <td><?= htmlspecialchars(($a['cliente_codigo'] ?? '') . ' - ' . ($a['cliente_nome'] ?? '')) ?></td>
          <td><?= htmlspecialchars($a['tecnico_nome'] ?? '') ?></td>
          <td><?= htmlspecialchars($a['servico1'] ?? '') ?><?= !empty($a['servico2']) ? ' / ' . htmlspecialchars($a['servico2']) : '' ?></td>
          <td><?= $a['periodo'] ?></td>
          <td><span class="status-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
          <td style="text-align:right;">R$ <?= number_format($a['valor'] ?? 0, 2, ',', '.') ?></td>
          <td><a href="atendimentos.php?editar=<?= $a['id'] ?>&cliente_id=<?= $a['cliente_id'] ?>" class="btn btn-light border btn-sm py-0" style="font-size:11px;">Abrir</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$lista_hoje): ?><tr><td colspan="8" class="text-center text-muted py-3">Nenhum atendimento agendado para hoje (<?= $hoje_br ?>).</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../footer.php'; ?>