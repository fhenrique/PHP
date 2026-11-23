
<?php

require_once 'includes/auth.php';
exigirEmpresaSelecionada();
require_once 'config/conexao.php';

$empresaId = (int)($_SESSION['empresa_id'] ?? 0);
$empresaNome = $_SESSION['empresa_nome'] ?? '';

if ($empresaId <= 0) {
    http_response_code(403);
    exit('Nenhuma empresa válida foi selecionada.');
}

$tituloPagina = 'Cadastro de Categorias';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$tipoMensagem = 'success';

$nome = '';
$descricao = '';
$idEdicao = 0;
$ativo = 1;

// ==========================================
// PROCESSAMENTO DOS FORMULÁRIOS
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        exit('Requisição inválida. Atualize a página e tente novamente.');
    }

    $acao = $_POST['acao'] ?? '';

    // ======================================
    // INCLUIR OU ALTERAR CATEGORIA
    // ======================================

    if ($acao === 'salvar') {

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '') {

            $mensagem = 'Informe o nome da categoria.';
            $tipoMensagem = 'danger';

        } else {

            try {

                if ($id > 0) {

                    // Confirma que a categoria pertence à empresa selecionada.
                    $stmt = $pdo->prepare("
                        SELECT id
                        FROM categorias
                        WHERE id = ?
                          AND empresa_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$id, $empresaId]);

                    if (!$stmt->fetchColumn()) {
                        $mensagem = 'Categoria não encontrada nesta empresa.';
                        $tipoMensagem = 'danger';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE categorias
                            SET nome = ?,
                                descricao = ?,
                                ativo = ?
                            WHERE id = ?
                              AND empresa_id = ?
                        ");

                        $stmt->execute([
                            $nome,
                            $descricao,
                            $ativo,
                            $id,
                            $empresaId
                        ]);

                        $mensagem = 'Categoria atualizada com sucesso!';
                    }

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO categorias
                            (empresa_id, nome, descricao, ativo)
                        VALUES (?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $empresaId,
                        $nome,
                        $descricao,
                        $ativo
                    ]);

                    $mensagem = 'Categoria cadastrada com sucesso!';
                }

                $nome = '';
                $descricao = '';
                $ativo = 1;

            } catch (PDOException $e) {

                if ($e->getCode() === '23000') {
                    $mensagem = 'Já existe uma categoria com esse nome.';
                } else {
                    error_log($e->getMessage());
                    $mensagem = 'Erro ao salvar a categoria.';
                }

                $tipoMensagem = 'danger';
            }
        }
    }

    // ======================================
    // ATIVAR OU DESATIVAR
    // ======================================

    if ($acao === 'alterar_status') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("
            UPDATE categorias
            SET ativo = IF(ativo = 1, 0, 1)
            WHERE id = ?
              AND empresa_id = ?
        ");

        $stmt->execute([$id, $empresaId]);

        if ($stmt->rowCount() > 0) {
            $mensagem = 'Situação da categoria alterada com sucesso!';
        } else {
            $mensagem = 'Categoria não encontrada nesta empresa ou sem alteração.';
            $tipoMensagem = 'danger';
        }
    }
}

// ==========================================
// CARREGAR CATEGORIA PARA EDIÇÃO
// ==========================================

