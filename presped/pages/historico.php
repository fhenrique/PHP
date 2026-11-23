<?php
// C:\xampp2\htdocs\presped\pages\historico.php
require_once '../config.php';

if (!estaLogado()) {
  header("Location: ../index.php?aba=login");
  exit;
}

$escritorio_id = $_SESSION['escritorio_id'];

// Busca todas as validações desse escritório
$stmt = $pdo->prepare("
  SELECT v.*, a.nome_arquivo, a.tipo, a.competencia, e.razao_social, e.cnpj
  FROM validacoes v
  JOIN arquivos a ON a.id = v.arquivo_id
  JOIN empresas e ON e.id = v.empresa_id
  WHERE a.escritorio_id = ?
  ORDER BY v.criado_em DESC
");
$stmt->execute([$escritorio_id]);
$lista = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <a href="dashboard.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Voltar para Dashboard</a>
    <h2 class="fw-bold mb-0 mt-1">Histórico de Validações</h2>
    <p class="text-muted mb-0"><?= count($lista) ?> arquivo(s) enviado(s)</p>
  </div>
  
	<?php if (pode(['admin', 'contador', 'auxiliar'])): ?>
		<a href="upload.php" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Novo SPED</a>
	<?php endif; ?>	  
  
</div>

<?php if (empty($lista)): ?>
  <div class="alert alert-info rounded-4">Nenhum SPED enviado ainda. <a href="upload.php">Envie o primeiro agora.</a></div>
<?php else: ?>
<div class="card border-0 shadow-sm rounded-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Empresa</th><th>Competência</th><th>Arquivo</th><th>Status</th><th>Erros</th><th>Enviado em</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($lista as $v): ?>
        <tr>
          <td><strong><?= htmlspecialchars($v['razao_social']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($v['cnpj']) ?></small></td>
          <td><?= htmlspecialchars($v['competencia']) ?><br><small class="text-muted"><?= htmlspecialchars($v['tipo']) ?></small></td>
          <td><small><?= htmlspecialchars($v['nome_arquivo']) ?></small></td>
          <td>
            <?php if ($v['total_erros'] > 0): ?>
              <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Com erro</span>
            <?php else: ?>
              <span class="badge bg-success"><i class="bi bi-check-circle"></i> Aprovado</span>
            <?php endif; ?>
          </td>
          <td><strong><?= $v['total_erros'] ?></strong></td>
          <td><small><?= date('d/m/Y H:i', strtotime($v['criado_em'])) ?></small></td>
          
		  <td class="text-end">
			<div class="d-grid gap-1" style="width: 90px;">
				
				<a href="validacao-detalhe.php?id=<?= $v['id'] ?>&origem=historico" class="btn btn-sm btn-outline-primary">Detalhes</a>
				<a href="relatorio.php?id=<?= $v['id'] ?>&origem=historico" target="_self" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
				
			</div>
          </td>
		  
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>