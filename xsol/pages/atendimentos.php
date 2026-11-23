<?php
//atendimentos.php - Gerenciamento de Atendimentos - COMPLETO COM PAINÉIS
require_once __DIR__ . '/../config.php';
exigirLogin();

$cliente_id_filtro = $_GET['cliente_id'] ?? '';
$modo = $_GET['modo'] ?? 'visualizar';
if (!in_array($modo, ['visualizar','incluir','editar'])) $modo = 'visualizar';
if ($modo === 'incluir') unset($_GET['editar']);
$mostrar_todos = isset($_GET['todos']) ? 1 : 0;
$endereco_filtro = $_GET['endereco_filtro'] ?? '';
$ver_atend_endereco = $_GET['ver_atend_endereco'] ?? '';
$em_finalizacao = isset($_GET['finalizando']) ? 1 : 0;
$ano_corrente = date('Y');
$qs_todos = $mostrar_todos ? '&todos=1' : '';
$qs_fin = $em_finalizacao ? '&finalizando=1' : '';

// ============================================================
// FICHAS - RESIDÊNCIA / PISCINA / ASSISTÊNCIA TÉCNICA
// REGRA: só equipamentos com Ficha = S aparecem no relatório
// S = Sim = APARECE / N = Não = NÃO aparece
// ============================================================
if (isset($_GET['ficha'])) {
  $tipoFicha = $_GET['ficha'] ?? '';
  $idFicha = $_GET['id'] ?? $_GET['editar'] ?? '';
  $mostrarTel = isset($_GET['mostrar_tel']) && $_GET['mostrar_tel'] == '1';
  if (in_array($tipoFicha, ['residencia','piscina','assistencia']) && $idFicha) {
    $stmt = $pdo->prepare("SELECT a.*, c.nome as cliente_nome, c.codigo as cliente_codigo, t.nome as tecnico_nome, t.codigo as tecnico_codigo FROM atendimentos a LEFT JOIN clientes c ON c.id=a.cliente_id LEFT JOIN tecnicos t ON t.id=a.tecnico_id WHERE a.id=?");
    $stmt->execute([$idFicha]);
    $at = $stmt->fetch();
    if (!$at) { echo "Atendimento não encontrado."; exit; }
    $end = null;
    if (!empty($at['endereco_id'])) {
      $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE id=?"); $stmt->execute([$at['endereco_id']]); $end = $stmt->fetch();
    }
    if (!$end && !empty($at['cliente_id'])) {
      $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE cliente_id=? ORDER BY codigo LIMIT 1"); $stmt->execute([$at['cliente_id']]); $end = $stmt->fetch();
    }
    $end = $end ?: [];
    $stmt = $pdo->prepare("SELECT * FROM atendimento_pecas WHERE atendimento_id=?"); $stmt->execute([$idFicha]); $pecasFicha = $stmt->fetchAll();
    $equipamentosFicha = [];
    if (!empty($at['cliente_id']) && !empty($end['id'])) {
      $stmt = $pdo->prepare("SELECT * FROM equipamentos WHERE cliente_id=? AND endereco_id=? ORDER BY codigo"); $stmt->execute([$at['cliente_id'], $end['id']]); $equipamentosFicha = $stmt->fetchAll();
      if (!$equipamentosFicha && !empty($end['codigo'])) {
        $stmt = $pdo->prepare("SELECT * FROM equipamentos WHERE cliente_id=? AND cod_end=? ORDER BY codigo"); $stmt->execute([$at['cliente_id'], $end['codigo']]); $equipamentosFicha = $stmt->fetchAll();
      }
    }
    if (!$equipamentosFicha && !empty($at['cliente_id'])) {
      $stmt = $pdo->prepare("SELECT * FROM equipamentos WHERE cliente_id=? ORDER BY codigo"); $stmt->execute([$at['cliente_id']]); $equipamentosFicha = $stmt->fetchAll();
    }
    $equipamentosFichaS = [];
    foreach ($equipamentosFicha as $eqF) {
      $fichaEq = strtoupper(trim($eqF['ficha'] ?? ''));
      if ($fichaEq === 'S') $equipamentosFichaS[] = $eqF;
    }
    $equipAtendFicha = null;
    if (!empty($at['equipamento_id'])) {
      $stmt = $pdo->prepare("SELECT * FROM equipamentos WHERE id=?"); $stmt->execute([$at['equipamento_id']]); $tmpEq = $stmt->fetch();
      if ($tmpEq && strtoupper(trim($tmpEq['ficha'] ?? '')) === 'S') $equipAtendFicha = $tmpEq;
    }
    $equip1Ficha = $equipAtendFicha ? trim(($equipAtendFicha['equipamento'] ?? '') . ' ' . ($equipAtendFicha['marca'] ?? '') . ' ' . ($equipAtendFicha['modelo'] ?? '')) : '';
    if (!$equip1Ficha && !empty($equipamentosFichaS)) {
      $e0 = $equipamentosFichaS[0];
      $equip1Ficha = trim(($e0['equipamento'] ?? '') . ' ' . ($e0['marca'] ?? '') . ' ' . ($e0['modelo'] ?? ''));
    }
    $numColetoresFicha = count($equipamentosFichaS);
    if ($equipAtendFicha && !empty($equipAtendFicha['quantidade'])) $numColetoresFicha = $equipAtendFicha['quantidade'];
    $listaEquipAssistencia = [];
    if ($equipAtendFicha) $listaEquipAssistencia[] = $equipAtendFicha;
    foreach ($equipamentosFichaS as $eq) {
      $ja = false;
      foreach ($listaEquipAssistencia as $jaEq) { if ($jaEq['id'] == $eq['id']) $ja = true; }
      if (!$ja) $listaEquipAssistencia[] = $eq;
    }
    $osFicha = $at['os'] ?? '';
    $dataServFicha = !empty($at['data_serv']) ? date('d/m/Y', strtotime($at['data_serv'])) : '';
    $periodoFicha = $at['periodo'] ?? '';
    $tecnicoFicha = $at['tecnico_nome'] ?? '';
    $codCliFicha = $at['cliente_codigo'] ?? $at['cliente_id'] ?? '';
    $nomeCliFicha = $at['cliente_nome'] ?? '';
    $enderecoFicha = trim(($end['endereco'] ?? '') . ', ' . ($end['numero'] ?? '') . ' ' . ($end['complemento'] ?? ''));
    $bairroFicha = $end['bairro'] ?? '';
    $zonaFicha = $end['zona'] ?? '';
    if (!$zonaFicha && !empty($end['zona_id'])) {
      try { $z = $pdo->prepare("SELECT nome FROM zonas WHERE id=?"); $z->execute([$end['zona_id']]); $zonaFicha = $z->fetch()['nome'] ?? ''; } catch(Exception $e) {}
    }
    if (!$zonaFicha && !empty($end['zona_nome'])) $zonaFicha = $end['zona_nome'];
    $cepFicha = $end['cep'] ?? '';
    $cidadeFicha = $end['cidade'] ?? '';
    $referenciaFicha = $end['referencia'] ?? $end['ponto_referencia'] ?? '';
    $telResFicha = $end['fone_residencial'] ?? $end['telefone_residencial'] ?? '';
    $telComFicha = $end['fone_comercial'] ?? $end['telefone_comercial'] ?? $end['fone'] ?? '';
    $telCelFicha = $end['fone_celular'] ?? $end['celular'] ?? $end['telefone_celular'] ?? $end['celular1'] ?? '';
    $obsFicha = $at['obs'] ?? '';
    $valorFicha = floatval($at['valor'] ?? 0);
    $serv1Ficha = $at['servico1'] ?? '';
    $serv2Ficha = $at['servico2'] ?? '';
    $meses = ['01'=>'JANEIRO','02'=>'FEVEREIRO','03'=>'MARÇO','04'=>'ABRIL','05'=>'MAIO','06'=>'JUNHO','07'=>'JULHO','08'=>'AGOSTO','09'=>'SETEMBRO','10'=>'OUTUBRO','11'=>'NOVEMBRO','12'=>'DEZEMBRO'];
    $diaHoje = date('d'); $mesHoje = $meses[date('m')]; $anoHoje = date('Y');
    $ehPiscina = ($tipoFicha === 'piscina');
    $ehAssistencia = ($tipoFicha === 'assistencia');
    if ($ehAssistencia) $tituloFicha = 'FICHA DE ASSISTÊNCIA TÉCNICA';
    elseif ($ehPiscina) $tituloFicha = 'FICHA DE INSTALAÇÃO (PISCINA)';
    else $tituloFicha = 'INSTALAÇÃO DE RESIDÊNCIA';
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR"><head><meta charset="UTF-8"><title><?= $tituloFicha ?> - OS <?= htmlspecialchars($osFicha) ?></title>
    <style>
      *{ margin:0; padding:0; box-sizing:border-box; }
      body{ font-family:Arial, sans-serif; font-size:12px; color:#000; padding:18px 28px 10px; }
      .topo{ display:flex; justify-content:space-between; align-items:flex-start; }
      .logo{ line-height:1; text-align:center; }
      .logo-xsol{ font-size:38px; font-weight:900; color:#D32F2F; letter-spacing:-1px; }
      .logo-xsol span{ color:#F57C00; }
      .logo-sub{ font-size:13px; font-weight:700; color:#D32F2F; letter-spacing:4px; }
      .fones{ text-align:right; font-size:12px; font-weight:700; line-height:1.4; }
      .titulo{ text-align:center; font-size:16px; font-weight:900; margin:14px 0 2px; letter-spacing:.5px; }
      .linha-os{ display:flex; justify-content:space-between; font-weight:700; font-size:12px; margin:4px 0 8px; }
      .linha-os .vermelho{ color:#C00000; }
      .linha-os-centro{ text-align:center; font-weight:700; font-size:13px; margin:2px 0 2px; }
      .linha-os-centro .vermelho{ color:#C00000; }
      .linha-data-periodo{ display:flex; justify-content:space-between; font-weight:700; font-size:12px; margin:4px 0 8px; }
      .linha-data-periodo .vermelho{ color:#C00000; }
      hr{ border:none; border-top:1px solid #000; margin:6px 0; }
      .bloco{ margin:6px 0; line-height:1.5; }
      .azul{ color:#0000CC; font-weight:700; }
      .vermelho{ color:#C00000; font-weight:700; }
      .campo{ font-weight:700; }
      .linha{ border-bottom:1px solid #000; height:16px; margin:2px 0; }
      .linhas-pecas .linha{ height:17px; }
      .linhas-relatorio .linha{ height:19px; }
      .texto-just{ text-align:justify; line-height:1.4; font-size:11px; }
      .titulo-vermelho{ color:#C00000; font-weight:900; font-size:11px; }
      .data-ass{ text-align:center; margin:22px 0 28px; font-size:12px; }
      .assinaturas{ display:flex; justify-content:space-between; margin-top:18px; }
      .assinaturas div{ width:48%; text-align:center; font-size:11px; }
      .assinaturas .linha-ass{ border-top:1px solid #000; padding-top:3px; }
      .botoes{ text-align:center; margin-top:16px; }
      .botoes button{ padding:6px 16px; font-size:13px; cursor:pointer; margin:0 4px; }
      @media print{ .botoes{ display:none; } body{ padding:10px 18px; } }
    </style></head>
    <body>
      <div class="topo">
        <div class="logo"><div class="logo-xsol">X<span>S☀</span>L</div><div class="logo-sub">AQUECEDORES</div></div>
        <div class="fones">FONES:&nbsp;&nbsp;(11) 2116-6100<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(11) 94077-7778</div>
      </div>
      <div class="titulo"><?= $tituloFicha ?></div>
      <?php if($ehAssistencia): ?>
        <div class="linha-os-centro">OS: <span class="vermelho">X<?= htmlspecialchars($osFicha) ?></span></div>
        <div class="linha-data-periodo"><span>DATA DO SERVIÇO: <span class="vermelho"><?= $dataServFicha ?></span></span><span>PERÍODO: <span class="vermelho"><?= htmlspecialchars($periodoFicha) ?></span></span></div>
      <?php else: ?>
        <div class="linha-os"><span>DATA DO SERVIÇO: <span class="vermelho"><?= $dataServFicha ?></span></span><span>OS: <span class="vermelho"><?= htmlspecialchars($osFicha) ?></span></span><span>PERÍODO: <span class="vermelho"><?= htmlspecialchars($periodoFicha) ?></span></span></div>
      <?php endif; ?>
      <hr>
      <div class="bloco"><span class="campo">EMPRESA EXECUTANTE:</span> <span class="azul">JOSE CLEITON LOURENÇO ME / XSOL AQUECEDORES</span><br><span class="campo">TÉCNICO RESPONSÁVEL:</span> <span class="azul"><?= htmlspecialchars($tecnicoFicha) ?></span></div>
      <hr>
      <div class="bloco">
        <span class="campo">CÓDIGO DO CLIENTE:</span> <?= htmlspecialchars($codCliFicha) ?><br>
        <span class="campo">NOME DO CLIENTE:</span> <?= htmlspecialchars($nomeCliFicha) ?><br><br>
        <span class="campo">ENDEREÇO:</span> <span class="azul"><?= htmlspecialchars($enderecoFicha) ?></span><br>
        <span class="campo">BAIRRO:</span> <span class="azul"><?= htmlspecialchars($bairroFicha) ?></span><br>
        <span class="campo">ZONA:</span> <span class="azul"><?= htmlspecialchars($zonaFicha) ?></span><br>
        <span class="campo">CEP:</span> <span class="azul"><?= htmlspecialchars($cepFicha) ?></span><br>
        <span class="campo">CIDADE:</span> <span class="azul"><?= htmlspecialchars($cidadeFicha) ?></span><br>
        <span class="campo">REFERÊNCIA:</span> <?= htmlspecialchars($referenciaFicha) ?><br>
        <span class="campo">TEL. RES.:</span> <?= $mostrarTel ? htmlspecialchars($telResFicha) : '' ?> &nbsp;&nbsp;&nbsp;&nbsp; <span class="campo">TEL. COM.:</span> <?= $mostrarTel ? htmlspecialchars($telComFicha) : '' ?> &nbsp;&nbsp;&nbsp;&nbsp; <span class="campo">CELULAR:</span> <?= $mostrarTel ? htmlspecialchars($telCelFicha) : '' ?>
      </div>
      <hr>
      <?php if($ehAssistencia): ?>
      <div class="bloco">
        <span class="campo">EQUIPAMENTOS:</span><br>
        <?php $nEq = 1; foreach($listaEquipAssistencia as $eqA): ?>
          <?= $nEq++ ?>: <?= htmlspecialchars(trim(($eqA['equipamento'] ?? '') . ' ' . ($eqA['marca'] ?? '') . ' ' . ($eqA['modelo'] ?? ''))) ?><br>
        <?php endforeach; ?>
        <?php if(!$listaEquipAssistencia): ?><br><?php endif; ?>
        <span class="campo">TIPO DE SERVIÇO 1:</span> <span class="azul"><?= htmlspecialchars($serv1Ficha) ?></span><br>
        <span class="campo">TIPO DE SERVIÇO 2:</span> <span class="azul"><?= htmlspecialchars($serv2Ficha) ?></span><br>
        <span class="campo">VALOR DO SERVIÇO:</span> <span class="vermelho">R$ <?= number_format($valorFicha, 2, ',', '.') ?></span><br>
        <span class="vermelho">OBS.:</span> <?= htmlspecialchars($obsFicha) ?><br><br>
        <span class="campo">RELATÓRIO:</span>
        <div class="linhas-relatorio"><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div></div>
      </div>
      <hr>
      <div class="bloco texto-just"><span class="titulo-vermelho">IMPORTANTE:</span> OS SERVIÇOS EXECUTADOS PODERÃO SER RECLAMADOS NO PRAZO DE 3 MESES, A CONTAR DA DATA DE SUA REALIZAÇÃO. APÓS ESTE PRAZO ENTENDE-SE QUE O PROBLEMA FOI SANADO E QUE NOVAS SOLICITAÇÕES SERÃO COBRADAS.</div>
      <div class="data-ass">____________________, <?= $diaHoje ?> DE <?= $mesHoje ?> DE <?= $anoHoje ?></div>
      <div class="assinaturas"><div><div class="linha-ass">CLIENTE OU RESPONSÁVEL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; RG</div></div><div><div class="linha-ass">TÉCNICO RESPONSÁVEL</div></div></div>
      <?php else: ?>
      <div class="bloco">
        <?php if($ehPiscina): ?>
          <span class="campo">Nº DE COLETORES:</span> <?= $numColetoresFicha ? htmlspecialchars($numColetoresFicha) : '' ?><br>
          <span class="campo">EQUIPAMENTO 1:</span> <?= htmlspecialchars($equip1Ficha) ?><br>
        <?php endif; ?>
        <span class="vermelho">OBS.:</span> <?= htmlspecialchars($obsFicha) ?><br>
        <span class="vermelho">VALOR DA INSTALAÇÃO: R$ <?= number_format($valorFicha, 2, ',', '.') ?></span><br>
        <span class="vermelho">PEÇAS SUBSTITUÍDAS</span><br>
        <?php if($pecasFicha): ?><?php foreach($pecasFicha as $pp): ?><div style="font-size:12px;"><?= htmlspecialchars($pp['descricao']) ?> - Qtd: <?= $pp['qtd'] ?> - R$ <?= number_format($pp['valor'], 2, ',', '.') ?></div><?php endforeach; ?><?php endif; ?>
        <div class="linhas-pecas"><div class="linha"></div><div class="linha"></div><div class="linha"></div><div class="linha"></div></div>
      </div>
      <hr>
      <div class="bloco texto-just"><span class="titulo-vermelho">IMPORTANTE:</span><br>
        <?php if($ehPiscina): ?>SEMPRE MANTER O SISTEMA LIGADO JUNTAMENTE COM A CAPA TÉRMICA, PARA QUE O MESMO ATINJA A TEMPERATURA DE CONTRATO 25° A 29 GRAUS.<br>QUALQUER TIPO DE MANUTENÇÃO NO SISTEMA DE AQUECIMENTO SOLAR DA PISCINA, É ACONSELHÁVEL SER EFETUADO APENAS POR PROFISSIONAIS QUALIFICADOS DA XSOL.
        <?php else: ?>MANTER A VÁLVULA ANTI-CONGELAMENTO (DISPOSITIVO QUE PROTEGE OS COLETORES CONTRA CONGELAMENTO EM GEADAS), SEMPRE ENERGIZADA. CASO NO ATO DA INSTALAÇÃO O TÉCNICO NÃO ENCONTRE A FIAÇÃO DE ALIMENTAÇÃO DA MESMA (ITEM PEDIDO EM VISITA TÉCNICA), O CLIENTE FICA CIENTE QUE O RETORNO SERÁ COBRADO UMA TAXA REFERENTE A UMA VISITA TÉCNICA. O MESMO SE APLICA COM OS DEMAIS ITENS SOLICITADOS NA VISTORIA.<?php endif; ?>
      </div>
      <hr>
      <div class="bloco texto-just"><span class="titulo-vermelho">GARANTIA DE INSTALAÇÃO</span><br>A INSTALAÇÃO POSSUI 3 MESES DE GARANTIA A PARTIR DA DATA DE CONCLUSÃO DA MESMA, E COBRE:<br>* MAU FUNCIONAMENTO DO SISTEMA, DECORRENTES A MOTIVOS DE INSTALAÇÃO.<br>* VAZAMENTO NOS CASOS DE INTERLIGAÇÃO DO SISTEMA.</div>
      <hr>
      <div class="bloco texto-just"><span class="titulo-vermelho">LEMBRETE:</span><br>COM O OBJETIVO DE SEMPRE MANTER A BOA PERFORMANCE DO SISTEMA, EXISTE A NECESSIDADE DE NO MÁXIMO 6 MESES SER REALIZADA UMA MANUTENÇÃO PREVENTIVA.</div>
      <div class="bloco"><span class="campo">OBSERVAÇÕES:</span><div class="linha"></div><div class="linha"></div><div class="linha"></div></div>
      <div class="data-ass">____________________, <?= $diaHoje ?> DE <?= $mesHoje ?> DE <?= $anoHoje ?></div>
      <div class="assinaturas"><div><div class="linha-ass">CLIENTE OU RESPONSÁVEL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; RG</div></div><div><div class="linha-ass">TÉCNICO RESPONSÁVEL</div></div></div>
      <?php endif; ?>
      <div class="botoes"><button onclick="window.print()">🖨️ Imprimir</button><button onclick="window.close()">Fechar</button></div>
      <script>window.onload = function(){ window.print(); };</script>
    </body></html>
    <?php exit;
  }
  echo "<script>alert('Ficha inválida.'); window.close();</script>"; exit;
}

// --- Listas de apoio ---
$listaTecnicos = $pdo->query("SELECT id, codigo, nome FROM tecnicos ORDER BY nome ASC")->fetchAll();
$listaServicos = $pdo->query("SELECT id, codigo, descricao FROM tipos_servicos ORDER BY descricao ASC")->fetchAll();
$listaClientes = $pdo->query("SELECT id, codigo, nome FROM clientes WHERE arquivo_morto=0 ORDER BY nome ASC")->fetchAll();
$listaMateriais = $pdo->query("SELECT id, codigo, descricao FROM materiais ORDER BY descricao ASC")->fetchAll();
$listaGarantia = ['COBRANÇA','EM ABERTO','GARANTIA BOSH','GARANTIA PRESTADORA','GARANTIA XSOL'];
$listaFormaCobranca = ['BOLETO','CARTÃO','CHEQUE','CLIENTE NÃO PAGOU','DEPÓSITO','DINHEIRO','PIX','TRANSFERÊNCIA'];
try { $listaEquipamentosTodos = $pdo->query("SELECT id, codigo, equipamento, marca, modelo FROM equipamentos ORDER BY equipamento ASC, marca ASC, modelo ASC")->fetchAll(); }
catch(Exception $e) { $listaEquipamentosTodos = []; }

// --- Salvar / Editar Atendimento ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_atendimento'])) {
  $id = $_POST['id'] ?? ''; $os = $_POST['os'] ?? ''; $status = $_POST['status'] ?? 'ABERTO';
  $cliente_id = $_POST['cliente_id'] ?? ''; $tecnico_id = $_POST['tecnico_id'] ?? '';
  $servico1 = $_POST['servico1'] ?? ''; $servico2 = $_POST['servico2'] ?? '';
  $data_serv = $_POST['data_serv'] ?: date('Y-m-d'); $periodo = $_POST['periodo'] ?? 'MANHA';
  $ficha = $_POST['ficha'] ?? 'ASSISTENCIA'; $valor = floatval(str_replace(',', '.', $_POST['valor'] ?? '0'));
  $endereco_id = $_POST['endereco_id'] ?? ''; $equipamento_id = $_POST['equipamento_id'] ?? '';
  $obs_pre = trim($_POST['obs_pre_finalizacao'] ?? ''); $garantia = $_POST['garantia_cobranca'] ?? 'GARANTIA XSOL';
  $forma_cobranca = $_POST['forma_cobranca'] ?? ''; $obs = trim($_POST['obs'] ?? '');
  if ($garantia !== 'COBRANÇA') $forma_cobranca = '';
  if ($cliente_id && $os) {
    if ($id) {
      $pdo->prepare("UPDATE atendimentos SET os=?, status=?, cliente_id=?, tecnico_id=?, servico1=?, servico2=?, data_serv=?, periodo=?, ficha=?, valor=?, endereco_id=?, equipamento_id=?, obs_pre_finalizacao=?, garantia_cobranca=?, forma_cobranca=?, obs=? WHERE id=?")->execute([$os,$status,$cliente_id,$tecnico_id,$servico1,$servico2,$data_serv,$periodo,$ficha,$valor,$endereco_id,$equipamento_id,$obs_pre,$garantia,$forma_cobranca,$obs,$id]);
    } else {
      $pdo->prepare("INSERT INTO atendimentos (os, status, cliente_id, tecnico_id, servico1, servico2, data_serv, periodo, ficha, valor, endereco_id, equipamento_id, obs_pre_finalizacao, garantia_cobranca, forma_cobranca, obs) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$os,$status,$cliente_id,$tecnico_id,$servico1,$servico2,$data_serv,$periodo,$ficha,$valor,$endereco_id,$equipamento_id,$obs_pre,$garantia,$forma_cobranca,$obs]);
      $id = $pdo->lastInsertId();
    }
    header("Location: atendimentos.php?editar=$id&cliente_id=$cliente_id&salvo=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
  }
}
// --- Salvar Finalização ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_finalizacao'])) {
  $idFin = $_POST['id'] ?? $_POST['atendimento_id_fin'] ?? '';
  $cliFin = $_POST['cliente_id'] ?? '';
  $garantiaFin = $_POST['garantia_cobranca'] ?? 'GARANTIA XSOL';
  $formaFin = $_POST['forma_cobranca'] ?? '';
  if ($garantiaFin !== 'COBRANÇA') $formaFin = '';
  if ($idFin) {
    $st = $pdo->prepare("SELECT status FROM atendimentos WHERE id=?"); $st->execute([$idFin]); $statusAtual = strtoupper(trim($st->fetch()['status'] ?? ''));
    if ($statusAtual === 'BAIXADO') {
      header("Location: atendimentos.php?editar=$idFin&cliente_id=$cliFin&ja_baixado=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
    }
    if ($statusAtual === 'CANCELADO') {
      header("Location: atendimentos.php?editar=$idFin&cliente_id=$cliFin&ja_cancelado=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
    }
    $pdo->prepare("UPDATE atendimentos SET status='BAIXADO', garantia_cobranca=?, forma_cobranca=? WHERE id=?")->execute([$garantiaFin,$formaFin,$idFin]);
    header("Location: atendimentos.php?editar=$idFin&cliente_id=$cliFin&finalizado=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
  }
}
if (isset($_GET['finalizar'])) {
  $idFin = $_GET['finalizar'];
  $st = $pdo->prepare("SELECT status FROM atendimentos WHERE id=?"); $st->execute([$idFin]); $statusAtual = strtoupper(trim($st->fetch()['status'] ?? ''));
  if ($statusAtual === 'BAIXADO') {
    header("Location: atendimentos.php?editar=$idFin&cliente_id=".($_GET['cliente_id'] ?? '')."&ja_baixado=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
  }
  if ($statusAtual === 'CANCELADO') {
    header("Location: atendimentos.php?editar=$idFin&cliente_id=".($_GET['cliente_id'] ?? '')."&ja_cancelado=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
  }
  header("Location: atendimentos.php?editar=$idFin&cliente_id=".($_GET['cliente_id'] ?? '')."&finalizando=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
}
if (isset($_GET['cancelar'])) {
  $pdo->prepare("UPDATE atendimentos SET status='CANCELADO' WHERE id=?")->execute([$_GET['cancelar']]);
  header("Location: atendimentos.php?editar=".$_GET['cancelar']."&cliente_id=".($_GET['cliente_id'] ?? '')."&cancelado=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
}
if (isset($_GET['excluir'])) {
  $pdo->prepare("DELETE FROM atendimento_pecas WHERE atendimento_id=?")->execute([$_GET['excluir']]);
  $pdo->prepare("DELETE FROM atendimentos WHERE id=?")->execute([$_GET['excluir']]);
  header("Location: atendimentos.php?cliente_id=".($_GET['cliente_id'] ?? '')."&excluido=1" . ($mostrar_todos ? '&todos=1' : '')); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_peca'])) {
  $aid = $_POST['atendimento_id_peca'] ?? ''; $descricao_peca = trim($_POST['descricao_peca'] ?? '');
  $valor_peca = floatval(str_replace(',', '.', $_POST['valor_peca'] ?? '0')); $qtd_peca = intval($_POST['qtd_peca'] ?? 1);
  $voltando_fin = isset($_POST['finalizando']) && $_POST['finalizando'] == '1';
  if ($aid && $descricao_peca) {
    $pdo->prepare("INSERT INTO atendimento_pecas (atendimento_id, descricao, valor, qtd) VALUES (?,?,?,?)")->execute([$aid,$descricao_peca,$valor_peca,$qtd_peca]);
    header("Location: atendimentos.php?editar=$aid&cliente_id=".($_POST['cliente_id_peca'] ?? '')."&peca_add=1" . ($mostrar_todos ? '&todos=1' : '') . ($voltando_fin ? '&finalizando=1' : '')); exit;
  }
}
if (isset($_GET['excluir_peca'])) {
  $aid = $_GET['atendimento_id'] ?? '';
  $pdo->prepare("DELETE FROM atendimento_pecas WHERE id=?")->execute([$_GET['excluir_peca']]);
  $voltando_fin2 = isset($_GET['finalizando']) ? '&finalizando=1' : '';
  header("Location: atendimentos.php?editar=$aid&cliente_id=".($_GET['cliente_id'] ?? '') . ($mostrar_todos ? '&todos=1' : '') . $voltando_fin2); exit;
}
// --- Alternar Ficha S/N ---
if (isset($_GET['toggle_ficha_equip'])) {
  $eqId = $_GET['toggle_ficha_equip'];
  $pdo->prepare("UPDATE equipamentos SET ficha = CASE WHEN UPPER(TRIM(ficha))='S' THEN 'N' ELSE 'S' END WHERE id=?")->execute([$eqId]);
  $voltar = "atendimentos.php?cliente_id=".($_GET['cliente_id'] ?? '');
  if (!empty($_GET['editar'])) $voltar .= "&editar=".$_GET['editar'];
  if (!empty($_GET['modo'])) $voltar .= "&modo=".$_GET['modo'];
  if (!empty($_GET['endereco_id_sel'])) $voltar .= "&endereco_id_sel=".$_GET['endereco_id_sel'];
  if (!empty($_GET['endereco_filtro'])) $voltar .= "&endereco_filtro=".$_GET['endereco_filtro'];
  if (!empty($_GET['todos'])) $voltar .= "&todos=1";
  $voltar .= "&ficha_alt=1";
  header("Location: $voltar"); exit;
}
$edit = null;
if (isset($_GET['editar'])) {
  $stmt = $pdo->prepare("SELECT a.*, c.nome as cliente_nome, c.codigo as cliente_codigo FROM atendimentos a LEFT JOIN clientes c ON c.id=a.cliente_id WHERE a.id=?");
  $stmt->execute([$_GET['editar']]); $edit = $stmt->fetch();
  if ($edit && !$cliente_id_filtro) $cliente_id_filtro = $edit['cliente_id'];
}
$edit = $edit ?: [];
$edit_id = $edit['id'] ?? ''; $edit_os = $edit['os'] ?? ''; $edit_status = $edit['status'] ?? 'ABERTO';
$edit_tecnico = $edit['tecnico_id'] ?? ''; $edit_serv1 = $edit['servico1'] ?? ''; $edit_serv2 = $edit['servico2'] ?? '';
$edit_data = $edit['data_serv'] ?? date('Y-m-d'); $edit_periodo = $edit['periodo'] ?? 'MANHA';
$edit_ficha = $edit['ficha'] ?? 'ASSISTENCIA'; $edit_valor = $edit['valor'] ?? '0';
$edit_obs_pre = $edit['obs_pre_finalizacao'] ?? ''; $edit_garantia = $edit['garantia_cobranca'] ?? 'GARANTIA XSOL';
$edit_cobranca = $edit['forma_cobranca'] ?? ''; $edit_obs = $edit['obs'] ?? '';
$edit_end_id = $edit['endereco_id'] ?? ''; $edit_equip_id = $edit['equipamento_id'] ?? '';
$clienteSel = null;
if ($cliente_id_filtro) {
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=?"); $stmt->execute([$cliente_id_filtro]); $clienteSel = $stmt->fetch();
}
$enderecosCliente = [];
if ($clienteSel) {
  $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE cliente_id=? ORDER BY codigo"); $stmt->execute([$clienteSel['id']]); $enderecosCliente = $stmt->fetchAll();
}
$endereco_id_atual = $edit_end_id;
if (isset($_GET['endereco_id_sel']) && $_GET['endereco_id_sel']) $endereco_id_atual = $_GET['endereco_id_sel'];
if (!$endereco_id_atual && $enderecosCliente) $endereco_id_atual = $enderecosCliente[0]['id'];
$enderecoSel = null;
if ($endereco_id_atual) {
  $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE id=?"); $stmt->execute([$endereco_id_atual]); $enderecoSel = $stmt->fetch();
}
$equipamentosEnd = [];
if ($clienteSel && $enderecoSel) {
  $stmt = $pdo->prepare("SELECT * FROM equipamentos WHERE cliente_id=? AND endereco_id=? ORDER BY codigo");
  $stmt->execute([$clienteSel['id'], $enderecoSel['id']]); $equipamentosEnd = $stmt->fetchAll();
  if (!$equipamentosEnd) {
    $stmt = $pdo->prepare("SELECT * FROM equipamentos WHERE cliente_id=? AND cod_end=? ORDER BY codigo");
    $stmt->execute([$clienteSel['id'], $enderecoSel['codigo']]); $equipamentosEnd = $stmt->fetchAll();
  }
}
$enderecoModal = null; $listaAtendEndModal = [];
if ($ver_atend_endereco) {
  $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE id=?"); $stmt->execute([$ver_atend_endereco]); $enderecoModal = $stmt->fetch();
  if ($enderecoModal) {
    $stmt = $pdo->prepare("SELECT * FROM atendimentos WHERE endereco_id=? AND cliente_id=? ORDER BY data_serv DESC, os DESC");
    $stmt->execute([$enderecoModal['id'], $enderecoModal['cliente_id']]); $listaAtendEndModal = $stmt->fetchAll();
  }
}
$paramsExec = [];
$sqlLista = "SELECT * FROM atendimentos WHERE 1=1 ";
if (!$mostrar_todos && $cliente_id_filtro) { $sqlLista .= " AND cliente_id=? "; $paramsExec[] = $cliente_id_filtro; }
if (!$mostrar_todos) { $sqlLista .= " AND YEAR(data_serv)=? "; $paramsExec[] = $ano_corrente; }
if ($endereco_filtro) { $sqlLista .= " AND endereco_id=? "; $paramsExec[] = $endereco_filtro; }
$sqlLista .= " ORDER BY data_serv DESC, os DESC";
$stmt = $pdo->prepare($sqlLista); $stmt->execute($paramsExec); $listaAtendimentos = $stmt->fetchAll();
$pecasAtendimento = []; $totalPecas = 0;
if ($edit_id) {
  $stmt = $pdo->prepare("SELECT * FROM atendimento_pecas WHERE atendimento_id=?");
  $stmt->execute([$edit_id]); $pecasAtendimento = $stmt->fetchAll();
  foreach ($pecasAtendimento as $pp) $totalPecas += floatval($pp['valor']) * intval($pp['qtd']);
}
$prox_os = $pdo->query("SELECT IFNULL(MAX(os),0)+1 as p FROM atendimentos")->fetch()['p'];
$pagina_ativa = 'atendimentos';
$editando = in_array($modo, ['incluir','editar']);
$readonly = $editando ? '' : 'readonly';
$disabled = $editando ? '' : 'disabled';
$disabled_garantia = ($em_finalizacao || $editando) ? '' : 'disabled';
$disabled_cobranca = ((($em_finalizacao || $editando) && $edit_garantia === 'COBRANÇA')) ? '' : 'disabled';
$disabled_pecas = $em_finalizacao ? '' : 'disabled';
$ja_baixada = strtoupper(trim($edit_status)) === 'BAIXADO';
$ja_cancelada = strtoupper(trim($edit_status)) === 'CANCELADO';
require_once __DIR__ . '/../sidebar.php';
?>
<style>
  html,body{ height:100%; overflow:hidden; }
  .pagina-atend{ height:calc(100vh - 55px); overflow:hidden; display:flex; flex-direction:column; }
  .pagina-atend > .row{ flex:1; min-height:0; }
  .pagina-atend > .row > div{ display:flex; flex-direction:column; min-height:0; height:100%; }
  .card-atend{ flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; padding:10px 12px !important; }
  .label-old{ font-size:11px; font-weight:600; text-align:right; padding-right:4px !important; }
  .form-dados .form-control{ font-size:12px; padding:2px 6px; height:26px; }
  .form-dados .row{ --bs-gutter-x:6px; --bs-gutter-y:2px; }
  .grid-old{ background:#808080; flex:1; min-height:40px; overflow-y:auto; overflow-x:hidden; }
  .grid-old table{ background:#fff; width:100%; }
  .grid-old th{ background:#E5E7EB; font-size:11px; border:1px solid #ccc; padding:2px 4px; position:sticky; top:0; z-index:1; }
  .grid-old td{ font-size:11px; border:1px solid #ddd; padding:2px 4px; cursor:pointer; }
  .grid-pequena{ max-height:90px; }
  .linha-selecionada, .linha-selecionada td{ background:#BFDBFE !important; }
  .linha-baixado, .linha-baixado td{ background:#86EFAC !important; }
  .linha-aberto, .linha-aberto td{ background:#93C5FD !important; }
  .linha-equip-atendido, .linha-equip-atendido td{ background:#86EFAC !important; }
  .btn-old{ border:1px solid #999; border-radius:4px; padding:4px 10px; font-size:12px; font-weight:600; background:#EFEFEF; text-decoration:none; color:#000; display:inline-block; cursor:pointer; }
  .btn-old:disabled{ opacity:.5; cursor:not-allowed; }
  .btn-verde{ background:#4ADE80; } .btn-vermelho{ background:#F87171; color:#fff; } .btn-laranja{ background:#F59E0B; }
  .box-edicao{ padding:6px !important; } .box-edicao small{ font-size:11px; }
  .titulo-faixa{ font-size:11px; font-weight:700; margin:0 0 4px; }
  .dica{ font-size:11px; color:#666; font-style:italic; }
  .painel-bloco{ border:1px solid #999; padding:6px 6px 4px; margin:6px 0; background:#fff; flex-shrink:0; }
  .painel-bloco .grid-old{ border:1px solid #999 !important; }
  .painel-finalizando{ border:2px solid #F59E0B !important; background:#FFFBEB !important; }
  .painel-pecas-travado{ opacity:.6; }
  .modal, .modal-body{ overflow-y:auto; }
  .overlay-end{ position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1050; display:flex; align-items:center; justify-content:center; }
  .popup-end{ background:#D3D3D3; width:640px; max-width:94vw; max-height:88vh; display:flex; flex-direction:column; border:1px solid #666; box-shadow:0 0 0 2px #999; }
  .popup-end-header{ background:#BFE3FF; padding:6px 10px; display:flex; justify-content:space-between; align-items:center; font-weight:700; font-size:13px; border-bottom:1px solid #666; }
  .popup-end-header .btn-fechar{ background:#FF6B6B; border:1px solid #666; width:24px; height:22px; line-height:1; font-weight:700; cursor:pointer; }
  .popup-end-body{ padding:10px; background:#D3D3D3; overflow-y:auto; }
  .popup-end-body .linha-info{ font-size:12px; margin-bottom:4px; }
  .popup-end-body .grid-old{ background:#808080; }
  .cel-ficha{ text-align:center; font-weight:700; }
</style>
<div class="pagina-atend">
<div class="row g-2">
  <div class="col-lg-6">
    <div class="card card-atend">
      <div class="d-flex justify-content-between align-items-center flex-shrink-0">
        <h6 class="fw-bold mb-0">Dados do Atendimento</h6>
        <small>
          <?php if($em_finalizacao): ?><span class="badge bg-warning text-dark">FINALIZANDO...</span>
          <?php elseif($modo==='incluir'): ?><span class="badge bg-success">INCLUINDO...</span>
          <?php elseif($modo==='editar'): ?><span class="badge bg-warning text-dark">EDITANDO...</span>
          <?php else: ?><span class="badge bg-secondary">VISUALIZANDO</span><?php endif; ?>
        </small>
      </div>
      <?php if(isset($_GET['salvo'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Atendimento salvo!</div><?php endif; ?>
      <?php if(isset($_GET['finalizado'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">OS Finalizada / Baixada!</div><?php endif; ?>
      <?php if(isset($_GET['excluido'])): ?><div class="alert alert-warning py-1 mt-2 mb-0 flex-shrink-0">Atendimento excluído.</div><?php endif; ?>
      <?php if(isset($_GET['ficha_alt'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Ficha do equipamento alterada S &lt;-&gt; N!</div><?php endif; ?>
      <?php if(isset($_GET['peca_add'])): ?><div class="alert alert-success py-1 mt-2 mb-0 flex-shrink-0">Equipamento adicionado!</div><?php endif; ?>
      <?php if($endereco_filtro && !$ver_atend_endereco): ?><div class="alert alert-info py-1 mt-2 mb-0 flex-shrink-0">Filtrando só os atendimentos deste endereço. <a href="atendimentos.php?cliente_id=<?= $cliente_id_filtro ?>&editar=<?= $edit_id ?><?= $qs_todos ?>">Mostrar todos</a></div><?php endif; ?>
      <form method="POST" id="formAtend" class="form-dados" style="display:flex; flex-direction:column; overflow:hidden;">
        <input type="hidden" name="id" value="<?= $edit_id ?>">
        <div class="row g-1 align-items-center mt-2 flex-shrink-0">
          <div class="col-2 label-old">OS:</div>
          <div class="col-3"><input name="os" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit_os ?: $prox_os ?>" required></div>
          <div class="col-7"><input name="status" class="form-control form-control-sm text-center fw-bold" <?= $readonly ?> value="<?= $edit_status ?>" style="background:#E5E7EB;"></div>
          <div class="col-2 label-old">Cliente:</div>
          <div class="col-10">
            <select name="cliente_id" class="form-control form-control-sm" <?= $disabled ?> onchange="location.href='atendimentos.php?cliente_id='+this.value+'&modo=<?= $modo ?><?= $qs_todos ?>'">
              <option value="">-- Selecione --</option>
              <?php foreach($listaClientes as $c): ?><option value="<?= $c['id'] ?>" <?= ($cliente_id_filtro==$c['id']) ? 'selected' : '' ?>><?= $c['codigo'].' - '.$c['nome'] ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-2 label-old">Técnico:</div>
          <div class="col-10">
            <select name="tecnico_id" class="form-control form-control-sm" <?= $disabled ?>>
              <option value="">-- Selecione --</option>
              <?php foreach($listaTecnicos as $t): ?><option value="<?= $t['id'] ?>" <?= ($edit_tecnico==$t['id']) ? 'selected' : '' ?>><?= $t['codigo'].' - '.$t['nome'] ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-2 label-old">Serviço Solicitado:</div>
          <div class="col-10">
            <select name="servico1" class="form-control form-control-sm" <?= $disabled ?>>
              <option value="">-- Selecione --</option>
              <?php foreach($listaServicos as $s): ?><option value="<?= htmlspecialchars($s['descricao']) ?>" <?= ($edit_serv1==$s['descricao']) ? 'selected' : '' ?>><?= $s['codigo'].' - '.$s['descricao'] ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-2"></div>
          <div class="col-10">
            <select name="servico2" class="form-control form-control-sm" <?= $disabled ?>>
              <option value="">-- Selecione --</option>
              <?php foreach($listaServicos as $s): ?><option value="<?= htmlspecialchars($s['descricao']) ?>" <?= ($edit_serv2==$s['descricao']) ? 'selected' : '' ?>><?= $s['codigo'].' - '.$s['descricao'] ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-2 label-old">Data Serv.:</div>
          <div class="col-4"><input type="date" name="data_serv" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit_data ?>"></div>
          <div class="col-2 label-old">Obs. Pré-finalização:</div>
          <div class="col-4"><input name="obs_pre_finalizacao" class="form-control form-control-sm" <?= $readonly ?> value="<?= htmlspecialchars($edit_obs_pre) ?>"></div>
          <div class="col-2 label-old">Período:</div>
          <div class="col-4">
            <select name="periodo" class="form-control form-control-sm" <?= $disabled ?>>
              <option value="MANHA" <?= $edit_periodo=='MANHA' ? 'selected' : '' ?>>MANHA</option>
              <option value="TARDE" <?= $edit_periodo=='TARDE' ? 'selected' : '' ?>>TARDE</option>
              <option value="INTEGRAL" <?= $edit_periodo=='INTEGRAL' ? 'selected' : '' ?>>INTEGRAL</option>
            </select>
          </div>
          <div class="col-6"></div>
          <div class="col-2 label-old">Ficha de Atendimento:</div>
          <div class="col-4">
            <select name="ficha" class="form-control form-control-sm" <?= $disabled ?>>
              <option value="ASSISTENCIA" <?= $edit_ficha=='ASSISTENCIA' ? 'selected' : '' ?>>ASSISTENCIA</option>
              <option value="INSTALACAO" <?= $edit_ficha=='INSTALACAO' ? 'selected' : '' ?>>INSTALACAO</option>
              <option value="MANUTENCAO" <?= $edit_ficha=='MANUTENCAO' ? 'selected' : '' ?>>MANUTENCAO</option>
            </select>
          </div>
          <div class="col-2 label-old">Valor:</div>
          <div class="col-4"><input name="valor" class="form-control form-control-sm" <?= $readonly ?> value="<?= $edit_valor ?>"></div>
        </div>
        <div class="painel-bloco">
          <p class="titulo-faixa">Endereços</p>
          <div class="row g-1 align-items-center" style="font-size:11px;">
            <div class="col-2 text-end"><b>Código:</b></div><div class="col-2"><?= $enderecoSel['codigo'] ?? '' ?></div>
            <div class="col-2 text-end"><b>End.:</b></div><div class="col-6"><?= htmlspecialchars(trim(($enderecoSel['endereco'] ?? '').', '.($enderecoSel['numero'] ?? '').' '.($enderecoSel['complemento'] ?? ''))) ?></div>
            <div class="col-2 text-end"><b>Bairro:</b></div><div class="col-4"><?= htmlspecialchars($enderecoSel['bairro'] ?? '') ?></div>
            <div class="col-2 text-end"><b>Cidade:</b></div><div class="col-3"><?= htmlspecialchars($enderecoSel['cidade'] ?? '') ?></div>
            <div class="col-12 d-flex gap-2 mt-1"><span><b>CEP:</b> <?= htmlspecialchars($enderecoSel['cep'] ?? '') ?></span><span><b>Contato:</b> <?= htmlspecialchars($enderecoSel['contato'] ?? '') ?></span></div>
          </div>
          <input type="hidden" name="endereco_id" value="<?= $enderecoSel['id'] ?? '' ?>">
          <input type="hidden" name="equipamento_id" value="<?= $edit_equip_id ?: ($equipamentosEnd[0]['id'] ?? '') ?>">
          <p class="dica mb-1">Duplo clique no endereço para visualizar somente os atendimentos dele.</p>
          <div class="grid-old grid-pequena border">
            <table><thead><tr><th>Código</th><th>Endereço</th><th>Cidade</th></tr></thead><tbody>
              <?php foreach($enderecosCliente as $e): ?>
              <tr class="<?= ($enderecoSel && $enderecoSel['id']==$e['id']) ? 'linha-selecionada' : '' ?>" ondblclick="location.href='atendimentos.php?cliente_id=<?= $cliente_id_filtro ?>&editar=<?= $edit_id ?>&ver_atend_endereco=<?= $e['id'] ?><?= $qs_todos ?>'" onclick="location.href='atendimentos.php?cliente_id=<?= $cliente_id_filtro ?>&editar=<?= $edit_id ?>&endereco_id_sel=<?= $e['id'] ?><?= $qs_todos ?>'">
                <td><?= $e['codigo'] ?></td><td><?= htmlspecialchars($e['endereco'].', '.$e['numero']) ?></td><td><?= htmlspecialchars($e['cidade']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if(!$enderecosCliente): ?><tr><td colspan="3" class="text-center text-muted">Nenhum endereço. Selecione um cliente.</td></tr><?php endif; ?>
            </tbody></table>
          </div>
        </div>
        <div class="painel-bloco">
          <p class="titulo-faixa">Equipamentos do cliente no endereço acima:</p>
          <p class="dica mb-1">Duplo clique na coluna Ficha para alternar S &lt;-&gt; N.</p>
          <div class="grid-old grid-pequena border">
            <table><thead><tr><th>Código</th><th>Equipamento</th><th>Ficha</th><th>Cód.End.</th></tr></thead><tbody>
              <?php foreach($equipamentosEnd as $eq): ?>
              <tr class="<?= ($edit_equip_id==$eq['id']) ? 'linha-equip-atendido' : '' ?>" onclick="document.querySelector('input[name=equipamento_id]').value='<?= $eq['id'] ?>'">
                <td><?= $eq['codigo'] ?></td><td><?= htmlspecialchars($eq['equipamento']) ?></td><td class="cel-ficha" title="Duplo clique para alternar S / N" ondblclick="event.stopPropagation(); location.href='atendimentos.php?toggle_ficha_equip=<?= $eq['id'] ?>&cliente_id=<?= $cliente_id_filtro ?>&editar=<?= $edit_id ?>&modo=<?= $modo ?>&endereco_id_sel=<?= $enderecoSel['id'] ?? '' ?><?= $endereco_filtro ? '&endereco_filtro='.$endereco_filtro : '' ?><?= $qs_todos ?>'"><?= $eq['ficha'] ?? 'N' ?></td><td><?= $eq['cod_end'] ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if(!$equipamentosEnd): ?><tr><td colspan="4" class="text-center text-muted">Nenhum equipamento neste endereço.</td></tr><?php endif; ?>
            </tbody></table>
          </div>
          <p class="dica mt-1 mb-0">Legenda: <span style="background:#86EFAC; padding:0 6px;">Equipamento(s) atendido(s) pela OS <?= $edit_os ?></span></p>
        </div>
        <div class="row g-2 mt-1 flex-shrink-0">
          <div class="col-md-7 border box-edicao">
            <small>Edição</small><br>
            <?php if($modo==='incluir'): ?><a href="atendimentos.php?cliente_id=<?= $cliente_id_filtro ?><?= $qs_todos ?>" class="btn-old btn-laranja">Cancelar</a>
            <?php else: ?><a href="atendimentos.php?cliente_id=<?= $cliente_id_filtro ?>&modo=incluir<?= $qs_todos ?>" class="btn-old btn-verde">Incluir</a><?php endif; ?>
            <?php if($edit_id): ?><a href="atendimentos.php?editar=<?= $edit_id ?>&cliente_id=<?= $cliente_id_filtro ?>&modo=editar<?= $qs_todos ?>" class="btn-old">Editar</a><?php endif; ?>
            <button type="submit" name="salvar_atendimento" class="btn-old" <?= $editando ? '' : 'disabled' ?>>Salvar</button>
            <button type="button" class="btn-old btn-vermelho" <?= $edit_id ? '' : 'disabled' ?> onclick="pedirConfirmacao('Excluir este atendimento?', '?excluir=<?= $edit_id ?>&cliente_id=<?= $cliente_id_filtro ?><?= $qs_todos ?>')">Excluir</button>
          </div>
          <div class="col-md-5 border box-edicao">
            <small>Ação</small><br>
            <?php if($edit_id): ?>
              <button type="button" class="btn-old btn-laranja" onclick="<?php if($ja_baixada): ?>mostrarAviso('Esta OS já está baixada.');<?php elseif($ja_cancelada): ?>mostrarAviso('Esta OS já está cancelada!');<?php else: ?>pedirConfirmacao('Finalizar / Baixar esta OS?', '?finalizar=<?= $edit_id ?>&cliente_id=<?= $cliente_id_filtro ?><?= $qs_todos ?>')<?php endif; ?>">Finalizar</button>
              <button type="button" class="btn-old" onclick="pedirConfirmacao('Cancelar esta OS?', '?cancelar=<?= $edit_id ?>&cliente_id=<?= $cliente_id_filtro ?><?= $qs_todos ?>')">Cancelar</button>
            <?php else: ?>
              <span class="btn-old" style="opacity:.5;">Finalizar</span>
              <span class="btn-old" style="opacity:.5;">Cancelar</span>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card card-atend">
      <div class="painel-bloco" style="flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden;">
        <div class="d-flex justify-content-between align-items-center flex-shrink-0">
          <h6 class="fw-bold mb-0">Lista de Atendimentos <?= $mostrar_todos ? '' : '<small class="text-muted fw-normal">- ' . $ano_corrente . '</small>' ?></h6>
          <label class="small"><input type="checkbox" <?= $mostrar_todos ? 'checked' : '' ?> onchange="location.href='atendimentos.php?cliente_id=<?= $cliente_id_filtro ?>&editar=<?= $edit_id ?><?= $endereco_filtro ? '&endereco_filtro='.$endereco_filtro : '' ?>'+(this.checked ? '&todos=1' : '')"> Mostrar todos os anos</label>
        </div>
        <div class="grid-old border mt-1" style="max-height:160px;">
          <table><thead><tr><th>OS</th><th>Dt Atendimento</th><th>Tipo Serv.</th><th>Valor</th><th>Status</th><th>Dt Status</th></tr></thead><tbody>
            <?php foreach($listaAtendimentos as $a): ?>
            <tr class="<?= ($edit_id==$a['id']) ? 'linha-selecionada' : ($a['status']=='BAIXADO' ? 'linha-baixado' : 'linha-aberto') ?>" onclick="location.href='?editar=<?= $a['id'] ?>&cliente_id=<?= $a['cliente_id'] ?><?= $qs_todos ?>'">
              <td><?= $a['os'] ?></td><td><?= $a['data_serv'] ? date('d/m/Y', strtotime($a['data_serv'])) : '' ?></td><td><?= htmlspecialchars($a['servico1'] ?? '') ?></td><td style="text-align:right;"><?= number_format($a['valor'],2,',','.') ?></td><td><?= $a['status'] ?></td><td><?= $a['data_serv'] ? date('d/m/Y', strtotime($a['data_serv'])) : '' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(!$listaAtendimentos): ?><tr><td colspan="6" class="text-center text-muted"><?= $mostrar_todos ? 'Nenhum atendimento. Clique em Incluir.' : 'Nenhum atendimento em ' . $ano_corrente . '. Marque "Mostrar todos os anos" para ver os anos anteriores.' ?></td></tr><?php endif; ?>
          </tbody></table>
        </div>
      </div>
      <div class="painel-bloco <?= $em_finalizacao ? 'painel-finalizando' : '' ?>">
        <p class="titulo-faixa">Dados de Finalização <?= $em_finalizacao ? '<span class="badge bg-warning text-dark">Escolha abaixo e clique em Salvar Finalização</span>' : '' ?></p>
        <div class="row g-1 align-items-center" style="font-size:11px;">
          <div class="col-3 text-end"><b>Garantia / Cobrança:</b></div>
          <div class="col-4">
            <select id="garantiaCobranca" form="formAtend" name="garantia_cobranca" class="form-control form-control-sm" <?= $disabled_garantia ?> onchange="atualizarCobranca()">
              <?php foreach($listaGarantia as $g): ?><option value="<?= $g ?>" <?= $edit_garantia==$g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?>
              <?php if($edit_garantia && !in_array($edit_garantia, $listaGarantia)): ?><option value="<?= htmlspecialchars($edit_garantia) ?>" selected><?= htmlspecialchars($edit_garantia) ?></option><?php endif; ?>
            </select>
          </div>
          <div class="col-2 text-end"><b>Cobrança:</b></div>
          <div class="col-3">
            <select id="formaCobranca" form="formAtend" name="forma_cobranca" class="form-control form-control-sm" <?= $disabled_cobranca ?>>
              <option value="">-- Selecione --</option>
              <?php foreach($listaFormaCobranca as $f): ?><option value="<?= $f ?>" <?= $edit_cobranca==$f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?>
              <?php if($edit_cobranca && !in_array($edit_cobranca, $listaFormaCobranca)): ?><option value="<?= htmlspecialchars($edit_cobranca) ?>" selected><?= htmlspecialchars($edit_cobranca) ?></option><?php endif; ?>
            </select>
          </div>
        </div>
        <?php if($em_finalizacao): ?>
        <div class="d-flex gap-2 justify-content-end mt-2">
          <a href="atendimentos.php?editar=<?= $edit_id ?>&cliente_id=<?= $cliente_id_filtro ?><?= $qs_todos ?>" class="btn-old">Cancelar Finalização</a>
          <button type="button" class="btn-old btn-laranja" onclick="salvarFinalizacao()">Salvar Finalização</button>
        </div>
        <?php endif; ?>
      </div>
      <div class="painel-bloco <?= $em_finalizacao ? 'painel-finalizando' : 'painel-pecas-travado' ?>">
        <p class="titulo-faixa">Peças <?= $em_finalizacao ? '<span class="badge bg-warning text-dark">Preencha durante a finalização</span>' : '<span class="badge bg-secondary">Somente durante a finalização</span>' ?></p>
        <div class="row g-1 align-items-center">
          <div class="col-2 label-old">Descrição:</div>
          <div class="col-5">
            <select id="descricaoPeca" class="form-control form-control-sm" <?= $disabled_pecas ?>>
              <option value="">-- Selecione o equipamento --</option>
              <?php foreach($listaEquipamentosTodos as $eqT): $nomeEqT = trim(($eqT['equipamento'] ?? '') . ' ' . ($eqT['marca'] ?? '') . ' ' . ($eqT['modelo'] ?? '')); ?>
                <option value="<?= htmlspecialchars($nomeEqT) ?>"><?= htmlspecialchars(($eqT['codigo'] ?? '') . ' - ' . $nomeEqT) ?></option>
              <?php endforeach; ?>
              <?php if(!$listaEquipamentosTodos): ?><?php foreach($listaMateriais as $m): ?><option value="<?= htmlspecialchars($m['descricao']) ?>"><?= htmlspecialchars($m['descricao']) ?></option><?php endforeach; ?><?php endif; ?>
            </select>
          </div>
          <div class="col-2 label-old">Valor:</div><div class="col-3"><input id="valorPeca" type="number" step="0.01" min="0" class="form-control form-control-sm" <?= $disabled_pecas ?>></div>
          <div class="col-2"></div><div class="col-5"></div>
          <div class="col-2 label-old">Qtd:</div><div class="col-2"><input id="qtdPeca" type="number" min="1" value="1" class="form-control form-control-sm" <?= $disabled_pecas ?>></div>
          <div class="col-1"><button type="button" class="btn btn-success btn-sm" <?= $disabled_pecas ?> onclick="addPeca()">+</button></div>
        </div>
        <div class="grid-old border mt-1" style="max-height:100px;">
          <table><thead><tr><th>Peça</th><th>Valor</th><th>Qtd.</th><th></th></tr></thead><tbody>
            <?php foreach($pecasAtendimento as $pp): ?><tr><td><?= htmlspecialchars($pp['descricao']) ?></td><td><?= number_format($pp['valor'],2,',','.') ?></td><td><?= $pp['qtd'] ?></td><td><?php if($em_finalizacao): ?><a href="?excluir_peca=<?= $pp['id'] ?>&atendimento_id=<?= $edit_id ?>&cliente_id=<?= $cliente_id_filtro ?><?= $qs_todos ?>&finalizando=1" class="btn btn-danger btn-sm py-0">-</a><?php endif; ?></td></tr><?php endforeach; ?>
            <?php if(!$pecasAtendimento): ?><tr><td colspan="4" class="text-center text-muted">Nenhuma peça.</td></tr><?php endif; ?>
          </tbody></table>
        </div>
        <p class="text-end small mb-0 mt-1">Valor total dos equipamentos inseridos no atendimento: <b>R$ <?= number_format($totalPecas,2,',','.') ?></b></p>
      </div>
      <div class="painel-bloco">
        <div class="row g-2">
          <div class="col-8">
            <label class="small">Obs.:</label>
            <textarea form="formAtend" name="obs" class="form-control form-control-sm" rows="2" <?= $readonly ?>><?= htmlspecialchars($edit_obs) ?></textarea>
          </div>
          <div class="col-4 border p-2">
            <small><b>Fichas</b></small><br>
            <label class="small"><input type="radio" name="ficha_tipo" value="residencia"> Instalação de Resid.</label><br>
            <label class="small"><input type="radio" name="ficha_tipo" value="piscina"> Instalação de Piscina</label><br>
            <label class="small"><input type="radio" name="ficha_tipo" value="assistencia" checked> Assistência Técnica</label><br>
            <label class="small"><input type="checkbox" id="mostrarTelefone"> Mostrar Telefone</label><br>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 mt-1 justify-content-end flex-shrink-0">
        <a href="clientes.php?editar=<?= $cliente_id_filtro ?>" class="btn-old">Voltar p/ Cliente</a>
        <button type="button" class="btn-old" onclick="imprimirFicha()">Imprimir</button>
        <button type="button" class="btn-old" onclick="mostrarAviso('Opções: Imprimir Ficha, Enviar por E-mail, Gerar PDF')">Opções</button>
      </div>
    </div>
  </div>
</div>
</div>
<?php if($enderecoModal): ?>
<div class="overlay-end" onclick="if(event.target===this) location.href='atendimentos.php?cliente_id=<?= $cliente_id_filtro ?>&editar=<?= $edit_id ?><?= $qs_todos ?>'">
  <div class="popup-end">
    <div class="popup-end-header"><span>XSOL Aquecedores - Atendimentos do Endereço</span><button type="button" class="btn-fechar" onclick="location.href='atendimentos.php?cliente_id=<?= $cliente_id_filtro ?>&editar=<?= $edit_id ?><?= $qs_todos ?>'">X</button></div>
    <div class="popup-end-body">
      <div class="linha-info"><b>Código:</b> <?= htmlspecialchars($enderecoModal['codigo'] ?? '') ?> &nbsp;&nbsp;&nbsp;&nbsp; <b>End.:</b> <?= htmlspecialchars(trim(($enderecoModal['endereco'] ?? '').', '.($enderecoModal['numero'] ?? '').' '.($enderecoModal['complemento'] ?? ''))) ?></div>
      <div class="linha-info"><b>Bairro:</b> <?= htmlspecialchars($enderecoModal['bairro'] ?? '') ?> &nbsp;&nbsp;&nbsp;&nbsp; <b>Cidade:</b> <?= htmlspecialchars($enderecoModal['cidade'] ?? '') ?> &nbsp;&nbsp;&nbsp;&nbsp; <b>UF:</b> <?= htmlspecialchars($enderecoModal['uf'] ?? $enderecoModal['estado'] ?? '') ?></div>
      <p style="font-size:12px; margin:10px 0 4px;"><b>Atendimentos já realizados no endereço acima:</b></p>
      <div class="grid-old border" style="max-height:280px;">
        <table><thead><tr><th>OS</th><th>Dt Atendimento</th><th>Tipo Serv.</th><th>Valor</th><th>Status</th><th>Dt Status</th></tr></thead><tbody>
          <?php foreach($listaAtendEndModal as $ae): ?>
          <tr class="<?= ($ae['status']=='BAIXADO' ? 'linha-baixado' : 'linha-aberto') ?>" onclick="location.href='atendimentos.php?editar=<?= $ae['id'] ?>&cliente_id=<?= $ae['cliente_id'] ?>&endereco_id_sel=<?= $enderecoModal['id'] ?><?= $qs_todos ?>'">
            <td><?= $ae['os'] ?></td><td><?= $ae['data_serv'] ? date('d/m/Y', strtotime($ae['data_serv'])) : '' ?></td><td><?= htmlspecialchars($ae['servico1'] ?? '') ?></td><td style="text-align:right;"><?= number_format($ae['valor'],2,',','.') ?></td><td><?= $ae['status'] ?></td><td><?= $ae['data_serv'] ? date('d/m/Y', strtotime($ae['data_serv'])) : '' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(!$listaAtendEndModal): ?><tr><td colspan="6" class="text-center text-muted">Nenhum atendimento realizado neste endereço.</td></tr><?php endif; ?>
        </tbody></table>
      </div>
      <div class="text-end mt-2"><button type="button" class="btn-old" onclick="location.href='atendimentos.php?cliente_id=<?= $cliente_id_filtro ?>&editar=<?= $edit_id ?><?= $qs_todos ?>'">Fechar</button></div>
    </div>
  </div>
</div>
<?php endif; ?>
<form id="formFinalizacao" method="POST" style="display:none;">
  <input type="hidden" name="atendimento_id_fin" value="<?= $edit_id ?>">
  <input type="hidden" name="cliente_id" value="<?= $cliente_id_filtro ?>">
  <input type="hidden" name="garantia_cobranca" id="finGarantia">
  <input type="hidden" name="forma_cobranca" id="finForma">
  <input type="hidden" name="salvar_finalizacao" value="1">
</form>
<form id="formPecaHidden" method="POST" style="display:none;">
  <input type="hidden" name="atendimento_id_peca" value="<?= $edit_id ?>">
  <input type="hidden" name="cliente_id_peca" value="<?= $cliente_id_filtro ?>">
  <input type="hidden" name="descricao_peca" id="hiddenDescricaoPeca">
  <input type="hidden" name="valor_peca" id="hiddenValorPeca">
  <input type="hidden" name="qtd_peca" id="hiddenQtdPeca">
  <input type="hidden" name="finalizando" value="<?= $em_finalizacao ? '1' : '0' ?>">
  <input type="hidden" name="add_peca" value="1">
</form>
<div class="modal fade" id="modalAviso" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="mb-0">Aviso</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="textoAviso" class="mb-0"></p></div><div class="modal-footer"><button type="button" class="btn btn-sol" data-bs-dismiss="modal">OK</button></div></div></div></div>
<div class="modal fade" id="modalConfirmacao" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="mb-0">Confirmação</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="textoConfirmacao" class="mb-0"></p></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-sol" id="btnConfirmarAcao">Sim</button></div></div></div></div>
<script>
let urlConfirmacao = '';
function atualizarCobranca(){
  var g = document.getElementById('garantiaCobranca');
  var c = document.getElementById('formaCobranca');
  if(!g || !c) return;
  if(g.value === 'COBRANÇA'){ c.disabled = false; }
  else { c.disabled = true; c.value = ''; }
}
function salvarFinalizacao(){
  var g = document.getElementById('garantiaCobranca');
  var c = document.getElementById('formaCobranca');
  if(!g || !g.value){ mostrarAviso('Selecione Garantia / Cobrança.'); return; }
  if(g.value === 'COBRANÇA' && (!c || !c.value)){ mostrarAviso('Selecione a forma de Cobrança.'); return; }
  document.getElementById('finGarantia').value = g.value;
  document.getElementById('finForma').value = (g.value === 'COBRANÇA' && c) ? c.value : '';
  document.getElementById('formFinalizacao').submit();
}
function addPeca(){
  <?php if(!$em_finalizacao): ?>mostrarAviso('Somente durante a finalização é possível inserir equipamentos. Clique em Finalizar e confirme com Sim.'); return;<?php endif; ?>
  let desc = document.getElementById('descricaoPeca').value.trim();
  if(!desc){ mostrarAviso('Selecione o equipamento.'); return; }
  <?php if(!$edit_id): ?>mostrarAviso('Salve o atendimento primeiro para adicionar equipamentos.'); return;<?php endif; ?>
  document.getElementById('hiddenDescricaoPeca').value = desc;
  document.getElementById('hiddenValorPeca').value = document.getElementById('valorPeca').value;
  document.getElementById('hiddenQtdPeca').value = document.getElementById('qtdPeca').value;
  document.getElementById('formPecaHidden').submit();
}
function imprimirFicha(){
  <?php if(!$edit_id): ?>mostrarAviso('Selecione um atendimento para imprimir a ficha.'); return;<?php endif; ?>
  var radio = document.querySelector('input[name="ficha_tipo"]:checked');
  var tipo = radio ? radio.value : 'assistencia';
  var mostrarTel = document.getElementById('mostrarTelefone') && document.getElementById('mostrarTelefone').checked ? 1 : 0;
  window.open('atendimentos.php?ficha=' + tipo + '&id=<?= $edit_id ?>&mostrar_tel=' + mostrarTel, '_blank');
}
function mostrarAviso(mensagem){ document.getElementById('textoAviso').textContent = mensagem; new bootstrap.Modal(document.getElementById('modalAviso')).show(); }
function pedirConfirmacao(mensagem, url){ document.getElementById('textoConfirmacao').textContent = mensagem; urlConfirmacao = url; new bootstrap.Modal(document.getElementById('modalConfirmacao')).show(); }
document.getElementById('btnConfirmarAcao').addEventListener('click', function(){ if(urlConfirmacao) location.href = urlConfirmacao; });
<?php if(isset($_GET['ja_baixado'])): ?>
document.addEventListener('DOMContentLoaded', function(){ mostrarAviso('Esta OS já está baixada. Não há mais o que baixar.'); });
<?php endif; ?>
<?php if(isset($_GET['ja_cancelado'])): ?>
document.addEventListener('DOMContentLoaded', function(){ mostrarAviso('A OS já está cancelada!'); });
<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../footer.php'; ?>