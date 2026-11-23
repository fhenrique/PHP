<?php
// C:\xampp2\htdocs\presped\pages\empresas.php
require_once '../config.php';
require_once '../includes/log.php';

if (!estaLogado()) {
  header("Location: ../index.php?aba=login");
  exit;
}

// Se chegou com origem=lote, guarda na sessão pra não perder ao salvar / atualizar
if (!empty($_GET['origem'])) {
  $_SESSION['origem_empresas'] = $_GET['origem'];
}

$origem = $_POST['origem'] ?? $_GET['origem'] ?? $_SESSION['origem_empresas'] ?? '';

$escritorio_id = $_SESSION['escritorio_id'];
$plano = $_SESSION['escritorio_plano'] ?? 'teste';
$erro = '';
$sucesso = '';

// --- EXCLUIR / DESATIVAR ---
if (isset($_GET['excluir'])) {
  $id = (int)$_GET['excluir'];
  $stmt = $pdo->prepare("SELECT razao_social, cnpj FROM empresas WHERE id = ? AND escritorio_id = ?");
  $stmt->execute([$id, $escritorio_id]);
  $e_excluir = $stmt->fetch();

  $stmt = $pdo->prepare("UPDATE empresas SET ativo = 0 WHERE id = ? AND escritorio_id = ?");
  $stmt->execute([$id, $escritorio_id]);

  if ($e_excluir) {
    registrar_log($pdo, 'exclusao_empresa', "Desativou a empresa: {$e_excluir['razao_social']} - CNPJ: {$e_excluir['cnpj']} - ID: $id");
  }
  header("Location: empresas.php?ok=excluido");
  exit;
}

// --- CARREGAR PARA EDITAR ---
$editando = null;
if (isset($_GET['editar'])) {
  $id = (int)$_GET['editar'];
  $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = ? AND escritorio_id = ? AND ativo = 1");
  $stmt->execute([$id, $escritorio_id]);
  $editando = $stmt->fetch();
}

