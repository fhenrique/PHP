<?php
//sidebar.php
if (!isset($pagina_ativa)) $pagina_ativa = '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>XSOL - <?= ucfirst($pagina_ativa ?: 'Painel') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{ background:#FFF7E0; font-family:'Segoe UI',system-ui,sans-serif; }
  .sidebar{ width:260px; min-height:100vh; background:#fff; border-right:1px solid #FFE9A8; position:fixed; left:0; top:0; padding:20px; display:flex; flex-direction:column; z-index:1000; }
  .sidebar-logo{ max-width:180px; width:100%; height:auto; display:block; margin:0 auto 10px; }
  .sidebar a.menu{ display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; color:#444; text-decoration:none; font-weight:600; margin-bottom:4px; }
  .sidebar a.menu:hover{ background:#FFF7E0; color:#92400E; }
  .sidebar a.menu.ativo{ background:#F59E0B; color:#fff; }
  .conteudo{ margin-left:260px; padding:24px; width:calc(100% - 260px); min-height:100vh; }
  .card{ border-radius:16px; border:none; box-shadow:0 10px 30px rgba(180,120,0,.12); }
  .btn-sol{ background:#F59E0B; color:#fff; font-weight:700; border:none; border-radius:10px; }
  .btn-sol:hover{ background:#E08E00; color:#fff; }
  .form-control, .form-select{ border-radius:10px; }
  .morto{ background:#FEF08A !important; }
  .legenda-morto{ background:#FEF08A; padding:2px 8px; border-radius:6px; font-size:12px; font-weight:700; }
  @media(max-width:768px){
    .sidebar{ position:relative; width:100%; min-height:auto; }
    .conteudo{ margin-left:0; width:100%; }
  }
</style>
</head>
<body>
<div class="sidebar">
  <img src="../imagens/logo_v1.png" alt="XSOL" class="sidebar-logo">
  <div class="text-center mb-3">
    <small class="text-muted">Olá,</small><br>
    <strong><?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?></strong><br>
    <span class="badge bg-warning text-dark"><?= htmlspecialchars($_SESSION['perfil'] ?? '') ?></span>
  </div>

  <a href="dashboard.php" class="menu <?= $pagina_ativa=='dashboard'?'ativo':'' ?>">🏠 Dashboard</a>
  <a href="clientes.php" class="menu <?= $pagina_ativa=='clientes'?'ativo':'' ?>">👥 Clientes</a>
  <a href="tecnicos.php" class="menu <?= $pagina_ativa=='tecnicos'?'ativo':'' ?>">🧑‍🔧 Técnicos</a>
  <a href="zonas.php" class="menu <?= $pagina_ativa=='zonas'?'ativo':'' ?>">📍 Zonas</a>
  <a href="equipamentos.php" class="menu <?= $pagina_ativa=='equipamentos'?'ativo':'' ?>">🔧 Equipamentos</a>
  <a href="agendamentos.php" class="menu <?= $pagina_ativa=='agendamentos'?'ativo':'' ?>">📅 Agendamentos</a>
  <a href="tipos_servicos.php" class="menu <?= $pagina_ativa=='tipos_servicos'?'ativo':'' ?>">🛠️ Tipos de Serviços</a>
  <a href="fornecedores.php" class="menu <?= $pagina_ativa=='fornecedores'?'ativo':'' ?>">🏭 Fornecedores</a>

  <div class="mt-auto">
    <hr>
    <a href="../logout.php" class="menu">🚪 Sair</a>

  </div>
</div>

<div class="conteudo">