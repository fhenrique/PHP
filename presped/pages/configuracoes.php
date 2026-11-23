<?php
// C:\xampp2\htdocs\presped\pages\configuracoes.php
require_once '../config.php';
exigePerfil(['admin']);


if (!estaLogado()) {
  header("Location: ../index.php?aba=login");
  exit;
}

$escritorio_id = $_SESSION['escritorio_id'];
$msg = '';

// Busca logo atual antes de qualquer alteração
$stmt = $pdo->prepare("SELECT logo FROM escritorios WHERE id = ?");
$stmt->execute([$escritorio_id]);
$logo_atual = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // --- 1. REMOVER LOGO ---
  if (isset($_POST['remover_logo'])) {
    // Apaga o arquivo físico, se existir
    if (!empty($logo_atual) && file_exists('../' . $logo_atual)) {
      unlink('../' . $logo_atual);
    }
    $stmt = $pdo->prepare("UPDATE escritorios SET logo = NULL WHERE id = ?");
    $stmt->execute([$escritorio_id]);
    $_SESSION['escritorio_logo'] = null;
    $msg = 'Logo removida! O relatório voltou a mostrar SPEDFácil.';
    $logo_atual = null;
  } else {

    // --- 2. SALVAR / TROCAR LOGO ---
    $nome_exibicao = trim($_POST['nome_exibicao'] ?? '');

    $logo_nova = null;
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === 0) {
      $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
      $permitidas = ['png', 'jpg', 'jpeg', 'webp'];
      if (in_array($ext, $permitidas)) {
        $nome_arq = 'logo_' . $escritorio_id . '_' . time() . '.' . $ext;
        $destino = '../uploads/logos/' . $nome_arq;
        if (!is_dir('../uploads/logos/')) mkdir('../uploads/logos/', 0777, true);
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
          $logo_nova = 'uploads/logos/' . $nome_arq;
          // Apaga a logo antiga pra não acumular arquivo
          if (!empty($logo_atual) && file_exists('../' . $logo_atual)) {
            unlink('../' . $logo_atual);
          }
        }
      } else {
        $msg = 'Só aceito PNG, JPG ou WEBP.';
      }
    }

    if ($logo_nova) {
      $stmt = $pdo->prepare("UPDATE escritorios SET nome_exibicao = ?, logo = ? WHERE id = ?");
      $stmt->execute([$nome_exibicao, $logo_nova, $escritorio_id]);
      $_SESSION['escritorio_logo'] = $logo_nova;
      $logo_atual = $logo_nova;
    } else {
      $stmt = $pdo->prepare("UPDATE escritorios SET nome_exibicao = ? WHERE id = ?");
      $stmt->execute([$nome_exibicao, $escritorio_id]);
    }
    $msg = $msg ?: 'Configurações salvas!';
  }
}

$stmt = $pdo->prepare("SELECT nome, logo, nome_exibicao FROM escritorios WHERE id = ?");
$stmt->execute([$escritorio_id]);
$esc = $stmt->fetch();

require_once '../includes/header.php';
?>

<h2 class="fw-bold mb-1">Configurações do escritório</h2>
<p class="text-muted">Sua logo vai aparecer no topo do relatório PDF no lugar do SPEDFácil.</p>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label fw-bold">Nome que aparece no relatório</label>
        <input type="text" name="nome_exibicao" class="form-control" placeholder="Ex: Dias Contabilidade" value="<?= htmlspecialchars($esc['nome_exibicao'] ?? $esc['nome'] ?? '') ?>">
        <small class="text-muted">Se deixar em branco, usa o nome do escritório.</small>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Logo do escritório</label><br>
        <?php if (!empty($esc['logo'])): ?>
          <img src="../<?= htmlspecialchars($esc['logo']) ?>" style="max-height:80px" class="border rounded p-1 mb-2"><br>
          <small class="text-muted">Logo atual. Envie outra para trocar.</small><br>
        <?php else: ?>
          <small class="text-muted">Nenhuma logo cadastrada. O relatório está mostrando SPEDFácil.</small><br>
        <?php endif; ?>
        <input type="file" name="logo" class="form-control mt-2" accept="image/png,image/jpeg,image/webp">
        <small class="text-muted">Recomendado: PNG com fundo transparente, 600 x 200 px.</small>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary btn-lg">Salvar configurações</button>
        <?php if (!empty($esc['logo'])): ?>
          <button type="submit" name="remover_logo" value="1" class="btn btn-outline-danger btn-lg" onclick="return confirm('Remover a logo? O relatório vai voltar a mostrar SPEDFácil.')">
            <i class="bi bi-trash"></i> Remover logo
          </button>
        <?php endif; ?>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">Voltar</a>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>