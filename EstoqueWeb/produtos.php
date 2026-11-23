
<?php

require_once 'includes/auth.php';
require_once 'config/conexao.php';

exigirEmpresaSelecionada();

$empresaId = (int) $_SESSION['empresa_id'];

$tituloPagina = 'Cadastro de Produtos';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$tipoMensagem = 'success';

$idEdicao = 0;
$codigo = '';
$nome = '';
$categoriaId = 0;
$descricao = '';
$unidade = 'UN';
$estoqueMinimo = '0.000';
$ativo = 1;

// ==========================================
// PROCESSAMENTO DOS FORMULÁRIOS
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Requisição inválida. Atualize a página.');
    }

    $acao = $_POST['acao'] ?? '';

    // ======================================
    // SALVAR PRODUTO
    // ======================================

    if ($acao === 'salvar') {

        $id = (int) ($_POST['id'] ?? 0);
        $idEdicao = $id;

        $codigo = trim($_POST['codigo'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $unidade = strtoupper(trim($_POST['unidade'] ?? 'UN'));
        $estoqueMinimo = str_replace(',', '.', trim($_POST['estoque_minimo'] ?? '0'));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (
            $codigo === '' ||
            $nome === '' ||
            $categoriaId <= 0 ||
            $unidade === '' ||
            !is_numeric($estoqueMinimo) ||
            (float) $estoqueMinimo < 0
        ) {

            $mensagem = 'Preencha corretamente todos os campos obrigatórios.';
            $tipoMensagem = 'danger';

        } else {

            // ======================================
            // VERIFICAR SE A CATEGORIA PERTENCE
            // À EMPRESA SELECIONADA
            // ======================================

            $stmt = $pdo->prepare("
                SELECT id, ativo
                FROM categorias
                WHERE id = ?
                  AND empresa_id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $categoriaId,
                $empresaId
            ]);

            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$categoria) {

                $mensagem = 'Categoria não encontrada ou não pertence à empresa selecionada.';
                $tipoMensagem = 'danger';

            } elseif (
                !(int) $categoria['ativo'] &&
                $id === 0
            ) {

                $mensagem = 'Não é possível cadastrar produtos em uma categoria inativa.';
                $tipoMensagem = 'danger';

            } else {

                try {

                    if ($id > 0) {

                        // ======================================
                        // VERIFICAR SE O PRODUTO PERTENCE
                        // À EMPRESA SELECIONADA
                        // ======================================

                        $stmt = $pdo->prepare("
                            SELECT id
                            FROM produtos
                            WHERE id = ?
                              AND empresa_id = ?
                            LIMIT 1
                        ");

                        $stmt->execute([
                            $id,
                            $empresaId
                        ]);

                        if (!$stmt->fetchColumn()) {

                            $mensagem = 'Produto não encontrado ou não pertence à empresa selecionada.';
                            $tipoMensagem = 'danger';

                        } else {

                            // Atualização sem alterar o estoque atual.
                            $stmt = $pdo->prepare("
                                UPDATE produtos
                                SET
                                    categoria_id = ?,
                                    codigo = ?,
                                    nome = ?,
                                    descricao = ?,
                                    unidade = ?,
                                    estoque_minimo = ?,
                                    ativo = ?
                                WHERE id = ?
                                  AND empresa_id = ?
                            ");

                            $stmt->execute([
                                $categoriaId,
                                $codigo,
                                $nome,
                                $descricao,
                                $unidade,
                                $estoqueMinimo,
                                $ativo,
                                $id,
                                $empresaId
                            ]);

                            $mensagem = 'Produto atualizado com sucesso!';

                            $idEdicao = 0;
                            $codigo = '';
                            $nome = '';
                            $categoriaId = 0;
                            $descricao = '';
                            $unidade = 'UN';
                            $estoqueMinimo = '0.000';
                            $ativo = 1;
                        }

                    } else {

                        // ======================================
                        // CADASTRAR PRODUTO
                        // VINCULADO À EMPRESA SELECIONADA
                        // ======================================

                        // O estoque começa sempre zerado.
                        $stmt = $pdo->prepare("
                            INSERT INTO produtos (
                                empresa_id,
                                categoria_id,
                                codigo,
                                nome,
                                descricao,
                                unidade,
                                estoque_atual,
                                estoque_minimo,
                                ativo
                            ) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)
                        ");

                        $stmt->execute([
                            $empresaId,
                            $categoriaId,
                            $codigo,
                            $nome,
                            $descricao,
                            $unidade,
                            $estoqueMinimo,
                            $ativo
                        ]);

                        $mensagem = 'Produto cadastrado com sucesso!';

                        $idEdicao = 0;
                        $codigo = '';
                        $nome = '';
                        $categoriaId = 0;
                        $descricao = '';
                        $unidade = 'UN';
                        $estoqueMinimo = '0.000';
                        $ativo = 1;
                    }

                } catch (PDOException $e) {

                    if ($e->getCode() === '23000') {
                        $mensagem = 'Este código já está cadastrado nesta empresa.';
                    } else {
                        error_log('Erro ao salvar produto: ' . $e->getMessage());
                        $mensagem = 'Erro ao salvar o produto.';
                    }

                    $tipoMensagem = 'danger';
                }
            }
        }
    }

    // ======================================
    // ATIVAR OU DESATIVAR
    // ======================================

    elseif ($acao === 'alterar_status') {

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {

            $mensagem = 'Produto inválido.';
            $tipoMensagem = 'danger';

        } else {

            $stmt = $pdo->prepare("
                UPDATE produtos
                SET ativo = IF(ativo = 1, 0, 1)
                WHERE id = ?
                  AND empresa_id = ?
            ");

            $stmt->execute([
                $id,
                $empresaId
            ]);

            if ($stmt->rowCount() > 0) {

                $mensagem = 'Situação do produto alterada com sucesso!';

            } else {

                $mensagem = 'Produto não encontrado ou não pertence à empresa selecionada.';
                $tipoMensagem = 'danger';
            }
        }
    }
}

// ==========================================
// CARREGAR PRODUTO PARA EDIÇÃO
// ==========================================

if (isset($_GET['editar'])) {

    $idEdicao = (int) $_GET['editar'];

    if ($idEdicao > 0) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM produtos
            WHERE id = ?
              AND empresa_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $idEdicao,
            $empresaId
        ]);

        $produtoEdicao = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produtoEdicao) {

            $codigo = $produtoEdicao['codigo'];
            $nome = $produtoEdicao['nome'];
            $categoriaId = (int) $produtoEdicao['categoria_id'];
            $descricao = $produtoEdicao['descricao'];
            $unidade = $produtoEdicao['unidade'];
            $estoqueMinimo = $produtoEdicao['estoque_minimo'];
            $ativo = (int) $produtoEdicao['ativo'];

        } else {

            $idEdicao = 0;
            $mensagem = 'Produto não encontrado ou não pertence à empresa selecionada.';
            $tipoMensagem = 'danger';
        }

    } else {

        $idEdicao = 0;
    }
}

