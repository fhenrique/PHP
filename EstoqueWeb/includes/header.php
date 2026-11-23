<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);

if (!function_exists('h')) {
    function h($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

// O seletor de empresa e o menu Empresas são exclusivos do Administrador Geral.
$ehAdministradorGeral = ((int) ($_SESSION['perfil_id'] ?? 0) === 3);
$empresasDisponiveis = [];

if ($ehAdministradorGeral) {
    $stmtEmpresas = $pdo->query("
        SELECT id, nome
        FROM empresas
        WHERE ativo = 1
        ORDER BY nome
    ");
    $empresasDisponiveis = $stmtEmpresas->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($tituloPagina ?? 'EstoqueWeb') ?> - EstoqueWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/estilo.css" rel="stylesheet">
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="assets/img/logo.jpg" alt="EstoqueWeb">
    </div>

    <div class="menu-titulo">Principal</div>
    <nav class="nav flex-column">
        <a href="index.php" class="nav-link <?= $paginaAtual === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            Painel inicial
        </a>
    </nav>

    <div class="menu-titulo">Cadastros</div>
    <nav class="nav flex-column">
        <?php if ($ehAdministradorGeral): ?>
            <a href="empresas.php" class="nav-link <?= $paginaAtual === 'empresas.php' ? 'active' : '' ?>">
                <i class="bi bi-buildings"></i>
                Empresas
            </a>
        <?php endif; ?>

        <a href="categorias.php" class="nav-link <?= $paginaAtual === 'categorias.php' ? 'active' : '' ?>">
            <i class="bi bi-tags"></i>
            Categorias
        </a>

        <a href="produtos.php" class="nav-link <?= $paginaAtual === 'produtos.php' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i>
            Produtos
        </a>

        
        <a href="fornecedores.php" class="nav-link <?= $paginaAtual === 'fornecedores.php' ? 'active' : '' ?>">
            <i class="bi bi-truck"></i>
            <span>Fornecedores</span>
        </a>

        <?php if (in_array((int) ($_SESSION['perfil_id'] ?? 0), [1, 3], true)): ?>
            <a href="usuarios.php" class="nav-link <?= $paginaAtual === 'usuarios.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                Usuários
            </a>
        <?php endif; ?>
    </nav>

    <div class="menu-titulo">Estoque</div>
    <nav class="nav flex-column">
        
            <a href="entrada_estoque.php" class="nav-link <?= $paginaAtual === 'entrada_estoque.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                Entrada de estoque
            </a>        


            <a href="saida_estoque.php" class="nav-link <?= $paginaAtual === 'saida_estoque.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                Saída de estoque
            </a>



<a href="movimentacoes.php"
   class="nav-link <?= $paginaAtual === 'movimentacoes.php' ? 'active' : '' ?>">
    <i class="bi bi-clock-history"></i>
    Movimentações
</a>
        
        
    </nav>
</aside>

<main class="main-content">
    <header class="topbar">
        <div>
            <strong>EstoqueWeb</strong>
            <small class="text-muted ms-2 d-none d-sm-inline">
                Controle de Estoque e Almoxarifado
            </small>
        </div>

        <div class="d-flex align-items-center gap-3 flex-wrap">
            <?php if ($ehAdministradorGeral): ?>
                <form method="POST" class="d-flex align-items-center gap-2 mb-0">
                    <input type="hidden" name="acao" value="selecionar_empresa">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token'] ?? '') ?>">
                    <label for="empresa_id" class="small text-muted mb-0">Empresa:</label>
                    <select name="empresa_id" id="empresa_id" class="form-select form-select-sm"
                            onchange="this.form.submit()" aria-label="Selecionar empresa">
                        <?php foreach ($empresasDisponiveis as $empresaOpcao): ?>
                            <option value="<?= (int) $empresaOpcao['id'] ?>"
                                <?= (int) ($_SESSION['empresa_id'] ?? 0) === (int) $empresaOpcao['id'] ? 'selected' : '' ?>>
                                <?= h($empresaOpcao['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else: ?>
                <span class="small text-muted">
                    <i class="bi bi-building me-1"></i>
                    <?= h($_SESSION['empresa_nome'] ?? 'Empresa não identificada') ?>
                </span>
            <?php endif; ?>

            <span class="text-secondary">
                <i class="bi bi-person-circle me-1"></i>
                <?= h($_SESSION['usuario_nome'] ?? '') ?>
            </span>

            <a href="logout.php" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i>
                Sair
            </a>
        </div>
    </header>

    <section class="page-content">
