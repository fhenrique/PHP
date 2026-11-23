<?php
//equipamentos.php - 2 MODOS:
// SEM cliente_id = Cadastro de Materiais / Equipamentos + Fornecedores
// COM cliente_id = Cadastro de Equipamento DO CLIENTE (tabela equipamentos)
require_once __DIR__ . '/../config.php';
exigirLogin();

$cliente_id_vindo = $_GET['cliente_id'] ?? $_POST['cliente_id'] ?? '';
$modoCliente = !empty($cliente_id_vindo);
$clienteDoEquipamento = null;
$enderecosDoCliente = [];
if ($modoCliente) {
  $stmtCli = $pdo->prepare("SELECT * FROM clientes WHERE id=?");
  $stmtCli->execute([$cliente_id_vindo]);
  $clienteDoEquipamento = $stmtCli->fetch();
  if (!$clienteDoEquipamento) { echo "Cliente não encontrado."; exit; }
  $stmtEnd = $pdo->prepare("SELECT * FROM enderecos WHERE cliente_id=? ORDER BY codigo");
  $stmtEnd->execute([$cliente_id_vindo]);
  $enderecosDoCliente = $stmtEnd->fetchAll();
}

// --- MODO - SEM AUTO-INCLUIR ---
// Se não vier modo na URL, é VISUALIZAR. Ponto. Não força incluir nunca.
$modo = $_GET['modo'] ?? 'visualizar';
if (!in_array($modo, ['visualizar','incluir','editar'])) $modo = 'visualizar';
if ($modo === 'incluir') unset($_GET['editar']);

if ($modoCliente) {
  $stmtPx = $pdo->prepare("SELECT IFNULL(MAX(codigo),0)+1 as p FROM equipamentos WHERE cliente_id=?");
  $stmtPx->execute([$cliente_id_vindo]);
  $prox_codigo = $stmtPx->fetch()['p'];
} else {
  $prox_codigo = $pdo->query("SELECT IFNULL(MAX(codigo),0)+1 as p FROM materiais")->fetch()['p'];
}

