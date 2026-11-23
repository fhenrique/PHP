<?php require_once '../config.php'; exigeLogin(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serenitá</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-serenita sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-light" href="dashboard.php" style="color:#6B8577; letter-spacing:1px;">🌿 Serenitá</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-house"></i> Início</a></li>
        <li class="nav-item"><a class="nav-link" href="clientes.php"><i class="bi bi-people"></i> Clientes</a></li>
        <li class="nav-item"><a class="nav-link" href="profissionais.php"><i class="bi bi-person-badge"></i> Profissionais</a></li>
        <li class="nav-item"><a class="nav-link" href="servicos.php"><i class="bi bi-spa"></i> Serviços</a></li>
      </ul>
      <span class="small text-muted me-3"><?= htmlspecialchars($_SESSION['spa_nome'] ?? '') ?></span>
      <a href="../logout.php" class="btn btn-outline-secondary btn-sm">Sair</a>
    </div>
  </div>
</nav>
<div class="container py-4">