
<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/conexao.php';

// Exige uma empresa selecionada.
exigirEmpresaSelecionada();

$empresaId = (int) $_SESSION['empresa_id'];

$tituloPagina = 'Produtos com Estoque Baixo';

// Busca somente produtos ativos da empresa selecionada,
// cujo estoque atual esteja igual ou abaixo do estoque mínimo.
$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.codigo,
        p.nome,
        p.unidade,
        p.estoque_atual,
        p.estoque_minimo,
        c.nome AS categoria
    FROM produtos p
    INNER JOIN categorias c
        ON c.id = p.categoria_id
       AND c.empresa_id = p.empresa_id
    WHERE p.empresa_id = :empresa_id
      AND p.ativo = 1
      AND p.estoque_atual <= p.estoque_minimo
    ORDER BY p.nome ASC
");

$stmt->execute([
    ':empresa_id' => $empresaId
]);

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';

?>

<h1 class="page-title">
    <i class="bi bi-exclamation-triangle text-danger"></i>
    Produtos com Estoque Baixo
</h1>

<p class="text-muted mb-3">
    Empresa:
    <strong><?= h($_SESSION['empresa_nome'] ?? 'Empresa selecionada') ?></strong>
</p>

<div class="card card-padrao">

    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">

        <strong>
            <i class="bi bi-box-seam me-2"></i>
            Produtos que precisam de reposição
        </strong>

        <span class="badge bg-danger">
            <?= count($produtos) ?>
            produto(s)
        </span>

    </div>

    <div class="card-body">

        <?php if ($produtos): ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Unidade</th>
                            <th>Estoque atual</th>
                            <th>Estoque mínimo</th>
                            <th>Diferença</th>
                            <th class="text-center">Ação</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($produtos as $produto): ?>

                            <?php
                                $atual = (float) $produto['estoque_atual'];
                                $minimo = (float) $produto['estoque_minimo'];
                                $diferenca = max(0, $minimo - $atual);
                            ?>

                            <tr>

                                <td>
                                    <?= h($produto['codigo']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= h($produto['nome']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= h($produto['categoria']) ?>
                                </td>

                                <td>
                                    <?= h($produto['unidade']) ?>
                                </td>

                                <td>
                                    <span class="text-danger fw-bold">
                                        <?= number_format($atual, 3, ',', '.') ?>
                                    </span>
                                </td>

                                <td>
                                    <?= number_format($minimo, 3, ',', '.') ?>
                                </td>

                                <td>
                                    <?= number_format($diferenca, 3, ',', '.') ?>
                                </td>

<td class="text-center">

    <div class="d-flex justify-content-center gap-1">

        <a
            href="produtos.php?editar=<?= (int) $produto['id'] ?>"
            class="btn btn-sm btn-outline-primary"
            title="Editar produto"
        >
            <i class="bi bi-pencil"></i>
            Editar
        </a>

        <a
            href="entrada_estoque.php?produto_id=<?= (int) $produto['id'] ?>"
            class="btn btn-sm btn-success"
            title="Dar entrada no estoque deste produto"
        >
            <i class="bi bi-box-arrow-in-down"></i>
            Dar entrada
        </a>

    </div>

</td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="text-center text-muted py-5">

                <i class="bi bi-check-circle text-success"
                   style="font-size: 3rem;"></i>

                <h5 class="mt-3">
                    Nenhum produto com estoque baixo!
                </h5>

                <p class="mb-0">
                    Todos os produtos ativos estão com estoque acima do mínimo.
                </p>

            </div>

        <?php endif; ?>

        <div class="mt-3">

            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                Voltar ao painel
            </a>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
