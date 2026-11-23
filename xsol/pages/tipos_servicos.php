<?php
//tipos_servicos.php - Cadastro de Tipos de Serviços
require_once __DIR__ . '/../config.php';
exigirLogin();

// --- MODO: visualizar | incluir | editar ---
$modo = $_GET['modo'] ?? 'visualizar';
if (!in_array($modo, ['visualizar','incluir','editar'])) $modo = 'visualizar';
if ($modo === 'incluir') unset($_GET['editar']);

// --- Salvar / Editar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_servico'])) {
  $id = $_POST['id'] ?? '';
  $codigo = $_POST['codigo'] ?? '';
  $descricao = trim($_POST['descricao'] ?? '');
  $auto_agend = isset($_POST['auto_agend']) ? 'S' : 'N';

  if ($descricao && $codigo) {
    if ($id) {
      $stmt = $pdo->prepare("UPDATE tipos_servicos SET codigo=?, descricao=?, auto_agend=? WHERE id=?");
      $stmt->execute([$codigo, $descricao, $auto_agend, $id]);
    } else {
      $stmt = $pdo->prepare("INSERT INTO tipos_servicos (codigo, descricao, auto_agend) VALUES (?,?,?)");
      $stmt->execute([$codigo, $descricao, $auto_agend]);
      $id = $pdo->lastInsertId();
    }
    header("Location: tipos_servicos.php?editar=$id&salvo=1"); exit;
  }
}

// --- Excluir ---
if (isset($_GET['excluir'])) {
  $pdo->prepare("DELETE FROM tipos_servicos WHERE id=?")->execute([$_GET['excluir']]);
  header("Location: tipos_servicos.php?excluido=1"); exit;
}

// --- Editar: carrega dados ---
$edit = null;
if (isset($_GET['editar'])) {
  $stmt = $pdo->prepare("SELECT * FROM tipos_servicos WHERE id=?");
  $stmt->execute([$_GET['editar']]);
  $edit = $stmt->fetch();
}

// --- Pesquisa Lista Direita ---
$busca_codigo = $_GET['busca_codigo'] ?? '';
$busca_descricao = $_GET['busca_descricao'] ?? '';
$ordenacao = $_GET['ordenacao'] ?? 'codigo';

$where = " WHERE 1=1 ";
$params = [];
if ($busca_codigo) { $where .= " AND codigo LIKE ? "; $params[] = "%$busca_codigo%"; }
if ($busca_descricao) { $where .= " AND descricao LIKE ? "; $params[] = "%$busca_descricao%"; }

$orderBy = $ordenacao == 'descricao' ? 'descricao ASC' : 'codigo ASC';
$stmt = $pdo->prepare("SELECT * FROM tipos_servicos $where ORDER BY $orderBy");
$stmt->execute($params);
$servicos = $stmt->fetchAll();

$prox_codigo = $pdo->query("SELECT IFNULL(MAX(codigo),0)+1 as p FROM tipos_servicos")->fetch()['p'];
$pagina_ativa = 'tipos_servicos';

$editando = in_array($modo, ['incluir','editar']);
$readonly = $editando ? '' : 'readonly';
$disabled = $editando ? '' : 'disabled';
$temSelecionado = !empty($edit);

require_once __DIR__ . '/../sidebar.php';
?>

