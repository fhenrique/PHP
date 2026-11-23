<?php
//tecnicos.php - Cadastro de Técnicos
require_once __DIR__ . '/../config.php';
exigirLogin();

// --- MODO: visualizar | incluir | editar ---
$modo = $_GET['modo'] ?? 'visualizar';
if (!in_array($modo, ['visualizar','incluir','editar'])) $modo = 'visualizar';
if ($modo === 'incluir') unset($_GET['editar']);

// --- Salvar / Editar Técnico ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_tecnico'])) {
  $id = $_POST['id'] ?? '';
  $codigo = $_POST['codigo'] ?? '';
  $nome = trim($_POST['nome'] ?? '');
  $cpf = trim($_POST['cpf'] ?? '');
  $rg = trim($_POST['rg'] ?? '');
  $endereco = trim($_POST['endereco'] ?? '');
  $bairro = trim($_POST['bairro'] ?? '');
  $cidade = trim($_POST['cidade'] ?? '');
  $cep = trim($_POST['cep'] ?? '');
  $fone = trim($_POST['fone'] ?? '');
  $celular = trim($_POST['celular'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $funcao = trim($_POST['funcao'] ?? '');
  $data_admissao = $_POST['data_admissao'] ?: null;
  $ativo = isset($_POST['ativo']) ? 1 : 0;
  $obs = trim($_POST['obs'] ?? '');

  if ($nome && $codigo) {
    if ($id) {
      $stmt = $pdo->prepare("UPDATE tecnicos SET codigo=?, nome=?, cpf=?, rg=?, endereco=?, bairro=?, cidade=?, cep=?, fone=?, celular=?, email=?, funcao=?, data_admissao=?, ativo=?, obs=? WHERE id=?");
      $stmt->execute([$codigo, $nome, $cpf, $rg, $endereco, $bairro, $cidade, $cep, $fone, $celular, $email, $funcao, $data_admissao, $ativo, $obs, $id]);
    } else {
      $stmt = $pdo->prepare("INSERT INTO tecnicos (codigo, nome, cpf, rg, endereco, bairro, cidade, cep, fone, celular, email, funcao, data_admissao, ativo, obs) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
      $stmt->execute([$codigo, $nome, $cpf, $rg, $endereco, $bairro, $cidade, $cep, $fone, $celular, $email, $funcao, $data_admissao, $ativo, $obs]);
      $id = $pdo->lastInsertId();
    }
    header("Location: tecnicos.php?editar=$id&salvo=1"); exit;
  }
}

// --- Excluir ---
if (isset($_GET['excluir'])) {
  $pdo->prepare("DELETE FROM tecnicos WHERE id=?")->execute([$_GET['excluir']]);
  header("Location: tecnicos.php?excluido=1"); exit;
}

// --- Editar: carrega dados ---
$edit = null;
if (isset($_GET['editar'])) {
  $stmt = $pdo->prepare("SELECT * FROM tecnicos WHERE id=?");
  $stmt->execute([$_GET['editar']]);
  $edit = $stmt->fetch();
}

// --- Pesquisa Lista Direita ---
$busca_codigo = $_GET['busca_codigo'] ?? '';
$busca_nome = $_GET['busca_nome'] ?? '';
$busca_cpf = $_GET['busca_cpf'] ?? '';
$busca_cidade = $_GET['busca_cidade'] ?? '';
$exibir = $_GET['exibir'] ?? 'todos';
$ordenacao = $_GET['ordenacao'] ?? 'codigo';

$where = " WHERE 1=1 ";
$params = [];
if ($busca_codigo) { $where .= " AND codigo LIKE ? "; $params[] = "%$busca_codigo%"; }
if ($busca_nome) { $where .= " AND nome LIKE ? "; $params[] = "%$busca_nome%"; }
if ($busca_cpf) { $where .= " AND cpf LIKE ? "; $params[] = "%$busca_cpf%"; }
if ($busca_cidade) { $where .= " AND cidade LIKE ? "; $params[] = "%$busca_cidade%"; }
if ($exibir == 'ativos') { $where .= " AND ativo=1 "; }
if ($exibir == 'inativos') { $where .= " AND ativo=0 "; }

$orderBy = $ordenacao == 'nome' ? 'nome ASC' : 'codigo ASC';
$stmt = $pdo->prepare("SELECT * FROM tecnicos $where ORDER BY $orderBy");
$stmt->execute($params);
$tecnicos = $stmt->fetchAll();

$prox_codigo = $pdo->query("SELECT IFNULL(MAX(codigo),0)+1 as p FROM tecnicos")->fetch()['p'];
$pagina_ativa = 'tecnicos';

$editando = in_array($modo, ['incluir','editar']);
$readonly = $editando ? '' : 'readonly';
$disabled = $editando ? '' : 'disabled';
$temSelecionado = !empty($edit);
$excluirDisabled = $temSelecionado ? '' : 'disabled';

require_once __DIR__ . '/../sidebar.php';
?>

<style>
  /* ===== AJUSTE DEFINITIVO: SÓ GRID TEM SCROLL, PÁGINA NÃO ===== */
  html, body{ height:100%; overflow:hidden; }
  body{ overflow:hidden; }
  .pagina-tecnicos{ height:calc(100vh - 20px); overflow:hidden; display:flex; flex-direction:column; }
  .pagina-tecnicos > .row{ flex:1; min-height:0; }
  .pagina-tecnicos > .row > div{ display:flex; flex-direction:column; min-height:0; height:100%; }
  .card-dados{ padding:10px 12px !important; flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; }
  .card-lista{ flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; }
  #formTecnico{ flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; }
  /* ÚNICO scroll da página: lista da direita */
  .lista-tecnicos-wrap{ flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; }
  .lista-tecnicos-wrap thead th{ position:sticky; top:0; z-index:1; }
  .label-old{ font-size:11px; font-weight:600; text-align:right; padding-right:4px !important; line-height:1.2; }
  .btn-old{ border:1px solid #999; border-radius:4px; padding:4px 10px; font-size:12px; font-weight:600; background:#EFEFEF; text-decoration:none; color:#000; display:inline-block; }
  .btn-old:disabled{ opacity:.5; cursor:not-allowed; }
  .btn-verde{ background:#4ADE80; color:#000; }
  .btn-vermelho{ background:#F87171; color:#fff; }
  .btn-vermelho:disabled{ background:#FCA5A5; color:#fff; opacity:.6; cursor:not-allowed; }
  .btn-laranja{ background:#F59E0B; color:#000; border-color:#D97706; }
  .btn-old.btn-desabilitado{ opacity:.5; pointer-events:none; }
  input[readonly], textarea[readonly]{ background:#F3F4F6; }
  .form-dados .form-control{ font-size:12px; padding:2px 6px; height:26px; }
  .form-dados .row{ --bs-gutter-x:6px; --bs-gutter-y:2px; }
  .form-dados .label-old{ padding-top:4px; }
  .box-edicao{ padding:6px !important; flex-shrink:0; }
  .box-edicao small{ font-size:11px; }
  .linha-selecionada, .linha-selecionada td{ background:#BFDBFE !important; --bs-table-bg:#BFDBFE !important; }
  .linha-inativo, .linha-inativo td{ background:#F3F4F6 !important; --bs-table-bg:#F3F4F6 !important; color:#6B7280; }
  .modal, .modal-body{ overflow-y:auto; }
</style>

<div class="pagina-tecnicos">
<div class="row g-2">
  <div class="col-lg-7">
    <div class="card card-dados">
      <div class="d-flex justify-content-between align-items-center flex-shrink-0">
        <h6 class="fw-bold mb-0">Dados do Técnico</h6>
        <small>
          <?php if($modo==='incluir'): ?><span class="badge bg-success">INCLUINDO...</span>
          <?php elseif($modo==='editar'): ?><span class="badge bg-warning text-dark">EDITANDO...</span>
          <?php else: ?><span class="badge bg-secondary">VISUALIZANDO</span><?php endif; ?>
          Registro: <input type="text" size="5" value="<?= $edit['id'] ?? '' ?>" readonly class="form-control form-control-sm d-inline-block" style="width:60px; height:26px; font-size:12px;">
        </small>
      </div>
      <?php if(isset($_GET['salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Técnico salvo com sucesso!</div><?php endif; ?>
      <?php if(isset($_GET['excluido'])): ?><div class="alert alert-warning py-1 mt-2 mb-0 flex-shrink-0">Técnico excluído.</div><?php endif; ?>

      <form method="POST" id="formTecnico" class="form-dados">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        <div class="row g-1 align-items-center mb-1 mt-2 flex-shrink-0">
          <div class="col-4 label-old">Código:</div><div class="col-8"><input type="number" name="codigo" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit['codigo'] ?? $prox_codigo ?>" required style="max-width:110px;"></div>
          <div class="col-4 label-old">Nome:</div><div class="col-8"><input name="nome" id="campoNome" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['nome'] ?? '') ?>" required></div>
          <div class="col-4 label-old">CPF:</div><div class="col-8"><input name="cpf" id="campoCpf" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['cpf'] ?? '') ?>" placeholder="000.000.000-00"></div>
          <div class="col-4 label-old">RG:</div><div class="col-8"><input name="rg" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['rg'] ?? '') ?>"></div>
          <div class="col-4 label-old">End.:</div><div class="col-8"><input name="endereco" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['endereco'] ?? '') ?>"></div>
          <div class="col-4 label-old">Bairro:</div><div class="col-8"><input name="bairro" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['bairro'] ?? '') ?>"></div>
          <div class="col-4 label-old">Cidade:</div><div class="col-8"><input name="cidade" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['cidade'] ?? '') ?>"></div>
          <div class="col-4 label-old">CEP:</div><div class="col-8"><input name="cep" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['cep'] ?? '') ?>" style="max-width:140px;"></div>
          <div class="col-4 label-old">Fone:</div><div class="col-8"><input name="fone" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['fone'] ?? '') ?>"></div>
          <div class="col-4 label-old">Celular:</div><div class="col-8"><input name="celular" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['celular'] ?? '') ?>"></div>
          <div class="col-4 label-old">E-Mail:</div><div class="col-8"><input name="email" type="email" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['email'] ?? '') ?>"></div>
          <div class="col-4 label-old">Função:</div><div class="col-8"><input name="funcao" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['funcao'] ?? '') ?>" placeholder="Ex: TÉCNICO, INSTALADOR, AUXILIAR"></div>
          <div class="col-4 label-old">Data Admissão:</div>
          <div class="col-8 d-flex gap-2 align-items-center">
            <input type="date" name="data_admissao" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit['data_admissao'] ?? '' ?>" style="max-width:150px;">
            <label class="small text-nowrap"><input type="checkbox" name="ativo" <?= $disabled ?> <?= ($edit ? !empty($edit['ativo']) : true) ? 'checked' : '' ?>> Ativo</label>
          </div>
          <div class="col-4 label-old">Obs.:</div><div class="col-8"><textarea name="obs" class="form-control form-control-sm" rows="2" <?= $readonly ?> style="height:auto;"><?= htmlspecialchars($edit['obs'] ?? '') ?></textarea></div>
        </div>

        <div class="row g-2 mt-2 flex-shrink-0">
          <div class="col-12 border box-edicao">
            <small>Edição</small><br>
            <?php if($modo==='incluir'): ?>
              <a href="tecnicos.php" class="btn-old btn-laranja">Cancelar</a>
            <?php else: ?>
              <a href="tecnicos.php?modo=incluir" class="btn-old btn-verde">Incluir</a>
            <?php endif; ?>
            <?php if($modo==='editar' && $edit): ?>
              <a href="tecnicos.php?editar=<?= $edit['id'] ?>" class="btn-old btn-laranja">Cancelar</a>
            <?php elseif($edit): ?>
              <a href="tecnicos.php?editar=<?= $edit['id'] ?>&modo=editar" class="btn-old">Editar</a>
            <?php else: ?>
              <a href="#" class="btn-old btn-desabilitado" onclick="mostrarAviso('Selecione um técnico na lista da direita para editar.'); return false;">Editar</a>
            <?php endif; ?>
            <button type="submit" name="salvar_tecnico" class="btn-old" <?= $editando ? '' : 'disabled' ?>>Salvar</button>
            <button type="button" class="btn-old btn-vermelho" <?= $excluirDisabled ?> onclick="excluirTecnico(<?= $edit['id'] ?? 0 ?>)">Excluir</button>
            <a href="dashboard.php" class="btn-old ms-2">Fechar</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- LADO DIREITO -->
  <div class="col-lg-5">
    <div class="card p-3 card-lista">
      <h6 class="fw-bold flex-shrink-0">Lista de Técnicos <?= $exibir=='ativos' ? '<span class="badge bg-success">ATIVOS</span>' : ($exibir=='inativos' ? '<span class="badge bg-secondary">INATIVOS</span>' : '') ?></h6>
      <div class="lista-tecnicos-wrap">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light sticky-top"><tr><th>Código</th><th>Nome</th><th>Fone</th><th>Cidade</th><th>Ativo</th></tr></thead>
          <tbody>
          <?php foreach($tecnicos as $t): ?>
            <tr class="<?= empty($t['ativo']) ? 'linha-inativo' : '' ?> <?= ($edit && $edit['id']==$t['id']) ? 'linha-selecionada' : '' ?>" onclick="location.href='?editar=<?= $t['id'] ?>&exibir=<?= $exibir ?>&ordenacao=<?= $ordenacao ?>'" style="cursor:pointer;">
              <td><?= $t['codigo'] ?></td>
              <td><?= htmlspecialchars($t['nome']) ?></td>
              <td><?= htmlspecialchars($t['fone']) ?></td>
              <td><?= htmlspecialchars($t['cidade']) ?></td>
              <td><?= !empty($t['ativo']) ? 'Sim' : 'Não' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$tecnicos): ?><tr><td colspan="5" class="text-center text-muted py-3">Nenhum técnico encontrado.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <hr class="flex-shrink-0">
      <form method="GET" id="formFiltro" class="row g-1 flex-shrink-0">
        <div class="col-12"><small>Pesquisa</small></div>
        <div class="col-8"><label class="small">Código:</label><input name="busca_codigo" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_codigo) ?>"></div>
        <div class="col-4 d-flex align-items-end justify-content-end"><button class="btn btn-sm btn-light border">🔍</button></div>
        <div class="col-12"><label class="small">CPF:</label><input name="busca_cpf" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_cpf) ?>"></div>
        <div class="col-12"><label class="small">Nome:</label><input name="busca_nome" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_nome) ?>"></div>
        <div class="col-12"><label class="small">Cidade:</label><input name="busca_cidade" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_cidade) ?>"></div>
        <div class="col-12"><button class="btn btn-sol btn-sm w-100 mt-1">🔍 Pesquisar</button></div>

        <div class="col-6 border p-2 mt-2">
          <small>Ordenação</small><br>
          <label class="small"><input type="radio" name="ordenacao" value="codigo" <?= $ordenacao=='codigo'?'checked':'' ?> onchange="this.form.submit()"> Código</label><br>
          <label class="small"><input type="radio" name="ordenacao" value="nome" <?= $ordenacao=='nome'?'checked':'' ?> onchange="this.form.submit()"> Nome</label>
        </div>
        <div class="col-6 border p-2 mt-2">
          <small>Exibição</small><br>
          <label class="small"><input type="radio" name="exibir" value="todos" <?= $exibir=='todos'?'checked':'' ?> onchange="this.form.submit()"> Todos</label><br>
          <label class="small"><input type="radio" name="exibir" value="ativos" <?= $exibir=='ativos'?'checked':'' ?> onchange="this.form.submit()"> Ativos</label><br>
          <label class="small"><input type="radio" name="exibir" value="inativos" <?= $exibir=='inativos'?'checked':'' ?> onchange="this.form.submit()"> Inativos</label>
        </div>
      </form>
    </div>
  </div>