// --- SALVAR (CADASTRAR OU EDITAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
  $razao_social = trim($_POST['razao_social'] ?? '');
  $cnpj = trim($_POST['cnpj'] ?? '');
  $uf = strtoupper(trim($_POST['uf'] ?? ''));
  $regime = trim($_POST['regime'] ?? '');
  $responsavel = trim($_POST['responsavel'] ?? '');

  // Limpa CNPJ para validar (só números)
  $cnpj_limpo = preg_replace('/\D/', '', $cnpj);

  if ($razao_social === '' || $cnpj_limpo === '' || $uf === '' || $regime === '') {
    $erro = 'Preencha todos os campos obrigatórios.';
  } elseif (strlen($cnpj_limpo) !== 14) {
    $erro = 'CNPJ inválido. Digite os 14 dígitos.';
  } else {
    // Verifica CNPJ duplicado (em outro registro)
    $sql = "SELECT COUNT(*) FROM empresas WHERE cnpj = ? AND escritorio_id = ? AND ativo = 1";
    $params = [$cnpj, $escritorio_id];
    if ($id) { $sql .= " AND id != ?"; $params[] = $id; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn() > 0) {
      $erro = 'Já existe uma empresa com este CNPJ.';
    } else {
      // Trava plano teste: máximo 5 empresas
      if (!$id && $plano === 'teste') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE escritorio_id = ? AND ativo = 1");
        $stmt->execute([$escritorio_id]);
        if ($stmt->fetchColumn() >= 5) {
          $erro = 'Seu plano Teste permite até 5 empresas. Faça upgrade para cadastrar mais. <a href="planos.php" class="alert-link"><strong>Clique aqui para trocar seu plano!</strong></a>';
        }
      }

      if ($erro === '') {
        if ($id) {
          // --- LOG DE EDIÇÃO: busca os dados antigos antes de salvar ---
          $stmt = $pdo->prepare("SELECT razao_social, cnpj, uf, regime, responsavel FROM empresas WHERE id = ? AND escritorio_id = ?");
          $stmt->execute([$id, $escritorio_id]);
          $antiga = $stmt->fetch();

          $stmt = $pdo->prepare("UPDATE empresas SET razao_social=?, cnpj=?, uf=?, regime=?, responsavel=? WHERE id=? AND escritorio_id=?");
          $stmt->execute([$razao_social, $cnpj, $uf, $regime, $responsavel, $id, $escritorio_id]);

          if ($antiga) {
            $mudancas = [];
            if ($antiga['razao_social'] !== $razao_social) $mudancas[] = "Razão Social - De: {$antiga['razao_social']} Para: $razao_social";
            if ($antiga['cnpj'] !== $cnpj) $mudancas[] = "CNPJ - De: {$antiga['cnpj']} Para: $cnpj";
            if ($antiga['uf'] !== $uf) $mudancas[] = "UF - De: {$antiga['uf']} Para: $uf";
            if ($antiga['regime'] !== $regime) $mudancas[] = "Regime - De: {$antiga['regime']} Para: $regime";
            if (($antiga['responsavel'] ?? '') !== $responsavel) $mudancas[] = "Responsável - De: " . ($antiga['responsavel'] ?: '(vazio)') . " Para: " . ($responsavel ?: '(vazio)');
            $detalhe = !empty($mudancas) ? "\nAlterações:\n" . implode("\n", $mudancas) : "\nNenhuma alteração detectada";
            registrar_log($pdo, 'edicao_empresa', "Editou a empresa: $razao_social - CNPJ: $cnpj - ID: $id$detalhe");
          }

          header("Location: empresas.php?ok=editado");
          exit;
        } else {
          $stmt = $pdo->prepare("INSERT INTO empresas (escritorio_id, cnpj, razao_social, uf, regime, responsavel, ativo) VALUES (?, ?, ?, ?, ?, ?, 1)");
          $stmt->execute([$escritorio_id, $cnpj, $razao_social, $uf, $regime, $responsavel]);
          $nova_empresa_id = $pdo->lastInsertId();

          registrar_log($pdo, 'cadastro_empresa', "Cadastrou a empresa: $razao_social - CNPJ: $cnpj - ID: $nova_empresa_id");

          // Já cria a competência do mês atual como pendente
          $mes = (int)date('m');
          $ano = (int)date('Y');
          $stmt = $pdo->prepare("INSERT IGNORE INTO competencias (empresa_id, mes, ano, status) VALUES (?, ?, ?, 'pendente')");
          $stmt->execute([$nova_empresa_id, $mes, $ano]);

          // Se veio do upload, volta pra upload já com a empresa nova selecionada
          if (($_POST['origem'] ?? '') === 'upload') {
            $competencia_volta = $_POST['competencia_retorno'] ?? '';
            $url_volta = 'upload.php?empresa_id=' . $nova_empresa_id;
            if ($competencia_volta) {
              $url_volta .= '&competencia=' . urlencode($competencia_volta);
            }
            header("Location: " . $url_volta);
            exit;
          }

          header("Location: empresas.php?ok=cadastrado");
          exit;
        }
      }
    }
  }
  // Se deu erro, mantém os dados digitados para não apagar o form
  $editando = ['id' => $id, 'razao_social' => $razao_social, 'cnpj' => $cnpj, 'uf' => $uf, 'regime' => $regime, 'responsavel' => $responsavel];
}

if (isset($_GET['ok'])) {
  if ($_GET['ok'] === 'cadastrado') $sucesso = 'Empresa cadastrada com sucesso!';
  if ($_GET['ok'] === 'editado') $sucesso = 'Empresa atualizada com sucesso!';
  if ($_GET['ok'] === 'excluido') $sucesso = 'Empresa removida.';
}

// --- LISTAR ---
$stmt = $pdo->prepare("SELECT * FROM empresas WHERE escritorio_id = ? AND ativo = 1 ORDER BY razao_social");
$stmt->execute([$escritorio_id]);
$empresas = $stmt->fetchAll();

$total = count($empresas);

// CORREÇÃO: Valor do CNPJ no formulário
// 1º prioridade: $editando (quando clicou em Editar ou quando deu erro ao salvar)
// 2º prioridade: ?cnpj= da URL (quando veio do lote pra cadastrar empresa nova)
$cnpj_form = $editando['cnpj'] ?? $_GET['cnpj'] ?? '';

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Empresas</h2>
    <p class="text-muted mb-0"><?= $total ?> empresa(s) cadastrada(s) <?= $plano === 'teste' ? '- limite de 5 no plano Teste' : '' ?></p>
  </div>
  
<div class="d-flex flex-wrap gap-3">
  <a href="dashboard.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Voltar para Dashboard</a>
  <?php if ($origem === 'lote'): ?>
    <a href="validacao-lote.php" class="text-decoration-none small fw-bold"><i class="bi bi-arrow-left"></i> Voltar para Validação em Lote</a>
  <?php endif; ?>
</div>  
  
</div>

<?php if ($erro): ?><div class="alert alert-danger"><?= $erro ?></div><?php endif; ?>

<?php if ($sucesso): ?><div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

