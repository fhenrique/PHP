<?php
// C:\xampp2\htdocs\presped\pages\relatorio.php
require_once '../config.php';

$validacao_id = $_GET['id'] ?? 0;
if (!$validacao_id) { die('Validação não encontrada.'); }

$stmt = $pdo->prepare("SELECT v.*, a.nome_arquivo, a.tipo, a.competencia, e.razao_social, e.cnpj FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id JOIN empresas e ON e.id = v.empresa_id WHERE v.id = ?");
$stmt->execute([$validacao_id]);
$v = $stmt->fetch();
if (!$v) { die('Validação não encontrada.'); }

$stmt = $pdo->prepare("SELECT * FROM validacoes_erros WHERE validacao_id = ? ORDER BY linha ASC");
$stmt->execute([$validacao_id]);
$erros = $stmt->fetchAll();

// NOVO: De onde veio? Dashboard, Histórico ou Simulador?
$origem = $_GET['origem'] ?? 'dashboard';
$competencia_volta = $_GET['competencia'] ?? ($v['competencia'] ?? date('Y-m'));

$voltar = [
  'historico' => ['url' => 'historico.php', 'texto' => '🏠 Voltar para Histórico'],
  'simulador' => ['url' => 'simulador-dctfweb.php?competencia=' . urlencode($competencia_volta), 'texto' => '🏠 Voltar para o Simulador DCTFWeb'],
  'dashboard' => ['url' => 'dashboard.php?competencia=' . urlencode($competencia_volta), 'texto' => '🏠 Voltar para Dashboard'],
];
$link_voltar = $voltar[$origem] ?? $voltar['dashboard'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório de Validação - <?= htmlspecialchars($v['razao_social']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  @media print { .no-print { display: none !important; } body { -webkit-print-color-adjust: exact; } }
  .header-pdf { border-bottom: 3px solid #000; padding-bottom: 12px; margin-bottom: 16px; }
</style>
</head>
<body class="p-4">
<div class="no-print text-end mb-3">
  <a href="<?= $link_voltar['url'] ?>" class="btn btn-outline-primary btn-lg"><?= $link_voltar['texto'] ?></a>
  <button onclick="window.print()" class="btn btn-danger btn-lg">🖨️ Imprimir / Salvar como PDF</button>
  <a href="validacao-detalhe.php?id=<?= $v['id'] ?>&origem=<?= urlencode($origem) ?>&competencia=<?= urlencode($competencia_volta) ?>" class="btn btn-secondary btn-lg">Voltar</a>
</div>

<div class="header-pdf d-flex justify-content-between">
  <div>
	
<?php
$stmt = $pdo->prepare("SELECT logo, nome_exibicao, nome FROM escritorios WHERE id = ?");
$stmt->execute([$_SESSION['escritorio_id']]);
$esc = $stmt->fetch();
$logo = $esc['logo'] ?? null;
$nome_exibir = $esc['nome_exibicao'] ?? $esc['nome'] ?? 'SPEDFácil';
?>

<?php if ($logo): ?>
  <img src="../<?= htmlspecialchars($logo) ?>" style="max-height:60px">
  <div style="font-size:14px; color:#555;"><?= htmlspecialchars($nome_exibir) ?></div>
<?php else: ?>
  <h1 style="margin:0;">SPED<span style="color:green;">Fácil</span></h1>
<?php endif; ?>
	
	<small>Relatório de Validação de SPED</small>
  </div>
  <div class="text-end"><small>Emitido em <?= date('d/m/Y H:i') ?></small></div>
</div>

<h4 class="fw-bold"><?= htmlspecialchars($v['razao_social']) ?></h4>
<p class="text-muted">CNPJ <?= htmlspecialchars($v['cnpj']) ?> • <?= htmlspecialchars($v['tipo']) ?> • Competência <?= htmlspecialchars($v['competencia']) ?><br>Arquivo: <?= htmlspecialchars($v['nome_arquivo']) ?></p>

<?php if ($v['total_erros'] > 0): ?>
  <div class="alert alert-danger"><strong>⚠️ <?= $v['total_erros'] ?> erro(s) encontrados</strong><br><?= htmlspecialchars($v['resumo']) ?></div>
<?php else: ?>
  <div class="alert alert-success"><strong>✅ Sem erros</strong><br><?= htmlspecialchars($v['resumo']) ?></div>
<?php endif; ?>

<?php if (!empty($erros)): ?>
<table class="table table-bordered">
  <thead class="table-dark"><tr><th>Nota</th><th>Série / Data</th><th>Valor C100</th><th>Soma conferida</th><th>Diferença</th><th>Tipo</th><th>Mensagem</th></tr></thead>
  <tbody>
    <?php foreach ($erros as $e): ?>
    <?php $eh_c190 = stripos($e['tipo'] ?? '', 'C190') !== false; ?>
    <tr>
      <td><strong><?= htmlspecialchars($e['nota']) ?></strong></td>
      <td><?= htmlspecialchars($e['serie']) ?><br><small><?= htmlspecialchars($e['data_doc']) ?></small></td>
      <td>R$ <?= number_format($e['vl_c100'], 2, ',', '.') ?></td>
      <td>R$ <?= number_format($e['soma_c170'], 2, ',', '.') ?><br><small class="text-muted"><?= $eh_c190 ? '(C190 - VL_OPR)' : '(C170 - VL_ITEM)' ?></small></td>
      <td><strong>R$ <?= number_format($e['diferenca'], 2, ',', '.') ?></strong></td>
      <td><?= htmlspecialchars($e['tipo']) ?></td>
      <td><small><?= htmlspecialchars($e['mensagem']) ?></small></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<hr>

<?php if ($v['total_erros'] > 0): ?>
  <p><strong>Como corrigir:</strong><br>1. Corrija as notas listadas acima no seu sistema contábil.<br>2. Gere um novo arquivo SPED .txt.<br>3. Envie novamente no SPEDFácil para validar.</p>
<?php else: ?>
  <p><strong>Nenhuma ação necessária.</strong><br>Arquivo pronto para transmissão à SEFAZ.</p>
<?php endif; ?>

<p class="text-muted small">Relatório gerado automaticamente pelo SPEDFácil. Este documento é apenas informativo e não substitui a validação oficial da SEFAZ.</p>
</body>
</html>