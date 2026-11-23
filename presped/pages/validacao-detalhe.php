<?php
// C:\xampp2\htdocs\presped\pages\validacao-detalhe.php
// CORRIGIDO: exige login + trava por escritorio_id + mostra totais + volta do lote
require_once '../config.php';

if (!estaLogado()) {
  header("Location: ../index.php?aba=login");
  exit;
}
$escritorio_id = $_SESSION['escritorio_id'] ?? 0;

require_once '../includes/header.php';

$validacao_id = (int)($_GET['id'] ?? 0);
$erros = [];
$validacao = null;
$totais = null;

$origem = $_GET['origem'] ?? 'dashboard';
$competencia_volta = $_GET['competencia'] ?? '';

if ($validacao_id) {
  // CORRIGIDO: filtra por escritorio_id via empresas. Sem isso qualquer ?id= abria validação de outro escritório.
  $stmt = $pdo->prepare("SELECT v.*, a.nome_arquivo, a.tipo, a.competencia, e.razao_social, e.cnpj FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id JOIN empresas e ON e.id = v.empresa_id WHERE v.id = ? AND e.escritorio_id = ?");
  $stmt->execute([$validacao_id, $escritorio_id]);
  $validacao = $stmt->fetch();

  if ($validacao) {
    if (empty($competencia_volta)) $competencia_volta = $validacao['competencia'];
    $stmt = $pdo->prepare("SELECT * FROM validacoes_erros WHERE validacao_id = ? ORDER BY linha ASC");
    $stmt->execute([$validacao_id]);
    $erros = $stmt->fetchAll();

    // NOVO: busca os totais salvos pelo lote / upload para conferir com o Simulador
    $stmt = $pdo->prepare("SELECT * FROM validacoes_totais WHERE validacao_id = ? LIMIT 1");
    $stmt->execute([$validacao_id]);
    $totais = $stmt->fetch();
  }
}

if (empty($competencia_volta)) $competencia_volta = date('Y-m');

$voltar = [
  'historico'  => ['url' => 'historico.php', 'texto' => 'Voltar para Histórico'],
  'simulador'  => ['url' => 'simulador-dctfweb.php?competencia=' . urlencode($competencia_volta), 'texto' => 'Voltar para o Simulador DCTFWeb'],
  'lote'       => ['url' => 'validacao-lote.php', 'texto' => 'Voltar para Validação em lote'],
  'dashboard'  => ['url' => 'dashboard.php?competencia=' . urlencode($competencia_volta), 'texto' => 'Voltar para Dashboard'],
];
$link_voltar = $voltar[$origem] ?? $voltar['dashboard'];

$is_xml = $validacao && in_array($validacao['tipo'], ['EFD-Reinf', 'eSocial']);
$erros_reais = array_values(array_filter($erros, fn($e) => ($e['tipo'] ?? '') !== 'AVISO'));
$avisos = array_values(array_filter($erros, fn($e) => ($e['tipo'] ?? '') === 'AVISO'));

