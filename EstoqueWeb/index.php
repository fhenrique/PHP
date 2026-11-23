<?php
require_once __DIR__ . '/includes/auth.php';

exigirEmpresaSelecionada();

$tituloPagina = 'Painel Inicial';

// Todos os indicadores são limitados à empresa selecionada na sessão.
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM produtos
    WHERE empresa_id = ?
      AND ativo = 1
");
$stmt->execute([$empresaId]);
$totalProdutos = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM produtos
    WHERE empresa_id = ?
      AND ativo = 1
      AND estoque_atual <= estoque_minimo
");
$stmt->execute([$empresaId]);
$estoqueBaixo = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM usuarios
    WHERE empresa_id = ?
      AND ativo = 1
");
$stmt->execute([$empresaId]);
$totalUsuarios = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM categorias
    WHERE empresa_id = ?
      AND ativo = 1
");
$stmt->execute([$empresaId]);
$totalCategorias = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT
        m.*,
        p.nome AS produto
    FROM movimentacoes m
    INNER JOIN produtos p
        ON p.id = m.produto_id
       AND p.empresa_id = m.empresa_id
    WHERE m.empresa_id = ?
    ORDER BY m.data_movimentacao DESC
    LIMIT 10
");
$stmt->execute([$empresaId]);
$movimentacoes = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">
    <i class="bi bi-speedometer2 text-primary"></i>
    Painel Inicial
</h1>

<p class="text-muted mb-4">
    Empresa: <strong><?= h($empresaNome) ?></strong>
</p>

<div class="row g-3">
    <div class="col-md-6 col-xl-3">
        <div class="card card-dashboard">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Produtos cadastrados</div>
                    <h2 class="mt-2 mb-0"><?= $totalProdutos ?></h2>
                </div>
                <div class="card-icon icon-azul">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <?php if ($estoqueBaixo > 0): ?>
            <a href="produtos_estoque_baixo.php"
               class="card card-dashboard text-decoration-none text-reset card-estoque-clicavel">
        <?php else: ?>
            <div class="card card-dashboard">
        <?php endif; ?>

            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Produto(s) c/ estoque baixo</div>
                    <h2 class="mt-2 mb-0 <?= $estoqueBaixo > 0 ? 'text-primary' : 'text-danger' ?>">
                        <?= $estoqueBaixo ?>
                    </h2>
                </div>
                <div class="card-icon <?= $estoqueBaixo > 0 ? 'icon-azul' : 'icon-vermelho' ?>">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>

        <?php if ($estoqueBaixo > 0): ?>
            </a>
        <?php else: ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card card-dashboard">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Categorias ativas</div>
                    <h2 class="mt-2 mb-0"><?= $totalCategorias ?></h2>
                </div>
                <div class="card-icon icon-verde">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card card-dashboard">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Usuários ativos</div>
                    <h2 class="mt-2 mb-0"><?= $totalUsuarios ?></h2>
                </div>
                <div class="card-icon icon-azul">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-padrao mt-4">
    <div class="card-header bg-white py-3">
        <strong>
            <i class="bi bi-clock-history me-2"></i>
            Últimas movimentações
        </strong>
    </div>

    <div class="card-body table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Valor unitário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimentacoes as $m): ?>
                    <tr>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($m['data_movimentacao'])) ?>
                        </td>
                        <td><?= h($m['produto']) ?></td>
                        <td>
                            <?php if ($m['tipo'] === 'ENTRADA'): ?>
                                <span class="badge bg-success">Entrada</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Saída</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($m['quantidade']) ?></td>
                        <td>
                            <?= $m['valor_unitario'] !== null
                                ? 'R$ ' . number_format((float) $m['valor_unitario'], 2, ',', '.')
                                : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$movimentacoes): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Nenhuma movimentação registrada.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
