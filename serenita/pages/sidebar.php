<?php
// pages/sidebar.php - Menu lateral único do Serenitá
$pagina_atual = basename($_SERVER['PHP_SELF']);
$perfil = $_SESSION['perfil'] ?? 'Administrador';
$nome_user = $_SESSION['nome'] ?? 'Maria';
$nome_spa = $_SESSION['spa_nome'] ?? 'Serenità';

function itemMenu($pagina_atual, $arquivo, $icone, $label) {
  $ativo = ($pagina_atual === $arquivo) ? 'active' : '';
  return '<a href="'.$arquivo.'" class="menu-item '.$ativo.'"><i class="bi bi-'.$icone.'"></i> '.$label.'</a>';
}

// ✅ 2 INICIAIS do usuário logado - igual dos Profissionais
// Ex: Maria Silva = MS | Maria da Silva = MS | Maria = M
function iniciaisUsuario($nome){
  $nome = trim($nome);
  if ($nome === '') return '?';
  $partes = preg_split('/\s+/', $nome);
  $ignorar = ['da','de','do','dos','das','e'];
  $validas = [];
  foreach ($partes as $p) {
    $p = trim($p);
    if ($p === '') continue;
    if (!preg_match('/^\p{L}/u', $p)) continue;
    if (in_array(mb_strtolower($p, 'UTF-8'), $ignorar)) continue;
    $validas[] = $p;
  }
  if (empty($validas)) return '?';
  $primeira = mb_strtoupper(mb_substr($validas[0], 0, 1, 'UTF-8'), 'UTF-8');
  if (count($validas) === 1) return $primeira;
  $ultima = mb_strtoupper(mb_substr(end($validas), 0, 1, 'UTF-8'), 'UTF-8');
  return $primeira . $ultima;
}
?>
<style>
  /* ✅ CORREÇÃO: sidebar em coluna, menu rola e user-box fica presa embaixo */
  .sidebar{
    display:flex !important;
    flex-direction:column !important;
    height:100vh !important;
    overflow:hidden !important;
  }
  .sidebar-menu-scroll{
    flex:1;
    overflow-y:auto;
    overflow-x:hidden;
    padding-right:2px;
    /* Esconde um pouco a barra de rolagem pra ficar bonito */
    scrollbar-width:thin;
    scrollbar-color:#E3B8E3 transparent;
  }
  .sidebar-menu-scroll::-webkit-scrollbar{ width:5px; }
  .sidebar-menu-scroll::-webkit-scrollbar-thumb{ background:#E3B8E3; border-radius:10px; }
  .sidebar .user-box{
    margin-top:12px !important;
    flex-shrink:0;
  }
</style>

<div class="sidebar">
  <!-- Logo -->
  <div class="mb-3 text-center" style="flex-shrink:0;">
    <img src="../imagens/logo_v1.png" alt="Serenità - Beleza & Estética" style="width:100%; max-width:210px; height:auto; border-radius:16px; display:block; margin:0 auto;">
  </div>

  <div class="px-2 mb-2 fw-bold" style="color:#7A4A7A; flex-shrink:0;">Menu</div>

  <!-- ✅ Menu com rolagem própria - é ele que rola, não a sidebar inteira -->
  <div class="sidebar-menu-scroll">
    <?= itemMenu($pagina_atual, 'dashboard.php', 'grid', 'Dashboard') ?>
    <?= itemMenu($pagina_atual, 'agenda.php', 'calendar3', 'Agenda') ?>
    <?= itemMenu($pagina_atual, 'clientes.php', 'person', 'Pacientes') ?>
    <?= itemMenu($pagina_atual, 'profissionais.php', 'person-badge', 'Profissionais') ?>
    <?= itemMenu($pagina_atual, 'servicos.php', 'stars', 'Serviços') ?>
    <?= itemMenu($pagina_atual, 'faturamento.php', 'cash-stack', 'Faturamento') ?>
    <?= itemMenu($pagina_atual, 'pacotes.php', 'box', 'Pacotes') ?>
    <?= itemMenu($pagina_atual, 'estoque.php', 'box-seam', 'Estoque') ?>
    <?= itemMenu($pagina_atual, 'relatorios.php', 'bar-chart', 'Relatórios') ?>
    <?= itemMenu($pagina_atual, 'configuracoes.php', 'gear', 'Configurações') ?>

    <?php if ($perfil === 'Administrador'): ?>
      <?= itemMenu($pagina_atual, 'usuarios.php', 'people', 'Usuários') ?>
    <?php endif; ?>
  </div>

  <!-- ✅ User-box + botão Sair sempre visível embaixo -->
  <div class="user-box">
    <div style="width:42px; height:42px; min-width:42px; border-radius:50%; background:#F9D8E8; color:#7A4A7A; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px;"><?= iniciaisUsuario($nome_user) ?></div>
    <div style="line-height:1.2;">
      <div style="font-weight:700; font-size:13.5px;"><?= htmlspecialchars($nome_user) ?></div>
      <div style="font-size:11.5px; color:#999;"><?= htmlspecialchars($perfil) ?></div>
    </div>
    <a href="../logout.php" class="ms-auto text-muted" title="Sair do sistema"><i class="bi bi-box-arrow-right"></i></a>
  </div>
</div>