if ($modoCliente && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_equip_cliente'])) {
  $id = $_POST['id'] ?? '';
  $codigo = $_POST['codigo'] ?? '';
  $equipamento = trim($_POST['equipamento'] ?? '');
  $endereco_id = $_POST['endereco_id'] ?? '';
  $cod_end = '';
  if ($endereco_id) {
    $s = $pdo->prepare("SELECT codigo FROM enderecos WHERE id=?"); $s->execute([$endereco_id]); $cod_end = $s->fetchColumn() ?: '';
  }
  $ficha = $_POST['ficha'] ?? 'S';
  $marca = trim($_POST['marca'] ?? ''); $modelo = trim($_POST['modelo'] ?? ''); $serie = trim($_POST['serie'] ?? ''); $observacao = trim($_POST['observacao'] ?? '');
  if ($equipamento && $codigo) {
    if ($id) {
      $pdo->prepare("UPDATE equipamentos SET codigo=?, equipamento=?, endereco_id=?, cod_end=?, ficha=?, marca=?, modelo=?, serie=?, observacao=? WHERE id=? AND cliente_id=?")->execute([$codigo,$equipamento,$endereco_id,$cod_end,$ficha,$marca,$modelo,$serie,$observacao,$id,$cliente_id_vindo]);
    } else {
      $pdo->prepare("INSERT INTO equipamentos (cliente_id, endereco_id, codigo, equipamento, cod_end, ficha, marca, modelo, serie, observacao) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$cliente_id_vindo,$endereco_id,$codigo,$equipamento,$cod_end,$ficha,$marca,$modelo,$serie,$observacao]);
      $id = $pdo->lastInsertId();
    }
    header("Location: equipamentos.php?cliente_id=$cliente_id_vindo&editar=$id&salvo=1"); exit;
  }
}
if ($modoCliente && isset($_GET['excluir'])) {
  $pdo->prepare("DELETE FROM equipamentos WHERE id=? AND cliente_id=?")->execute([$_GET['excluir'], $cliente_id_vindo]);
  header("Location: equipamentos.php?cliente_id=$cliente_id_vindo&excluido=1"); exit;
}

if (!$modoCliente && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_material'])) {
  $id = $_POST['id'] ?? ''; $codigo = $_POST['codigo'] ?? ''; $descricao = trim($_POST['descricao'] ?? '');
  if ($descricao && $codigo) {
    if ($id) { $pdo->prepare("UPDATE materiais SET codigo=?, descricao=? WHERE id=?")->execute([$codigo, $descricao, $id]); }
    else { $pdo->prepare("INSERT INTO materiais (codigo, descricao) VALUES (?,?)")->execute([$codigo, $descricao]); $id = $pdo->lastInsertId(); }
    header("Location: equipamentos.php?editar=$id&salvo=1"); exit;
  }
}
if (!$modoCliente && isset($_GET['excluir'])) {
  $pdo->prepare("DELETE FROM material_fornecedores WHERE material_id=?")->execute([$_GET['excluir']]);
  $pdo->prepare("DELETE FROM materiais WHERE id=?")->execute([$_GET['excluir']]);
  header("Location: equipamentos.php?excluido=1"); exit;
}
if (!$modoCliente && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_forn_material'])) {
  $mid = $_POST['material_id_forn'] ?? ''; $forn_edit_id = $_POST['forn_edit_id'] ?? ''; $fornecedor_id = $_POST['fornecedor_id'] ?? '';
  $cod_forn = '';
  if ($fornecedor_id) { $stmtCod = $pdo->prepare("SELECT codigo FROM fornecedores WHERE id=?"); $stmtCod->execute([$fornecedor_id]); $cod_forn = $stmtCod->fetchColumn() ?: ''; }
  $compra = floatval(str_replace(',', '.', $_POST['preco_compra'] ?? '0'));
  $lucro = floatval(str_replace(',', '.', $_POST['perc_lucro'] ?? '0'));
  $venda = $compra * (1 + $lucro / 100);
  if ($mid && $fornecedor_id) {
    if ($forn_edit_id) {
      $pdo->prepare("UPDATE material_fornecedores SET fornecedor_id=?, cod_forn=?, preco_compra=?, perc_lucro=?, preco_venda=? WHERE id=?")->execute([$fornecedor_id, $cod_forn, $compra, $lucro, $venda, $forn_edit_id]);
      header("Location: equipamentos.php?editar=$mid&forn_atualizado=1"); exit;
    } else {
      $pdo->prepare("INSERT INTO material_fornecedores (material_id, fornecedor_id, cod_forn, preco_compra, perc_lucro, preco_venda) VALUES (?,?,?,?,?,?)")->execute([$mid, $fornecedor_id, $cod_forn, $compra, $lucro, $venda]);
      header("Location: equipamentos.php?editar=$mid&forn_salvo=1"); exit;
    }
  }
}
if (!$modoCliente && isset($_GET['excluir_forn'])) {
  $mid = $_GET['material_id'] ?? '';
  $pdo->prepare("DELETE FROM material_fornecedores WHERE id=?")->execute([$_GET['excluir_forn']]);
  header("Location: equipamentos.php?editar=$mid&forn_excluido=1"); exit;
}

$edit = null;
if (isset($_GET['editar'])) {
  if ($modoCliente) {
    $stmt = $pdo->prepare("SELECT * FROM equipamentos WHERE id=? AND cliente_id=?");
    $stmt->execute([$_GET['editar'], $cliente_id_vindo]);
  } else {
    $stmt = $pdo->prepare("SELECT * FROM materiais WHERE id=?");
    $stmt->execute([$_GET['editar']]);
  }
  $edit = $stmt->fetch();
}
if ($modoCliente && !$edit && $modo === 'incluir') {
  $edit = ['id' => '', 'cliente_id' => $cliente_id_vindo, 'codigo' => $prox_codigo, 'equipamento' => '', 'endereco_id' => '', 'ficha' => 'S', 'marca' => '', 'modelo' => '', 'serie' => '', 'observacao' => ''];
}
$edit = $edit ?: [];
$edit_id = $edit['id'] ?? '';
$edit_codigo = ($edit['codigo'] ?? '') !== '' ? $edit['codigo'] : $prox_codigo;
$edit_equipamento = $edit['equipamento'] ?? '';
$edit_descricao = $edit['descricao'] ?? '';
$edit_endereco_id = $edit['endereco_id'] ?? '';
$edit_ficha = $edit['ficha'] ?? 'S';
$edit_marca = $edit['marca'] ?? '';
$edit_modelo = $edit['modelo'] ?? '';
$edit_serie = $edit['serie'] ?? '';
$edit_obs = $edit['observacao'] ?? '';

$fornecedoresDoMaterial = [];
if (!$modoCliente && $edit_id) {
  $stmt = $pdo->prepare("SELECT mf.*, f.nome as fornecedor_nome, f.codigo as fornecedor_codigo FROM material_fornecedores mf LEFT JOIN fornecedores f ON f.id=mf.fornecedor_id WHERE mf.material_id=? ORDER BY f.nome ASC");
  $stmt->execute([$edit_id]);
  $fornecedoresDoMaterial = $stmt->fetchAll();
}
$listaFornecedores = $pdo->query("SELECT id, codigo, nome FROM fornecedores ORDER BY nome ASC")->fetchAll();
$fornEdit = null;
if (!$modoCliente && isset($_GET['selecionar_forn']) && $_GET['selecionar_forn']) {
  $stmt = $pdo->prepare("SELECT * FROM material_fornecedores WHERE id=?"); $stmt->execute([$_GET['selecionar_forn']]); $fornEdit = $stmt->fetch();
}

$busca_codigo = $_GET['busca_codigo'] ?? '';
$busca_descricao = $_GET['busca_descricao'] ?? '';
$ordenacao = $_GET['ordenacao'] ?? 'codigo';
$where = " WHERE 1=1 "; $params = [];
if ($modoCliente) {
  $where .= " AND cliente_id=? "; $params[] = $cliente_id_vindo;
  if ($busca_codigo) { $where .= " AND codigo LIKE ? "; $params[] = "%$busca_codigo%"; }
  if ($busca_descricao) { $where .= " AND equipamento LIKE ? "; $params[] = "%$busca_descricao%"; }
  $orderBy = $ordenacao == 'descricao' ? 'equipamento ASC' : 'codigo ASC';
  $stmt = $pdo->prepare("SELECT * FROM equipamentos $where ORDER BY $orderBy"); $stmt->execute($params); $materiais = $stmt->fetchAll();
} else {
  if ($busca_codigo) { $where .= " AND codigo LIKE ? "; $params[] = "%$busca_codigo%"; }
  if ($busca_descricao) { $where .= " AND descricao LIKE ? "; $params[] = "%$busca_descricao%"; }
  $orderBy = $ordenacao == 'descricao' ? 'descricao ASC' : 'codigo ASC';
  $stmt = $pdo->prepare("SELECT * FROM materiais $where ORDER BY $orderBy"); $stmt->execute($params); $materiais = $stmt->fetchAll();
}
$pagina_ativa = 'equipamentos';
$editando = in_array($modo, ['incluir','editar']);
$readonly = $editando ? '' : 'readonly';
$disabled = $editando ? '' : 'disabled';
$temSelecionado = !empty($edit_id);
require_once __DIR__ . '/../sidebar.php';
?>
<style>
  html, body{ height:100%; overflow:hidden; } body{ overflow:hidden; }
  .pagina-equipamentos{ height:calc(100vh - 20px); overflow:hidden; display:flex; flex-direction:column; }
  .parte-cima{ flex-shrink:0; } .parte-cima > div{ display:flex; flex-direction:column; min-height:0; }
  .parte-baixo{ flex:1; min-height:0; display:flex; } .parte-baixo > div{ display:flex; flex-direction:column; min-height:0; height:100%; }
  .card-dados{ padding:10px 12px !important; display:flex; flex-direction:column; overflow:hidden; }
  .card-lista{ display:flex; flex-direction:column; overflow:hidden; }
  .card-forn-form, .card-forn-grid{ flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; }
  #formMaterial{ display:flex; flex-direction:column; overflow:hidden; }
  .lista-materiais-wrap{ flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; max-height:190px; }
  .lista-materiais-wrap thead th{ position:sticky; top:0; z-index:1; }
  .lista-materiais-wrap table{ background:#fff; width:100%; font-family:inherit; font-size:12px; }
  .lista-materiais-wrap table th{ background:#E5E7EB; font-family:inherit; font-size:12px; font-weight:600; border:1px solid #ccc; padding:2px 4px; line-height:1.2; }
  .lista-materiais-wrap table td{ font-family:inherit; font-size:12px; font-weight:400; border:1px solid #ddd; padding:2px 4px; cursor:pointer; line-height:1.2; }
  .grid-old{ background:#808080; flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; }
  .grid-old table{ background:#fff; width:100%; }
  .grid-old th{ background:#E5E7EB; font-size:11px; border:1px solid #ccc; padding:2px 4px; position:sticky; top:0; z-index:1; }
  .grid-old td{ font-size:11px; border:1px solid #ddd; padding:2px 4px; cursor:pointer; }
  .label-old{ font-size:11px; font-weight:600; text-align:right; padding-right:4px !important; line-height:1.2; }
  .btn-old{ border:1px solid #999; border-radius:4px; padding:4px 10px; font-size:12px; font-weight:600; background:#EFEFEF; text-decoration:none; color:#000; display:inline-block; cursor:pointer; }
  .btn-old:disabled{ opacity:.5; cursor:not-allowed; } .btn-verde{ background:#4ADE80; color:#000; } .btn-vermelho{ background:#F87171; color:#fff; } .btn-laranja{ background:#F59E0B; color:#000; border-color:#D97706; }
  .btn-old.btn-desabilitado{ opacity:.5; pointer-events:none; } input[readonly]{ background:#F3F4F6; }
  .form-dados .form-control{ font-size:12px; padding:2px 6px; height:26px; }
  .form-dados .row{ --bs-gutter-x:6px; --bs-gutter-y:2px; } .form-dados .label-old{ padding-top:4px; }
  .box-edicao{ padding:6px !important; } .box-edicao small{ font-size:11px; }
  .linha-selecionada, .linha-selecionada td{ background:#BFDBFE !important; --bs-table-bg:#BFDBFE !important; }
  .titulo-faixa{ background:#C0C0C0; text-align:center; font-weight:700; font-size:14px; padding:4px; margin:8px 0 8px 0; flex-shrink:0; }
  .dica-vermelha{ color:#D32F2F; font-size:11px; font-style:italic; }
  .modal, .modal-body{ overflow-y:auto; }
</style>
<div class="pagina-equipamentos">
<?php if($modoCliente): ?>
  <div class="alert alert-info py-1 mb-2 flex-shrink-0">Equipamentos do cliente: <b><?= htmlspecialchars($clienteDoEquipamento['codigo'].' - '.$clienteDoEquipamento['nome']) ?></b> &nbsp;|&nbsp; <a href="clientes.php?editar=<?= $cliente_id_vindo ?>">← Voltar p/ Cliente</a></div>
  <div class="row g-2 parte-cima">
    <div class="col-lg-7">
      <div class="card card-dados">
        <div class="d-flex justify-content-between align-items-center flex-shrink-0">
          <h6 class="fw-bold mb-0">Equipamento do Cliente</h6>
          <small>
            <?php if($modo==='incluir'): ?><span class="badge bg-success">INCLUINDO...</span>
            <?php elseif($modo==='editar'): ?><span class="badge bg-warning text-dark">EDITANDO...</span>
            <?php else: ?><span class="badge bg-secondary">VISUALIZANDO</span><?php endif; ?>
            Registro: <input type="text" size="5" value="<?= $edit_id ?>" readonly class="form-control form-control-sm d-inline-block" style="width:60px; height:26px; font-size:12px;">
          </small>
        </div>
        <?php if(isset($_GET['salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Equipamento salvo com sucesso!</div><?php endif; ?>
        <?php if(isset($_GET['excluido'])): ?><div class="alert alert-warning py-1 mt-2 mb-0 flex-shrink-0">Equipamento excluído.</div><?php endif; ?>
        <form method="POST" id="formMaterial" class="form-dados">
          <input type="hidden" name="id" value="<?= $edit_id ?>">
          <input type="hidden" name="cliente_id" value="<?= $cliente_id_vindo ?>">
          <input type="hidden" name="codigo" value="<?= $edit_codigo ?>">
          <div class="row g-1 align-items-center mb-1 mt-3 flex-shrink-0">
            <div class="col-3 label-old">Cliente:</div>
            <div class="col-9"><input class="form-control form-control-sm" readonly value="<?= htmlspecialchars($clienteDoEquipamento['codigo'].' - '.$clienteDoEquipamento['nome']) ?>"></div>
            <div class="col-3 label-old">Equipamento:</div>
            <div class="col-9"><input name="equipamento" id="campoDescricao" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit_equipamento) ?>" required placeholder="Ex: AQUECEDOR SOLAR 200 LITROS"></div>
            <div class="col-3 label-old">Endereço:</div>
            <div class="col-9">
              <select name="endereco_id" class="form-control form-control-sm" <?= $disabled ?>>
                <option value="">-- Selecione o endereço --</option>
                <?php foreach($enderecosDoCliente as $en): ?><option value="<?= $en['id'] ?>" <?= $edit_endereco_id==$en['id'] ? 'selected' : '' ?>><?= $en['codigo'].' - '.htmlspecialchars($en['endereco'].', '.$en['numero'].' - '.$en['bairro']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-3 label-old">Ficha:</div>
            <div class="col-9"><select name="ficha" class="form-control form-control-sm" <?= $disabled ?> style="max-width:110px;"><option value="S" <?= $edit_ficha=='S' ? 'selected' : '' ?>>S</option><option value="N" <?= $edit_ficha=='N' ? 'selected' : '' ?>>N</option></select></div>
            <div class="col-3 label-old">Marca:</div><div class="col-9"><input name="marca" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit_marca) ?>"></div>
            <div class="col-3 label-old">Modelo:</div><div class="col-9"><input name="modelo" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit_modelo) ?>"></div>
            <div class="col-3 label-old">Série:</div><div class="col-9"><input name="serie" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit_serie) ?>"></div>
            <div class="col-3 label-old">Observação:</div><div class="col-9"><textarea name="observacao" class="form-control form-control-sm" rows="2" <?= $readonly ?>><?= htmlspecialchars($edit_obs) ?></textarea></div>
          </div>
          <div class="row g-2 mt-3 flex-shrink-0">
            <div class="col-12 border box-edicao">
              <small>Edição</small><br>
              <!-- NOVO CANCELAR: botão com JS, não é mais link. Funciona mesmo com cache -->
              <?php if($editando): ?>
                <button type="button" class="btn-old btn-laranja" onclick="cancelarEdicaoEquipamento()">Cancelar</button>
              <?php else: ?>
                <a href="equipamentos.php?cliente_id=<?= $cliente_id_vindo ?>&modo=incluir" class="btn-old btn-verde">Inserir</a>
              <?php endif; ?>
              <button type="submit" name="salvar_equip_cliente" class="btn-old" <?= $editando ? '' : 'disabled' ?>>Salvar</button>
              <button type="button" class="btn-old btn-vermelho" <?= $temSelecionado ? '' : 'disabled' ?> onclick="excluirMaterial(<?= $edit_id ?: 0 ?>)">Excluir</button>
              <?php if($modo==='editar' && $edit_id): ?>
              <?php elseif($edit_id): ?>
                <a href="equipamentos.php?cliente_id=<?= $cliente_id_vindo ?>&editar=<?= $edit_id ?>&modo=editar" class="btn-old">Editar</a>
              <?php else: ?>
                <a href="#" class="btn-old btn-desabilitado" onclick="mostrarAviso('Selecione um equipamento na lista da direita para editar.'); return false;">Editar</a>
              <?php endif; ?>
              <a href="clientes.php?editar=<?= $cliente_id_vindo ?>" class="btn-old">Voltar p/ Cliente</a>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card p-3 card-lista">
        <h6 class="fw-bold flex-shrink-0">Lista de Equipamentos do Cliente:</h6>
        <div class="lista-materiais-wrap" style="max-height:320px;">
          <table class="mb-0">
            <thead><tr><th>Código</th><th>Equipamento</th><th>Cód. End.</th><th>Ficha</th></tr></thead>
            <tbody>
            <?php foreach($materiais as $m): ?>
              <tr class="<?= ($edit_id==$m['id']) ? 'linha-selecionada' : '' ?>" onclick="location.href='?cliente_id=<?= $cliente_id_vindo ?>&editar=<?= $m['id'] ?>'" style="cursor:pointer;">
                <td><?= $m['codigo'] ?></td><td><?= htmlspecialchars($m['equipamento']) ?></td><td><?= $m['cod_end'] ?></td><td><?= $m['ficha'] ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if(!$materiais): ?><tr><td colspan="4" class="text-center text-muted py-3">Nenhum equipamento. Clique em Inserir.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="row g-2 parte-cima">
    <div class="col-lg-7">
      <div class="card card-dados">
        <div class="d-flex justify-content-between align-items-center flex-shrink-0">
          <h6 class="fw-bold mb-0">Equipamento</h6>
          <small>
            <?php if($modo==='incluir'): ?><span class="badge bg-success">INCLUINDO...</span>
            <?php elseif($modo==='editar'): ?><span class="badge bg-warning text-dark">EDITANDO...</span>
            <?php else: ?><span class="badge bg-secondary">VISUALIZANDO</span><?php endif; ?>
            Registro: <input type="text" size="5" value="<?= $edit_id ?>" readonly class="form-control form-control-sm d-inline-block" style="width:60px; height:26px; font-size:12px;">
          </small>
        </div>
        <?php if(isset($_GET['salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Equipamento salvo com sucesso!</div><?php endif; ?>
        <?php if(isset($_GET['excluido'])): ?><div class="alert alert-warning py-1 mt-2 mb-0 flex-shrink-0">Equipamento excluído.</div><?php endif; ?>
        <?php if(isset($_GET['forn_salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Fornecedor adicionado ao equipamento!</div><?php endif; ?>
        <?php if(isset($_GET['forn_atualizado'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Valores do fornecedor atualizados!</div><?php endif; ?>
        <?php if(isset($_GET['forn_excluido'])): ?><div class="alert alert-warning py-1 mt-2 mb-0 flex-shrink-0">Fornecedor removido do equipamento.</div><?php endif; ?>
        <form method="POST" id="formMaterial" class="form-dados">
          <input type="hidden" name="id" value="<?= $edit_id ?>">
          <div class="row g-1 align-items-center mb-1 mt-3 flex-shrink-0">
            <div class="col-3 label-old">Código:</div><div class="col-9"><input type="number" name="codigo" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit_codigo ?>" required style="max-width:110px;"></div>
            <div class="col-3 label-old">Descrição:</div><div class="col-9"><input name="descricao" id="campoDescricao" class="form-control form-control-sm text-uppercase" <?= $readonly ?> value="<?= htmlspecialchars($edit_descricao) ?>" required></div>
          </div>
          <div class="row g-2 mt-3 flex-shrink-0">
            <div class="col-12 border box-edicao">
              <small>Edição</small><br>
              <?php if($editando): ?>
                <button type="button" class="btn-old btn-laranja" onclick="cancelarEdicaoEquipamento()">Cancelar</button>
              <?php else: ?>
                <a href="equipamentos.php?modo=incluir" class="btn-old btn-verde">Inserir</a>
              <?php endif; ?>
              <button type="submit" name="salvar_material" class="btn-old" <?= $editando ? '' : 'disabled' ?>>Salvar</button>
              <button type="button" class="btn-old btn-vermelho" <?= $temSelecionado ? '' : 'disabled' ?> onclick="excluirMaterial(<?= $edit_id ?: 0 ?>)">Excluir</button>
              <?php if($modo==='editar' && $edit_id): ?>
              <?php elseif($edit_id): ?><a href="equipamentos.php?editar=<?= $edit_id ?>&modo=editar" class="btn-old">Editar</a>
              <?php else: ?><a href="#" class="btn-old btn-desabilitado" onclick="mostrarAviso('Selecione um equipamento na lista da direita para editar.'); return false;">Editar</a><?php endif; ?>
              <a href="dashboard.php" class="btn-old ms-2">Fechar</a>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card p-3 card-lista">
        <h6 class="fw-bold flex-shrink-0">Lista de Equipamentos:</h6>
        <div class="lista-materiais-wrap">
          <table class="mb-0">
            <thead><tr><th>Código</th><th>Descrição</th></tr></thead>
            <tbody>
            <?php foreach($materiais as $m): ?>
              <tr class="<?= ($edit_id==$m['id']) ? 'linha-selecionada' : '' ?>" onclick="location.href='?editar=<?= $m['id'] ?>&ordenacao=<?= $ordenacao ?>'" style="cursor:pointer;"><td><?= $m['codigo'] ?></td><td><?= htmlspecialchars($m['descricao']) ?></td></tr>
            <?php endforeach; ?>
            <?php if(!$materiais): ?><tr><td colspan="2" class="text-center text-muted py-3">Nenhum equipamento encontrado.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <hr class="flex-shrink-0">
        <form method="GET" class="row g-1 flex-shrink-0">
          <div class="col-4"><label class="small">Código:</label><input name="busca_codigo" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_codigo) ?>"></div>
          <div class="col-8"><label class="small">Descrição:</label><input name="busca_descricao" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_descricao) ?>"></div>
          <div class="col-12 d-flex gap-1 mt-1"><button class="btn btn-sol btn-sm flex-fill">🔍 Pesquisar</button><a href="equipamentos.php" class="btn btn-sm btn-light border">Atualizar</a></div>
          <div class="col-12 border p-2 mt-2"><small>Ordenação</small><br><label class="small"><input type="radio" name="ordenacao" value="codigo" <?= $ordenacao=='codigo'?'checked':'' ?> onchange="this.form.submit()"> Por código</label><label class="small ms-3"><input type="radio" name="ordenacao" value="descricao" <?= $ordenacao=='descricao'?'checked':'' ?> onchange="this.form.submit()"> Por descrição</label></div>
        </form>
      </div>
    </div>
  </div>
  <div class="titulo-faixa">FORNECEDOR (ES) DO EQUIPAMENTO</div>
  <div class="row g-2 parte-baixo">
    <div class="col-lg-5">
      <div class="card p-3 card-forn-form">
        <h6 class="fw-bold flex-shrink-0" style="font-size:12px;"><?= $fornEdit ? 'Editando fornecedor de: <b>'.htmlspecialchars($edit_descricao).'</b>' : 'Fornecedor do equipamento: <b>'.htmlspecialchars($edit_descricao ?: '— selecione um equipamento acima —').'</b>' ?></h6>
        <?php if($edit_id): ?>
        <form method="POST" id="formForn" class="form-dados flex-shrink-0">
          <input type="hidden" name="material_id_forn" value="<?= $edit_id ?>">
          <input type="hidden" name="forn_edit_id" value="<?= $fornEdit['id'] ?? '' ?>">
          <div class="row g-1 align-items-center">
            <div class="col-4 label-old">Nome:</div><div class="col-8"><select name="fornecedor_id" id="selectFornecedor" class="form-control form-control-sm" required><option value="">-- Selecione --</option><?php foreach($listaFornecedores as $f): ?><option value="<?= $f['id'] ?>" <?= ($fornEdit && $fornEdit['fornecedor_id']==$f['id']) ? 'selected' : '' ?>><?= htmlspecialchars($f['codigo'].' - '.$f['nome']) ?></option><?php endforeach; ?></select></div>
            <div class="col-4 label-old">Valor de compra:</div><div class="col-8"><input name="preco_compra" id="campoCompra" type="number" step="0.01" class="form-control form-control-sm" value="<?= $fornEdit['preco_compra'] ?? '0' ?>" oninput="calcularVenda()" style="max-width:140px;"></div>
            <div class="col-4 label-old">Lucro (%):</div><div class="col-8"><input name="perc_lucro" id="campoLucro" type="number" step="0.01" class="form-control form-control-sm" value="<?= $fornEdit['perc_lucro'] ?? '0' ?>" oninput="calcularVenda()" style="max-width:140px;"></div>
            <div class="col-4 label-old">Valor de venda:</div><div class="col-8 d-flex gap-1"><input id="campoVenda" class="form-control form-control-sm" readonly style="background:#F3F4F6; font-weight:700; max-width:140px;"></div>
          </div>
          <div class="d-flex gap-2 mt-3 justify-content-end">
            <?php if($fornEdit): ?><button type="submit" name="salvar_forn_material" class="btn-old btn-verde">Atualizar</button><a href="?editar=<?= $edit_id ?>&material_id=<?= $edit_id ?>&excluir_forn=<?= $fornEdit['id'] ?>" onclick="return confirm('Remover este fornecedor?')" class="btn-old btn-vermelho"><< Remover</a><a href="?editar=<?= $edit_id ?>" class="btn-old">Limpar</a>
            <?php else: ?><button type="submit" name="salvar_forn_material" class="btn-old btn-verde">Adicionar >></button><?php endif; ?>
          </div>
        </form>
        <?php else: ?><p class="small text-muted mb-0">Selecione um equipamento na lista acima para vincular fornecedores.</p><?php endif; ?>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card p-3 card-forn-grid">
        <h6 class="fw-bold flex-shrink-0" style="font-size:12px;">Fornecedor(es) do equipamento acima selecionado:</h6>
        <div class="grid-old border">
          <table><thead><tr><th>Cód. Forn.</th><th>Fornecedor</th><th style="text-align:right;">R$ Compra</th><th style="text-align:right;">% Lucro</th><th style="text-align:right;">R$ Venda</th></tr></thead>
          <tbody>
            <?php foreach($fornecedoresDoMaterial as $rf): ?>
            <tr class="<?= ($fornEdit && $fornEdit['id']==$rf['id']) ? 'linha-selecionada' : '' ?>" onclick="location.href='?editar=<?= $edit_id ?>&selecionar_forn=<?= $rf['id'] ?>'">
              <td><?= htmlspecialchars($rf['fornecedor_codigo'] ?: $rf['cod_forn']) ?></td><td><?= htmlspecialchars($rf['fornecedor_nome']) ?></td><td style="text-align:right;"><?= number_format($rf['preco_compra'], 2, ',', '.') ?></td><td style="text-align:right;"><?= number_format($rf['perc_lucro'], 2, ',', '.') ?>%</td><td style="text-align:right; font-weight:700;"><?= number_format($rf['preco_venda'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$fornecedoresDoMaterial): ?><tr><td colspan="5" class="text-center text-muted">Nenhum fornecedor vinculado a este equipamento.</td></tr><?php endif; ?>
          </tbody></table>
        </div>
        <p class="dica-vermelha mt-2 mb-0 flex-shrink-0">Selecione um fornecedor acima para editar os valores de compra e lucro.</p>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>
<div class="modal fade" id="modalAviso" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="mb-0">Aviso</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="textoAviso" class="mb-0"></p></div><div class="modal-footer"><button type="button" class="btn btn-sol" data-bs-dismiss="modal">OK</button></div></div></div></div>
<div class="modal fade" id="modalConfirmacao" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="mb-0">Confirmação</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="textoConfirmacao" class="mb-0"></p></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-sol" id="btnConfirmarAcao">Sim</button></div></div></div></div>
<script>
let urlConfirmacao = '';
// NOVO: Cancelar com JS - limpa o form e força voltar pra VISUALIZANDO
function cancelarEdicaoEquipamento(){
  <?php if($modoCliente): ?>
    <?php if($modo === 'editar' && $edit_id): ?>
      location.href = 'equipamentos.php?cliente_id=<?= $cliente_id_vindo ?>&editar=<?= $edit_id ?>&modo=visualizar';
    <?php else: ?>
      location.href = 'equipamentos.php?cliente_id=<?= $cliente_id_vindo ?>&modo=visualizar';
    <?php endif; ?>
  <?php else: ?>
    <?php if($modo === 'editar' && $edit_id): ?>
      location.href = 'equipamentos.php?editar=<?= $edit_id ?>&modo=visualizar';
    <?php else: ?>
      location.href = 'equipamentos.php?modo=visualizar';
    <?php endif; ?>
  <?php endif; ?>
}
function calcularVenda(){ var c=document.getElementById('campoCompra'); if(!c) return; var compra=parseFloat(c.value)||0; var lucro=parseFloat(document.getElementById('campoLucro').value)||0; var venda=compra*(1+lucro/100); document.getElementById('campoVenda').value=venda.toFixed(2).replace('.', ','); }
calcularVenda();
function excluirMaterial(id){ if(!id){ mostrarAviso('Selecione um equipamento na lista da direita para excluir.'); return; } <?php if($modoCliente): ?>pedirConfirmacao('Excluir este equipamento do cliente?', '?cliente_id=<?= $cliente_id_vindo ?>&excluir=' + id);<?php else: ?>pedirConfirmacao('Excluir este equipamento e todos os fornecedores vinculados?', '?excluir=' + id);<?php endif; ?> }
function mostrarAviso(mensagem){ document.getElementById('textoAviso').textContent=mensagem; new bootstrap.Modal(document.getElementById('modalAviso')).show(); }
function pedirConfirmacao(mensagem, url){ document.getElementById('textoConfirmacao').textContent=mensagem; urlConfirmacao=url; new bootstrap.Modal(document.getElementById('modalConfirmacao')).show(); }
document.getElementById('btnConfirmarAcao').addEventListener('click', function(){ if(urlConfirmacao) location.href=urlConfirmacao; });
<?php if($editando): ?>document.addEventListener('DOMContentLoaded', function(){ var campo=document.getElementById('campoDescricao'); if(campo){ campo.focus(); } });<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../footer.php'; ?>