function comoCorrigir($tipo, $e) {
  $nota = htmlspecialchars($e['nota'] ?? '');
  $dif = number_format($e['diferenca'] ?? 0, 2, ',', '.');
  $vl_c100 = number_format($e['vl_c100'] ?? 0, 2, ',', '.');
  $soma = number_format($e['soma_c170'] ?? 0, 2, ',', '.');
  $mensagem = $e['mensagem'] ?? '';
  if (stripos($tipo, 'XML') !== false || stripos($tipo, 'AVISO') !== false) {
    if (stripos($mensagem, 'CNPJ') !== false) {
      return ['titulo' => 'CNPJ do XML diferente da empresa - ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> " . htmlspecialchars($mensagem) . "</p><ol><li>Confira qual empresa você selecionou na tela de upload</li><li>Abra o XML no Bloco de Notas e procure por <code>&lt;nrInsc&gt;</code></li><li>Veja se o CNPJ de lá é o mesmo da empresa selecionada</li><li>Se gerou o XML da empresa errada, gere de novo no seu sistema fiscal com a empresa certa</li><li>Valide o novo arquivo aqui</li></ol><div class='alert alert-warning mb-0'>Se transmitir com CNPJ errado, a Receita rejeita na hora.</div>"];
    }
    if (stripos($mensagem, 'dmDev') !== false) {
      return ['titulo' => 'S-1200 sem dmDev - ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> O evento S-1200 (remuneração) precisa ter o bloco <code>&lt;dmDev&gt;</code> com os valores do trabalhador.</p><ol><li>Abra a folha de pagamento no seu sistema</li><li>Confira se o trabalhador tem rubricas lançadas na competência</li><li>Se a folha está zerada, o sistema pode ter gerado o S-1200 vazio</li><li>Reprocesse a folha e gere o eSocial de novo</li><li>Valide o novo arquivo aqui</li></ol>"];
    }
    if (stripos($mensagem, 'ideEvento') !== false) {
      return ['titulo' => 'eSocial sem ideEvento - ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> Todo evento do eSocial precisa ter o bloco <code>&lt;ideEvento&gt;</code> com ambiente, processo de emissão e versão.</p><ol><li>Esse erro normalmente é o sistema que gerou o XML incompleto</li><li>Atualize seu sistema de folha / fiscal para a versão mais recente do leiaute</li><li>Gere o evento de novo</li><li>Valide o novo arquivo aqui</li></ol>"];
    }
    if (stripos($mensagem, 'perApur') !== false) {
      return ['titulo' => 'Reinf sem perApur - ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> O Reinf precisa ter o <code>&lt;perApur&gt;</code> com o período, ex: <code>2026-11</code>.</p><ol><li>Abra o Reinf no seu sistema</li><li>Confira se a competência foi informada na geração</li><li>Gere de novo com a competência certa</li><li>Valide o novo arquivo aqui</li></ol>"];
    }
    if (stripos($mensagem, 'assinatura') !== false) {
      return ['titulo' => 'Arquivo sem assinatura digital', 'html' => "<p><strong>O que aconteceu:</strong> O XML foi gerado mas ainda não foi assinado com o certificado digital.</p><p>Isso é só um aviso porque você ainda está na fase de <strong>validação</strong>. A validação passou, você pode conferir o resto do arquivo assim.</p><p>Mas na hora de <strong>transmitir</strong>, sem assinatura a Receita rejeita o evento na hora.</p><ol><li>No seu sistema, clique em <strong>Assinar</strong> antes de transmitir</li><li>Use o certificado A1 ou A3 da empresa</li><li>Depois de assinado, valide de novo se quiser confirmar</li></ol><div class='alert alert-warning mb-0'>Sem assinatura a Receita não aceita a transmissão. Não adianta tentar enviar assim.</div>"];
    }
    return ['titulo' => 'Erro no XML - ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> " . htmlspecialchars($mensagem) . "</p><ol><li>Abra o XML no Bloco de Notas</li><li>Confira a mensagem de erro ao lado</li><li>Corrija no seu sistema fiscal / de folha</li><li>Gere o XML de novo e valide novamente aqui</li></ol>"];
  }
  if (stripos($tipo, 'C100 sem C170') !== false) {
    return ['titulo' => 'C100 sem C170 - Nota ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> A nota <strong>{$nota}</strong> está na capa (C100) com R$ {$vl_c100}, mas não tem nenhum item (C170) escriturado.</p><ol><li>Abra seu sistema fiscal / emissor de NF-e</li><li>Abra a nota <strong>{$nota}</strong></li><li>Confira se os itens da nota foram lançados</li><li>Se o item não apareceu no SPED, marque para escriturar os itens no EFD</li><li>Gere o SPED de novo e valide novamente aqui</li></ol><div class='alert alert-warning mb-0'>Sem o C170, a SEFAZ entende que a nota foi emitida sem mercadoria.</div>"];
  }
  if (stripos($tipo, 'C100 sem C190') !== false) {
    return ['titulo' => 'C100 sem C190 - Nota ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> A nota <strong>{$nota}</strong> não tem resumo por CFOP (C190). O sistema gerou a capa, mas não gerou o totalizador.</p><ol><li>Abra seu sistema fiscal</li><li>Vá em <strong>Apuração / Gerar EFD ICMS/IPI</strong></li><li>Marque a opção <strong>Gerar C190 - Resumo por CFOP</strong></li><li>Reprocesse a competência toda, não só essa nota</li><li>Gere o SPED de novo e valide novamente aqui</li></ol><div class='alert alert-warning mb-0'>Sem C190 o arquivo é rejeitado pela SEFAZ com erro 999.</div>"];
  }
  if (stripos($tipo, 'C100 x C170') !== false) {
    return ['titulo' => 'Divergência C100 x C170 - Nota ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> A capa (C100) está com R$ {$vl_c100}, mas a soma dos itens (C170) está com R$ {$soma}. Diferença de <strong>R$ {$dif}</strong>.</p><ol><li>Abra a nota <strong>{$nota}</strong> no seu sistema</li><li>Some os valores dos itens manualmente</li><li>Confira: tem desconto, frete ou IPI que entrou no total mas não entrou no item? Ou o contrário?</li><li>Corrija o valor do item ou o valor total da nota</li><li>Gere o SPED de novo e valide novamente aqui</li></ol>"];
  }
  return ['titulo' => 'Divergência C100 x C190 - Nota ' . $nota, 'html' => "<p><strong>O que aconteceu:</strong> A capa (C100) está com R$ {$vl_c100}, mas a soma do C190 (VL_OPR) está com R$ {$soma}. Diferença de <strong>R$ {$dif}</strong>.</p><ol><li>Abra a nota <strong>{$nota}</strong> e confira o CFOP dela</li><li>Veja se a nota tem 2 CFOPs diferentes (ex: venda + remessa) e só 1 foi para o C190</li><li>Confira se o VL_OPR do C190 está somando todos os itens daquele CFOP</li><li>Reprocesse o EFD no seu sistema</li><li>Gere o SPED de novo e valide novamente aqui</li></ol>"];
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <a href="<?= $link_voltar['url'] ?>" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> <?= $link_voltar['texto'] ?></a>
    <h2 class="fw-bold mb-0 mt-1">Validação - <?= $validacao ? htmlspecialchars($validacao['tipo']) : 'EFD ICMS/IPI' ?></h2>
    <p class="text-muted mb-0">
      <?= $validacao ? htmlspecialchars($validacao['razao_social']) . ' • CNPJ ' . htmlspecialchars($validacao['cnpj']) . ' • Competência ' . htmlspecialchars($validacao['competencia']) : 'Validação não encontrada' ?>
    </p>
  </div>
  <?php if ($validacao): ?>
    <?php if ($validacao['total_erros'] > 0): ?>
      <span class="badge bg-danger fs-6"><i class="bi bi-x-circle"></i> <?= $validacao['total_erros'] ?> erros encontrados</span>
      <?php if (count($avisos) > 0): ?>
        <span class="badge bg-warning text-dark fs-6"><i class="bi bi-exclamation-triangle"></i> <?= count($avisos) ?> aviso(s)</span>
      <?php endif; ?>
    <?php else: ?>
      <?php if ($is_xml): ?>
        <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> Sem erros - XML válido</span>
      <?php else: ?>
        <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> Sem erros - C100 x C170 x C190 OK</span>
      <?php endif; ?>
      <?php if (count($avisos) > 0): ?>
        <span class="badge bg-warning text-dark fs-6"><i class="bi bi-exclamation-triangle"></i> <?= count($avisos) ?> aviso(s)</span>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php if (!$validacao): ?>
  <div class="alert alert-warning">Validação não encontrada ou de outro escritório. Volte para o Dashboard e clique em uma validação.</div>
<?php elseif ($validacao['total_erros'] == 0): ?>
  <div class="alert alert-success d-flex gap-3 rounded-4">
    <i class="bi bi-check-circle fs-3"></i>
    <div>
      <strong>Arquivo validado com sucesso!</strong><br>
      <?php if ($is_xml): ?>
        XML válido - <?= htmlspecialchars($validacao['tipo']) ?> sem erros estruturais.<br>
      <?php else: ?>
        Todos os C100 batem com a soma dos C170 e com a soma dos C190.<br>
      <?php endif; ?>
      <small>Arquivo: <?= htmlspecialchars($validacao['nome_arquivo']) ?></small>
    </div>
  </div>
  <?php if (count($avisos) > 0): ?>
    <div class="alert alert-warning d-flex gap-3 rounded-4">
      <i class="bi bi-exclamation-triangle fs-3"></i>
      <div>
        <strong><?= count($avisos) ?> aviso(s) - não bloqueiam a validação, mas bloqueiam a transmissão:</strong>
        <ul class="mb-0 mt-2">
          <?php foreach ($avisos as $a): ?>
            <li><?= htmlspecialchars($a['mensagem']) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>
<?php elseif (!empty($erros_reais)): ?>
  <div class="alert alert-danger d-flex gap-3 rounded-4">
    <i class="bi bi-exclamation-triangle fs-3"></i>
    <div>
      <strong>Divergência encontrada</strong><br>
      <?= htmlspecialchars($validacao['resumo'] ?? 'Confira as notas abaixo.') ?><br>
      <small>Arquivo: <?= htmlspecialchars($validacao['nome_arquivo']) ?></small>
    </div>
  </div>
  <div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <span class="fw-bold"><i class="bi bi-receipt"></i> <?= $is_xml ? 'Erros no XML' : 'Notas com divergência' ?></span>
      <span class="badge bg-danger"><?= count($erros_reais) ?> <?= $is_xml ? 'erro(s)' : 'notas' ?></span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th><?= $is_xml ? 'Evento' : 'Nota' ?></th>
            <th><?= $is_xml ? 'Competência' : 'Série / Data' ?></th>
            <?php if (!$is_xml): ?>
              <th>Valor C100</th>
              <th>Soma conferida</th>
              <th>Diferença</th>
              <th>Linha</th>
            <?php endif; ?>
            <th>Tipo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($erros_reais as $idx => $e): ?>
          <?php $eh_c190 = stripos($e['tipo'] ?? '', 'C190') !== false; ?>
          <?php $guia = comoCorrigir($e['tipo'] ?? '', $e); ?>
          <tr>
            <td class="fw-bold"><?= $is_xml ? htmlspecialchars($e['nota']) : 'Nota ' . htmlspecialchars($e['nota']) ?></td>
            <td>
              <?php if ($is_xml): ?>
                <?= htmlspecialchars($e['data_doc']) ?>
              <?php else: ?>
                Série <?= htmlspecialchars($e['serie']) ?><br><small class="text-muted"><?= htmlspecialchars($e['data_doc']) ?></small>
              <?php endif; ?>
            </td>
            <?php if (!$is_xml): ?>
              <td>R$ <?= number_format($e['vl_c100'], 2, ',', '.') ?></td>
              <td class="text-danger">R$ <?= number_format($e['soma_c170'], 2, ',', '.') ?> <br><small class="text-muted"><?= $eh_c190 ? '(C190 - VL_OPR)' : '(C170 - VL_ITEM)' ?></small></td>
              <td class="fw-bold text-danger">R$ <?= number_format($e['diferenca'], 2, ',', '.') ?></td>
              <td><code><?= $e['linha'] ?></code></td>
            <?php endif; ?>
            <td><span class="badge <?= $eh_c190 ? 'bg-warning text-dark' : 'bg-danger' ?>"><?= htmlspecialchars($e['tipo']) ?></span><br><small><?= htmlspecialchars($e['mensagem']) ?></small></td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#modalCorrigir<?= $idx ?>">
                <i class="bi bi-question-circle"></i> Como corrigir
              </button>
            </td>
          </tr>
          <div class="modal fade" id="modalCorrigir<?= $idx ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content rounded-4">
                <div class="modal-header">
                  <h5 class="modal-title fw-bold"><?= $guia['titulo'] ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><?= $guia['html'] ?></div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                  <a href="upload.php?empresa_id=<?= $validacao['empresa_id'] ?>&tipo=<?= urlencode($validacao['tipo']) ?>&competencia=<?= urlencode($validacao['competencia']) ?>" class="btn btn-primary">Validar o novo arquivo</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if (count($avisos) > 0): ?>
  <div class="alert alert-warning d-flex gap-3 rounded-4 mb-3">
    <i class="bi bi-exclamation-triangle fs-3"></i>
    <div>
      <strong><?= count($avisos) ?> aviso(s) - não bloqueiam a validação, mas bloqueiam a transmissão:</strong>
      <ul class="mb-0 mt-2">
        <?php foreach ($avisos as $a): ?>
          <li><?= htmlspecialchars($a['mensagem']) ?> <button type="button" class="btn btn-sm btn-outline-warning text-dark ms-2" data-bs-toggle="modal" data-bs-target="#modalAviso<?= $a['id'] ?>">Como corrigir</button></li>
          <div class="modal fade" id="modalAviso<?= $a['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content rounded-4">
                <div class="modal-header">
                  <h5 class="modal-title fw-bold"><?= comoCorrigir($a['tipo'] ?? '', $a)['titulo'] ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><?= comoCorrigir($a['tipo'] ?? '', $a)['html'] ?></div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>
<?php endif; ?>

<?php if ($validacao && $totais): ?>
<div class="card border-0 shadow-sm rounded-4 mb-3">
  <div class="card-header bg-white"><span class="fw-bold"><i class="bi bi-currency-dollar"></i> Totais apurados neste arquivo - é o que o Simulador soma</span></div>
  <div class="card-body">
    <div class="row text-center g-3">
      <div class="col"><small class="text-muted d-block">INSS Segurado</small><strong>R$ <?= number_format($totais['vlr_inss_segurado'] ?? 0, 2, ',', '.') ?></strong></div>
      <div class="col"><small class="text-muted d-block">INSS Patronal</small><strong>R$ <?= number_format($totais['vlr_inss_patronal'] ?? 0, 2, ',', '.') ?></strong></div>
      <div class="col"><small class="text-muted d-block">RAT</small><strong>R$ <?= number_format($totais['vlr_rat'] ?? 0, 2, ',', '.') ?></strong></div>
      <div class="col"><small class="text-muted d-block">Terceiros</small><strong>R$ <?= number_format($totais['vlr_terceiros'] ?? 0, 2, ',', '.') ?></strong></div>
      <div class="col"><small class="text-muted d-block">Retenção</small><strong>R$ <?= number_format($totais['vlr_retencao'] ?? 0, 2, ',', '.') ?></strong></div>
      <div class="col"><small class="text-muted d-block">Total</small><strong class="text-primary">R$ <?= number_format($totais['vlr_total'] ?? 0, 2, ',', '.') ?></strong></div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2">
  <?php if ($validacao): ?>
    <?php if (pode(['admin', 'contador', 'auxiliar'])): ?>
      <a href="upload.php?empresa_id=<?= $validacao['empresa_id'] ?>&tipo=<?= urlencode($validacao['tipo']) ?>&competencia=<?= urlencode($validacao['competencia']) ?>" class="btn btn-primary btn-lg"><i class="bi bi-cloud-upload"></i> Validar novo arquivo </a>
    <?php endif; ?>
    <a href="relatorio.php?id=<?= $validacao['id'] ?>" target="_self" class="btn btn-outline-danger btn-lg"><i class="bi bi-file-earmark-pdf"></i> Baixar Relatório PDF</a>
  <?php else: ?>
    <a href="upload.php" class="btn btn-primary btn-lg"><i class="bi bi-cloud-upload"></i> Validar outro arquivo</a>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>