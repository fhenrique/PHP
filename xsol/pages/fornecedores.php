<?php
//fornecedores.php - Cadastro de Fornecedores
require_once __DIR__ . '/../config.php';
exigirLogin();

// --- MODO ---
$modo = $_GET['modo'] ?? 'visualizar';
if (!in_array($modo, ['visualizar','incluir','editar'])) $modo = 'visualizar';
if ($modo === 'incluir') unset($_GET['editar']);

// --- Salvar / Editar Fornecedor ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_fornecedor'])) {
  $id = $_POST['id'] ?? '';
  $codigo = $_POST['codigo'] ?? '';
  $nome = trim($_POST['nome'] ?? '');
  $endereco = trim($_POST['endereco'] ?? '');
  $bairro = trim($_POST['bairro'] ?? '');
  $cidade = trim($_POST['cidade'] ?? '');
  $cep = trim($_POST['cep'] ?? '');
  $cnpj = trim($_POST['cnpj'] ?? '');
  $fone = trim($_POST['fone'] ?? '');
  $contato = trim($_POST['contato'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $tipo_material = trim($_POST['tipo_material'] ?? '');

  if ($nome && $codigo) {
    if ($id) {
      $stmt = $pdo->prepare("UPDATE fornecedores SET codigo=?, nome=?, endereco=?, bairro=?, cidade=?, cep=?, cnpj=?, fone=?, contato=?, email=?, tipo_material=? WHERE id=?");
      $stmt->execute([$codigo, $nome, $endereco, $bairro, $cidade, $cep, $cnpj, $fone, $contato, $email, $tipo_material, $id]);
    } else {
      $stmt = $pdo->prepare("INSERT INTO fornecedores (codigo, nome, endereco, bairro, cidade, cep, cnpj, fone, contato, email, tipo_material) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
      $stmt->execute([$codigo, $nome, $endereco, $bairro, $cidade, $cep, $cnpj, $fone, $contato, $email, $tipo_material]);
      $id = $pdo->lastInsertId();
    }
    header("Location: fornecedores.php?editar=$id&salvo=1"); exit;
  }
}

// --- Excluir Fornecedor (apaga também os equipamentos dele) ---
if (isset($_GET['excluir'])) {
  $pdo->prepare("DELETE FROM fornecedor_equipamentos WHERE fornecedor_id=?")->execute([$_GET['excluir']]);
  $pdo->prepare("DELETE FROM fornecedores WHERE id=?")->execute([$_GET['excluir']]);
  header("Location: fornecedores.php?excluido=1"); exit;
}

// --- Adicionar Equipamento na grade de baixo ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_equip_forn'])) {
  $fid = $_POST['fornecedor_id_equip'] ?? '';
  $codigoEq = $_POST['codigo_equip'] ?? '';
  $descEq = trim($_POST['descricao_equip'] ?? '');
  $valorEq = str_replace(',', '.', $_POST['valor_equip'] ?? '0');
  if ($fid && $descEq) {
    $pdo->prepare("INSERT INTO fornecedor_equipamentos (fornecedor_id, codigo, descricao, valor) VALUES (?,?,?,?)")->execute([$fid, $codigoEq, $descEq, $valorEq]);
    header("Location: fornecedores.php?editar=$fid&equip_salvo=1"); exit;
  }
}
if (isset($_GET['excluir_equip'])) {
  $fid = $_GET['fornecedor_id'] ?? '';
  $pdo->prepare("DELETE FROM fornecedor_equipamentos WHERE id=?")->execute([$_GET['excluir_equip']]);
  header("Location: fornecedores.php?editar=$fid&equip_excluido=1"); exit;
}

// --- Editar: carrega dados ---
$edit = null;
if (isset($_GET['editar'])) {
  $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id=?");
  $stmt->execute([$_GET['editar']]);
  $edit = $stmt->fetch();
}

// --- Equipamentos do fornecedor selecionado ---
$equipamentos = [];
if ($edit) {
  $stmt = $pdo->prepare("SELECT * FROM fornecedor_equipamentos WHERE fornecedor_id=? ORDER BY codigo ASC");
  $stmt->execute([$edit['id']]);
  $equipamentos = $stmt->fetchAll();
}

// --- Pesquisa Lista Direita ---
$busca_codigo = $_GET['busca_codigo'] ?? '';
$busca_nome = $_GET['busca_nome'] ?? '';
$busca_cidade = $_GET['busca_cidade'] ?? '';
$ordenacao = $_GET['ordenacao'] ?? 'codigo';

$where = " WHERE 1=1 ";
$params = [];
if ($busca_codigo) { $where .= " AND codigo LIKE ? "; $params[] = "%$busca_codigo%"; }
if ($busca_nome) { $where .= " AND nome LIKE ? "; $params[] = "%$busca_nome%"; }
if ($busca_cidade) { $where .= " AND cidade LIKE ? "; $params[] = "%$busca_cidade%"; }

$orderBy = $ordenacao == 'nome' ? 'nome ASC' : 'codigo ASC';
$stmt = $pdo->prepare("SELECT * FROM fornecedores $where ORDER BY $orderBy");
$stmt->execute($params);
$fornecedores = $stmt->fetchAll();

$prox_codigo = $pdo->query("SELECT IFNULL(MAX(codigo),0)+1 as p FROM fornecedores")->fetch()['p'];
$prox_codigo_equip = $pdo->query("SELECT IFNULL(MAX(codigo),0)+1 as p FROM fornecedor_equipamentos")->fetch()['p'];
$pagina_ativa = 'fornecedores';

$editando = in_array($modo, ['incluir','editar']);
$readonly = $editando ? '' : 'readonly';
$disabled = $editando ? '' : 'disabled';
$temSelecionado = !empty($edit);

require_once __DIR__ . '/../sidebar.php';
?>

<style>
  .card-dados{ padding:10px 12px !important; }
  .label-old{ font-size:11px; font-weight:600; text-align:right; padding-right:4px !important; line-height:1.2; }
  .grid-old{ background:#808080; min-height:70px; }
  .grid-old table{ background:#fff; width:100%; }
  .grid-old th{ background:#E5E7EB; font-size:11px; border:1px solid #ccc; padding:2px 4px; }
  .grid-old td{ font-size:11px; border:1px solid #ddd; padding:2px 4px; }
  .btn-old{ border:1px solid #999; border-radius:4px; padding:4px 10px; font-size:12px; font-weight:600; background:#EFEFEF; text-decoration:none; color:#000; display:inline-block; }
  .btn-old:disabled{ opacity:.5; cursor:not-allowed; }
  .btn-verde{ background:#4ADE80; color:#000; }
  .btn-vermelho{ background:#F87171; color:#fff; }
  .btn-laranja{ background:#F59E0B; color:#000; border-color:#D97706; }
  .btn-old.btn-desabilitado{ opacity:.5; pointer-events:none; }
  input[readonly], textarea[readonly]{ background:#F3F4F6; }
  .form-dados .form-control{ font-size:12px; padding:2px 6px; height:26px; }
  .form-dados .row{ --bs-gutter-x:6px; --bs-gutter-y:2px; }
  .form-dados .label-old{ padding-top:4px; }
  .titulo-grade{ font-size:11px; margin:6px 0 2px 0; }
  .box-edicao{ padding:6px !important; }
  .box-edicao small{ font-size:11px; }
  .linha-selecionada, .linha-selecionada td{ background:#BFDBFE !important; --bs-table-bg:#BFDBFE !important; }
</style>

<div class="row g-2">
  <div class="col-lg-7">
    <div class="card card-dados">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Fornecedor</h6>
        <small>
          <?php if($modo==='incluir'): ?><span class="badge bg-success">INCLUINDO...</span>
          <?php elseif($modo==='editar'): ?><span class="badge bg-warning text-dark">EDITANDO...</span>
          <?php else: ?><span class="badge bg-secondary">VISUALIZANDO</span><?php endif; ?>
          Registro: <input type="text" size="5" value="<?= $edit['id'] ?? '' ?>" readonly class="form-control form-control-sm d-inline-block" style="width:60px; height:26px; font-size:12px;">
        </small>
      </div>
      <?php if(isset($_GET['salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0">Fornecedor salvo com sucesso!</div><?php endif; ?>
      <?php if(isset($_GET['excluido'])): ?><div class="alert alert-warning py-1 mt-2 mb-0">Fornecedor excluído.</div><?php endif; ?>
      <?php if(isset($_GET['equip_salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0">Equipamento adicionado!</div><?php endif; ?>
      <?php if(isset($_GET['equip_excluido'])): ?><div class="alert alert-warning py-1 mt-2 mb-0">Equipamento removido.</div><?php endif; ?>

      <form method="POST" id="formFornecedor" class="form-dados">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        <div class="row g-1 align-items-center mb-1 mt-2">
          <div class="col-3 label-old">Código:</div><div class="col-9"><input type="number" name="codigo" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit['codigo'] ?? $prox_codigo ?>" required style="max-width:110px;"></div>
          <div class="col-3 label-old">Nome:</div><div class="col-9"><input name="nome" id="campoNome" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['nome'] ?? '') ?>" required></div>
          <div class="col-3 label-old">End.:</div><div class="col-9"><input name="endereco" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['endereco'] ?? '') ?>"></div>
          <div class="col-3 label-old">Bairro:</div><div class="col-9"><input name="bairro" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['bairro'] ?? '') ?>"></div>
          <div class="col-3 label-old">Cidade:</div><div class="col-9"><input name="cidade" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['cidade'] ?? '') ?>"></div>
          <div class="col-3 label-old">CEP:</div>
          <div class="col-9 d-flex gap-2 align-items-center">
            <input name="cep" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['cep'] ?? '') ?>" style="max-width:140px;">
            <span class="label-old" style="text-align:left;">CNPJ:</span>
            <input name="cnpj" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['cnpj'] ?? '') ?>">
          </div>
          <div class="col-3 label-old">Fone:</div><div class="col-9"><input name="fone" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['fone'] ?? '') ?>"></div>
          <div class="col-3 label-old">Contato:</div><div class="col-9"><input name="contato" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit['contato'] ?? '') ?>"></div>
          <div class="col-3 label-old">Email:</div><div class="col-9"><input name="email" type="email" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit['email'] ?? '') ?>"></div>
          <div class="col-3 label-old">Tipo Material:</div><div class="col-9"><textarea name="tipo_material" class="form-control form-control-sm" rows="3" <?= $readonly ?> style="height:auto;"><?= htmlspecialchars($edit['tipo_material'] ?? '') ?></textarea></div>
        </div>

        <div class="row g-2 mt-2">
          <div class="col-12 border box-edicao">
            <small>Edição</small><br>
            <?php if($modo==='incluir'): ?>
              <a href="fornecedores.php" class="btn-old btn-laranja">Cancelar</a>
            <?php else: ?>
              <a href="fornecedores.php?modo=incluir" class="btn-old btn-verde">Inserir</a>
            <?php endif; ?>
            <?php if($modo==='editar' && $edit): ?>
              <a href="fornecedores.php?editar=<?= $edit['id'] ?>" class="btn-old btn-laranja">Cancelar</a>
            <?php elseif($edit): ?>
              <a href="fornecedores.php?editar=<?= $edit['id'] ?>&modo=editar" class="btn-old">Editar</a>
            <?php else: ?>
              <a href="#" class="btn-old btn-desabilitado" onclick="mostrarAviso('Selecione um fornecedor na lista da direita para editar.'); return false;">Editar</a>
            <?php endif; ?>
            <button type="submit" name="salvar_fornecedor" class="btn-old" <?= $editando ? '' : 'disabled' ?>>Salvar</button>
            <button type="button" class="btn-old btn-vermelho" <?= $temSelecionado ? '' : 'disabled' ?> onclick="excluirFornecedor(<?= $edit['id'] ?? 0 ?>)">Excluir</button>
            <a href="dashboard.php" class="btn-old ms-2">Fechar</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- LADO DIREITO - LISTA + EQUIPAMENTOS -->
  <div class="col-lg-5">
    <div class="card p-3">
      <h6 class="fw-bold">Listagem de Fornecedores</h6>
      <div class="table-responsive" style="max-height:300px; overflow:auto;">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light sticky-top"><tr><th>Código</th><th>Nome Fornecedor</th><th>Endereço</th><th>Bairro</th><th>Cidade</th></tr></thead>
          <tbody>
          <?php foreach($fornecedores as $f): ?>
            <tr class="<?= ($edit && $edit['id']==$f['id']) ? 'linha-selecionada' : '' ?>" onclick="location.href='?editar=<?= $f['id'] ?>&ordenacao=<?= $ordenacao ?>'" style="cursor:pointer;">
              <td><?= $f['codigo'] ?></td>
              <td class="text-nowrap"><?= htmlspecialchars($f['nome']) ?></td>
              <td class="text-nowrap"><?= htmlspecialchars($f['endereco']) ?></td>
              <td><?= htmlspecialchars($f['bairro']) ?></td>
              <td><?= htmlspecialchars($f['cidade']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$fornecedores): ?><tr><td colspan="5" class="text-center text-muted py-3">Nenhum fornecedor encontrado.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <hr>
      <form method="GET" id="formFiltro" class="row g-1">
        <div class="col-12"><small>Pesquisa</small></div>
        <div class="col-4"><label class="small">Código:</label><input name="busca_codigo" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_codigo) ?>"></div>
        <div class="col-8"><label class="small">Nome:</label><input name="busca_nome" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_nome) ?>"></div>
        <div class="col-12"><label class="small">Cidade:</label><input name="busca_cidade" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_cidade) ?>"></div>
        <div class="col-12 d-flex gap-1 mt-1">
          <button class="btn btn-sol btn-sm flex-fill">🔍 Pesquisar</button>
          <a href="fornecedores.php" class="btn btn-sm btn-light border">Atualizar</a>
        </div>
        <div class="col-12 border p-2 mt-2">
          <small>Ordenação</small><br>
          <label class="small"><input type="radio" name="ordenacao" value="codigo" <?= $ordenacao=='codigo'?'checked':'' ?> onchange="this.form.submit()"> Ordenar por código</label>
          <label class="small ms-3"><input type="radio" name="ordenacao" value="nome" <?= $ordenacao=='nome'?'checked':'' ?> onchange="this.form.submit()"> Ordenar por nome</label>
        </div>
      </form>

      <hr>
      <h6 class="fw-bold" style="font-size:12px;">Equipamentos</h6>
      <?php if($edit): ?>
      <form method="POST" class="row g-1 align-items-end">
        <input type="hidden" name="fornecedor_id_equip" value="<?= $edit['id'] ?>">
        <div class="col-2"><label class="small">Código:</label><input name="codigo_equip" type="number" class="form-control form-control-sm" value="<?= $prox_codigo_equip ?>"></div>
        <div class="col-6"><label class="small">Descrição:</label><input name="descricao_equip" class="form-control form-control-sm text-uppercase" placeholder="Ex: BOMBA DE RECIRCULAÇÃO" required></div>
        <div class="col-2"><label class="small">Valor:</label><input name="valor_equip" class="form-control form-control-sm" placeholder="0,00"></div>
        <div class="col-2"><button type="submit" name="salvar_equip_forn" class="btn btn-sm btn-sol w-100">＋</button></div>
      </form>
      <?php else: ?>
      <p class="small text-muted mb-1">Selecione um fornecedor para ver / adicionar equipamentos.</p>
      <?php endif; ?>
      <div class="grid-old border mt-2">
        <table><thead><tr><th>Código</th><th>Descrição</th><th style="text-align:right;">Valor</th><th style="width:40px;"></th></tr></thead>
        <tbody>
          <?php foreach($equipamentos as $eq): ?>
          <tr>
            <td><?= $eq['codigo'] ?></td>
            <td><?= htmlspecialchars($eq['descricao']) ?></td>
            <td style="text-align:right;"><?= number_format($eq['valor'], 2, ',', '.') ?></td>
            <td><a href="?editar=<?= $edit['id'] ?>&fornecedor_id=<?= $edit['id'] ?>&excluir_equip=<?= $eq['id'] ?>" onclick="return confirm('Excluir este equipamento?')" class="text-danger text-decoration-none">🗑️</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if(!$equipamentos): ?><tr><td colspan="4" class="text-center text-muted">Nenhum equipamento cadastrado.</td></tr><?php endif; ?>
        </tbody></table>
      </div>
    </div>
  </div>
</div>

<!-- Modais -->
<div class="modal fade" id="modalAviso" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="mb-0">Aviso</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p id="textoAviso" class="mb-0"></p></div>
  <div class="modal-footer"><button type="button" class="btn btn-sol" data-bs-dismiss="modal">OK</button></div>
</div></div></div>

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
function excluirFornecedor(id){
  if(!id){ mostrarAviso('Selecione um fornecedor na lista da direita para excluir.'); return; }
  pedirConfirmacao('Excluir este fornecedor e todos os equipamentos dele?', '?excluir=' + id);
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