</div>
</div>

<!-- Modal Aviso -->
<div class="modal fade" id="modalAviso" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="mb-0">Aviso</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p id="textoAviso" class="mb-0"></p></div>
  <div class="modal-footer"><button type="button" class="btn btn-sol" data-bs-dismiss="modal">OK</button></div>
</div></div></div>

<!-- Modal Confirmação -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="mb-0">Confirmação</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p id="textoConfirmacao" class="mb-0"></p></div>
  <div class="modal-footer">
    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
    <button type="button" class="btn btn-sol" id="btnConfirmarAcao">Sim</button>
  </div>
</div></div></div>

<script>
let urlConfirmacao = '';
function excluirTecnico(id){
  if(!id){ mostrarAviso('Selecione um técnico na lista da direita para excluir.'); return; }
  pedirConfirmacao('Excluir este técnico?', '?excluir=' + id);
}
function mostrarAviso(mensagem){
  document.getElementById('textoAviso').textContent = mensagem;
  new bootstrap.Modal(document.getElementById('modalAviso')).show();
}
function pedirConfirmacao(mensagem, url){
  document.getElementById('textoConfirmacao').textContent = mensagem;
  urlConfirmacao = url;
  new bootstrap.Modal(document.getElementById('modalConfirmacao')).show();
}
document.getElementById('btnConfirmarAcao').addEventListener('click', function(){
  if(urlConfirmacao) location.href = urlConfirmacao;
});
<?php if($editando): ?>
document.addEventListener('DOMContentLoaded', function(){
  var campo = document.getElementById('campoNome');
  if(campo){ campo.focus(); }
});
<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once __DIR__ . '/../footer.php'; ?>