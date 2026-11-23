<?php
// includes/sidebar.php
$pagina_atual = basename($_SERVER['PHP_SELF']);
function ativo($arquivo, $pagina_atual) {
  return $pagina_atual === $arquivo ? 'active' : '';
}
?>
<style>
  /* LOGO OCUPANDO TODO O ESPAÇO DO SIDEBAR */
  .sidebar {
    padding-top: 24px !important;
	padding-bottom: 15px !important;
  }
  .sidebar-logo {
    width: 100% !important;
    height: auto !important;
    padding: 0 !important;
    margin: 0 0 32px 0 !important;
    box-sizing: border-box !important;
    display: block !important;
    overflow: hidden;
  }
  .sidebar-logo-wrap {
    width: 100% !important;
    height: auto !important;
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 0;
    display: block;
  }
  .logo-sidebar-img {
    width: 100% !important;
    height: auto !important;
    max-width: 100% !important;
    max-height: none !important;
    object-fit: fill !important;
    object-position: center !important;
    display: block;
  }
  /* ESPAÇO ENTRE LOGO E MENU */
  .sidebar-menu {
    padding-top: 8px !important;
  }
</style>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-wrap">
      <img
        src="/presped/imagens/logo_sidebar_v5.png"
        alt="Pré-SPED Seguro"
        class="logo-sidebar-img"
      >
    </div>
  </div>

  <nav class="sidebar-menu">
    <a href="dashboard.php" class="<?= ativo('dashboard.php', $pagina_atual) ?>">
      <i class="bi bi-columns-gap"></i> Visão geral
    </a>

    <?php if (pode(['admin', 'contador', 'auxiliar'])): ?>
    <a href="upload.php" class="<?= ativo('upload.php', $pagina_atual) ?>">
      <i class="bi bi-cloud-upload"></i> Validar arquivo
    </a>
    <a href="validacao-lote.php" class="<?= ativo('validacao-lote.php', $pagina_atual) ?>">
      <i class="bi bi-journal-check"></i> Validação em lote
    </a>
    <?php endif; ?>

    <?php if (pode(['admin', 'contador'])): ?>
    <a href="empresas.php" class="<?= ativo('empresas.php', $pagina_atual) ?>">
      <i class="bi bi-building"></i> Empresas
    </a>
    <?php endif; ?>

    <a href="historico.php" class="<?= ativo('historico.php', $pagina_atual) ?>">
      <i class="bi bi-clock-history"></i> Histórico
    </a>
    <a href="simulador-dctfweb.php" class="<?= ativo('simulador-dctfweb.php', $pagina_atual) ?>">
      <i class="bi bi-calculator"></i> Simulador DCTFWeb <span class="dot"></span>
    </a>

    <?php if (pode(['admin'])): ?>
		<a href="planos.php" class="<?= ativo('planos.php', $pagina_atual) ?>">
		  <i class="bi bi-gem"></i> Planos
		</a>
		<a href="usuarios.php" class="<?= ativo('usuarios.php', $pagina_atual) ?>">
		  <i class="bi bi-people"></i> Usuários
		</a>
		
		<a href="logs.php" class="<?= ativo('logs.php', $pagina_atual) ?>">
		  <i class="bi bi-journal-text"></i> Log de atividades
		</a>	
    <?php endif; ?>

    <?php if (pode(['admin'])): ?>
      <a href="configuracoes.php" class="nav-link">
        <i class="bi bi-gear"></i> Configurações
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="../logout.php"><i class="bi bi-box-arrow-left"></i> Sair do sistema</a>
  </div>
</aside>