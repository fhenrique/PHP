<?php
// C:\xampp2\htdocs\presped\pages\planos.php
require_once '../config.php';
exigePerfil(['admin']);

if (!estaLogado()) {
  header("Location: ../index.php?aba=login");
  exit;
}

$escritorio_id = $_SESSION['escritorio_id'];
$plano_atual = $_SESSION['escritorio_plano'] ?? 'teste';
$sucesso = '';

// Ao clicar em escolher plano - por enquanto só salva no banco
if (isset($_GET['escolher'])) {
  $novo_plano = $_GET['escolher'];
  $planos_validos = ['start', 'pro', 'premium'];
  if (in_array($novo_plano, $planos_validos)) {
    
// Se mudou pra Premium/Pro, limpa a data de expiração do teste
if (in_array($novo_plano, ['premium', 'pro'])) {
  $stmt = $pdo->prepare("UPDATE escritorios SET plano = ?, plano_expira_em = NULL WHERE id = ?");
} else {
  // Se voltou pra teste, cria nova expiração de 7 dias
  $stmt = $pdo->prepare("UPDATE escritorios SET plano = ?, plano_expira_em = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id = ?");
}
$stmt->execute([$novo_plano, $escritorio_id]);

// Atualiza a sessão na hora, pra não precisar sair e entrar
$_SESSION['escritorio_plano'] = $novo_plano;
	
	
    $_SESSION['escritorio_plano'] = $novo_plano;
    $plano_atual = $novo_plano;
    $sucesso = 'Plano alterado para ' . ucfirst($novo_plano) . ' com sucesso! Agora você pode cadastrar mais empresas.';
  }
}

// Conta empresas para mostrar o uso
$stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE escritorio_id = ? AND ativo = 1");
$stmt->execute([$escritorio_id]);
$total_empresas = $stmt->fetchColumn();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-1">
  <h2 class="fw-bold mb-0">Trocar de plano</h2>
  <a href="empresas.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>
<p class="text-muted">Você está no plano <strong><?= ucfirst($plano_atual) ?></strong> com <?= $total_empresas ?> empresa(s) cadastrada(s).</p>

<?php if ($sucesso): ?><div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

<div class="row g-3">
  <!-- START -->
  <div class="col-md-4">
    <div class="card h-100 <?= $plano_atual === 'start' ? 'border-primary border-2' : 'border-0 shadow-sm' ?>">
      <div class="card-body text-center">
        <h5>Start</h5>
        <h2>R$ 97<small class="text-muted fs-6">/mês</small></h2>
        <p class="text-muted small">Para começar a organizar</p>
        <ul class="list-unstyled text-start">
          <li>✅ Até 10 empresas</li>
          <li>✅ Validação de SPED Fiscal</li>
          <li>✅ Relatórios básicos</li>
        </ul>
        <?php if ($plano_atual === 'start'): ?>
          <button class="btn btn-secondary w-100" disabled>Plano atual</button>
        <?php else: ?>
                    <div class="alert alert-transparent-success" role="alert">
                        
                    </div>          
		  <a href="planos.php?escolher=start" class="btn btn-primary w-100">Escolher Start</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- PRO -->
  <div class="col-md-4">
    <div class="card h-100 <?= $plano_atual === 'pro' ? 'border-primary border-2' : 'border-0 shadow-sm' ?>">
      <div class="card-body text-center">
        <span class="badge bg-success mb-2">Mais popular</span>
        <h5>Pro</h5>
        <h2>R$ 197<small class="text-muted fs-6">/mês</small></h2>
        <p class="text-muted small">Para escritórios em crescimento</p>
        <ul class="list-unstyled text-start">
          <li>✅ Até 30 empresas</li>
          <li>✅ Validação SPED Fiscal + Contribuições</li>
          <li>✅ Relatórios completos</li>
        </ul>
        <?php if ($plano_atual === 'pro'): ?>
          <button class="btn btn-secondary w-100" disabled>Plano atual</button>
        <?php else: ?>
			<a>
                        
			</a>
		  <a href="planos.php?escolher=pro" class="btn btn-primary w-100">Escolher Pro</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- PREMIUM -->
  <div class="col-md-4">
    <div class="card h-100 <?= $plano_atual === 'premium' ? 'border-primary border-2' : 'border-0 shadow-sm' ?>">
      <div class="card-body text-center">
        <h5>Premium</h5>
        <h2>R$ 397<small class="text-muted fs-6">/mês</small></h2>
        <p class="text-muted small">Para escritórios grandes</p>
        <ul class="list-unstyled text-start">
          <li>✅ Empresas ilimitadas</li>
          <li>✅ Todas as validações</li>
          <li>✅ Relatórios completos</li>
        </ul>
        <?php if ($plano_atual === 'premium'): ?>
          <button class="btn btn-secondary w-100" disabled>Plano atual</button>
        <?php else: ?>
          <a href="planos.php?escolher=premium" class="btn btn-primary w-100">Escolher Premium</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<p class="text-muted small mt-3">* Por enquanto a troca é direta para teste. Depois a gente liga isso no pagamento (Pix / cartão).</p>

<?php require_once '../includes/footer.php'; ?>