if (isset($_GET['editar'])) {

    $idEdicao = (int)$_GET['editar'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM categorias
        WHERE id = ?
          AND empresa_id = ?
        LIMIT 1
    ");

    $stmt->execute([$idEdicao, $empresaId]);

    $categoriaEdicao = $stmt->fetch();

    if ($categoriaEdicao) {

        $nome = $categoriaEdicao['nome'];
        $descricao = $categoriaEdicao['descricao'];
        $ativo = $categoriaEdicao['ativo'];

    } else {

        $idEdicao = 0;
    }
}

// ==========================================
// PESQUISA E LISTAGEM
// ==========================================

$pesquisa = trim($_GET['pesquisa'] ?? '');

$sql = "
    SELECT *
    FROM categorias
    WHERE empresa_id = ?
";

$parametros = [$empresaId];

if ($pesquisa !== '') {

    $sql .= " AND nome LIKE ?";

    $parametros[] = '%' . $pesquisa . '%';
}

$sql .= " ORDER BY nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);

$categorias = $stmt->fetchAll();

require_once 'includes/header.php';

?>

<h1 class="page-title">
    <i class="bi bi-tags text-primary"></i>
    Cadastro de Categorias
</h1>

<?php if ($empresaNome !== ''): ?>
    <p class="text-muted small mb-3">
        Empresa selecionada: <strong><?= h($empresaNome) ?></strong>
    </p>
<?php endif; ?>

<?php if ($mensagem): ?>

    <div class="alert alert-<?= h($tipoMensagem) ?> alert-dismissible fade show">

        <?= h($mensagem) ?>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"></button>

    </div>

<?php endif; ?>

<!-- ======================================
     FORMULÁRIO
======================================= -->

<div class="card card-padrao mb-4">

    <div class="card-header bg-white py-3">

        <strong>
            <i class="bi bi-pencil-square me-2"></i>

            <?= $idEdicao > 0
                ? 'Alterar Categoria'
                : 'Nova Categoria' ?>
        </strong>

    </div>

    <div class="card-body">

        <form method="POST">

            <input type="hidden" name="acao" value="salvar">

            <input type="hidden" name="id" value="<?= $idEdicao ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= h($_SESSION['csrf_token']) ?>">

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Nome da categoria *
                    </label>

                    <input type="text"
                           name="nome"
                           class="form-control"
                           maxlength="100"
                           value="<?= h($nome) ?>"
                           required>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Descrição
                    </label>

                    <input type="text"
                           name="descricao"
                           class="form-control"
                           maxlength="255"
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
                            Categoria ativa
                        </label>

                    </div>

                </div>

            </div>

            <button type="submit" class="btn btn-principal">
                <i class="bi bi-check-circle me-1"></i>
                Salvar
            </button>

            <?php if ($idEdicao > 0): ?>

                <a href="categorias.php" class="btn btn-secondary">
                    Cancelar
                </a>

            <?php endif; ?>

        </form>

    </div>

</div>

<!-- ======================================
     PESQUISA
======================================= -->

<div class="card card-padrao">

    <div class="card-header bg-white py-3">

        <strong>
            <i class="bi bi-list-ul me-2"></i>
            Categorias cadastradas
        </strong>

    </div>

    <div class="card-body">

        <form method="GET" class="row g-2 mb-4">

            <div class="col-md-5">

                <input type="text"
                       name="pesquisa"
                       class="form-control"
                       placeholder="Pesquisar categoria..."
                       value="<?= h($pesquisa) ?>">

            </div>

            <div class="col-auto">

                <button type="submit" class="btn btn-principal">
                    <i class="bi bi-search"></i>
                    Pesquisar
                </button>

            </div>

            <div class="col-auto">

                <a href="categorias.php" class="btn btn-secondary">
                    Limpar
                </a>

            </div>

        </form>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Situação</th>
                        <th class="text-center">Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($categorias as $categoria): ?>

                        <tr>

                            <td><?= (int)$categoria['id'] ?></td>

                            <td>
                                <?= h($categoria['nome']) ?>
                            </td>

                            <td>
                                <?= h($categoria['descricao']) ?>
                            </td>

                            <td>

                                <?php if ($categoria['ativo']): ?>

                                    <span class="badge bg-success">
                                        Ativa
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Inativa
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="text-center">

                                <a href="categorias.php?editar=<?= (int)$categoria['id'] ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Editar">

                                    <i class="bi bi-pencil"></i>

                                </a>

                                <form method="POST"
                                      class="d-inline"
                                      data-confirm="Deseja realmente alterar a situação desta categoria?">                                    

                                    <input type="hidden"
                                           name="acao"
                                           value="alterar_status">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int)$categoria['id'] ?>">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?= h($_SESSION['csrf_token']) ?>">

                                    <button type="submit"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="<?= $categoria['ativo'] ? 'Desativar' : 'Ativar' ?>">

                                        <i class="bi <?= $categoria['ativo']
                                            ? 'bi-toggle-on'
                                            : 'bi-toggle-off' ?>"></i>

                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$categorias): ?>

                        <tr>

                            <td colspan="5"
                                class="text-center text-muted py-4">

                                Nenhuma categoria encontrada.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="text-muted small">
            Total de categorias encontradas: <?= count($categorias) ?>
        </div>

    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
