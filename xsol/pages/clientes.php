<?php
//clientes.php
require_once __DIR__ . '/../config.php';
exigirLogin();

function colunasDaTabela($pdo, $tabela){
  try{
    $s = $pdo->query("SHOW COLUMNS FROM `$tabela`");
    $cols = [];
    foreach($s->fetchAll(PDO::FETCH_ASSOC) as $r){ $cols[] = $r['Field']; }
    return $cols;
  }catch(Exception $e){ return []; }
}
function acharColuna($cols, $candidatos){
  foreach($candidatos as $c){
    if(in_array($c, $cols)) return $c;
  }
  return null;
}
function maiusculo($v){
  return mb_strtoupper(trim((string)$v), 'UTF-8');
}

// --- RELATÓRIO ---
if (isset($_GET['relatorio'])) {
  $tipoRel = $_GET['relatorio'] ?? 'geral';
  if (!in_array($tipoRel, ['geral','arquivo','cidade','cliente'])) $tipoRel = 'geral';
  if ($tipoRel === 'cliente' && !empty($_GET['id'])) {
    $idCliente = $_GET['id'];
    $stmtC = $pdo->prepare("SELECT * FROM clientes WHERE id=?");
    $stmtC->execute([$idCliente]);
    $cli = $stmtC->fetch();
    if (!$cli) { echo "Cliente não encontrado."; exit; }
    $stmtE = $pdo->prepare("SELECT * FROM enderecos WHERE cliente_id=? ORDER BY codigo LIMIT 1");
    $stmtE->execute([$idCliente]);
    $endCli = $stmtE->fetch();

    $equipamentosFichaS = [];
    try {
      $stmtEq = $pdo->prepare("SELECT * FROM equipamentos WHERE cliente_id=? AND UPPER(TRIM(ficha))='S' ORDER BY codigo ASC");
      $stmtEq->execute([$idCliente]);
      $equipamentosFichaS = $stmtEq->fetchAll();
    } catch (Exception $e) { $equipamentosFichaS = []; }
    $textoEquipamentosFichaS = '';
    foreach ($equipamentosFichaS as $eq) {
      $partes = [];
      $partes[] = trim(($eq['codigo'] ?? '') . ' - ' . ($eq['equipamento'] ?? ''));
      if (!empty($eq['marca'])) $partes[] = 'Marca: ' . $eq['marca'];
      if (!empty($eq['modelo'])) $partes[] = 'Modelo: ' . $eq['modelo'];
      if (!empty($eq['serie'])) $partes[] = 'Série: ' . $eq['serie'];
      $textoEquipamentosFichaS .= ($textoEquipamentosFichaS ? ' | ' : '') . implode(' - ', $partes);
    }

    $colsAt = colunasDaTabela($pdo, 'atendimentos');
    $colCliente = acharColuna($colsAt, ['cliente_id','id_cliente','cliente','codigo_cliente','cod_cliente','cod_cli','id_cli']);
    $colData = acharColuna($colsAt, ['data_serv','data_servico','data_atendimento','data','dt_atendimento','data_atend','data_os']);
    $colCodigo = acharColuna($colsAt, ['codigo','cod_os','os','numero']);
    $colValor = acharColuna($colsAt, ['valor','valor_servico','valor_total','total','preco','valor1','vlr']);
    $colTipo = acharColuna($colsAt, ['servico1','servico','tipo_servico','tipo','descricao_servico','tipo_de_servico','serv1']);
    $colStatus = acharColuna($colsAt, ['status','situacao']);
    $colEnd = acharColuna($colsAt, ['endereco','endereco_atendimento','local']);
    $colEquip = acharColuna($colsAt, ['equipamento','equipamento_desc','descricao_equipamento']);

    $erroRel = '';
    $atendimentos = [];
    if (!$colCliente) {
      $erroRel = 'Tabela atendimentos sem coluna de cliente. Colunas encontradas: ' . implode(', ', $colsAt);
    } else {
      $valorCliente = in_array($colCliente, ['codigo_cliente','cod_cliente']) ? ($cli['codigo'] ?? $idCliente) : $idCliente;
      try {
        $ord = $colData ? "$colData ASC" : ($colCodigo ? "$colCodigo ASC" : "id ASC");
        $stmtA = $pdo->prepare("SELECT * FROM atendimentos WHERE $colCliente=? ORDER BY $ord");
        $stmtA->execute([$valorCliente]);
        $atendimentos = $stmtA->fetchAll();
      } catch (Exception $e) { $erroRel = 'Erro ao buscar atendimentos: ' . $e->getMessage(); $atendimentos = []; }
    }
    $totalAtend = count($atendimentos);
    $totalValor = 0;
    foreach ($atendimentos as $a) { $totalValor += floatval($colValor && isset($a[$colValor]) ? $a[$colValor] : ($a['valor'] ?? 0)); }
    $contato = $endCli['contato'] ?? '';
    $email = $cli['email'] ?? '';
    $telCom = $endCli['fone_comercial'] ?? $endCli['telefone_comercial'] ?? $endCli['fone'] ?? '';
    $telRes = $endCli['fone_residencial'] ?? $endCli['telefone_residencial'] ?? '';
    $telCel = $endCli['fone_celular'] ?? $endCli['celular'] ?? $endCli['telefone_celular'] ?? $endCli['celular1'] ?? '';
    $tipoTelhadoRel = $endCli['tipo_telhado'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR"><head><meta charset="UTF-8"><title>Relatório de atendimentos por Cliente - <?= htmlspecialchars($cli['nome']) ?></title>
    <style>
      *{ margin:0; padding:0; box-sizing:border-box; }
      body{ font-family:Arial, sans-serif; font-size:12px; color:#000; padding:18px 28px; }
      .topo{ display:flex; align-items:flex-start; gap:18px; margin-bottom:10px; }
      .logo{ line-height:1; text-align:center; flex-shrink:0; }
      .logo-xsol{ font-size:38px; font-weight:900; color:#D32F2F; letter-spacing:-1px; }
      .logo-xsol span{ color:#F57C00; }
      .logo-sub{ font-size:13px; font-weight:700; color:#D32F2F; letter-spacing:4px; }
      .empresa{ flex:1; text-align:center; padding-top:6px; }
      .empresa h1{ font-size:17px; font-weight:400; text-transform:uppercase; }
      .empresa p{ font-size:16px; margin-top:2px; }
      .titulo-rel{ text-align:center; font-size:14px; font-weight:700; margin:14px 0 16px; }
      .dados-cli{ width:100%; margin-bottom:12px; }
      .dados-cli td{ padding:2px 4px; font-size:12px; vertical-align:top; }
      .dados-cli .rotulo{ font-weight:700; white-space:nowrap; width:1%; }
      .linha-tels{ display:flex; justify-content:space-between; padding:2px 4px; }
      .linha-tels span{ font-size:12px; }
      .linha-tels b{ font-size:11px; }
      table.atend{ width:100%; border-collapse:collapse; margin-top:6px; }
      table.atend th{ border:1px solid #000; font-size:11px; padding:3px 4px; text-align:center; background:#fff; }
      table.atend td{ padding:3px 4px; font-size:12px; vertical-align:top; }
      table.atend td.os{ color:#B00000; font-weight:700; }
      table.atend td.valor, table.atend td.status{ text-align:center; }
      table.atend tr.linha-detalhe td{ border-bottom:1px solid #000; padding-bottom:5px; }
      table.atend tr.linha-principal td{ border-top:1px solid #000; }
      .col-os{ width:55px; text-align:center; }
      .col-data{ width:90px; text-align:center; }
      .col-valor{ width:90px; text-align:right; }
      .col-status{ width:90px; text-align:center; }
      .detalhe{ font-size:12px; }
      .box-equip{ border:1px solid #000; padding:6px 8px; margin:8px 0 10px; }
      .box-equip b.tit{ font-size:12px; }
      .box-equip ul{ margin:4px 0 0 16px; }
      .box-equip li{ font-size:12px; padding:1px 0; }
      .rodape{ margin-top:10px; background:#E8E8E8; padding:5px 6px; font-size:12px; font-weight:700; }
      .botoes{ text-align:center; margin-top:16px; }
      .botoes button{ padding:6px 16px; font-size:13px; cursor:pointer; margin:0 4px; }
      @media print{ .botoes{ display:none; } body{ padding:0 10px; } }
    </style></head>
    <body>
      <div class="topo">
        <div class="logo"><div class="logo-xsol">X<span>S☀</span>L</div><div class="logo-sub">AQUECEDORES</div></div>
        <div class="empresa"><h1>JOSE CLEITON LOURENCO ME.</h1><p>CNPJ 20.557.233/0001-05</p></div>
      </div>
      <div class="titulo-rel">Relatório de atendimentos por Cliente</div>
      <table class="dados-cli">
        <tr><td class="rotulo">Cliente:</td><td><b><?= htmlspecialchars($cli['nome']) ?></b> (ID <?= $cli['id'] ?> / Cód. <?= $cli['codigo'] ?? '' ?>)</td><td class="rotulo">CPF:</td><td><?= htmlspecialchars(($cli['cpf'] ?? '') ?: ($cli['cnpj'] ?? '')) ?></td></tr>
        <tr><td class="rotulo">Contato:</td><td><?= htmlspecialchars($contato) ?></td><td class="rotulo">E-mail:</td><td><?= htmlspecialchars($email) ?></td></tr>
        <tr><td class="rotulo">Tipo de Telhado:</td><td><?= htmlspecialchars($tipoTelhadoRel) ?></td><td class="rotulo"></td><td></td></tr>
        <tr><td colspan="4"><div class="linha-tels"><span><b>Telefones:</b></span><span><b>Comercial</b><br><?= htmlspecialchars($telCom) ?></span><span><b>Residencial</b><br><?= htmlspecialchars($telRes) ?></span><span><b>Celular</b><br><?= htmlspecialchars($telCel) ?></span></div></td></tr>
      </table>
      <?php if($erroRel): ?><div style="background:#FEE; border:1px solid #C00; padding:8px; margin:8px 0; font-weight:700;"><?= htmlspecialchars($erroRel) ?></div><?php endif; ?>
      <div class="box-equip">
        <b class="tit">Equipamentos do Cliente (Ficha = S):</b>
        <?php if($equipamentosFichaS): ?>
          <ul>
          <?php foreach($equipamentosFichaS as $eq): ?>
            <li><?= htmlspecialchars(trim(($eq['codigo'] ?? '') . ' - ' . ($eq['equipamento'] ?? ''))) ?><?php if(!empty($eq['marca'])): ?> - Marca: <?= htmlspecialchars($eq['marca']) ?><?php endif; ?><?php if(!empty($eq['modelo'])): ?> - Modelo: <?= htmlspecialchars($eq['modelo']) ?><?php endif; ?><?php if(!empty($eq['serie'])): ?> - Série: <?= htmlspecialchars($eq['serie']) ?><?php endif; ?></li>
          <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div style="margin-top:4px;">Nenhum equipamento com Ficha = S para este cliente.</div>
        <?php endif; ?>
      </div>
      <table class="atend">
        <thead><tr><th class="col-os">OS</th><th class="col-data">DT. ATEND.</th><th>TIPO DE SERVIÇO</th><th class="col-valor">VALOR</th><th class="col-status">STATUS</th></tr></thead>
        <tbody>
        <?php foreach($atendimentos as $a): ?>
          <?php
            $vOS = $colCodigo && isset($a[$colCodigo]) ? $a[$colCodigo] : ($a['id'] ?? '');
            $vData = $colData && !empty($a[$colData]) ? date('d/m/Y', strtotime($a[$colData])) : '';
            $vTipo = $colTipo && isset($a[$colTipo]) ? $a[$colTipo] : ($a['tipo_servico'] ?? $a['tipo'] ?? '');
            $vValor = $colValor && isset($a[$colValor]) ? floatval($a[$colValor]) : floatval($a['valor'] ?? 0);
            $vStatus = $colStatus && isset($a[$colStatus]) ? $a[$colStatus] : ($a['status'] ?? '');
            $vEnd = $colEnd && isset($a[$colEnd]) ? $a[$colEnd] : ($a['endereco'] ?? $endCli['endereco'] ?? '');
            $vEquipAtend = $colEquip && isset($a[$colEquip]) ? trim($a[$colEquip]) : trim($a['equipamento'] ?? '');
            $vEquip = $vEquipAtend ? $vEquipAtend : $textoEquipamentosFichaS;
            if ($vEquipAtend && $textoEquipamentosFichaS && $vEquipAtend !== $textoEquipamentosFichaS) { $vEquip = $vEquipAtend . ' | ' . $textoEquipamentosFichaS; }
          ?>
          <tr class="linha-principal"><td class="os"><?= htmlspecialchars($vOS) ?></td><td style="text-align:center;"><?= $vData ?></td><td><b><?= htmlspecialchars($vTipo) ?></b></td><td style="text-align:right;"><?= number_format($vValor, 2, ',', '.') ?></td><td class="status"><?= htmlspecialchars(strtoupper($vStatus)) ?></td></tr>
          <tr class="linha-detalhe"><td colspan="5" class="detalhe">Endereço: <?= htmlspecialchars($vEnd) ?><br>Equipamento: <?= htmlspecialchars($vEquip) ?></td></tr>
        <?php endforeach; ?>
        <?php if(!$atendimentos && !$erroRel): ?><tr><td colspan="5" style="text-align:center; padding:10px;">Nenhum atendimento encontrado para este cliente.</td></tr><?php endif; ?>
        </tbody>
      </table>
      <div class="rodape">Quantidade total de atendimentos: &nbsp;<?= $totalAtend ?> &nbsp;|&nbsp; Valor total: R$ <?= number_format($totalValor, 2, ',', '.') ?></div>
      <div class="botoes"><button onclick="window.print()">🖨️ Imprimir</button><button onclick="window.close()">Fechar</button></div>
      <script>window.onload = function(){ window.print(); };</script>
    </body></html>
    <?php exit;
  }
  $tituloRel = 'Geral';
  $whereRel = " WHERE arquivo_morto=0 AND excluido_manualmente=0 ";
  $paramsRel = [];
  $orderRel = "codigo ASC";
  $cidadeFiltro = trim($_GET['cidade'] ?? '');
  if ($tipoRel === 'cidade' && $cidadeFiltro === '') {
    $stmtCidades = $pdo->query("SELECT cidade, COUNT(DISTINCT cliente_id) as total FROM enderecos WHERE cidade IS NOT NULL AND cidade <> '' GROUP BY cidade ORDER BY cidade ASC");
    $listaCidades = $stmtCidades->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR"><head><meta charset="UTF-8"><title>Relatório Por Cidade - Selecione a Cidade</title>
    <style>
      *{ margin:0; padding:0; box-sizing:border-box; }
      body{ font-family:Arial, sans-serif; font-size:12px; color:#000; padding:18px 22px; }
      .cabecalho{ display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:10px; }
      .logo{ line-height:1; text-align:center; }
      .logo-xsol{ font-size:38px; font-weight:900; color:#D32F2F; letter-spacing:-1px; }
      .logo-xsol span{ color:#F57C00; }
      .logo-sub{ font-size:13px; font-weight:700; color:#D32F2F; letter-spacing:4px; }
      .titulo{ text-align:center; }
      .titulo h1{ font-size:16px; text-transform:uppercase; }
      .titulo p{ font-size:14px; }
      .fones{ font-size:12px; text-align:right; line-height:1.5; }
      .instrucao{ text-align:center; margin:16px 0 10px; font-size:13px; }
      table{ width:100%; max-width:520px; margin:0 auto; border-collapse:collapse; }
      th{ text-align:left; font-size:11px; border-bottom:1px solid #000; padding:4px; }
      td{ font-size:13px; padding:5px 4px; border-bottom:1px solid #ccc; }
      td a{ color:#000; font-weight:700; text-decoration:none; display:block; }
      td a:hover{ color:#D32F2F; text-decoration:underline; }
      .col-total{ width:100px; text-align:center; }
      .botoes{ text-align:center; margin-top:16px; }
      .botoes button{ padding:6px 16px; font-size:13px; cursor:pointer; margin:0 4px; }
      @media print{ .botoes{ display:none; } }
    </style></head>
    <body>
      <div class="cabecalho"><div class="logo"><div class="logo-xsol">X<span>S☀</span>L</div><div class="logo-sub">AQUECEDORES</div></div><div class="titulo"><h1>Cadastro Geral de Clientes</h1><p>Por Cidade</p></div><div class="fones">FONES: (11) 2116-6100<br>(11) 2301-3651<br>(11) 94077-7778</div></div>
      <p class="instrucao">Selecione a cidade para gerar o relatório:</p>
      <table><thead><tr><th>CIDADE</th><th class="col-total">CLIENTES</th></tr></thead><tbody>
          <?php foreach($listaCidades as $cid): ?><tr><td><a href="clientes.php?relatorio=cidade&cidade=<?= urlencode($cid['cidade']) ?>">➤ <?= htmlspecialchars($cid['cidade']) ?></a></td><td class="col-total"><?= $cid['total'] ?></td></tr><?php endforeach; ?>
          <?php if(!$listaCidades): ?><tr><td colspan="2" style="text-align:center; padding:10px;">Nenhuma cidade encontrada nos endereços.</td></tr><?php endif; ?>
        </tbody></table>
      <div class="botoes"><button onclick="window.close()">Fechar</button></div>
    </body></html>
    <?php exit;
  }
  if ($tipoRel === 'arquivo') { $tituloRel = 'Arquivo Morto'; $whereRel = " WHERE arquivo_morto=1 "; $orderRel = "nome ASC"; }
  if ($tipoRel === 'cidade') { $tituloRel = 'Por Cidade - ' . $cidadeFiltro; $orderRel = "nome ASC"; }
  if ($tipoRel === 'cidade') {
    // CORREÇÃO: mostra TODOS da cidade, inclusive Arquivo Morto. Só exclui os excluídos manualmente.
    // Traz arquivo_morto para montar a coluna STATUS.
    $stmtRel = $pdo->prepare("SELECT c.codigo, c.data_emissao, c.nome, c.cpf, c.cnpj, c.arquivo_morto, (SELECT e.cidade FROM enderecos e WHERE e.cliente_id=c.id AND e.cidade=? ORDER BY e.codigo LIMIT 1) as cidade FROM clientes c WHERE EXISTS (SELECT 1 FROM enderecos e2 WHERE e2.cliente_id=c.id AND e2.cidade=?) AND c.excluido_manualmente=0 ORDER BY c.nome ASC");
    $stmtRel->execute([$cidadeFiltro, $cidadeFiltro]);
  } else {
    $stmtRel = $pdo->prepare("SELECT codigo, data_emissao, nome, cpf, cnpj, arquivo_morto FROM clientes $whereRel ORDER BY $orderRel");
    $stmtRel->execute($paramsRel);
  }
  $relClientes = $stmtRel->fetchAll();
  $totalRel = count($relClientes);
  // Contagem para o rodapé do relatório Por Cidade
  $totalAtivosRel = 0; $totalMortoRel = 0;
  foreach($relClientes as $rr){ if(!empty($rr['arquivo_morto'])) $totalMortoRel++; else $totalAtivosRel++; }
  ?>
  <!DOCTYPE html>
  <html lang="pt-BR"><head><meta charset="UTF-8"><title>Cadastro Geral de Clientes - <?= $tituloRel ?></title>
  <style>
    *{ margin:0; padding:0; box-sizing:border-box; }
    body{ font-family:Arial, sans-serif; font-size:12px; color:#000; padding:18px 22px; }
    .cabecalho{ display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:10px; }
    .logo{ line-height:1; text-align:center; }
    .logo-xsol{ font-size:38px; font-weight:900; color:#D32F2F; letter-spacing:-1px; }
    .logo-xsol span{ color:#F57C00; }
    .logo-sub{ font-size:13px; font-weight:700; color:#D32F2F; letter-spacing:4px; }
    .titulo{ text-align:center; }
    .titulo h1{ font-size:16px; text-transform:uppercase; }
    .titulo p{ font-size:14px; }
    .fones{ font-size:12px; text-align:right; line-height:1.5; }
    table{ width:100%; border-collapse:collapse; }
    th{ text-align:left; font-size:11px; border-bottom:1px solid #000; padding:3px 4px; }
    td{ font-size:12px; padding:2px 4px; border-bottom:1px solid #ccc; }
    .col-cod{ width:60px; } .col-data{ width:100px; } .col-status{ width:130px; text-align:center; }
    .status-ativo{ font-weight:700; }
    .status-morto{ font-weight:700; background:#FEF9C3; }
    .rodape{ margin-top:12px; font-size:11px; display:flex; justify-content:space-between; }
    .botoes{ text-align:center; margin-top:16px; }
    .botoes button{ padding:6px 16px; font-size:13px; cursor:pointer; margin:0 4px; }
    @media print{ .botoes{ display:none; } body{ padding:0; } .status-morto{ background:#FEF9C3 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; } }
  </style></head>
  <body>
    <div class="cabecalho"><div class="logo"><div class="logo-xsol">X<span>S☀</span>L</div><div class="logo-sub">AQUECEDORES</div></div><div class="titulo"><h1>Cadastro Geral de Clientes</h1><p><?= htmlspecialchars($tituloRel) ?></p></div><div class="fones">FONES: (11) 2116-6100<br>(11) 2301-3651<br>(11) 94077-7778</div></div>
    <table><thead><tr><th class="col-cod">CÓD.</th><th class="col-data">DT.CADASTRO</th><th>NOME</th><?php if($tipoRel==='cidade'): ?><th>CIDADE</th><th class="col-status">STATUS</th><?php endif; ?></tr></thead>
      <tbody>
        <?php foreach($relClientes as $r): ?><tr><td><?= htmlspecialchars($r['codigo']) ?></td><td><?= $r['data_emissao'] ? date('d/m/Y', strtotime($r['data_emissao'])) : '' ?></td><td><?= htmlspecialchars($r['nome']) ?></td><?php if($tipoRel==='cidade'): ?><td><?= htmlspecialchars($r['cidade'] ?? $cidadeFiltro) ?></td><td class="col-status <?= !empty($r['arquivo_morto']) ? 'status-morto' : 'status-ativo' ?>"><?= !empty($r['arquivo_morto']) ? 'ARQUIVO MORTO' : 'ATIVO' ?></td><?php endif; ?></tr><?php endforeach; ?>
        <?php if(!$relClientes): ?><tr><td colspan="<?= $tipoRel==='cidade' ? 5 : 3 ?>" style="text-align:center; padding:10px;">Nenhum cliente encontrado.</td></tr><?php endif; ?>
      </tbody></table>
    <div class="rodape"><span>Total: <b><?= $totalRel ?></b> cliente(s)<?php if($tipoRel==='cidade'): ?> | Ativos: <b><?= $totalAtivosRel ?></b> | Arquivo Morto: <b><?= $totalMortoRel ?></b><?php endif; ?></span><span>Emitido em: <?= date('d/m/Y H:i') ?></span></div>
    <div class="botoes"><button onclick="window.print()">🖨️ Imprimir</button><button onclick="window.close()">Fechar</button></div>
    <script>window.onload = function(){ window.print(); };</script>
  </body></html>
  <?php exit;
}
$modo = $_GET['modo'] ?? 'visualizar';
if (!in_array($modo, ['visualizar','incluir','editar'])) $modo = 'visualizar';
if ($modo === 'incluir') unset($_GET['editar']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_info_adicional'])) {
  $idInfo = $_POST['id_info'] ?? '';
  $info = maiusculo($_POST['info_adicional'] ?? '');
  if ($idInfo) {
    $pdo->prepare("UPDATE clientes SET info_adicional=? WHERE id=?")->execute([$info, $idInfo]);
    header("Location: clientes.php?editar=$idInfo&exibir=".($_POST['exibir_voltar'] ?? 'todos')."&ordenacao=".($_POST['ordenacao_voltar'] ?? 'codigo')."&info_salva=1"); exit;
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_cliente'])) {
  $id = $_POST['id'] ?? ''; $codigo = $_POST['codigo'] ?? ''; $data_emissao = $_POST['data_emissao'] ?: date('Y-m-d');
  $cpf = trim($_POST['cpf'] ?? ''); $cnpj = trim($_POST['cnpj'] ?? '');
  $nome = maiusculo($_POST['nome'] ?? '');
  $email = maiusculo($_POST['email'] ?? '');
  $empresa = maiusculo($_POST['empresa_solicitante'] ?? 'XSOL AQUECEDORES');
  $prox_visita = $_POST['data_proxima_visita'] ?: null; $arquivo_morto = isset($_POST['arquivo_morto']) ? 1 : 0;
  $justificativa = maiusculo($_POST['justificativa'] ?? '');
  if ($nome && $codigo) {
    if ($id) {
      $pdo->prepare("UPDATE clientes SET codigo=?, data_emissao=?, cpf=?, cnpj=?, nome=?, email=?, empresa_solicitante=?, data_proxima_visita=?, arquivo_morto=?, justificativa=? WHERE id=?")->execute([$codigo, $data_emissao, $cpf, $cnpj, $nome, $email, $empresa, $prox_visita, $arquivo_morto, $justificativa, $id]);
    } else {
      $pdo->prepare("INSERT INTO clientes (codigo, data_emissao, cpf, cnpj, nome, email, empresa_solicitante, data_proxima_visita, arquivo_morto, justificativa) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$codigo, $data_emissao, $cpf, $cnpj, $nome, $email, $empresa, $prox_visita, $arquivo_morto, $justificativa]);
      $id = $pdo->lastInsertId();
    }
    header("Location: clientes.php?editar=$id&salvo=1"); exit;
  }
}
if (isset($_GET['excluir'])) {
  $idExcluir = $_GET['excluir']; $just = maiusculo($_GET['justificativa_excluir'] ?? '');
  if ($just !== '') { $pdo->prepare("UPDATE clientes SET arquivo_morto=1, justificativa=? WHERE id=?")->execute([$just, $idExcluir]); }
  else { $pdo->prepare("UPDATE clientes SET arquivo_morto=1 WHERE id=?")->execute([$idExcluir]); }
  header("Location: clientes.php?exibir=arquivo&arquivado=1"); exit;
}
if (isset($_GET['arquivar'])) { $pdo->prepare("UPDATE clientes SET arquivo_morto=1 WHERE id=?")->execute([$_GET['arquivar']]); header("Location: clientes.php?exibir=arquivo"); exit; }
if (isset($_GET['reativar'])) { $pdo->prepare("UPDATE clientes SET arquivo_morto=0, excluido_manualmente=0 WHERE id=?")->execute([$_GET['reativar']]); header("Location: clientes.php?exibir=todos&reativado=1"); exit; }
$edit = null;
if (isset($_GET['editar'])) { $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=?"); $stmt->execute([$_GET['editar']]); $edit = $stmt->fetch(); }
$enderecos = []; $equipamentos = [];
if ($edit) {
  $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE cliente_id=? ORDER BY codigo"); $stmt->execute([$edit['id']]); $enderecos = $stmt->fetchAll();
  $stmt = $pdo->prepare("SELECT * FROM equipamentos WHERE cliente_id=? ORDER BY codigo"); $stmt->execute([$edit['id']]); $equipamentos = $stmt->fetchAll();
}
$busca_codigo = $_GET['busca_codigo'] ?? ''; $busca_nome = $_GET['busca_nome'] ?? ''; $busca_cpf = $_GET['busca_cpf'] ?? ''; $busca_end = $_GET['busca_end'] ?? '';
$exibir = $_GET['exibir'] ?? 'todos'; $ordenacao = $_GET['ordenacao'] ?? 'codigo';
$where = " WHERE 1=1 "; $params = [];
if ($busca_codigo) { $where .= " AND codigo LIKE ? "; $params[] = "%$busca_codigo%"; }
if ($busca_nome) { $where .= " AND nome LIKE ? "; $params[] = "%$busca_nome%"; }
if ($busca_cpf) { $where .= " AND (cpf LIKE ? OR cnpj LIKE ?) "; $params[] = "%$busca_cpf%"; $params[] = "%$busca_cpf%"; }
if ($exibir == 'arquivo') { $where .= " AND arquivo_morto=1 "; }
if ($exibir == 'todos') { $where .= " AND excluido_manualmente=0 "; }
$orderBy = $ordenacao == 'nome' ? 'nome ASC' : 'codigo ASC';
$stmt = $pdo->prepare("SELECT * FROM clientes $where ORDER BY $orderBy"); $stmt->execute($params); $clientes = $stmt->fetchAll();
$prox_codigo = $pdo->query("SELECT IFNULL(MAX(codigo),0)+1 as p FROM clientes")->fetch()['p'];
$pagina_ativa = 'clientes';
$editando = in_array($modo, ['incluir','editar']); $readonly = $editando ? '' : 'readonly'; $disabled = $editando ? '' : 'disabled';
$temClienteSelecionado = !empty($edit); $excluirDisabled = $temClienteSelecionado ? '' : 'disabled';
require_once __DIR__ . '/../sidebar.php';
?>
<style>
  html, body{ height:100%; overflow:hidden; } body{ overflow:hidden; }
  .pagina-clientes{ height:calc(100vh - 55px); overflow:hidden; display:flex; flex-direction:column; }
  .pagina-clientes > .row{ flex:1; min-height:0; } .pagina-clientes > .row > div{ display:flex; flex-direction:column; min-height:0; height:100%; }
  .card-dados{ padding:8px 12px !important; flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; }
  .card-lista{ flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; }
  #formCliente{ flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; }
  .grid-old{ background:#808080; overflow-y:auto; overflow-x:hidden; flex:1; min-height:40px; max-height:78px; }
  .grid-old table{ background:#fff; width:100%; } .grid-old th{ background:#E5E7EB; font-size:11px; border:1px solid #ccc; padding:2px 4px; position:sticky; top:0; z-index:1; white-space:nowrap; } .grid-old td{ font-size:11px; border:1px solid #ddd; padding:2px 4px; cursor:pointer; white-space:nowrap; }
  .grid-enderecos{ overflow-x:auto; overflow-y:auto; }
  .grid-enderecos table{ min-width:820px; }
  .lista-clientes-wrap{ flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; } .lista-clientes-wrap thead th{ position:sticky; top:0; z-index:1; }
  .lista-clientes-wrap table{ background:#fff; width:100%; font-family:inherit; font-size:12px; }
  .lista-clientes-wrap table th{ background:#E5E7EB; font-family:inherit; font-size:12px; font-weight:600; border:1px solid #ccc; padding:2px 4px; line-height:1.2; }
  .lista-clientes-wrap table td{ font-family:inherit; font-size:12px; font-weight:400; border:1px solid #ddd; padding:2px 4px; cursor:pointer; line-height:1.2; }
  .label-old{ font-size:11px; font-weight:600; text-align:right; padding-right:4px !important; line-height:1.2; }
  .leg-excluido{ background:#FCA5A5; padding:5px 12px; font-size:12px; font-weight:700; white-space:nowrap; display:inline-block; line-height:1.2; }
  .leg-morto{ background:#FEF9C3; padding:5px 12px; font-size:12px; font-weight:700; white-space:nowrap; display:inline-block; line-height:1.2; border:1px solid #FDE68A; }
  .btn-info-adicional{ white-space:nowrap; padding:5px 12px; font-size:12px; line-height:1.2; }
  .linha-excluido, .linha-excluido td{ background:#FECACA !important; --bs-table-bg:#FECACA !important; }
  .linha-morto, .linha-morto td{ background:#FEF9C3 !important; --bs-table-bg:#FEF9C3 !important; } .linha-morto:hover td{ background:#FEF08A !important; }
  .linha-selecionada, .linha-selecionada td{ background:#BFDBFE !important; --bs-table-bg:#BFDBFE !important; }
  .btn-old{ border:1px solid #999; border-radius:4px; padding:4px 10px; font-size:12px; font-weight:600; background:#EFEFEF; text-decoration:none; color:#000; display:inline-block; }
  .btn-old:disabled{ opacity:.5; cursor:not-allowed; } .btn-verde{ background:#4ADE80; color:#000; } .btn-vermelho{ background:#F87171; color:#fff; }
  .btn-vermelho:disabled{ background:#FCA5A5; color:#fff; opacity:.6; cursor:not-allowed; } .btn-laranja{ background:#F59E0B; color:#000; border-color:#D97706; }
  .btn-old.btn-desabilitado{ opacity:.5; pointer-events:none; } input[readonly]{ background:#F3F4F6; }
  .form-dados .form-control{ font-size:12px; padding:2px 6px; height:26px; } .form-dados .row{ --bs-gutter-x:6px; --bs-gutter-y:2px; } .form-dados .label-old{ padding-top:4px; }
  .titulo-grade{ font-size:11px; margin:4px 0 2px 0; flex-shrink:0; } .box-edicao{ padding:5px !important; flex-shrink:0; } .box-edicao small{ font-size:11px; }
  .modal, .modal-body{ overflow-y:auto; }
  .maiusculo{ text-transform:uppercase; }
</style>
<div class="pagina-clientes">
<div class="row g-2">
  <div class="col-lg-7">
    <div class="card card-dados">
      <div class="d-flex justify-content-between align-items-center flex-shrink-0">
        <h6 class="fw-bold mb-0">Dados do Cliente</h6>
        <small>
          <?php if($modo==='incluir'): ?><span class="badge bg-success">INCLUINDO...</span>
          <?php elseif($modo==='editar'): ?><span class="badge bg-warning text-dark">EDITANDO...</span>
          <?php else: ?><span class="badge bg-secondary">VISUALIZANDO</span><?php endif; ?>
          Código: <input type="text" size="5" value="<?= $edit['id'] ?? '' ?>" readonly class="form-control form-control-sm d-inline-block" style="width:60px; height:26px; font-size:12px;">
        </small>
      </div>
      <?php if(isset($_GET['salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Cliente salvo com sucesso!</div><?php endif; ?>
      <?php if(isset($_GET['info_salva'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Informação adicional salva!</div><?php endif; ?>
      <?php if(isset($_GET['arquivado'])): ?><div class="alert alert-warning py-1 mt-2 mb-0 flex-shrink-0">Cliente movido para Arquivo Morto.</div><?php endif; ?>
      <?php if(isset($_GET['reativado'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Cliente reativado com sucesso!</div><?php endif; ?>
      <?php if($edit && !empty($edit['arquivo_morto'])): ?><div class="alert alert-warning py-1 mt-2 mb-0 flex-shrink-0">⚠️ Este cliente está em <b>Arquivo Morto</b>. Justificativa: <?= htmlspecialchars($edit['justificativa'] ?? 'Sem justificativa') ?> <button type="button" class="btn btn-sm btn-success ms-2" onclick="pedirConfirmacao('Reativar este cliente?', '?reativar=<?= $edit['id'] ?>')">Reativar</button></div><?php endif; ?>
      <?php if($edit && !empty($edit['info_adicional'])): ?><div class="alert alert-info py-1 mt-2 mb-0 flex-shrink-0">📝 <b>Info. Adicional:</b> <?= nl2br(htmlspecialchars($edit['info_adicional'])) ?> <button type="button" class="btn btn-sm btn-light border ms-2" onclick="abrirInfoAdicional()">Editar</button></div><?php endif; ?>
      <form method="POST" id="formCliente" class="form-dados">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        <div class="row g-1 align-items-center mb-1 mt-2 flex-shrink-0">
          <div class="col-4 label-old">Data Emissão:</div><div class="col-8"><input type="date" name="data_emissao" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit['data_emissao'] ?? date('Y-m-d') ?>"></div>
          <div class="col-4 label-old">Cpf:</div><div class="col-8"><input id="campoCpf" name="cpf" class="form-control form-control-sm" placeholder="000.000.000-00" <?= $readonly ?> value="<?= htmlspecialchars($edit['cpf'] ?? '') ?>"></div>
          <div class="col-4 label-old">Cnpj:</div><div class="col-8"><input name="cnpj" class="form-control form-control-sm" placeholder="00.000.000/0000-00" <?= $readonly ?> value="<?= htmlspecialchars($edit['cnpj'] ?? '') ?>"></div>
          <div class="col-4 label-old">Nome do Cliente / Razão Social:</div><div class="col-8"><input name="nome" class="form-control form-control-sm maiusculo" <?= $readonly ?> value="<?= htmlspecialchars($edit['nome'] ?? '') ?>" required></div>
          <div class="col-4 label-old">E-Mail:</div><div class="col-8"><input name="email" type="email" class="form-control form-control-sm maiusculo" <?= $readonly ?> value="<?= htmlspecialchars($edit['email'] ?? '') ?>"></div>
          <div class="col-4 label-old">Empresa solicitante:</div><div class="col-8"><input name="empresa_solicitante" class="form-control form-control-sm maiusculo" <?= $readonly ?> value="<?= htmlspecialchars($edit['empresa_solicitante'] ?? 'XSOL AQUECEDORES') ?>"></div>
          <div class="col-4 label-old">Data Próxima Visita:</div>
          <div class="col-8 d-flex gap-1 align-items-center">
            <input type="date" name="data_proxima_visita" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit['data_proxima_visita'] ?? '' ?>" style="max-width:150px;">
            <label class="small text-nowrap"><input type="checkbox" name="arquivo_morto" <?= $disabled ?> <?= !empty($edit['arquivo_morto']) ? 'checked' : '' ?>> Arquivo Morto</label>
            <button type="button" class="btn btn-sm btn-light border py-0" style="font-size:11px;" data-bs-toggle="modal" data-bs-target="#modalJustificativa">Ver justificativa</button>
          </div>
        </div>
        <input type="hidden" name="justificativa" id="campoJustificativa" value="<?= htmlspecialchars($edit['justificativa'] ?? '') ?>">
        <p class="titulo-grade">Endereços (Duplo clique na grade para abrir o cadastro de endereços)</p>
        <div class="grid-old grid-enderecos border"><table><thead><tr><th>Código</th><th>Endereço</th><th>Bairro</th><th>Cidade</th><th>Contato</th><th>Tipo de Telhado</th><th>Fone Residencial</th><th>Fone Comercial</th><th>Fone Celular</th></tr></thead>
          <tbody>
            <?php foreach($enderecos as $e): ?>
            <tr ondblclick="location.href='enderecos.php?cliente_id=<?= $edit['id'] ?>&editar=<?= $e['id'] ?>'">
              <td><?= $e['codigo'] ?></td>
              <td><?= htmlspecialchars($e['endereco']) ?></td>
              <td><?= htmlspecialchars($e['bairro']) ?></td>
              <td><?= htmlspecialchars($e['cidade']) ?></td>
              <td><?= htmlspecialchars($e['contato']) ?></td>
              <td><?= htmlspecialchars($e['tipo_telhado'] ?? '') ?></td>
              <td><?= htmlspecialchars($e['fone_residencial'] ?? $e['telefone_residencial'] ?? '') ?></td>
              <td><?= htmlspecialchars($e['fone_comercial'] ?? $e['telefone_comercial'] ?? $e['fone'] ?? '') ?></td>
              <td><?= htmlspecialchars($e['fone_celular'] ?? $e['celular'] ?? $e['telefone_celular'] ?? $e['celular1'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$enderecos && $edit): ?><tr><td colspan="9" class="text-center text-muted">Nenhum endereço. <a href="enderecos.php?cliente_id=<?= $edit['id'] ?>&modo=incluir">Clique para cadastrar</a></td></tr><?php endif; ?>
            <?php if(!$edit): ?><tr><td colspan="9" class="text-center text-muted">Selecione um cliente para ver os endereços</td></tr><?php endif; ?>
          </tbody></table></div>
        <?php if($edit): ?><a href="enderecos.php?cliente_id=<?= $edit['id'] ?>&modo=incluir" class="btn btn-sm btn-sol mt-1 py-0 flex-shrink-0" style="font-size:11px;">+ Novo Endereço</a><?php endif; ?>
        <p class="titulo-grade">Equipamentos (Duplo clique na grade para abrir o cadastro de equipamentos)</p>
        <div class="grid-old border"><table><thead><tr><th>Código</th><th>Equipamento</th><th>Cód. End.</th><th>Ficha</th></tr></thead>
          <tbody>
            <?php foreach($equipamentos as $eq): ?><tr ondblclick="location.href='equipamentos.php?cliente_id=<?= $edit['id'] ?>&editar=<?= $eq['id'] ?>'"><td><?= $eq['codigo'] ?></td><td><?= htmlspecialchars($eq['equipamento']) ?></td><td><?= $eq['cod_end'] ?></td><td><?= $eq['ficha'] ?></td></tr><?php endforeach; ?>
            <?php if(!$equipamentos && $edit): ?><tr><td colspan="4" class="text-center text-muted">Nenhum equipamento. <a href="equipamentos.php?cliente_id=<?= $edit['id'] ?>&modo=incluir">Clique para cadastrar</a></td></tr><?php endif; ?>
            <?php if(!$edit): ?><tr><td colspan="4" class="text-center text-muted">Selecione um cliente para ver os equipamentos</td></tr><?php endif; ?>
          </tbody></table></div>
        <?php if($edit): ?><a href="equipamentos.php?cliente_id=<?= $edit['id'] ?>&modo=incluir" class="btn btn-sm btn-sol mt-1 py-0 flex-shrink-0" style="font-size:11px;">+ Novo Equipamento</a><?php endif; ?>
        <div class="row g-2 mt-2 flex-shrink-0">
          <div class="col-md-6 border box-edicao">
            <small>Edição</small><br>
            <?php if($modo==='incluir'): ?><a href="clientes.php" class="btn-old btn-laranja">Cancelar</a>
            <?php else: ?><a href="clientes.php?modo=incluir" class="btn-old btn-verde">Incluir</a><?php endif; ?>
            <?php if($modo==='editar' && $edit): ?><a href="clientes.php?editar=<?= $edit['id'] ?>" class="btn-old btn-laranja">Cancelar</a>
            <?php elseif($edit): ?><a href="clientes.php?editar=<?= $edit['id'] ?>&modo=editar" class="btn-old">Editar</a>
            <?php else: ?><a href="#" class="btn-old btn-desabilitado" onclick="mostrarAviso('Selecione um cliente na lista da direita para editar.'); return false;">Editar</a><?php endif; ?>
            <button type="submit" name="salvar_cliente" id="btnSalvar" class="btn-old" <?= $editando ? '' : 'disabled' ?>>Salvar</button>
            <button type="button" id="btnExcluir" class="btn-old btn-vermelho" <?= $excluirDisabled ?> onclick="excluirCliente(<?= $edit['id'] ?? 0 ?>)">Excluir</button>
          </div>
          <div class="col-md-6 border box-edicao">
            <small>Relatório de Clientes</small><br>
            <label class="small"><input type="radio" name="tipo_relatorio" value="geral"> Geral</label>
            <label class="small"><input type="radio" name="tipo_relatorio" value="arquivo" checked> Arquivo morto</label>
            <label class="small"><input type="radio" name="tipo_relatorio" value="cidade"> Por Cidade</label>
            <label class="small"><input type="radio" name="tipo_relatorio" value="cliente"> Por Cliente</label>
            <button type="button" class="btn-old ms-1" onclick="gerarRelatorio()">Gerar</button>
            <?php if($edit): ?><a href="atendimentos.php?cliente_id=<?= $edit['id'] ?>" class="btn-old ms-1">Atende</a>
            <?php else: ?><a href="#" class="btn-old ms-1 btn-desabilitado" onclick="mostrarAviso('Selecione um cliente para abrir os atendimentos.'); return false;">Atende</a><?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-3 card-lista">
      <h6 class="fw-bold flex-shrink-0">Lista de Clientes <?= $exibir=='arquivo' ? '<span class="badge bg-warning text-dark">ARQUIVO MORTO</span>' : '' ?></h6>
      <div class="lista-clientes-wrap">
        <table class="mb-0"><thead><tr><th>Código</th><th>Nome</th><th>CPF</th><th>CNPJ</th></tr></thead>
          <tbody>
          <?php foreach($clientes as $c): ?>
            <tr class="<?= $c['excluido_manualmente'] ? 'linha-excluido' : ($c['arquivo_morto'] ? 'linha-morto' : '') ?> <?= ($edit && $edit['id']==$c['id']) ? 'linha-selecionada' : '' ?>" onclick="location.href='?editar=<?= $c['id'] ?>&exibir=<?= $exibir ?>&ordenacao=<?= $ordenacao ?>'" style="cursor:pointer;">
              <td><?= $c['codigo'] ?></td><td><?= htmlspecialchars($c['nome']) ?></td><td><?= htmlspecialchars($c['cpf']) ?></td><td><?= htmlspecialchars($c['cnpj']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$clientes): ?><tr><td colspan="4" class="text-center text-muted py-3">Nenhum cliente encontrado.</td></tr><?php endif; ?>
          </tbody></table>
      </div>
      <div class="d-flex gap-2 mt-2 small align-items-center flex-wrap flex-shrink-0">
        <span>Legenda:</span><span class="leg-morto">ARQUIVO MORTO</span>
        <button type="button" class="btn btn-sm btn-light border ms-auto btn-info-adicional" onclick="abrirInfoAdicional()">Info. Adicional <?= ($edit && !empty($edit['info_adicional'])) ? '●' : '' ?></button>
      </div>
      <hr class="flex-shrink-0">
      <form method="GET" id="formFiltro" class="row g-1 flex-shrink-0">
        <div class="col-12"><small>Pesquisa</small></div>
        <div class="col-8"><label class="small">Código:</label><input name="busca_codigo" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_codigo) ?>"></div>
        <div class="col-4 d-flex align-items-end justify-content-end"><button class="btn btn-sm btn-light border">🔍</button></div>
        <div class="col-12"><label class="small">CPF:</label><input name="busca_cpf" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_cpf) ?>"></div>
        <div class="col-12"><label class="small">Nome:</label><input name="busca_nome" class="form-control form-control-sm" value="<?= htmlspecialchars($busca_nome) ?>"></div>
        <div class="col-12"><button class="btn btn-sol btn-sm w-100 mt-1">🔍 Pesquisar</button></div>
        <div class="col-6 border p-2 mt-2"><small>Ordenação</small><br><label class="small"><input type="radio" name="ordenacao" value="codigo" <?= $ordenacao=='codigo'?'checked':'' ?> onchange="this.form.submit()"> Código</label><br><label class="small"><input type="radio" name="ordenacao" value="nome" <?= $ordenacao=='nome'?'checked':'' ?> onchange="this.form.submit()"> Nome</label></div>
        <div class="col-6 border p-2 mt-2"><small>Exibição</small><br><label class="small"><input type="radio" name="exibir" value="todos" <?= $exibir=='todos'?'checked':'' ?> onchange="this.form.submit()"> Todos</label><br><label class="small"><input type="radio" name="exibir" value="arquivo" <?= $exibir=='arquivo'?'checked':'' ?> onchange="this.form.submit()"> Arquivo morto</label></div>
      </form>
    </div>
  </div>
</div>
</div>
<div class="modal fade" id="modalJustificativa" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5>Justificativa - Arquivo Morto</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><textarea class="form-control maiusculo" rows="4" id="justificativaModal" oninput="document.getElementById('campoJustificativa').value=this.value.toUpperCase()"><?= htmlspecialchars($edit['justificativa'] ?? '') ?></textarea></div><div class="modal-footer"><button class="btn btn-sol" data-bs-dismiss="modal">Fechar</button></div></div></div></div>
<div class="modal fade" id="modalExcluir" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5>Mover para Arquivo Morto</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small">O cliente <b>não será apagado</b>, ele só vai para Arquivo Morto e pode ser reativado depois.</p><label class="small">Justificativa:</label><textarea id="justExcluir" class="form-control maiusculo" rows="3"></textarea></div><div class="modal-footer"><button class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger" onclick="confirmarExcluir()">Sim, Arquivar</button></div></div></div></div>
<div class="modal fade" id="modalInfoAdicional" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="mb-0">Info. Adicional</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="formInfoAdicional"><div class="modal-body">
      <?php if($edit): ?><p class="small text-muted mb-2">Cliente: <b><?= htmlspecialchars($edit['codigo'] . ' - ' . $edit['nome']) ?></b></p><input type="hidden" name="id_info" value="<?= $edit['id'] ?>"><?php else: ?><p class="small text-muted mb-2">Nenhum cliente selecionado.</p><?php endif; ?>
      <input type="hidden" name="exibir_voltar" value="<?= htmlspecialchars($exibir) ?>"><input type="hidden" name="ordenacao_voltar" value="<?= htmlspecialchars($ordenacao) ?>">
      <label class="small">Informações adicionais:</label><textarea name="info_adicional" id="campoInfoAdicional" class="form-control maiusculo" rows="10"><?= htmlspecialchars($edit['info_adicional'] ?? '') ?></textarea>
    </div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button><button type="submit" name="salvar_info_adicional" class="btn btn-sol">Salvar Info. Adicional</button></div></form>
</div></div></div>
<div class="modal fade" id="modalAviso" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="mb-0">Aviso</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="textoAviso" class="mb-0"></p></div><div class="modal-footer"><button type="button" class="btn btn-sol" data-bs-dismiss="modal">OK</button></div></div></div></div>
<div class="modal fade" id="modalConfirmacao" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="mb-0">Confirmação</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="textoConfirmacao" class="mb-0"></p></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-sol" id="btnConfirmarAcao">Sim</button></div></div></div></div>
<script>
let idParaExcluir = 0; let urlConfirmacao = ''; let idClienteSelecionado = <?= $edit['id'] ?? 0 ?>;
function excluirCliente(id){ var btn=document.getElementById('btnExcluir'); if(btn && btn.disabled) return; if(!id){ mostrarAviso('Selecione um cliente na lista da direita para excluir.'); return; } idParaExcluir=id; document.getElementById('justExcluir').value=document.getElementById('campoJustificativa').value||''; new bootstrap.Modal(document.getElementById('modalExcluir')).show(); }
function confirmarExcluir(){ var just=encodeURIComponent(document.getElementById('justExcluir').value.toUpperCase()); location.href='?excluir='+idParaExcluir+'&justificativa_excluir='+just; }
function gerarRelatorio(){ var radio=document.querySelector('input[name="tipo_relatorio"]:checked'); var tipo=radio?radio.value:'geral'; if(tipo==='cliente' && !idClienteSelecionado){ mostrarAviso('Selecione um cliente na lista da direita para gerar o relatório Por Cliente.'); return; } var url='clientes.php?relatorio='+tipo; if(tipo==='cliente') url+='&id='+idClienteSelecionado; window.open(url,'_blank'); }
function mostrarAviso(mensagem){ document.getElementById('textoAviso').textContent=mensagem; new bootstrap.Modal(document.getElementById('modalAviso')).show(); }
function pedirConfirmacao(mensagem,url){ document.getElementById('textoConfirmacao').textContent=mensagem; urlConfirmacao=url; new bootstrap.Modal(document.getElementById('modalConfirmacao')).show(); }
document.getElementById('btnConfirmarAcao').addEventListener('click',function(){ if(urlConfirmacao) location.href=urlConfirmacao; });
function abrirInfoAdicional(){ <?php if(!$edit): ?>mostrarAviso('Selecione um cliente na lista da direita para ver a Info. Adicional.'); return;<?php else: ?>new bootstrap.Modal(document.getElementById('modalInfoAdicional')).show(); setTimeout(function(){ var c=document.getElementById('campoInfoAdicional'); if(c) c.focus(); },300);<?php endif; ?> }
document.addEventListener('DOMContentLoaded', function(){
  var form = document.getElementById('formCliente');
  if(form){
    form.addEventListener('submit', function(){
      ['nome','email','empresa_solicitante'].forEach(function(n){
        var c = form.querySelector('[name="'+n+'"]');
        if(c) c.value = c.value.toUpperCase();
      });
      var j = document.getElementById('campoJustificativa');
      if(j) j.value = j.value.toUpperCase();
    });
  }
  var formInfo = document.getElementById('formInfoAdicional');
  if(formInfo){
    formInfo.addEventListener('submit', function(){
      var c = document.getElementById('campoInfoAdicional');
      if(c) c.value = c.value.toUpperCase();
    });
  }
});
<?php if($editando): ?>document.addEventListener('DOMContentLoaded',function(){ var cpf=document.getElementById('campoCpf'); if(cpf){ cpf.focus(); cpf.select(); } });<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../footer.php'; ?>