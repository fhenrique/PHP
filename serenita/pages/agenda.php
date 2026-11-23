<?php
//agenda.php
require_once __DIR__ . '/../config.php';
exigeLogin();
$spa_id = $_SESSION['spa_id'];

// Cliente pré-selecionado vindo de Pacientes
$cliente_preselecionado = $_GET['cliente'] ?? '';

// Data selecionada
$data_sel = $_GET['data'] ?? date('Y-m-d');
if ($data_sel == 'hoje') $data_sel = date('Y-m-d');
$timestamp = strtotime($data_sel);
$dia_semana = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][date('w', $timestamp)];
$data_anterior = date('Y-m-d', strtotime('-1 day', $timestamp));
$data_proxima = date('Y-m-d', strtotime('+1 day', $timestamp));

// Excluir / Cancelar
if (isset($_GET['cancelar'])) {
  $stmt = $pdo->prepare("UPDATE agendamentos SET status='Cancelado' WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['cancelar'], $spa_id]);
  header("Location: agenda.php?data=$data_sel"); exit;
}
if (isset($_GET['concluir'])) {
  $stmt = $pdo->prepare("UPDATE agendamentos SET status='Concluído' WHERE id=? AND spa_id=?");
  $stmt->execute([$_GET['concluir'], $spa_id]);
  header("Location: agenda.php?data=$data_sel"); exit;
}

// Novo agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cliente_id = $_POST['cliente_id'];
  $profissional_id = $_POST['profissional_id'];
  $servico_id = $_POST['servico_id'];
  $data = $_POST['data'];
  $hora = $_POST['hora'];
  $status = $_POST['status'] ?? 'Confirmado';

  // Pega o preço do serviço automaticamente
  $stmt = $pdo->prepare("SELECT preco FROM servicos WHERE id=? AND spa_id=?");
  $stmt->execute([$servico_id, $spa_id]);
  $valor = $stmt->fetchColumn() ?: 0;

  $stmt = $pdo->prepare("INSERT INTO agendamentos (spa_id, cliente_id, profissional_id, servico_id, data, hora, status, valor) VALUES (?,?,?,?,?,?,?,?)");
  $stmt->execute([$spa_id, $cliente_id, $profissional_id, $servico_id, $data, $hora, $status, $valor]);
  header("Location: agenda.php?data=$data"); exit;
}

// Filtro profissional
$filtro_prof = $_GET['profissional'] ?? 'Todos';

// Agenda do dia
$sql = "SELECT a.*, c.nome as cliente, c.telefone as telefone, p.nome as profissional, s.nome as servico, s.duracao_min, s.categoria
        FROM agendamentos a
        JOIN clientes c ON c.id=a.cliente_id
        JOIN profissionais p ON p.id=a.profissional_id
        JOIN servicos s ON s.id=a.servico_id
        WHERE a.spa_id=? AND a.data=? AND a.status != 'Cancelado'";
$params = [$spa_id, $data_sel];
if ($filtro_prof !== 'Todos') { $sql .= " AND a.profissional_id=?"; $params[] = $filtro_prof; }
$sql .= " ORDER BY a.hora ASC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$agenda = $stmt->fetchAll();

// Contagens do dia
$stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM agendamentos WHERE spa_id=? AND data=? AND status != 'Cancelado'");
$stmt->execute([$spa_id, $data_sel]); $faturamento_dia = $stmt->fetchColumn();

// Listas para o modal
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE spa_id=? AND ativo=1 ORDER BY nome"); $stmt->execute([$spa_id]); $clientes = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM profissionais WHERE spa_id=? AND ativo=1 ORDER BY nome"); $stmt->execute([$spa_id]); $profs = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM servicos WHERE spa_id=? AND ativo=1 ORDER BY nome"); $stmt->execute([$spa_id]); $servicos = $stmt->fetchAll();

