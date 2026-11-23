<?php
// C:\xampp2\htdocs\presped\pages\upload.php
// Tela "Validar arquivo" - 1 arquivo por vez
// UNIFICADO: usa processarUmArquivo() do validacao-lote.php
// CORRIGIDO: se salvou validacao_id mesmo com status=erro, vai pro detalhe (não mostra erro geral)
// CORRIGIDO 2: S-1000 / tabelas não têm perApur, só iniValid -> passa competência da tela pro servidor
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp2/htdocs/presped/logs/php-error.log');
error_reporting(E_ALL);
ob_start();

require_once '../config.php';

if (!estaLogado()) {
  header("Location: ../index.php?aba=login");
  exit;
}

define('SPED_LOTE_FUNCOES_ONLY', true);
require_once 'validacao-lote.php';

$escritorio_id = $_SESSION['escritorio_id'];
$erro = '';

$stmt = $pdo->prepare("SELECT * FROM empresas WHERE escritorio_id = ? ORDER BY razao_social ASC");
$stmt->execute([$escritorio_id]);
$empresas = $stmt->fetchAll();

$empresa_pre = $_POST['empresa_id'] ?? $_GET['empresa_id'] ?? '';
$competencia_pre = $_POST['competencia'] ?? $_GET['competencia'] ?? '';
$competencia_pre = str_replace('/', '-', $competencia_pre);
$origem = $_POST['origem'] ?? $_GET['origem'] ?? '';
$competencia_volta = $competencia_pre ?: date('Y-m');

