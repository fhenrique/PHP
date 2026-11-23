<?php
//enderecos.php
require_once __DIR__ . '/../config.php';
exigirLogin();

$cliente_id = $_GET['cliente_id'] ?? $_POST['cliente_id'] ?? 0;
if (!$cliente_id) { header("Location: clientes.php"); exit; }

// Cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=?");
$stmt->execute([$cliente_id]);
$cli = $stmt->fetch();
if (!$cli) { echo "Cliente não encontrado."; exit; }

$modo = $_GET['modo'] ?? 'visualizar';
if (!in_array($modo, ['visualizar','incluir','editar'])) $modo = 'visualizar';

// --- SALVAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_endereco'])) {
  $id = $_POST['id'] ?? '';
  $codigo = $_POST['codigo'] ?? '';
  $cep = trim($_POST['cep'] ?? '');
  $endereco = trim($_POST['endereco'] ?? '');
  $numero = trim($_POST['numero'] ?? '');
  $complemento = trim($_POST['complemento'] ?? '');
  $bairro = trim($_POST['bairro'] ?? '');
  $cidade = trim($_POST['cidade'] ?? '');
  $uf = trim($_POST['uf'] ?? '');
  $contato = trim($_POST['contato'] ?? '');
  // Novos campos
  $tipo_telhado = trim($_POST['tipo_telhado'] ?? '');
  $fone_residencial = trim($_POST['fone_residencial'] ?? '');
  $fone_comercial = trim($_POST['fone_comercial'] ?? '');
  $fone_celular = trim($_POST['fone_celular'] ?? '');

  if ($endereco && $codigo) {
    if ($id) {
      $pdo->prepare("UPDATE enderecos SET codigo=?, cep=?, endereco=?, numero=?, complemento=?, bairro=?, cidade=?, uf=?, contato=?, tipo_telhado=?, fone_residencial=?, fone_comercial=?, fone_celular=? WHERE id=? AND cliente_id=?")
        ->execute([$codigo, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $uf, $contato, $tipo_telhado, $fone_residencial, $fone_comercial, $fone_celular, $id, $cliente_id]);
    } else {
      $pdo->prepare("INSERT INTO enderecos (cliente_id, codigo, cep, endereco, numero, complemento, bairro, cidade, uf, contato, tipo_telhado, fone_residencial, fone_comercial, fone_celular) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$cliente_id, $codigo, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $uf, $contato, $tipo_telhado, $fone_residencial, $fone_comercial, $fone_celular]);
      $id = $pdo->lastInsertId();
    }
    header("Location: enderecos.php?cliente_id=$cliente_id&editar=$id&salvo=1"); exit;
  }
}

// --- EXCLUIR ---
if (isset($_GET['excluir'])) {
  $pdo->prepare("DELETE FROM enderecos WHERE id=? AND cliente_id=?")->execute([$_GET['excluir'], $cliente_id]);
  header("Location: enderecos.php?cliente_id=$cliente_id&excluido=1"); exit;
}

// Endereço em edição
$edit = null;
if (isset($_GET['editar'])) {
  $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE id=? AND cliente_id=?");
  $stmt->execute([$_GET['editar'], $cliente_id]);
  $edit = $stmt->fetch();
}
if ($modo === 'incluir') $edit = null;

// Lista
$stmt = $pdo->prepare("SELECT * FROM enderecos WHERE cliente_id=? ORDER BY codigo ASC");
$stmt->execute([$cliente_id]);
$lista = $stmt->fetchAll();

// Próximo código
$prox_codigo = $pdo->prepare("SELECT IFNULL(MAX(codigo),0)+1 as p FROM enderecos WHERE cliente_id=?");
$prox_codigo->execute([$cliente_id]);
$prox_codigo = $prox_codigo->fetch()['p'];