// ==========================================
// CATEGORIAS DISPONÍVEIS
// SOMENTE DA EMPRESA SELECIONADA
// ==========================================

$stmt = $pdo->prepare("
    SELECT id, nome, ativo
    FROM categorias
    WHERE empresa_id = ?
      AND (
          ativo = 1
          OR id = ?
      )
    ORDER BY nome
");

$stmt->execute([
    $empresaId,
    $categoriaId
]);

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// PESQUISA DE PRODUTOS
// SOMENTE DA EMPRESA SELECIONADA
// ==========================================

$pesquisa = trim($_GET['pesquisa'] ?? '');

$sql = "
    SELECT
        p.*,
        c.nome AS categoria
    FROM produtos p
    INNER JOIN categorias c
        ON c.id = p.categoria_id
       AND c.empresa_id = p.empresa_id
    WHERE p.empresa_id = ?
";

$parametros = [$empresaId];

if ($pesquisa !== '') {

    $sql .= "
        AND (
            p.nome LIKE ?
            OR p.codigo LIKE ?
        )
    ";

    $parametros[] = '%' . $pesquisa . '%';
    $parametros[] = '%' . $pesquisa . '%';
}

$sql .= " ORDER BY p.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';

?>

<h1 class="page-title">
    <i class="bi bi-box-seam text-primary"></i>
    Cadastro de Produtos
</h1>

<?php if ($mensagem): ?>

    <div class="alert alert-<?= h($tipoMensagem) ?> alert-dismissible fade show">

        <?= h($mensagem) ?>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"></button>

    </div>

<?php endif; ?>

<!-- ======================================
     FORMULÁRIO DE PRODUTOS
======================================= -->

<div class="card card-padrao mb-4">

    <div class="card-header bg-white py-3">

        <strong>
            <i class="bi bi-pencil-square me-2"></i>

            <?= $idEdicao > 0
                ? 'Alterar Produto'
                : 'Novo Produto' ?>
        </strong>

    </div>

    <div class="card-body">

        <form method="POST">

            <input type="hidden" name="acao" value="salvar">

            <input type="hidden" name="id" value="<?= (int) $idEdicao ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= h($_SESSION['csrf_token']) ?>">

            <div class="row">

                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Código *
                    </label>

                    <input type="text"
                           name="codigo"
                           class="form-control"
                           maxlength="50"
                           value="<?= h($codigo) ?>"
                           required>

                </div>

                <div class="col-md-5 mb-3">

                    <label class="form-label">
                        Nome do produto *
                    </label>

                    <input type="text"
                           name="nome"
                           class="form-control"
                           maxlength="150"
                           value="<?= h($nome) ?>"
                           required>

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Categoria *
                    </label>

                    <select name="categoria_id"
                            class="form-select"
                            required>

                        <option value="">Selecione uma categoria</option>

                        <?php foreach ($categorias as $categoria): ?>

                            <option
                                value="<?= (int) $categoria['id'] ?>"
                                <?= $categoriaId == $categoria['id'] ? 'selected' : '' ?>>

                                <?= h($categoria['nome']) ?>

                                <?= !(int) $categoria['ativo'] ? ' (Inativa)' : '' ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Unidade de medida *
                    </label>

                    <select name="unidade"
                            class="form-select"
                            required>

                        <?php
                        $unidades = [
                            'UN' => 'Unidade (UN)',
                            'CX' => 'Caixa (CX)',
                            'PC' => 'Peça (PC)',
                            'KG' => 'Quilograma (KG)',
                            'G'  => 'Grama (G)',
                            'LT' => 'Litro (LT)',
                            'ML' => 'Mililitro (ML)',
                            'MT' => 'Metro (MT)',
                            'M2' => 'Metro quadrado (M²)',
                            'M3' => 'Metro cúbico (M³)',
                            'PCT' => 'Pacote (PCT)',
                            'FD' => 'Fardo (FD)',
                            'RL' => 'Rolo (RL)',
                            'PAR' => 'Par (PAR)'
                        ];

                        foreach ($unidades as $sigla => $descricaoUnidade):
                        ?>

                            <option value="<?= $sigla ?>"
                                <?= $unidade === $sigla ? 'selected' : '' ?>>

                                <?= $descricaoUnidade ?>

                            </option>

                        <?php endforeach; ?>

                        <?php if (!isset($unidades[$unidade]) && $unidade !== ''): ?>

                            <option value="<?= h($unidade) ?>" selected>
                                <?= h($unidade) ?>
                            </option>

                        <?php endif; ?>

                    </select>

                </div>

                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Estoque mínimo
                    </label>

                    <input type="number"
                           name="estoque_minimo"
                           class="form-control"
                           min="0"
                           step="0.001"
                           value="<?= h($estoqueMinimo) ?>"
                           required>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Descrição
                    </label>

                    <input type="text"
                           name="descricao"
                           class="form-control"
                           maxlength="10000"
                           value="<?= h($descricao) ?>">

                </div>

                <div class="col-md-12 mb-3">

                    <div class="form-check">

                        <input type="checkbox"
                               class="form-check-input"
                               name="ativo"
                               id="ativo"
                               value="1"
                               <?= $ativo ? 'checked' : '' ?>>

                        <label class="form-check-label" for="ativo">
                            Produto ativo
                        </label>

                    </div>

                </div>

            </div>

            <button type="submit" class="btn btn-principal">

                <i class="bi bi-check-circle me-1"></i>
                Salvar

            </button>

            <?php if ($idEdicao > 0): ?>

                <a href="produtos.php" class="btn btn-secondary">
                    Cancelar
                </a>

            <?php endif; ?>

        </form>

    </div>

</div>

<!-- ======================================
     LISTAGEM DE PRODUTOS
======================================= -->

<div class="card card-padrao">

<div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">

    <strong>
        <i class="bi bi-list-ul me-2"></i>
        Produtos cadastrados
    </strong>

    <a href="exportar_produtos.php" class="btn btn-success btn-sm">

        <i class="bi bi-file-earmark-spreadsheet me-1"></i>
        Exportar produtos

    </a>

</div>

    <div class="card-body">

        <form method="GET" class="row g-2 mb-4">

            <div class="col-md-5">

                <input type="text"
                       name="pesquisa"
                       class="form-control"
                       placeholder="Pesquisar por código ou nome..."
                       value="<?= h($pesquisa) ?>">

            </div>

            <div class="col-auto">

                <button type="submit" class="btn btn-principal">
                    <i class="bi bi-search"></i>
                    Pesquisar
                </button>

            </div>

            <div class="col-auto">

                <a href="produtos.php" class="btn btn-secondary">
                    Limpar
                </a>

            </div>

        </form>

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
                        <th>Situação</th>
                        <th class="text-center">Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($produtos as $produto): ?>

                        <tr>

                            <td><?= h($produto['codigo']) ?></td>

                            <td><?= h($produto['nome']) ?></td>

                            <td><?= h($produto['categoria']) ?></td>

                            <td><?= h($produto['unidade']) ?></td>

                            <td>

                                <?= number_format(
                                    (float) $produto['estoque_atual'],
                                    3,
                                    ',',
                                    '.'
                                ) ?>

                            </td>

                            <td>

                                <?= number_format(
                                    (float) $produto['estoque_minimo'],
                                    3,
                                    ',',
                                    '.'
                                ) ?>

                            </td>

                            <td>

                                <?php if ((int) $produto['ativo'] === 1): ?>

                                    <span class="badge bg-success">
                                        Ativo
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Inativo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="text-center">

                                <a href="produtos.php?editar=<?= (int) $produto['id'] ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Editar">

                                    <i class="bi bi-pencil"></i>

                                </a>

                                <form method="POST"
                                      class="d-inline"
                                      data-confirm="Deseja realmente alterar a situação deste produto?">

                                    <input type="hidden"
                                           name="acao"
                                           value="alterar_status">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int) $produto['id'] ?>">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?= h($_SESSION['csrf_token']) ?>">

                                    <button type="submit"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="<?= (int) $produto['ativo'] === 1 ? 'Desativar' : 'Ativar' ?>">

                                        <i class="bi <?= (int) $produto['ativo'] === 1
                                            ? 'bi-toggle-on'
                                            : 'bi-toggle-off' ?>"></i>

                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$produtos): ?>

                        <tr>

                            <td colspan="8"
                                class="text-center text-muted py-4">

                                Nenhum produto encontrado.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="text-muted small">
            Total de produtos encontrados: <?= count($produtos) ?>
        </div>

    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