<!-- FORMULÁRIO -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><?= $editando ? 'Editar empresa' : 'Nova empresa' ?></div>
  <div class="card-body">
    <form method="POST">
      <?php if (!empty($editando['id'])): ?><input type="hidden" name="id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Razão Social *</label>
          <input type="text" name="razao_social" class="form-control" required value="<?= htmlspecialchars($editando['razao_social'] ?? '') ?>" placeholder="Ex: José Matheus Comércio Ltda">
        </div>
        <div class="col-md-3">
          <label class="form-label">CNPJ *</label>
          <input type="text" id="cnpj" name="cnpj" class="form-control" required maxlength="18" value="<?= htmlspecialchars($cnpj_form) ?>" placeholder="00.000.000/0000-00">
        </div>
        
<div class="col-md-2">
  <label class="form-label">UF *</label>
  <select name="uf" class="form-select" required>
    <option value="">Selecione</option>
    <option value="AC" <?= ($editando['uf'] ?? '') === 'AC' ? 'selected' : '' ?>>AC - Acre</option>
    <option value="AL" <?= ($editando['uf'] ?? '') === 'AL' ? 'selected' : '' ?>>AL - Alagoas</option>
    <option value="AP" <?= ($editando['uf'] ?? '') === 'AP' ? 'selected' : '' ?>>AP - Amapá</option>
    <option value="AM" <?= ($editando['uf'] ?? '') === 'AM' ? 'selected' : '' ?>>AM - Amazonas</option>
    <option value="BA" <?= ($editando['uf'] ?? '') === 'BA' ? 'selected' : '' ?>>BA - Bahia</option>
    <option value="CE" <?= ($editando['uf'] ?? '') === 'CE' ? 'selected' : '' ?>>CE - Ceará</option>
    <option value="DF" <?= ($editando['uf'] ?? '') === 'DF' ? 'selected' : '' ?>>DF - Distrito Federal</option>
    <option value="ES" <?= ($editando['uf'] ?? '') === 'ES' ? 'selected' : '' ?>>ES - Espírito Santo</option>
    <option value="GO" <?= ($editando['uf'] ?? '') === 'GO' ? 'selected' : '' ?>>GO - Goiás</option>
    <option value="MA" <?= ($editando['uf'] ?? '') === 'MA' ? 'selected' : '' ?>>MA - Maranhão</option>
    <option value="MT" <?= ($editando['uf'] ?? '') === 'MT' ? 'selected' : '' ?>>MT - Mato Grosso</option>
    <option value="MS" <?= ($editando['uf'] ?? '') === 'MS' ? 'selected' : '' ?>>MS - Mato Grosso do Sul</option>
    <option value="MG" <?= ($editando['uf'] ?? '') === 'MG' ? 'selected' : '' ?>>MG - Minas Gerais</option>
    <option value="PA" <?= ($editando['uf'] ?? '') === 'PA' ? 'selected' : '' ?>>PA - Pará</option>
    <option value="PB" <?= ($editando['uf'] ?? '') === 'PB' ? 'selected' : '' ?>>PB - Paraíba</option>
    <option value="PR" <?= ($editando['uf'] ?? '') === 'PR' ? 'selected' : '' ?>>PR - Paraná</option>
    <option value="PE" <?= ($editando['uf'] ?? '') === 'PE' ? 'selected' : '' ?>>PE - Pernambuco</option>
    <option value="PI" <?= ($editando['uf'] ?? '') === 'PI' ? 'selected' : '' ?>>PI - Piauí</option>
    <option value="RJ" <?= ($editando['uf'] ?? '') === 'RJ' ? 'selected' : '' ?>>RJ - Rio de Janeiro</option>
    <option value="RN" <?= ($editando['uf'] ?? '') === 'RN' ? 'selected' : '' ?>>RN - Rio Grande do Norte</option>
    <option value="RS" <?= ($editando['uf'] ?? '') === 'RS' ? 'selected' : '' ?>>RS - Rio Grande do Sul</option>
    <option value="RO" <?= ($editando['uf'] ?? '') === 'RO' ? 'selected' : '' ?>>RO - Rondônia</option>
    <option value="RR" <?= ($editando['uf'] ?? '') === 'RR' ? 'selected' : '' ?>>RR - Roraima</option>
    <option value="SC" <?= ($editando['uf'] ?? '') === 'SC' ? 'selected' : '' ?>>SC - Santa Catarina</option>
    <option value="SP" <?= ($editando['uf'] ?? '') === 'SP' ? 'selected' : '' ?>>SP - São Paulo</option>
    <option value="SE" <?= ($editando['uf'] ?? '') === 'SE' ? 'selected' : '' ?>>SE - Sergipe</option>
    <option value="TO" <?= ($editando['uf'] ?? '') === 'TO' ? 'selected' : '' ?>>TO - Tocantins</option>
  </select>