// Quantidade de equipamentos por endereço
$qtdEquip = [];
foreach ($lista as $e) {
  $s = $pdo->prepare("SELECT COUNT(*) as t FROM equipamentos WHERE cliente_id=? AND cod_end=?");
  $s->execute([$cliente_id, $e['codigo']]);
  $qtdEquip[$e['id']] = $s->fetch()['t'];
}

$editando = in_array($modo, ['incluir','editar']) || !empty($edit);
$readonly = ($modo === 'incluir' || $modo === 'editar') ? '' : 'readonly';

$pagina_ativa = 'clientes';
require_once __DIR__ . '/../sidebar.php';
?>
<style>
  .pagina-end{ max-width:1180px; }
  .card-end{ border-radius:12px; border:0; box-shadow:0 1px 6px rgba(0,0,0,.06); padding:16px 18px; background:#fff; }
  /* ESTREITADO: campos mais compactos para caber tudo */
  .form-compacto label{ font-size:11px; font-weight:600; margin-bottom:2px; display:block; }
  .form-compacto .form-control, .form-compacto .form-select{ font-size:12px; padding:4px 8px; height:32px; }
  .form-compacto .row{ --bs-gutter-x:6px; --bs-gutter-y:6px; }
  .form-compacto input[readonly]{ background:#F3F4F6; }
  .titulo-form{ font-size:14px; font-weight:700; margin-bottom:10px; }
  .btn-salvar{ background:#F59E0B; border:0; color:#000; font-weight:700; font-size:12px; padding:7px 14px; border-radius:6px; }
  .btn-cinza{ background:#F3F4F6; border:1px solid #ddd; color:#000; font-size:12px; padding:7px 12px; border-radius:6px; text-decoration:none; display:inline-block; }
  .btn-excluir{ background:#DC2626; border:0; color:#fff; font-size:12px; padding:7px 14px; border-radius:6px; }
  .btn-equip{ background:#F59E0B; color:#000; font-size:11px; font-weight:700; padding:4px 10px; border-radius:999px; text-decoration:none; white-space:nowrap; }
  .btn-editar{ background:#fff; border:1px solid #ddd; font-size:11px; padding:4px 10px; border-radius:6px; text-decoration:none; color:#000; }
  .badge-qtd{ background:#6B7280; color:#fff; font-size:11px; padding:2px 7px; border-radius:6px; }
  .tabela-end th{ font-size:11px; color:#374151; border-bottom:1px solid #eee; padding:6px 4px; text-align:left; }
  .tabela-end td{ font-size:12px; padding:8px 4px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
  .linha-selecionada td{ background:#BFDBFE !important; }
  .sub-end{ font-size:11px; color:#6B7280; }
  .btn-voltar{ font-size:12px; text-decoration:none; color:#000; border:1px solid #ddd; padding:6px 12px; border-radius:6px; background:#fff; }
  .campo-novo{ background:#FFFBEB; border-color:#FDE68A; }
  .campo-novo:focus{ background:#fff; border-color:#F59E0B; box-shadow:0 0 0 2px rgba(245,158,11,.15); }
</style>

<div class="pagina-end">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="fw-bold mb-0">Endereços do Cliente</h4>
      <small class="text-muted"><?= htmlspecialchars($cli['codigo'] . ' - ' . $cli['nome']) ?></small>
    </div>
    <a href="clientes.php?editar=<?= $cliente_id ?>" class="btn-voltar">← Voltar para Cliente</a>
  </div>

  <?php if(isset($_GET['salvo'])): ?><div class="alert alert-success py-1">Endereço salvo com sucesso!</div><?php endif; ?>
  <?php if(isset($_GET['excluido'])): ?><div class="alert alert-warning py-1">Endereço excluído.</div><?php endif; ?>

  <div class="row g-3">
    <!-- FORMULÁRIO -->
    <div class="col-lg-6">
      <div class="card-end">
        <div class="titulo-form"><?= $edit ? 'Editar Endereço - Código ' . htmlspecialchars($edit['codigo']) : 'Novo Endereço' ?></div>
        <form method="POST" class="form-compacto">
          <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
          <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

          <div class="row">
            <div class="col-3">
              <label>Código</label>
              <input name="codigo" class="form-control" value="<?= $edit['codigo'] ?? $prox_codigo ?>" required>
            </div>
            <div class="col-9">
              <label>CEP</label>
              <input name="cep" id="campoCep" class="form-control" placeholder="00000-000" value="<?= htmlspecialchars($edit['cep'] ?? '') ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-9">
              <label>Endereço *</label>
              <input name="endereco" id="campoEndereco" class="form-control" required value="<?= htmlspecialchars($edit['endereco'] ?? '') ?>">
            </div>
            <div class="col-3">
              <label>Número</label>
              <input name="numero" id="campoNumero" class="form-control" value="<?= htmlspecialchars($edit['numero'] ?? '') ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <label>Complemento</label>
              <input name="complemento" class="form-control" value="<?= htmlspecialchars($edit['complemento'] ?? '') ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <label>Bairro</label>
              <input name="bairro" id="campoBairro" class="form-control" value="<?= htmlspecialchars($edit['bairro'] ?? '') ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-9">
              <label>Cidade</label>
              <input name="cidade" id="campoCidade" class="form-control" value="<?= htmlspecialchars($edit['cidade'] ?? '') ?>">
            </div>
            <div class="col-3">
              <label>UF</label>
              <select name="uf" id="campoUf" class="form-select">
                <?php $ufs = ['','AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']; ?>
                <?php foreach($ufs as $u): ?><option value="<?= $u ?>" <?= ($edit['uf'] ?? '')===$u ? 'selected' : '' ?>><?= $u ?: 'UF' ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <label>Contato no local</label>
              <input name="contato" class="form-control" value="<?= htmlspecialchars($edit['contato'] ?? '') ?>">
            </div>
          </div>

          <!-- NOVOS CAMPOS -->
          <div class="row">
            <div class="col-6">
              <label>Tipo de Telhado</label>
              <input name="tipo_telhado" class="form-control campo-novo" placeholder="Ex: Cerâmica, Fibrocimento, Laje..." value="<?= htmlspecialchars($edit['tipo_telhado'] ?? '') ?>">
            </div>
            <div class="col-6">
              <label>Fone Residencial</label>
              <input name="fone_residencial" class="form-control campo-novo" placeholder="(00) 0000-0000" value="<?= htmlspecialchars($edit['fone_residencial'] ?? $edit['telefone_residencial'] ?? '') ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-6">
              <label>Fone Comercial</label>
              <input name="fone_comercial" class="form-control campo-novo" placeholder="(00) 0000-0000" value="<?= htmlspecialchars($edit['fone_comercial'] ?? $edit['telefone_comercial'] ?? $edit['fone'] ?? '') ?>">
            </div>
            <div class="col-6">
              <label>Fone Celular</label>
              <input name="fone_celular" class="form-control campo-novo" placeholder="(00) 90000-0000" value="<?= htmlspecialchars($edit['fone_celular'] ?? $edit['celular'] ?? $edit['telefone_celular'] ?? $edit['celular1'] ?? '') ?>">
            </div>
          </div>

          <div class="d-flex gap-2 mt-3 flex-wrap">
            <button type="submit" name="salvar_endereco" class="btn-salvar">💾 Salvar Endereço</button>
            <a href="enderecos.php?cliente_id=<?= $cliente_id ?>&modo=incluir" class="btn-cinza">Novo / Limpar</a>
            <?php if($edit): ?>
              <a href="enderecos.php?cliente_id=<?= $cliente_id ?>&excluir=<?= $edit['id'] ?>" class="btn-excluir" onclick="return confirm('Excluir este endereço?')">Excluir</a>
            <?php endif; ?>
          </div>

          <a href="equipamentos.php?cliente_id=<?= $cliente_id ?><?= $edit ? '&cod_end=' . $edit['codigo'] : '' ?>" class="btn btn-light border w-100 mt-2" style="font-size:12px;">+ Ver / Cadastrar Equipamentos deste Endereço</a>
          <small class="text-muted d-block mt-2" style="font-size:11px;">* Ao digitar o CEP, o endereço é preenchido automaticamente (ViaCEP).</small>
        </form>
      </div>
    </div>

    <!-- LISTA -->
    <div class="col-lg-6">
      <div class="card-end">
        <div class="titulo-form mb-2">Endereços Cadastrados (<?= count($lista) ?>)</div>
        <table class="w-100 tabela-end">
          <thead><tr><th style="width:45px;">Código</th><th>Endereço</th><th style="width:110px;">Bairro / Cidade</th><th style="width:80px;">Contato</th><th style="width:50px;">Equip.</th><th style="width:90px;"></th></tr></thead>
          <tbody>
            <?php foreach($lista as $e): ?>
            <tr class="<?= ($edit && $edit['id']==$e['id']) ? 'linha-selecionada' : '' ?>">
              <td><?= $e['codigo'] ?></td>
              <td>
                <?= htmlspecialchars($e['endereco']) ?>, <?= htmlspecialchars($e['numero']) ?>
                <div class="sub-end"><?= htmlspecialchars($e['complemento'] ?? '') ?> <?= $e['complemento'] ? ' - ' : '' ?>CEP <?= htmlspecialchars($e['cep'] ?? '') ?></div>
                <?php if(!empty($e['tipo_telhado'])): ?><div class="sub-end">Telhado: <?= htmlspecialchars($e['tipo_telhado']) ?></div><?php endif; ?>
                <?php $f1 = $e['fone_residencial'] ?? $e['telefone_residencial'] ?? ''; $f2 = $e['fone_comercial'] ?? $e['telefone_comercial'] ?? ''; $f3 = $e['fone_celular'] ?? $e['celular'] ?? ''; ?>
                <?php if($f1 || $f2 || $f3): ?><div class="sub-end"><?= $f1 ? 'Res: '.$f1.' ' : '' ?><?= $f2 ? 'Com: '.$f2.' ' : '' ?><?= $f3 ? 'Cel: '.$f3 : '' ?></div><?php endif; ?>
              </td>
              <td><?= htmlspecialchars($e['bairro']) ?><br><?= htmlspecialchars($e['cidade']) ?></td>
              <td><?= htmlspecialchars($e['contato']) ?></td>
              <td><span class="badge-qtd"><?= $qtdEquip[$e['id']] ?? 0 ?></span></td>
              <td class="text-end">
                <a href="enderecos.php?cliente_id=<?= $cliente_id ?>&editar=<?= $e['id'] ?>" class="btn-editar">Editar</a><br>
                <a href="equipamentos.php?cliente_id=<?= $cliente_id ?>&cod_end=<?= $e['codigo'] ?>" class="btn-equip mt-1 d-inline-block">Equipamentos</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$lista): ?><tr><td colspan="6" class="text-center text-muted">Nenhum endereço cadastrado.</td></tr><?php endif; ?>
          </tbody>
        </table>
        <a href="clientes.php?editar=<?= $cliente_id ?>" class="btn btn-light border w-100 mt-3" style="font-size:12px;">← Voltar para Dados do Cliente</a>
      </div>
    </div>
  </div>
</div>

<script>
// ViaCEP - preenche endereço pelo CEP
document.getElementById('campoCep').addEventListener('blur', function(){
  var cep = this.value.replace(/\D/g,'');
  if(cep.length !== 8) return;
  fetch('https://viacep.com.br/ws/'+cep+'/json/')
    .then(r => r.json())
    .then(d => {
      if(d.erro) return;
      document.getElementById('campoEndereco').value = d.logradouro || '';
      document.getElementById('campoBairro').value = d.bairro || '';
      document.getElementById('campoCidade').value = d.localidade || '';
      document.getElementById('campoUf').value = d.uf || '';
      document.getElementById('campoNumero').focus();
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../footer.php'; ?>