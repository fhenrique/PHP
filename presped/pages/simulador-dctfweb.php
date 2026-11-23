<?php
// C:\xampp2\htdocs\presped\pages\simulador-dctfweb.php
// FASE 2 Item 1 + TRAVA: R-1000 / S-1000 (só cadastro) NÃO conta como Pronta pra DCTFWeb
// Só conta como pronta quem tem MOVIMENTO: S-1200/S-1299 e R-2010/R-2020/R-2099/R-4010/R-4020
// + MODAL: se eSocial e/ou Reinf já estiver OK - Movimento, pergunta se quer gerar novamente
require_once '../config.php';
require_once '../includes/header.php';

if (!estaLogado()) {
  header("Location: ../index.php?aba=login");
  exit;
}

$escritorio_id = $_SESSION['escritorio_id'];
$nome_escritorio = $_SESSION['escritorio_nome'] ?? 'Escritório';

$competencia_selecionada = $_GET['competencia'] ?? date('Y-m');
$competencia_selecionada = str_replace('/', '-', $competencia_selecionada);
if (!preg_match('/^\d{4}-\d{2}$/', $competencia_selecionada)) {
  $competencia_selecionada = date('Y-m');
}
list($ano_sel, $mes_sel) = explode('-', $competencia_selecionada);
$mes_sel = (int)$mes_sel;
$ano_sel = (int)$ano_sel;
$eh_mes_atual = ($competencia_selecionada === date('Y-m'));

