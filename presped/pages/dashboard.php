<?php
// C:\xampp\htdocs\presped\pages\dashboard.php
// CORRIGIDO: mostra TODOS os arquivos validados da competência (lote ou single),
// não só o último com LIMIT 1. Status calculado pelos arquivos, não só por competencias.
// CORRIGIDO 2: badge de Tipo alinhado no topo com o OK de cada arquivo (mesma altura de linha).
// CORRIGIDO 3: Competência agora vem do topo fixo (header.php), não tem mais form aqui.
// CORRIGIDO 4: botão Validar abre modal se já houver arquivo OK na competência (igual ao simulador).
// CORRIGIDO 5: botões Validar e Cadastro com mesmo tamanho na coluna Ação.
// CORRIGIDO 6: botão Cadastro abre o cadastro da empresa em edição (empresas.php?editar=ID).
require_once '../config.php';

// Trava de perfil: todo mundo pode ver o Dashboard
exigePerfil(['admin', 'contador', 'auxiliar', 'visualizador']);

require_once '../includes/header.php';

if (!estaLogado()) {
  header("Location: ../index.php?aba=login");
  exit;
}

$escritorio_id = $_SESSION['escritorio_id'];
$nome_escritorio = $_SESSION['escritorio_nome'] ?? 'Escritório';
$plano = $_SESSION['escritorio_plano'] ?? 'teste';
$plano_label = $plano === 'teste' ? 'Teste' : ucfirst($plano);

// --- PERFIL DO USUÁRIO LOGADO ---
$perfil_atual = perfilAtual();
$perfis_nomes = [
  'admin' => 'Administrador',
  'contador' => 'Contador',
  'auxiliar' => 'Auxiliar',
  'visualizador' => 'Visualizador',
];
$perfil_nome = $perfis_nomes[$perfil_atual] ?? 'Visualizador';
$usuario_nome = nomeUsuarioLogado();
$perfil_badge = [
  'admin' => 'bg-danger',
  'contador' => 'bg-primary',
  'auxiliar' => 'bg-warning text-dark',
  'visualizador' => 'bg-secondary',
][$perfil_atual] ?? 'bg-secondary';

// --- DIAS RESTANTES DE VERDADE, sempre do banco ---
$stmt = $pdo->prepare("SELECT plano_expira_em FROM escritorios WHERE id = ?");
$stmt->execute([$escritorio_id]);
$plano_expira_em = $stmt->fetchColumn();
$_SESSION['plano_expira_em'] = $plano_expira_em;

$dias_restantes = null;
if ($plano_expira_em) {
  $hoje = new DateTime(date('Y-m-d'));
  $expira = new DateTime($plano_expira_em);
  $dias_restantes = (int) $hoje->diff($expira)->format('%r%a');
}

// --- COMPETÊNCIA SELECIONADA NO COMBO DO TOPO (header.php) ---
$competencia_selecionada = $competencia_topo ?? ($_GET['competencia'] ?? date('Y-m'));
$competencia_selecionada = str_replace('/', '-', $competencia_selecionada);
if (!preg_match('/^\d{4}-\d{2}$/', $competencia_selecionada)) {
  $competencia_selecionada = date('Y-m');
}
list($ano_atual, $mes_atual) = explode('-', $competencia_selecionada);
$mes_atual = (int)$mes_atual;
$ano_atual = (int)$ano_atual;
$eh_mes_atual = ($competencia_selecionada === date('Y-m'));

$competencia_formatada = substr($competencia_selecionada, 5, 2) . '-' . substr($competencia_selecionada, 0, 4);

function formataCompetencia($comp) {
  $comp = str_replace('/', '-', trim($comp ?? ''));
  if (preg_match('/^\d{4}-\d{2}$/', $comp)) {
    return substr($comp, 5, 2) . '-' . substr($comp, 0, 4);
  }
  return $comp;
}

function formataCnpj($cnpj) {
  $c = preg_replace('/\D/', '', $cnpj ?? '');
  if (strlen($c) !== 14) return $cnpj;
  return substr($c, 0, 2) . '.' . substr($c, 2, 3) . '.' . substr($c, 5, 3) . '/' . substr($c, 8, 4) . '-' . substr($c, 12, 2);
}