// Semana para os botões
$inicio_semana = date('Y-m-d', strtotime('monday this week', $timestamp));
$dias_semana = [];
for($i=0;$i<7;$i++){ $d = date('Y-m-d', strtotime("+$i days", strtotime($inicio_semana))); $dias_semana[] = ['data'=>$d, 'dia'=>date('d', strtotime($d)), 'nome'=>['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($d))]]; }

function corStatus($s){
  if($s=='Confirmado') return 'bg-success';
  if($s=='Em andamento') return 'bg-primary';
  if($s=='Concluído') return 'bg-secondary';
  if($s=='Pendente') return 'bg-warning text-dark';
  return 'bg-secondary';
}
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
<title>Serenità - Agenda</title>
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
  .card-serenita{ border:none; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.04); }
  .dia-btn{ min-width:58px; text-align:center; padding:8px 6px; border-radius:12px; background:#fff; border:1px solid #F0E0F0; text-decoration:none; color:#5A3A5A; }
  .dia-btn.active{ background:#E3B8E3; color:#fff; border-color:#E3B8E3; font-weight:700; }
  .badge-servico{ padding:6px 12px; border-radius:20px; font-size:12.5px; font-weight:600; color:#4A2A4A; }
  .btn-lilas{ background:#D8A8E0; color:#fff; border:none; border-radius:10px; padding:8px 16px; font-weight:600; }
  .btn-lilas:hover{ background:#C795D0; color:#fff; }
  .hora-col{ font-weight:700; font-size:15px; width:70px; }
  .user-box{ display:flex; align-items:center; gap:10px; background:#fff; padding:10px; border-radius:14px; margin-top:20px; }
  .user-box img{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
  @media(max-width:900px){ .sidebar{ display:none; } .main{ margin-left:0; } }
</style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main">
  <div class="d-flex align-items-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">Agenda</h4>
    <div class="search-box"><i class="bi bi-search text-muted"></i><input placeholder="Buscar paciente, serviço ou profissional..."></div>
    <i class="bi bi-bell fs-5"></i><i class="bi bi-question-circle fs-5"></i>
    <span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-calendar"></i> <?= date('d M Y', $timestamp) ?></span>
  </div>

  <h5 class="fw-bold mb-0">Agenda • <?= count($agenda) ?> agendamentos em <?= $dia_semana ?>, <?= date('d/m/Y', $timestamp) ?></h5>
  <p class="text-muted small">Faturamento previsto do dia: <b>R$ <?= number_format($faturamento_dia,2,',','.') ?></b></p>

  <!-- NAVEGAÇÃO DA SEMANA -->
  <div class="card card-serenita p-3 mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="?data=<?= $data_anterior ?>&profissional=<?= $filtro_prof ?>" class="btn btn-light rounded-circle"><i class="bi bi-chevron-left"></i></a>
      <div class="d-flex gap-2 flex-wrap">
        <?php foreach($dias_semana as $d): ?>
        <a href="?data=<?= $d['data'] ?>&profissional=<?= $filtro_prof ?>" class="dia-btn <?= $d['data']==$data_sel?'active':'' ?>">
          <div style="font-size:11px;"><?= $d['nome'] ?></div><div style="font-size:18px;"><?= $d['dia'] ?></div>
        </a>
        <?php endforeach; ?>
      </div>
      <a href="?data=<?= $data_proxima ?>&profissional=<?= $filtro_prof ?>" class="btn btn-light rounded-circle"><i class="bi bi-chevron-right"></i></a>
      <a href="?data=<?= date('Y-m-d') ?>" class="btn btn-sm btn-light rounded-pill ms-2">Hoje</a>
      <form method="GET" class="ms-auto d-flex gap-2">
        <input type="hidden" name="data" value="<?= $data_sel ?>">
        <select name="profissional" class="form-select" style="border-radius:12px; background:#F8EEF8; border:none; width:220px;" onchange="this.form.submit()">
          <option value="Todos">Profissional: Todos</option>
          <?php foreach($profs as $p): ?><option value="<?= $p['id'] ?>" <?= $filtro_prof==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nome']) ?></option><?php endforeach; ?>
        </select>
      </form>
      <button class="btn btn-lilas" data-bs-toggle="modal" data-bs-target="#modalAgendamento">+ Novo Agendamento</button>
    </div>
  </div>

  <!-- LISTA DO DIA -->
  <div class="card card-serenita p-3">
    <div class="d-flex justify-content-between align-items-center mb-3"><h6 class="fw-bold mb-0">Agenda do Dia — <?= date('d/m/Y', $timestamp) ?></h6><span class="badge bg-light text-dark border"><?= count($agenda) ?> agendamentos</span></div>
    <?php if(empty($agenda)): ?>
      <div class="text-center py-5"><div style="font-size:48px;">🌸</div><p class="text-muted mt-2">Nenhum agendamento para este dia.<br>Clique em + Novo Agendamento para criar o primeiro.</p></div>
    <?php else: ?>
    <div class="table-responsive"><table class="table align-middle"><tbody>
      <?php foreach($agenda as $a): ?>
      <tr>
        <td class="hora-col"><?= substr($a['hora'],0,5) ?></td>
        <td><span class="badge-servico" style="background:<?= corServico($a['servico']) ?>"><?= htmlspecialchars($a['servico']) ?></span><div class="small text-muted mt-1"><?= $a['duracao_min'] ?> min • R$ <?= number_format($a['valor'],2,',','.') ?></div></td>
        <td><div class="fw-semibold"><?= htmlspecialchars($a['cliente']) ?></div><div class="small text-muted"><?= htmlspecialchars($a['telefone']) ?> • Prof: <?= htmlspecialchars($a['profissional']) ?></div></td>
        <td class="text-end">
          <span class="badge <?= corStatus($a['status']) ?> rounded-pill px-3 py-2"><?= htmlspecialchars($a['status']) ?></span>
          <div class="dropdown d-inline"><button class="btn btn-sm" data-bs-toggle="dropdown">•••</button><ul class="dropdown-menu">
            <li><a class="dropdown-item" href="?concluir=<?= $a['id'] ?>&data=<?= $data_sel ?>">✅ Marcar como Concluído</a></li>
            <li><a class="dropdown-item" href="https://wa.me/55<?= preg_replace('/\D/','',$a['telefone']) ?>" target="_blank">💬 WhatsApp</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="?cancelar=<?= $a['id'] ?>&data=<?= $data_sel ?>" onclick="return confirm('Cancelar este agendamento?')">❌ Cancelar</a></li>
          </ul></div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </div>
  <p class="text-muted small text-end mt-3">Exibindo <?= count($agenda) ?> agendamentos • Atualizado hoje às <?= date('H:i') ?></p>
</div>

<!-- MODAL NOVO AGENDAMENTO -->
<div class="modal fade" id="modalAgendamento" tabindex="-1"><div class="modal-dialog"><div class="modal-content" style="border-radius:18px;">
  <form method="POST">
  <div class="modal-header border-0"><h5 class="fw-bold">Novo Agendamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label small fw-bold">Paciente</label>
    <select name="cliente_id" class="form-select mb-3" required>
      <option value="">Selecione a paciente...</option>
      <?php foreach($clientes as $c): ?><option value="<?= $c['id'] ?>" <?= $cliente_preselecionado==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?> — <?= htmlspecialchars($c['telefone']) ?></option><?php endforeach; ?>
    </select>
    <label class="form-label small fw-bold">Serviço</label>
    <select name="servico_id" id="f_servico" class="form-select mb-3" required onchange="atualizaPreco()">
      <option value="">Selecione o serviço...</option>
      <?php foreach($servicos as $s): ?><option value="<?= $s['id'] ?>" data-preco="<?= $s['preco'] ?>"><?= htmlspecialchars($s['nome']) ?> — R$ <?= number_format($s['preco'],2,',','.') ?> (<?= $s['duracao_min'] ?> min)</option><?php endforeach; ?>
    </select>
    <label class="form-label small fw-bold">Profissional</label>
    <select name="profissional_id" class="form-select mb-3" required>
      <option value="">Selecione...</option>
      <?php foreach($profs as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> — <?= htmlspecialchars($p['especialidade']) ?></option><?php endforeach; ?>
    </select>
    <div class="row">
      <div class="col-6"><label class="form-label small fw-bold">Data</label><input name="data" type="date" class="form-control mb-3" value="<?= $data_sel ?>" required></div>
      <div class="col-6"><label class="form-label small fw-bold">Hora</label><input name="hora" type="time" class="form-control mb-3" value="09:00" required></div>
    </div>
    <label class="form-label small fw-bold">Status</label>
    <select name="status" class="form-select"><option>Confirmado</option><option>Pendente</option><option>Em andamento</option></select>
    <div class="mt-2 small text-muted">Valor: <b id="preco_preview">R$ 0,00</b> — puxado automaticamente do serviço</div>
  </div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-lilas">Salvar Agendamento</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function atualizaPreco(){
  const sel = document.getElementById('f_servico');
  const preco = sel.options[sel.selectedIndex]?.dataset.preco || 0;
  document.getElementById('preco_preview').innerText = 'R$ ' + Number(preco).toFixed(2).replace('.',',');
}
</script>
</body>
</html>