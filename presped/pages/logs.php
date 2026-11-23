<?php
// C:\xampp\htdocs\presped\pages\logs.php
require_once '../config.php';

exigePerfil(['admin']);

$escritorio_id = $_SESSION['escritorio_id'];

// --- FILTROS ---
$filtro_acao = $_GET['acao'] ?? '';
$filtro_busca = trim($_GET['busca'] ?? '');
$filtro_data = $_GET['data'] ?? '';

$acoes_labels = [
  'criacao_usuario'  => 'Criação de usuário',
  'exclusao_usuario' => 'Exclusão de usuário',
  'cadastro_empresa' => 'Cadastro de empresa',
  'exclusao_empresa' => 'Exclusão de empresa',
  'edicao_usuario'  => 'Edição de usuário',
  'edicao_empresa'  => 'Edição de empresa',
];

$acoes_cores = [
  'criacao_usuario'  => 'success',
  'exclusao_usuario' => 'danger',
  'cadastro_empresa' => 'primary',
  'exclusao_empresa' => 'warning',
  'edicao_usuario'  => 'info',
  'edicao_empresa'  => 'info',
];

// Formata a descrição para exibir De: X Para: Y, um por linha
// Também converte logs antigos que estavam no formato "Campo: 'X' -> 'Y' | Campo2: ..."
function formatar_descricao_log($descricao) {
  // Log antigo: " - Alterações: Campo: 'X' -> 'Y' | Campo2: 'A' -> 'B'"
  if (strpos($descricao, ' - Alterações: ') !== false) {
    list($inicio, $alteracoes) = explode(' - Alterações: ', $descricao, 2);
    $partes = explode(' | ', $alteracoes);
    $linhas = [];
    foreach ($partes as $p) {
      // Converte "Nome: 'Maria' -> 'João'" para "Nome - De: Maria Para: João"
      if (preg_match("/^(.+?):\s*'([^']*)'\s*->\s*'([^']*)'$/", trim($p), $m)) {
        $linhas[] = trim($m[1]) . " - De: " . $m[2] . " Para: " . $m[3];
      } elseif ($p === 'Senha alterada') {
        $linhas[] = "Senha - De: ******** Para: ******** (alterada)";
      } else {
        $linhas[] = trim($p);
      }
    }
    return $inicio . "\nAlterações:\n" . implode("\n", $linhas);
  }
  // Log antigo: " - Nenhuma alteração detectada"
  if (strpos($descricao, ' - Nenhuma alteração detectada') !== false) {
    return str_replace(' - Nenhuma alteração detectada', "\nNenhuma alteração detectada", $descricao);
  }
  return $descricao;
}

$sql = "SELECT * FROM logs_atividades WHERE escritorio_id = ?";
$params = [$escritorio_id];

if ($filtro_acao !== '' && isset($acoes_labels[$filtro_acao])) {
  $sql .= " AND acao = ?";
  $params[] = $filtro_acao;
}

if ($filtro_busca !== '') {
  $sql .= " AND (descricao LIKE ? OR usuario_nome LIKE ?)";
  $params[] = "%$filtro_busca%";
  $params[] = "%$filtro_busca%";
}

if ($filtro_data !== '') {
  $sql .= " AND DATE(data_hora) = ?";
  $params[] = $filtro_data;
}

$sql .= " ORDER BY data_hora DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0"><i class="bi bi-journal-text"></i> Log de atividades</h2>
    <p class="text-muted mb-0">Somente administradores podem ver esta página. Exibindo os últimos 200 registros.</p>
  </div>
  <a href="dashboard.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Voltar para Dashboard</a>
</div>

<!-- FILTROS -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Tipo de ação</label>
        <select name="acao" class="form-select">
          <option value="">Todas as ações</option>
          <?php foreach ($acoes_labels as $valor => $rotulo): ?>
            <option value="<?= $valor ?>" <?= $filtro_acao === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Buscar</label>
        <input type="text" name="busca" class="form-control" value="<?= htmlspecialchars($filtro_busca) ?>" placeholder="Nome do usuário, empresa, e-mail...">
      </div>
      <div class="col-md-2">
        <label class="form-label">Data</label>
        <input type="date" name="data" class="form-control" value="<?= htmlspecialchars($filtro_data) ?>">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
        <a href="logs.php" class="btn btn-secondary">Limpar</a>
      </div>
    </form>
  </div>
</div>

<!-- LISTA -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-bold">Registros (<?= count($logs) ?>)</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th style="width:170px">Data / Hora</th>
          <th>Quem fez</th>
          <th style="width:190px">Ação</th>
          <th>Descrição</th>
          <th style="width:130px">IP</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum registro encontrado com esses filtros.</td></tr>
        <?php else: foreach ($logs as $log): ?>
        <tr>
          <td class="small"><?= date('d/m/Y H:i', strtotime($log['data_hora'])) ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($log['usuario_nome'] ?? 'Sistema') ?></td>
          <td>
            <span class="badge bg-<?= $acoes_cores[$log['acao']] ?? 'secondary' ?>">
              <?= htmlspecialchars($acoes_labels[$log['acao']] ?? $log['acao']) ?>
            </span>
          </td>
          <td style="white-space: pre-line;"><?= htmlspecialchars(formatar_descricao_log($log['descricao'])) ?></td>
          <td class="small text-muted"><?= htmlspecialchars($log['ip'] ?? '-') ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>