<style>
  .card-dados{ padding:10px 12px !important; }
  .label-old{ font-size:11px; font-weight:600; text-align:right; padding-right:4px !important; line-height:1.2; }
  .btn-old{ border:1px solid #999; border-radius:4px; padding:4px 10px; font-size:12px; font-weight:600; background:#EFEFEF; text-decoration:none; color:#000; display:inline-block; }
  .btn-old:disabled{ opacity:.5; cursor:not-allowed; }
  .btn-verde{ background:#4ADE80; color:#000; }
  .btn-vermelho{ background:#F87171; color:#fff; }
  .btn-old.btn-desabilitado{ opacity:.5; pointer-events:none; }
  input[readonly]{ background:#F3F4F6; }
  .form-dados .form-control{ font-size:12px; padding:2px 6px; height:26px; }
  .form-dados .row{ --bs-gutter-x:6px; --bs-gutter-y:2px; }
  .form-dados .label-old{ padding-top:4px; }
  .box-edicao{ padding:6px !important; }
  .box-edicao small{ font-size:11px; }
  .linha-selecionada, .linha-selecionada td{ background:#BFDBFE !important; --bs-table-bg:#BFDBFE !important; }
</style>

<div class="row g-2">
  <div class="col-lg-7">
    <div class="card card-dados">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Dados do Tipo de Serviço</h6>
        <small>
          <?php if($modo==='incluir'): ?><span class="badge bg-success">INCLUINDO...</span>
          <?php elseif($modo==='editar'): ?><span class="badge bg-warning text-dark">EDITANDO...</span>
          <?php else: ?><span class="badge bg-secondary">VISUALIZANDO</span><?php endif; ?>
          Registro: <input type="text" size="5" value="<?= $edit['id'] ?? '' ?>" readonly class="form-control form-control-sm d-inline-block" style="width:60px; height:26px; font-size:12px;">
        </small>
      </div>
      <?php if(isset($_GET['salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0">Tipo de serviço salvo com sucesso!</div><?php endif; ?>
      <?php if(isset($_GET['excluido'])): ?><div class="alert alert-warning py-1 mt-2 mb-0">Tipo de serviço excluído.</div><?php endif; ?>

      <form method="POST" id="formServico" class="form-dados">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        <div class="row g-1 align-items-center mb-1 mt-4">
          <div class="col-3 label-old">Código:</div>
          <div class="col-9"><input type="number" name="codigo" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit['codigo'] ?? $prox_codigo ?>" required style="max-width:110px;"></div>

          <div class="col-3 label-old">Tipo de Serviço:</div>
          <div class="col-9"><input name="descricao" id="campoDescricao" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['descricao'] ?? '') ?>" required></div>

          <div class="col-3"></div>
          <div class="col-9 mt-2">
            <label class="small">
              <input type="checkbox" name="auto_agend" <?= $disabled ?> <?= ($edit['auto_agend'] ?? 'N')=='S' ? 'checked' : '' ?>>
              Agendamento automático de próxima visita após 1 ano.
            </label>
          </div>
        </div>

        <div class="row g-2 mt-4">
          <div class="col-12 border box-edicao">
            <small>Edição</small><br>
            <a href="tipos_servicos.php?modo=incluir" class="btn-old btn-verde">Incluir</a>
            <?php if($edit): ?>
              <a href="tipos_servicos.php?editar=<?= $edit['id'] ?>&modo=editar" class="btn-old">Editar</a>
            <?php else: ?>
              <a href="#" class="btn-old btn-desabilitado" onclick="mostrarAviso('Selecione um tipo de serviço na lista da direita para editar.'); return false;">Editar</a>
            <?php endif; ?>
            <button type="submit" name="salvar_servico" class="btn-old" <?= $editando ? '' : 'disabled' ?>>Salvar</button>
            <button type="button" class="btn-old btn-vermelho" <?= $temSelecionado ? '' : 'disabled' ?> onclick="excluirServico(<?= $edit['id'] ?? 0 ?>)">Excluir</button>
            <a href="dashboard.php" class="btn-old ms-2">Fechar</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- LADO DIREITO - LISTA -->
  <div class="col-lg-5">
    <div class="card p-3">
      <h6 class="fw-bold">Lista de Tipos de Serviços</h6>
      <div class="table-responsive" style="max-height:380px; overflow:auto;">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light sticky-top"><tr><th>Código</th><th>Tipo de Serviço</th><th>Auto Agend.</th></tr></thead>
          <tbody>
          <?php foreach($servicos as $s): ?>
            <tr class="<?= ($edit && $edit['id']==$s['id']) ? 'linha-selecionada' : '' ?>" onclick="location.href='?editar=<?= $s['id'] ?>&ordenacao=<?= $ordenacao ?>'" style="cursor:pointer;">
              <td><?= $s['codigo'] ?></td>
              <td><?= htmlspecialchars($s['descricao']) ?></td>
              <td class="text-center"><?= htmlspecialchars($s['auto_agend']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$servicos): ?><tr><td colspan="3" class="text-center text-muted py-3">Nenhum tipo de serviço encontrado.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <hr>
      <form method="GET" id="formFiltro" class="row g-1">
        <div class="col-12"><small>Pesquisa</small></div>
        <div class="col-12"><label class="small">Código:</label><input name="busca_codigo" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_codigo) ?>"></div>
        <div class="col-12"><label class="small">Descrição:</label><input name="busca_descricao" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_descricao) ?>"></div>
        <div class="col-12 d-flex gap-1 mt-1">
          <button class="btn btn-sol btn-sm flex-fill">🔍 Pesquisar</button>
          <a href="tipos_servicos.php" class="btn btn-sm btn-light border">Atualizar</a>
        </div>

        <div class="col-12 border p-2 mt-2">
          <small>Ordenação</small><br>
          <label class="small"><input type="radio" name="ordenacao" value="codigo" <?= $ordenacao=='codigo'?'checked':'' ?> onchange="this.form.submit()"> Ordenar por código</label>
          <label class="small ms-3"><input type="radio" name="ordenacao" value="descricao" <?= $ordenacao=='descricao'?'checked':'' ?> onchange="this.form.submit()"> Ordenar por descrição</label>
        </div>
      </form>
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
function excluirServico(id){
  if(!id){ mostrarAviso('Selecione um tipo de serviço na lista da direita para excluir.'); return; }
  pedirConfirmacao('Excluir este tipo de serviço?', '?excluir=' + id);
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
  var campo = document.getElementById('campoDescricao');
  if(campo){ campo.focus(); }
});
<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once __DIR__ . '/../footer.php'; ?>