$empresa_texto_inicial = 'Será detectada ao escolher o arquivo';
$cnpj_empresa_pre = '';
if ($empresa_pre) {
  foreach ($empresas as $e) {
    if ((string)$e['id'] === (string)$empresa_pre) {
      $empresa_texto_inicial = $e['razao_social'] . ' - ' . $e['cnpj'];
      $cnpj_empresa_pre = preg_replace('/[^0-9]/', '', $e['cnpj']);
      break;
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $origem = $_POST['origem'] ?? '';
  // Garante que a competência do formulário vai pro processador (tabelas precisam dela)
  if (!empty($_POST['competencia'])) $competencia_pre = str_replace('/', '-', $_POST['competencia']);
  if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    $erro = 'Selecione um arquivo .txt ou .xml para validar.';
  } else {
    $nome_original = $_FILES['arquivo']['name'];
    $ext = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
    if (!in_array($ext, ['txt', 'xml'])) {
      $erro = 'O arquivo precisa ser .txt (EFD) ou .xml (Reinf / eSocial).';
    } else {
      // CORRIGIDO: 8º parâmetro = competência da tela
      $resultado = processarUmArquivo($pdo, $empresas, $escritorio_id, $nome_original, $_FILES['arquivo']['tmp_name'], $_FILES['arquivo']['error'], time(), $competencia_pre);
      if (!empty($resultado['validacao_id'])) {
        $validacao_id = $resultado['validacao_id'];
        $competencia = $resultado['competencia'];
        $params_detalhe = "id=" . $validacao_id . "&competencia=" . urlencode($competencia);
        if ($origem === 'simulador') $params_detalhe .= "&origem=simulador";
        elseif ($origem === 'lote') $params_detalhe .= "&origem=lote";
        if (ob_get_length()) ob_end_clean();
        header("Location: validacao-detalhe.php?" . $params_detalhe);
        exit;
      }
      if (!empty($resultado['cnpj_nao_cadastrado'])) {
        $erro = 'O arquivo pertence ao CNPJ ' . formataCnpjLote($resultado['cnpj_nao_cadastrado']) . ', e não está cadastrada aqui no Pré-SPED! Cadastre a empresa primeiro e tente de novo.';
      } else {
        $erro = $resultado['mensagem'] ?: 'Não consegui validar o arquivo.';
      }
      if (!empty($resultado['competencia']) && $resultado['competencia'] !== '-') {
        $competencia_pre = $resultado['competencia'];
        $competencia_volta = $resultado['competencia'];
      }
    }
  }
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="d-flex flex-wrap gap-3">
      <a href="dashboard.php?competencia=<?= htmlspecialchars($competencia_volta) ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Voltar para Dashboard</a>
      <?php if ($origem === 'simulador'): ?>
        <a href="simulador-dctfweb.php?competencia=<?= htmlspecialchars($competencia_volta) ?>" class="text-decoration-none small fw-bold"><i class="bi bi-arrow-left"></i> Voltar para o Simulador DCTFWeb</a>
      <?php endif; ?>
    </div>
    <h2 class="fw-bold mb-0 mt-1">Validar SPED</h2>
    <p class="text-muted mb-0">Envie o arquivo .txt (EFD) ou .xml (Reinf / eSocial) gerado pelo seu sistema contábil</p>
  </div>
</div>

<?php if ($erro): ?>
  <div class="alert alert-danger rounded-4"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<?php if ($empresa_pre): ?>
  <div class="alert alert-info rounded-4">Reenviando arquivo corrigido. Só falta escolher o novo arquivo <strong>.txt ou .xml</strong> — empresa, tipo e competência serão detectados sozinhos pelo novo arquivo.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-4">
    <form method="POST" enctype="multipart/form-data" id="form_upload">
      <input type="hidden" name="origem" value="<?= htmlspecialchars($origem) ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Empresa</label>
          <input type="text" id="campo_empresa_texto" class="form-control bg-light" value="<?= htmlspecialchars($empresa_texto_inicial) ?>" readonly>
          <input type="hidden" name="empresa_id" id="campo_empresa_id" value="<?= htmlspecialchars($empresa_pre) ?>">
          <small class="text-muted" id="aviso_empresa">Escolha o arquivo que a empresa será detectada pelo CNPJ dele.</small>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Tipo</label>
          <input type="text" name="tipo" id="campo_tipo" class="form-control bg-light" value="Será detectado ao escolher o arquivo" readonly>
          <small class="text-muted">Detectado automaticamente.</small>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Competência</label>
          <input type="month" name="competencia" id="campo_competencia" class="form-control bg-light" value="<?= htmlspecialchars($competencia_pre) ?>" readonly required>
          <small class="text-muted">Detectada pelo período do arquivo. Tabela (S-1000 etc.) usa a competência da tela.</small>
        </div>
        <div class="col-12">
          <label class="form-label fw-bold">Arquivo SPED (.txt ou .xml)</label>
          <input type="file" name="arquivo" id="campo_arquivo" class="form-control" accept=".txt,.xml" required>
          <small class="text-muted">EFD usa .txt, Reinf e eSocial usam .xml. O sistema detecta sozinho empresa, tipo e competência pelo conteúdo do arquivo.</small>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-cloud-upload"></i> Validar SPED </button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalEmpresaNaoCadastrada" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Empresa não cadastrada</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">O arquivo pertence ao CNPJ <strong id="cnpj_nao_cadastrado"></strong>, e não está cadastrada aqui no Pré-SPED! Quer cadastrar a mesma agora?</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
        <button type="button" class="btn btn-primary" id="btn_cadastrar_empresa">Sim, cadastrar agora</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEmpresaDiferente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Arquivo de outra empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Esse arquivo pertence à empresa <strong id="empresa_arquivo_nome"></strong>, diferente da empresa selecionada <strong id="empresa_selecionada_nome"></strong>. Quer trocar para a empresa do arquivo?</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não, manter selecionada</button>
        <button type="button" class="btn btn-primary" id="btn_trocar_empresa">Sim, trocar</button>
      </div>
    </div>
  </div>
</div>

<script>
const EMPRESAS_CADASTRADAS = <?= json_encode(array_map(function($e) {
  return ['id' => (string)$e['id'], 'nome' => $e['razao_social'], 'cnpj' => preg_replace('/[^0-9]/', '', $e['cnpj'] ?? ''), 'cnpj_formatado' => $e['cnpj']];
}, $empresas)) ?>;
let empresaSelecionadaInicial = {
  id: <?= json_encode((string)$empresa_pre) ?>,
  cnpj: <?= json_encode($cnpj_empresa_pre) ?>,
  texto: <?= json_encode($empresa_texto_inicial) ?>
};
const COMPETENCIA_TELA = <?= json_encode($competencia_pre ?: date('Y-m')) ?>;
let cnpjPendenteCadastro = '';
let empresaPendenteTroca = null;
function formataCnpjJS(cnpj) {
  cnpj = (cnpj || '').replace(/\D/g, '');
  if (cnpj.length !== 14) return cnpj;
  return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
}
function extrairCnpjJS(conteudo) {
  let m = conteudo.match(/<ide(?:Contri|Empregador)>[\s\S]*?<nrInsc>(\d{14})<\/nrInsc>/);
  if (m) return m[1];
  m = conteudo.match(/<(?:nrInsc|cnpj)>(\d{14})<\/(?:nrInsc|cnpj)>/);
  if (m) return m[1];
  return '';
}
function extrairCompetenciaJS(conteudo) {
  let m = conteudo.match(/<perApur>(\d{4})-?(\d{2})/) || conteudo.match(/<dtPerApur>(\d{4})-?(\d{2})/);
  if (m) return m[1] + '-' + m[2];
  let mIni = conteudo.match(/<iniValid>(\d{4})-?(\d{2})/);
  if (mIni) {
    if (COMPETENCIA_TELA && /^\d{4}-\d{2}$/.test(COMPETENCIA_TELA)) return COMPETENCIA_TELA;
    return mIni[1] + '-' + mIni[2];
  }
  let mAdm = conteudo.match(/<dtAdm>(\d{4})-(\d{2})-\d{2}<\/dtAdm>/);
  if (mAdm) return mAdm[1] + '-' + mAdm[2];
  let mDeslig = conteudo.match(/<dtDeslig>(\d{4})-(\d{2})-\d{2}<\/dtDeslig>/);
  if (mDeslig) return mDeslig[1] + '-' + mDeslig[2];
  if (COMPETENCIA_TELA && /^\d{4}-\d{2}$/.test(COMPETENCIA_TELA)) return COMPETENCIA_TELA;
  return '';
}
document.getElementById('campo_arquivo').addEventListener('change', function() {
  const arquivo = this.files[0];
  const campoTipo = document.getElementById('campo_tipo');
  const campoEmpresaTexto = document.getElementById('campo_empresa_texto');
  const campoEmpresaId = document.getElementById('campo_empresa_id');
  const campoComp = document.getElementById('campo_competencia');
  const avisoEmpresa = document.getElementById('aviso_empresa');
  if (!arquivo) { campoTipo.value = 'Será detectado ao escolher o arquivo'; return; }
  const nome = arquivo.name.toLowerCase();
  campoTipo.value = 'Detectando...';
  const leitor = new FileReader();
  leitor.onload = function(e) {
    const conteudo = e.target.result;
    let cnpj = '';
    let competencia = '';
    if (nome.endsWith('.xml')) {
      if (conteudo.includes('<Reinf') || conteudo.includes('evtInfoContri') || conteudo.includes('evtTotal') || conteudo.includes('evtServTom') || conteudo.includes('R-2010')) campoTipo.value = 'EFD-Reinf';
      else if (conteudo.includes('<eSocial') || conteudo.includes('evtAdmissao') || conteudo.includes('evtInfoEmpregador') || conteudo.includes('evtRemun') || conteudo.includes('S-1200') || conteudo.includes('evtTabRubrica') || conteudo.includes('evtTabLotacao') || conteudo.includes('evtTabCargo') || conteudo.includes('evtTabFuncao') || conteudo.includes('evtTabHorario') || conteudo.includes('evtTabAmbiente') || conteudo.includes('evtTabProcesso') || conteudo.includes('evtTabOperPort')) campoTipo.value = 'eSocial';
      else campoTipo.value = 'XML - tipo não identificado';
      cnpj = extrairCnpjJS(conteudo);
      competencia = extrairCompetenciaJS(conteudo);
    } else if (nome.endsWith('.txt')) {
      if (conteudo.includes('|A100|') || conteudo.includes('|F100|') || conteudo.includes('|M100|') || conteudo.includes('|P100|')) campoTipo.value = 'EFD Contribuições';
      else campoTipo.value = 'EFD ICMS/IPI';
      let m0000 = conteudo.match(/\|0000\|[^|]*\|[^|]*\|(\d{8})\|(\d{8})\|[^|]*\|(\d{14})\|/);
      if (m0000) { competencia = m0000[1].substring(4, 8) + '-' + m0000[1].substring(2, 4); cnpj = m0000[3]; }
    } else { campoTipo.value = 'Tipo não identificado'; }
    if (competencia) campoComp.value = competencia;
    if (!campoComp.value && COMPETENCIA_TELA) campoComp.value = COMPETENCIA_TELA;
    if (!cnpj) {
      campoEmpresaTexto.value = 'Não consegui ler o CNPJ do arquivo';
      avisoEmpresa.textContent = 'Verifique se o arquivo está completo.';
      avisoEmpresa.className = 'text-danger';
      return;
    }
    const achada = EMPRESAS_CADASTRADAS.find(emp => emp.cnpj === cnpj);
    if (empresaSelecionadaInicial.cnpj && cnpj === empresaSelecionadaInicial.cnpj) {
      campoEmpresaTexto.value = empresaSelecionadaInicial.texto;
      campoEmpresaId.value = empresaSelecionadaInicial.id;
      avisoEmpresa.textContent = 'Arquivo da mesma empresa selecionada. Pode validar.';
      avisoEmpresa.className = 'text-success';
      return;
    }
    if (achada) {
      if (!empresaSelecionadaInicial.cnpj) {
        campoEmpresaTexto.value = achada.nome + ' - ' + achada.cnpj_formatado;
        campoEmpresaId.value = achada.id;
        avisoEmpresa.textContent = 'Empresa detectada pelo CNPJ do arquivo.';
        avisoEmpresa.className = 'text-success';
        return;
      }
      empresaPendenteTroca = achada;
      document.getElementById('empresa_arquivo_nome').textContent = achada.nome + ' - ' + achada.cnpj_formatado;
      document.getElementById('empresa_selecionada_nome').textContent = empresaSelecionadaInicial.texto;
      new bootstrap.Modal(document.getElementById('modalEmpresaDiferente')).show();
      return;
    }
    cnpjPendenteCadastro = cnpj;
    campoEmpresaTexto.value = 'CNPJ ' + formataCnpjJS(cnpj) + ' - não cadastrada';
    campoEmpresaId.value = '';
    avisoEmpresa.textContent = 'CNPJ não cadastrado. Cadastre a empresa para validar.';
    avisoEmpresa.className = 'text-danger fw-bold';
    document.getElementById('cnpj_nao_cadastrado').textContent = formataCnpjJS(cnpj);
    new bootstrap.Modal(document.getElementById('modalEmpresaNaoCadastrada')).show();
  };
  leitor.readAsText(arquivo.slice(0, 200000));
});
document.getElementById('btn_cadastrar_empresa').addEventListener('click', function() {
  const competencia = document.getElementById('campo_competencia').value || '';
  window.location.href = 'empresas.php?cnpj=' + encodeURIComponent(cnpjPendenteCadastro) + '&origem=upload&competencia=' + encodeURIComponent(competencia);
});
document.getElementById('btn_trocar_empresa').addEventListener('click', function() {
  if (empresaPendenteTroca) {
    document.getElementById('campo_empresa_texto').value = empresaPendenteTroca.nome + ' - ' + empresaPendenteTroca.cnpj_formatado;
    document.getElementById('campo_empresa_id').value = empresaPendenteTroca.id;
    document.getElementById('aviso_empresa').textContent = 'Empresa trocada para a empresa do arquivo.';
    document.getElementById('aviso_empresa').className = 'text-success';
    empresaPendenteTroca = null;
  }
  bootstrap.Modal.getInstance(document.getElementById('modalEmpresaDiferente')).hide();
});
document.getElementById('modalEmpresaDiferente').addEventListener('hidden.bs.modal', function() {
  if (empresaPendenteTroca && document.getElementById('campo_empresa_id').value !== empresaPendenteTroca.id) {
    document.getElementById('campo_empresa_texto').value = empresaSelecionadaInicial.texto;
    document.getElementById('campo_empresa_id').value = empresaSelecionadaInicial.id;
    document.getElementById('campo_arquivo').value = '';
    document.getElementById('aviso_empresa').textContent = 'Arquivo descartado. Escolha um arquivo da empresa selecionada.';
    document.getElementById('aviso_empresa').className = 'text-warning';
    empresaPendenteTroca = null;
  }
});
document.getElementById('modalEmpresaNaoCadastrada').addEventListener('hidden.bs.modal', function() {
  if (cnpjPendenteCadastro && !document.getElementById('campo_empresa_id').value) {
    document.getElementById('campo_arquivo').value = '';
  }
});
document.getElementById('form_upload').addEventListener('submit', function(e) {
  const campoComp = document.getElementById('campo_competencia');
  if (!campoComp.value && COMPETENCIA_TELA) campoComp.value = COMPETENCIA_TELA;
  if (!document.getElementById('campo_empresa_id').value) {
    e.preventDefault();
    alert('Selecione um arquivo de uma empresa cadastrada, ou cadastre a empresa primeiro.');
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>