</div>
		
        <div class="col-md-2">
          <label class="form-label">Regime *</label>
          <select name="regime" class="form-select" required>
            <option value="">Selecione</option>
            <option value="Simples Nacional" <?= ($editando['regime'] ?? '') === 'Simples Nacional' ? 'selected' : '' ?>>Simples Nacional</option>
            <option value="Lucro Presumido" <?= ($editando['regime'] ?? '') === 'Lucro Presumido' ? 'selected' : '' ?>>Lucro Presumido</option>
            <option value="Lucro Real" <?= ($editando['regime'] ?? '') === 'Lucro Real' ? 'selected' : '' ?>>Lucro Real</option>
            <option value="MEI" <?= ($editando['regime'] ?? '') === 'MEI' ? 'selected' : '' ?>>MEI</option>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Responsável / Contato</label>
          <input type="text" name="responsavel" class="form-control" value="<?= htmlspecialchars($editando['responsavel'] ?? '') ?>" placeholder="Nome do responsável na empresa">
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $editando ? 'Salvar alterações' : 'Cadastrar empresa' ?></button>
        <?php if ($editando): ?><a href="empresas.php" class="btn btn-secondary">Cancelar edição</a><?php endif; ?>
      </div>
	  
<!-- Guarda de onde veio, pra saber se volta pro upload -->
<input type="hidden" name="origem" value="<?= htmlspecialchars($_GET['origem'] ?? $_POST['origem'] ?? '') ?>">

<input type="hidden" name="competencia_retorno" value="<?= htmlspecialchars($_GET['competencia'] ?? $_POST['competencia_retorno'] ?? '') ?>">	  
	  
    </form>
  </div>
</div>

<!-- LISTA -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-bold">Empresas cadastradas</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Razão Social</th><th>CNPJ</th><th>UF / Regime</th><th>Responsável</th><th style="width:180px">Ações</th></tr></thead>
      <tbody>
        <?php if (empty($empresas)): ?>
        <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma empresa ainda. Cadastre a primeira acima.</td></tr>
        <?php else: foreach ($empresas as $e): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($e['razao_social']) ?></td>
          <td><?= htmlspecialchars($e['cnpj']) ?></td>
          <td><?= htmlspecialchars($e['uf']) ?> - <?= htmlspecialchars($e['regime']) ?></td>
          <td><?= htmlspecialchars($e['responsavel'] ?: '-') ?></td>
          <td>
            <a href="empresas.php?editar=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <!-- AJUSTE: sem confirm() / alert(), agora abre o modal -->
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluir" data-id="<?= $e['id'] ?>" data-nome="<?= htmlspecialchars($e['razao_social']) ?>">Excluir</button>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL EXCLUIR - substitui o confirm() / alert() -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle text-danger"></i> Desativar empresa?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Tem certeza que deseja desativar <strong id="nomeEmpresaExcluir"></strong>?</p>
        <small class="text-muted">Ela vai sumir da lista, mas o histórico de validações será mantido.</small>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluir" class="btn btn-danger">Sim, desativar</a>
      </div>
    </div>
  </div>
</div>

<script>
// Máscara de CNPJ simples
const campoCnpj = document.getElementById('cnpj');
if (campoCnpj) {
  campoCnpj.addEventListener('input', function(e){
    let v = e.target.value.replace(/\D/g,'').slice(0,14);
    if(v.length > 12) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5');
    else if(v.length > 8) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4}).*/, '$1.$2.$3/$4');
    else if(v.length > 5) v = v.replace(/^(\d{2})(\d{3})(\d{3}).*/, '$1.$2.$3');
    else if(v.length > 2) v = v.replace(/^(\d{2})(\d{3}).*/, '$1.$2');
    e.target.value = v;
  });
  // Formata o valor que já veio do banco ao carregar
  campoCnpj.dispatchEvent(new Event('input'));
}

// Modal de excluir - preenche o nome e o link com o ID da linha clicada
const modalExcluir = document.getElementById('modalExcluir');
if (modalExcluir) {
  modalExcluir.addEventListener('show.bs.modal', function(event) {
    const botao = event.relatedTarget;
    const id = botao.getAttribute('data-id');
    const nome = botao.getAttribute('data-nome');
    document.getElementById('nomeEmpresaExcluir').textContent = nome;
    document.getElementById('btnConfirmarExcluir').href = 'empresas.php?excluir=' + id;
  });
}
</script>

<?php require_once '../includes/footer.php'; ?>