// Busca empresas + status + totais
// - esocial_status / reinf_status = última validação (pra mostrar badge OK/Erro)
// - esocial_mov_ok / reinf_mov_ok = existe ALGUM movimento OK na competência? (é isso que vale pra DCTFWeb)
// - esocial_cad_ok / reinf_cad_ok = existe ALGUM cadastro OK (R-1000 / S-1000)?
// - esocial_total / reinf_total = SOMA de todos os totais da competência (cadastro soma 0, então não atrapalha)
$stmt = $pdo->prepare("
  SELECT e.id, e.razao_social, e.cnpj, e.uf, e.regime,
    (SELECT v.status FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'eSocial'
     ORDER BY v.id DESC LIMIT 1) as esocial_status,
    (SELECT v.total_erros FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'eSocial'
     ORDER BY v.id DESC LIMIT 1) as esocial_erros,
    (SELECT v.id FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'eSocial'
     ORDER BY v.id DESC LIMIT 1) as esocial_id,
    (SELECT v.status FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'EFD-Reinf'
     ORDER BY v.id DESC LIMIT 1) as reinf_status,
    (SELECT v.total_erros FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'EFD-Reinf'
     ORDER BY v.id DESC LIMIT 1) as reinf_erros,
    (SELECT v.id FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'EFD-Reinf'
     ORDER BY v.id DESC LIMIT 1) as reinf_id,
    EXISTS(SELECT 1 FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'eSocial' AND v.status = 'ok'
     AND (v.resumo LIKE '%evtRemun%' OR v.resumo LIKE '%evtFechamento%' OR v.resumo LIKE '%S-1200%' OR v.resumo LIKE '%S-1299%' OR v.resumo LIKE '%evtDeslig%' OR v.resumo LIKE '%evtAdmissao%' OR v.resumo LIKE '%evtPgtos%' OR v.resumo LIKE '%evtBasesFgts%' OR v.resumo LIKE '%S-1210%' OR v.resumo LIKE '%S-1295%' OR v.resumo LIKE '%S-2299%')
    ) as esocial_mov_ok,
    EXISTS(SELECT 1 FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'EFD-Reinf' AND v.status = 'ok'
     AND (v.resumo LIKE '%evtServTom%' OR v.resumo LIKE '%evtServPrest%' OR v.resumo LIKE '%evtFechamento%' OR v.resumo LIKE '%R-2010%' OR v.resumo LIKE '%R-2020%' OR v.resumo LIKE '%R-2099%' OR v.resumo LIKE '%R-4010%' OR v.resumo LIKE '%R-4020%' OR v.resumo LIKE '%R-4040%' OR v.resumo LIKE '%R-4080%')
    ) as reinf_mov_ok,
    EXISTS(SELECT 1 FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'eSocial' AND v.status = 'ok'
     AND (v.resumo LIKE '%evtInfoEmpregador%' OR v.resumo LIKE '%S-1000%')
    ) as esocial_cad_ok,
    EXISTS(SELECT 1 FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id
     WHERE v.empresa_id = e.id AND a.competencia = ? AND a.tipo = 'EFD-Reinf' AND v.status = 'ok'
     AND (v.resumo LIKE '%evtInfoContri%' OR v.resumo LIKE '%R-1000%')
    ) as reinf_cad_ok,
    COALESCE((SELECT SUM(vt.vlr_total) FROM validacoes_totais vt JOIN validacoes vv ON vv.id = vt.validacao_id JOIN arquivos aa ON aa.id = vv.arquivo_id
     WHERE vv.empresa_id = e.id AND aa.competencia = ? AND aa.tipo = 'eSocial'), 0) as esocial_total,
    COALESCE((SELECT SUM(vt.vlr_total) FROM validacoes_totais vt JOIN validacoes vv ON vv.id = vt.validacao_id JOIN arquivos aa ON aa.id = vv.arquivo_id
     WHERE vv.empresa_id = e.id AND aa.competencia = ? AND aa.tipo = 'EFD-Reinf'), 0) as reinf_total
  FROM empresas e
  WHERE e.escritorio_id = ? AND e.ativo = 1
  ORDER BY e.razao_social
");
$stmt->execute([
  $competencia_selecionada, $competencia_selecionada, $competencia_selecionada,
  $competencia_selecionada, $competencia_selecionada, $competencia_selecionada,
  $competencia_selecionada, $competencia_selecionada,
  $competencia_selecionada, $competencia_selecionada,
  $competencia_selecionada, $competencia_selecionada,
  $escritorio_id
]);
$empresas = $stmt->fetchAll();

$total_prontas = 0;
$total_incompletas = 0;
$total_pendentes = 0;
$total_com_erro = 0;
$total_geral_recolher = 0;
$total_so_cadastro = 0;

foreach ($empresas as $emp) {
  // AGORA o que vale é o MOVIMENTO, não só o status OK
  $esocial_ok = !empty($emp['esocial_mov_ok']);
  $reinf_ok = !empty($emp['reinf_mov_ok']);
  $tem_erro = ($emp['esocial_status'] === 'erro' || $emp['reinf_status'] === 'erro');
  $tem_algum = ($emp['esocial_status'] !== null || $emp['reinf_status'] !== null);
  $so_cadastro = !$esocial_ok && !$reinf_ok && (!empty($emp['esocial_cad_ok']) || !empty($emp['reinf_cad_ok']));

  if ($tem_erro) $total_com_erro++;
  if ($esocial_ok && $reinf_ok) $total_prontas++;
  elseif (!$tem_algum) $total_pendentes++;
  else { $total_incompletas++; if ($so_cadastro) $total_so_cadastro++; }
  $total_geral_recolher += (float)($emp['esocial_total'] ?? 0) + (float)($emp['reinf_total'] ?? 0);
}

function badgeDctf($status, $erros, $id, $competencia, $mov_ok, $cad_ok) {
  if ($status === null) return '<span class="badge bg-warning text-dark">Não enviado</span>';
  $link = 'validacao-detalhe.php?id=' . $id . '&origem=simulador&competencia=' . urlencode($competencia);
  if ($status === 'ok') {
    if ($mov_ok) return '<a href="' . $link . '" class="badge bg-success text-decoration-none">OK - Movimento</a>';
    if ($cad_ok) return '<a href="' . $link . '" class="badge bg-info text-dark text-decoration-none">Só cadastro</a>';
    return '<a href="' . $link . '" class="badge bg-success text-decoration-none">OK</a>';
  }
  return '<a href="' . $link . '" class="badge bg-danger text-decoration-none">Com erro (' . (int)$erros . ')</a>';
}

function statusDctf($emp) {
  $esocial_ok = !empty($emp['esocial_mov_ok']);
  $reinf_ok = !empty($emp['reinf_mov_ok']);
  $tem_erro = ($emp['esocial_status'] === 'erro' || $emp['reinf_status'] === 'erro');
  $tem_algum = ($emp['esocial_status'] !== null || $emp['reinf_status'] !== null);
  $so_cadastro = !$esocial_ok && !$reinf_ok && (!empty($emp['esocial_cad_ok']) || !empty($emp['reinf_cad_ok']));

  if ($tem_erro) return '<span class="badge bg-danger">Com erro - corrigir antes</span>';
  if ($esocial_ok && $reinf_ok) return '<span class="badge bg-success">Pronta pra DCTFWeb</span>';
  if ($so_cadastro) return '<span class="badge bg-info text-dark">Só cadastro - Falta movimento</span>';
  if (!$tem_algum) return '<span class="badge bg-warning text-dark">Pendente</span>';
  return '<span class="badge bg-warning text-dark">Incompleta</span>';
}

function dicaDctf($emp) {
  $esocial_ok = !empty($emp['esocial_mov_ok']);
  $reinf_ok = !empty($emp['reinf_mov_ok']);
  $tem_erro = ($emp['esocial_status'] === 'erro' || $emp['reinf_status'] === 'erro');
  $esocial_cad = !empty($emp['esocial_cad_ok']);
  $reinf_cad = !empty($emp['reinf_cad_ok']);

  if ($tem_erro) return 'Tem arquivo com erro. Corrija e revalide antes de abrir o e-CAC.';
  if ($esocial_ok && $reinf_ok) return 'eSocial + Reinf com movimento OK. Pode conferir no e-CAC.';
  if (!$esocial_ok && !$reinf_ok && $esocial_cad && $reinf_cad) return 'Você só validou o cadastro (S-1000 + R-1000). Eles não têm valores. Valide o movimento: S-1200 / S-1299 + R-2010 / R-2020 / R-2099.';
  if (!$esocial_ok && !$reinf_ok && ($esocial_cad || $reinf_cad)) return 'Você só validou o cadastro (S-1000 / R-1000). Valide o movimento: S-1200 / S-1299 + R-2010 / R-2020 / R-2099.';
  if (!$esocial_ok && !$reinf_ok) return 'Nenhum arquivo enviado nesta competência.';
  if (!$esocial_ok) return $esocial_cad ? 'eSocial só tem cadastro (S-1000). Falta o movimento S-1200 / S-1299.' : 'Falta validar o eSocial (S-1200 / S-1299). A DCTFWeb vai vir incompleta.';
  if (!$reinf_ok) return $reinf_cad ? 'Reinf só tem cadastro (R-1000). Falta o movimento R-2010 / R-2020 / R-2099.' : 'Falta validar o Reinf (R-2010 / R-2020 / R-2099). A DCTFWeb vai vir incompleta.';
  return '';
}

function brl($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <div>
    <a href="dashboard.php?competencia=<?= htmlspecialchars($competencia_selecionada) ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Voltar para Dashboard</a>
    <h2 class="fw-bold mb-0 mt-1">Simulador DCTFWeb</h2>
  </div>

</div>

<div class="alert alert-info rounded-4">
  <i class="bi bi-info-circle"></i> <strong>Isso é uma prévia, não é a DCTFWeb oficial. Esta prévia serve para lhe garantir que o DCTFWeb da empresa não possui erros e poderá ser transmitido sem problemas.</strong> Ela mostra se o eSocial + Reinf já estão validados no SPEDFácil. A DCTFWeb oficial é montada pela Receita no e-CAC, e pode ter compensação, parcelamento e outros ajustes que não estão nos arquivos.
</div>

<?php if ($total_so_cadastro > 0): ?>
<div class="alert alert-warning rounded-4">
  <i class="bi bi-exclamation-triangle"></i> <strong><?= $total_so_cadastro ?> empresa(s) só com cadastro (S-1000 / R-1000).</strong> Cadastro não tem valores e não gera DCTFWeb. Valide o movimento: S-1200 / S-1299 + R-2010 / R-2020 / R-2099.
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Prontas pra DCTFWeb</small><h3 class="text-success mb-0"><?= $total_prontas ?> <i class="bi bi-check-circle"></i></h3></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Incompletas</small><h3 class="text-warning mb-0"><?= $total_incompletas ?> <i class="bi bi-exclamation-triangle"></i></h3></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Com erro</small><h3 class="text-danger mb-0"><?= $total_com_erro ?> <i class="bi bi-x-circle"></i></h3></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Pendentes</small><h3 class="text-warning mb-0"><?= $total_pendentes ?> <i class="bi bi-clock"></i></h3></div></div>
  </div>
</div>

<?php if ($total_geral_recolher > 0): ?>
<div class="alert alert-success rounded-4 d-flex justify-content-between align-items-center">
  <span><i class="bi bi-cash-coin"></i> <strong>Prévia DARF - <?= str_pad($mes_sel, 2, '0', STR_PAD_LEFT) ?>/<?= $ano_sel ?>:</strong> soma de eSocial + Reinf de todas as empresas validadas</span>
  <strong class="fs-5"><?= brl($total_geral_recolher) ?></strong>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <span class="fw-bold">Conferência DCTFWeb - <?= str_pad($mes_sel, 2, '0', STR_PAD_LEFT) ?>/<?= $ano_sel ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Empresa</th><th>CNPJ</th><th>eSocial</th><th>Reinf</th><th>Situação DCTFWeb</th><th>Total a recolher</th><th>O que fazer</th><th>Ação</th></tr></thead>
      <tbody>
        <?php if (empty($empresas)): ?>
        <tr><td colspan="8" class="text-center py-4 text-muted">Nenhuma empresa cadastrada ainda.</td></tr>
        <?php else: ?>
          <?php foreach ($empresas as $emp): ?>
          <?php $total_emp = (float)($emp['esocial_total'] ?? 0) + (float)($emp['reinf_total'] ?? 0); ?>
          <?php
            $esocial_mov = !empty($emp['esocial_mov_ok']);
            $reinf_mov = !empty($emp['reinf_mov_ok']);
            $url_upload = 'upload.php?empresa_id=' . $emp['id'] . '&competencia=' . urlencode($competencia_selecionada) . '&origem=simulador';
          ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($emp['razao_social']) ?></td>
            <td><?= htmlspecialchars($emp['cnpj']) ?></td>
            <td><?= badgeDctf($emp['esocial_status'], $emp['esocial_erros'], $emp['esocial_id'], $competencia_selecionada, $esocial_mov, !empty($emp['esocial_cad_ok'])) ?></td>
            <td><?= badgeDctf($emp['reinf_status'], $emp['reinf_erros'], $emp['reinf_id'], $competencia_selecionada, $reinf_mov, !empty($emp['reinf_cad_ok'])) ?></td>
            <td><?= statusDctf($emp) ?></td>
            <td>
              <?php if ($total_emp > 0): ?>
                <strong><?= brl($total_emp) ?></strong><br>
                <small class="text-muted">eSocial <?= brl($emp['esocial_total']) ?> + Reinf <?= brl($emp['reinf_total']) ?></small>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td><small class="text-muted"><?= dicaDctf($emp) ?></small></td>
            <td>
              <?php if (pode(['admin', 'contador', 'auxiliar'])): ?>
                <!-- Botão Validar: se já tem movimento OK, abre o modal de confirmação -->
                <button type="button"
                  class="btn btn-sm btn-primary btn-validar-sim"
                  data-url="<?= htmlspecialchars($url_upload) ?>"
                  data-empresa="<?= htmlspecialchars($emp['razao_social']) ?>"
                  data-competencia="<?= htmlspecialchars($competencia_selecionada) ?>"
                  data-esocial-mov="<?= $esocial_mov ? '1' : '0' ?>"
                  data-reinf-mov="<?= $reinf_mov ? '1' : '0' ?>">Validar</button>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL: já validado, gerar novamente? -->
<div class="modal fade" id="modalJaValidado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-check-circle-fill text-success"></i> Já validado nesta competência</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">A empresa <strong id="modalJaValidadoEmpresa">—</strong> já possui validação com movimento na competência <strong id="modalJaValidadoCompetencia">—</strong>:</p>
        <ul class="mb-3" id="modalJaValidadoLista"></ul>
        <p class="text-muted mb-0">Deseja gerar novamente? A nova validação vai substituir a anterior para esta competência.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="modalJaValidadoConfirmar" class="btn btn-primary">Sim, gerar novamente</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modalEl = document.getElementById('modalJaValidado');
  if (!modalEl) return;

  var modal = new bootstrap.Modal(modalEl);
  var elEmpresa = document.getElementById('modalJaValidadoEmpresa');
  var elCompetencia = document.getElementById('modalJaValidadoCompetencia');
  var elLista = document.getElementById('modalJaValidadoLista');
  var elConfirmar = document.getElementById('modalJaValidadoConfirmar');

  function formataCompModal(comp) {
    // 2026-11 -> 11/2026
    if (/^\d{4}-\d{2}$/.test(comp)) return comp.substring(5, 7) + '/' + comp.substring(0, 4);
    return comp;
  }

  document.querySelectorAll('.btn-validar-sim').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var url = btn.getAttribute('data-url');
      var esocialMov = btn.getAttribute('data-esocial-mov') === '1';
      var reinfMov = btn.getAttribute('data-reinf-mov') === '1';

      // Nenhum movimento OK -> vai direto, sem modal
      if (!esocialMov && !reinfMov) {
        window.location.href = url;
        return;
      }

      // Já tem movimento OK -> monta o modal com o que já foi validado
      var itens = [];
      if (esocialMov) itens.push('<li><strong>eSocial</strong> <span class="badge bg-success">OK - Movimento</span></li>');
      if (reinfMov) itens.push('<li><strong>Reinf</strong> <span class="badge bg-success">OK - Movimento</span></li>');

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