// Total de empresas do escritório
$stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE escritorio_id = ? AND ativo = 1");
$stmt->execute([$escritorio_id]);
$total_empresas = $stmt->fetchColumn();

// Lista de empresas
$stmt = $pdo->prepare("
  SELECT e.id, e.razao_social, e.cnpj, e.uf, e.regime,
         c.status, c.atualizado_em
  FROM empresas e
  LEFT JOIN competencias c ON c.empresa_id = e.id AND c.mes = ? AND c.ano = ?
  WHERE e.escritorio_id = ? AND e.ativo = 1
  ORDER BY e.razao_social
");
$stmt->execute([$mes_atual, $ano_atual, $escritorio_id]);
$empresas = $stmt->fetchAll();

// Busca TODAS as validações da competência selecionada
$stmt = $pdo->prepare("
  SELECT v.id, v.empresa_id, v.status, v.total_erros, v.resumo,
         a.nome_arquivo, a.tipo, a.competencia
  FROM validacoes v
  JOIN arquivos a ON a.id = v.arquivo_id
  JOIN empresas e ON e.id = v.empresa_id
  WHERE e.escritorio_id = ? AND a.competencia = ?
  ORDER BY v.id DESC
");
$stmt->execute([$escritorio_id, $competencia_selecionada]);
$todas_validacoes = $stmt->fetchAll();

$validacoes_por_empresa = [];
foreach ($todas_validacoes as $v) {
  $eid = $v['empresa_id'];
  $tipo = $v['tipo'];
  if (!isset($validacoes_por_empresa[$eid])) $validacoes_por_empresa[$eid] = [];
  if (!isset($validacoes_por_empresa[$eid][$tipo])) {
    $validacoes_por_empresa[$eid][$tipo] = $v;
  }
}

$status_por_empresa = [];
$total_ok = 0;
$total_erro = 0;
foreach ($empresas as $emp) {
  $lista = $validacoes_por_empresa[$emp['id']] ?? [];
  if (empty($lista)) {
    $status = 'pendente';
  } else {
    $tem_erro = false;
    foreach ($lista as $v) {
      if ($v['status'] === 'erro' || (int)$v['total_erros'] > 0) { $tem_erro = true; break; }
    }
    $status = $tem_erro ? 'erro' : 'ok';
  }
  $status_por_empresa[$emp['id']] = $status;
  if ($status === 'ok') $total_ok++;
  if ($status === 'erro') $total_erro++;
}

$total_pendente = $total_empresas - $total_ok - $total_erro;
if ($total_pendente < 0) $total_pendente = 0;

function badgeTipo($tipo) {
  if (empty($tipo)) return '<span class="text-muted">—</span>';
  if ($tipo === 'EFD-Reinf') return '<span class="badge bg-primary">Reinf</span>';
  if ($tipo === 'eSocial') return '<span class="badge bg-warning text-dark">eSocial</span>';
  if ($tipo === 'EFD Contribuições') return '<span class="badge bg-success">EFD Contrib.</span>';
  if ($tipo === 'EFD ICMS/IPI') return '<span class="badge bg-info text-dark">EFD ICMS/IPI</span>';
  if ($tipo === 'NFD' || $tipo === 'NF-e' || $tipo === 'NFe') return '<span class="badge bg-dark">NFD</span>';
  return '<span class="badge bg-secondary">' . htmlspecialchars($tipo) . '</span>';
}

function badgeStatus($status) {
  if ($status === 'ok') return '<span class="badge bg-success">OK</span>';
  if ($status === 'erro') return '<span class="badge bg-danger">Com erro</span>';
  return '<span class="badge bg-warning text-dark">Pendente</span>';
}

function nomeTipoModal($tipo) {
  if ($tipo === 'EFD-Reinf') return 'Reinf';
  if ($tipo === 'EFD Contribuições') return 'EFD Contribuições';
  if ($tipo === 'EFD ICMS/IPI') return 'EFD ICMS/IPI';
  return $tipo ?: '—';
}
?>

<style>
  .linha-arquivo {
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 30px;
  }
  .linha-arquivo a {
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 320px;
  }
  /* AJUSTE: Botões Validar e Cadastro com mesmo tamanho */
  .acoes-empresa {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 92px;
  }
  .acoes-empresa .btn {
    width: 100%;
    white-space: nowrap;
    padding-left: 0;
    padding-right: 0;
    text-align: center;
    display: block;
  }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
  <div>
    <h2 class="fw-bold mb-0">Dashboard</h2>
    <p class="text-muted mb-0">Olá, <strong><?= htmlspecialchars($usuario_nome) ?></strong> <span class="badge <?= $perfil_badge ?>"><?= htmlspecialchars($perfil_nome) ?></span> </p>
  </div>
  <div class="text-end">
    <?php if (!$eh_mes_atual): ?>
      <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Voltar para mês atual</a>
    <?php endif; ?>
    <div class="small text-muted mt-1">Competência atual: <strong><?= htmlspecialchars($competencia_formatada) ?></strong> <span class="text-muted">(troque no topo)</span></div>
  </div>
</div>

<?php if (isset($_GET['erro']) && $_GET['erro'] === 'sem_permissao'): ?>
<div class="alert alert-danger">Você não tem permissão para acessar essa página com o seu perfil.</div>
<?php endif; ?>

<?php if ($plano === 'teste' && pode(['admin'])): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center">
  <div><i class="bi bi-gift"></i> <strong>Você está no período de teste.</strong> Aproveite 1 mês grátis para testar o sistema com até 5 empresas.</div>
    <a href="planos.php" class="btn btn-dark btn-sm">Ver planos</a>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Empresas OK</small><h3 class="text-success mb-0"><?= $total_ok ?> <i class="bi bi-check-circle"></i></h3></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Com erro</small><h3 class="text-danger mb-0"><?= $total_erro ?> <i class="bi bi-x-circle"></i></h3></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Pendentes</small><h3 class="text-warning mb-0"><?= $total_pendente ?> <i class="bi bi-clock"></i></h3></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Total empresas</small><h3 class="mb-0"><?= $total_empresas ?></h3></div></div>
  </div>
</div>

<div class="card border-0 shadow-sm">
<div class="card-header text-center">
  <span class="fw-bold">Status de <?= htmlspecialchars($competencia_formatada) ?></span>
</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Empresa</th><th>CNPJ</th><th>UF / Regime</th><th>Status</th><th>Arquivos validados</th><th>Tipo</th><th>Ação</th></tr></thead>
      <tbody>
        <?php if (empty($empresas)): ?>
        <tr><td colspan="7" class="text-center py-4 text-muted">Nenhuma empresa cadastrada ainda.<br><?php if (pode(['admin', 'contador'])): ?><a href="empresas.php" class="btn btn-primary btn-sm mt-2">Cadastrar primeira empresa</a><?php endif; ?></td></tr>
        <?php else: ?>
          <?php foreach ($empresas as $emp): ?>
          <?php $lista = $validacoes_por_empresa[$emp['id']] ?? []; ?>
          <?php
            $tipos_ok_dash = [];
            foreach ($lista as $v_ok) {
              if ($v_ok['status'] === 'ok' && (int)$v_ok['total_erros'] === 0) {
                $tipos_ok_dash[] = nomeTipoModal($v_ok['tipo'] ?? '');
              }
            }
            $tipos_ok_dash = array_values(array_unique($tipos_ok_dash));
            $url_upload_dash = 'upload.php?empresa_id=' . $emp['id'] . '&competencia=' . urlencode($competencia_selecionada);
          ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($emp['razao_social']) ?></td>
            <td><?= htmlspecialchars(formataCnpj($emp['cnpj'])) ?></td>
            <td><?= htmlspecialchars($emp['uf']) ?> - <?= htmlspecialchars($emp['regime']) ?></td>
            <td><?= badgeStatus($status_por_empresa[$emp['id']]) ?></td>
            <td class="align-top">
              <?php if (!empty($lista)): ?>
                <?php foreach ($lista as $v): ?>
                  <div class="linha-arquivo">
                    <a href="validacao-detalhe.php?id=<?= $v['id'] ?>&competencia=<?= htmlspecialchars($competencia_selecionada) ?>&origem=dashboard" class="text-decoration-none small" title="Ver validação">
                      <i class="bi bi-file-check <?= ((int)$v['total_erros'] > 0) ? 'text-danger' : 'text-success' ?>"></i> <?= htmlspecialchars($v['nome_arquivo']) ?>
                    </a>
                    <?php if ((int)$v['total_erros'] > 0): ?>
                      <span class="badge bg-danger"><?= (int)$v['total_erros'] ?> erro(s)</span>
                    <?php else: ?>
                      <span class="badge bg-success">OK</span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
                <small class="text-muted">Comp. <?= htmlspecialchars(formataCompetencia($competencia_selecionada)) ?></small>
              <?php else: ?>
                <span class="text-muted small">Nenhum arquivo OK nesta competência</span>
              <?php endif; ?>
            </td>
            <td class="align-top">
              <?php if (!empty($lista)): ?>
                <?php foreach ($lista as $v): ?>
                  <div class="linha-arquivo"><?= badgeTipo($v['tipo'] ?? '') ?></div>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="acoes-empresa">
                <?php if (pode(['admin', 'contador', 'auxiliar'])): ?>
                  <button type="button"
                    class="btn btn-sm btn-primary btn-validar-dash"
                    data-url="<?= htmlspecialchars($url_upload_dash) ?>"
                    data-empresa="<?= htmlspecialchars($emp['razao_social']) ?>"
                    data-competencia="<?= htmlspecialchars($competencia_selecionada) ?>"
                    data-tipos='<?= htmlspecialchars(json_encode($tipos_ok_dash), ENT_QUOTES, 'UTF-8') ?>'>Validar</button>
                <?php endif; ?>
                <?php if (pode(['admin', 'contador'])): ?>
                  <!-- AJUSTE: vai para o cadastro DAQUELA empresa, já em edição -->
                  <a href="empresas.php?editar=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-outline-primary">Cadastro</a>
                <?php endif; ?>
                <?php if (!pode(['admin', 'contador', 'auxiliar'])): ?>
                  <span class="text-muted small">Somente visualização</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL: já validado, gerar novamente? -->
<div class="modal fade" id="modalJaValidadoDash" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-check-circle-fill text-success"></i> Já validado nesta competência</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">A empresa <strong id="modalJaValidadoDashEmpresa">—</strong> já possui validação com sucesso na competência <strong id="modalJaValidadoDashCompetencia">—</strong>:</p>
        <ul class="mb-3" id="modalJaValidadoDashLista"></ul>
        <p class="text-muted mb-0">Deseja gerar novamente? A nova validação vai substituir a anterior para esta competência.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="modalJaValidadoDashConfirmar" class="btn btn-primary">Sim, gerar novamente</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modalEl = document.getElementById('modalJaValidadoDash');
  if (!modalEl) return;

  var modal = new bootstrap.Modal(modalEl);
  var elEmpresa = document.getElementById('modalJaValidadoDashEmpresa');
  var elCompetencia = document.getElementById('modalJaValidadoDashCompetencia');
  var elLista = document.getElementById('modalJaValidadoDashLista');
  var elConfirmar = document.getElementById('modalJaValidadoDashConfirmar');

  function formataCompModal(comp) {
    if (/^\d{4}-\d{2}$/.test(comp)) return comp.substring(5, 7) + '/' + comp.substring(0, 4);
    return comp;
  }

  function escapaHtml(str) {
    return String(str).replace(/[&<>"']/g, function(c) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }

  document.querySelectorAll('.btn-validar-dash').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var url = btn.getAttribute('data-url');
      var tipos = [];
      try { tipos = JSON.parse(btn.getAttribute('data-tipos') || '[]'); } catch(e) { tipos = []; }

      if (!tipos || tipos.length === 0) {
        window.location.href = url;
        return;
      }

      var itens = tipos.map(function(t) {
        return '<li><strong>' + escapaHtml(t) + '</strong> <span class="badge bg-success">OK</span></li>';
      });

      elEmpresa.textContent = btn.getAttribute('data-empresa') || '';
      elCompetencia.textContent = formataCompModal(btn.getAttribute('data-competencia') || '');
      elLista.innerHTML = itens.join('');
      elConfirmar.href = url;

      modal.show();
    });
  });
});
</script>

<?php require_once '../includes/footer.php'; ?>