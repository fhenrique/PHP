<?php
require_once 'includes/auth.php';
exigirAdministrador();
exigirEmpresaSelecionada();

$tituloPagina = 'Cadastro de Usuários';
$empresaId = (int) $_SESSION['empresa_id'];
$administradorGeral = ((int) $_SESSION['perfil_id'] === 3);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$tipoMensagem = 'success';
$idEdicao = 0;
$nome = '';
$email = '';
$perfilId = 0;
$ativo = 1;

/**
 * Busca um usuário administrável dentro da empresa selecionada.
 * O perfil 3 (Administrador Geral) é reservado e não pode ser
 * administrado por este cadastro de usuários.
 */
function buscarUsuarioDaEmpresa(PDO $pdo, int $id, int $empresaId)
{
    $stmt = $pdo->prepare("\n        SELECT u.id, u.nome, u.email, u.perfil_id, u.ativo, p.nome AS perfil\n        FROM usuarios u\n        INNER JOIN perfis p ON p.id = u.perfil_id\n        WHERE u.id = ?\n          AND u.empresa_id = ?\n          AND u.perfil_id <> 3\n        LIMIT 1\n    ");
    $stmt->execute([$id, $empresaId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecebido = $_POST['csrf_token'] ?? '';
    if (!is_string($tokenRecebido) || !hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
        http_response_code(403);
        exit('Requisição inválida. Atualize a página.');
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $id = (int) ($_POST['id'] ?? 0);
        $idEdicao = $id;
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $perfilId = (int) ($_POST['perfil_id'] ?? 0);
        $senha = (string) ($_POST['senha'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (
            $nome === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !in_array($perfilId, [1, 2], true) ||
            ($id === 0 && strlen($senha) < 8) ||
            ($senha !== '' && strlen($senha) < 8)
        ) {
            $mensagem = 'Verifique os campos informados. A senha deve possuir pelo menos 8 caracteres e selecione um perfil válido.';
            $tipoMensagem = 'danger';
        } elseif ($id === (int) $_SESSION['usuario_id'] && $ativo === 0) {
            $mensagem = 'Você não pode desativar sua própria conta.';
            $tipoMensagem = 'danger';
        } elseif ($id === (int) $_SESSION['usuario_id'] && $perfilId !== (int) $_SESSION['perfil_id']) {
            $mensagem = 'Você não pode alterar seu próprio perfil de acesso.';
            $tipoMensagem = 'danger';
        } else {
            try {
                $usuarioAnterior = null;
                if ($id > 0) {
                    $usuarioAnterior = buscarUsuarioDaEmpresa($pdo, $id, $empresaId);
                    if (!$usuarioAnterior) {
                        throw new RuntimeException('Usuário não encontrado na empresa selecionada.');
                    }
                }

                $stmt = $pdo->prepare("\n                    SELECT id FROM perfis\n                    WHERE id = ? AND ativo = 1 AND id IN (1, 2)\n                    LIMIT 1\n                ");
                $stmt->execute([$perfilId]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Perfil inválido ou inativo.');
                }

                // O e-mail permanece único globalmente, conforme a validação original.
                $stmt = $pdo->prepare("\n                    SELECT id FROM usuarios\n                    WHERE email = ? AND id <> ?\n                    LIMIT 1\n                ");
                $stmt->execute([$email, $id]);
                if ($stmt->fetchColumn()) {
                    throw new RuntimeException('Este e-mail já está cadastrado.');
                }

                // Impede que a empresa fique sem nenhum Administrador ativo.
                if ($usuarioAnterior && (int) $usuarioAnterior['perfil_id'] === 1) {
                    $deixaraDeSerAdministrador = ($perfilId !== 1 || $ativo === 0);
                    if ($deixaraDeSerAdministrador) {
                        $stmt = $pdo->prepare("\n                            SELECT COUNT(*)\n                            FROM usuarios\n                            WHERE empresa_id = ?\n                              AND perfil_id = 1\n                              AND ativo = 1\n                              AND id <> ?\n                        ");
                        $stmt->execute([$empresaId, $id]);
                        if ((int) $stmt->fetchColumn() === 0) {
                            throw new RuntimeException('Não é possível remover o último Administrador ativo desta empresa.');
                        }
                    }
                }

                if ($id > 0) {
                    if ($senha !== '') {
                        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("\n                            UPDATE usuarios\n                            SET nome = ?, email = ?, perfil_id = ?, senha = ?, ativo = ?\n                            WHERE id = ? AND empresa_id = ? AND perfil_id <> 3\n                        ");
                        $stmt->execute([$nome, $email, $perfilId, $senhaHash, $ativo, $id, $empresaId]);
                    } else {
                        $stmt = $pdo->prepare("\n                            UPDATE usuarios\n                            SET nome = ?, email = ?, perfil_id = ?, ativo = ?\n                            WHERE id = ? AND empresa_id = ? AND perfil_id <> 3\n                        ");
                        $stmt->execute([$nome, $email, $perfilId, $ativo, $id, $empresaId]);
                    }
                    if ($stmt->rowCount() === 0) {
                        // UPDATE pode retornar zero quando os dados não mudaram;
                        // a existência já foi validada acima.
                    }
                    $mensagem = 'Usuário atualizado com sucesso!';
                } else {
                    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("\n                        INSERT INTO usuarios (empresa_id, perfil_id, nome, email, senha, ativo)\n                        VALUES (?, ?, ?, ?, ?, ?)\n                    ");
                    $stmt->execute([$empresaId, $perfilId, $nome, $email, $senhaHash, $ativo]);
                    $mensagem = 'Usuário cadastrado com sucesso!';
                }

                $idEdicao = 0;
                $nome = '';
                $email = '';
                $perfilId = 0;
                $ativo = 1;
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $mensagem = 'Erro ao salvar o usuário.';
                $tipoMensagem = 'danger';
            } catch (RuntimeException $e) {
                $mensagem = $e->getMessage();
                $tipoMensagem = 'danger';
            }
        }
    }

    if ($acao === 'alterar_status') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id === (int) $_SESSION['usuario_id']) {
            $mensagem = 'Você não pode desativar sua própria conta.';
            $tipoMensagem = 'danger';
        } else {
            try {
                $usuarioAlvo = buscarUsuarioDaEmpresa($pdo, $id, $empresaId);
                if (!$usuarioAlvo) {
                    throw new RuntimeException('Usuário não encontrado na empresa selecionada.');
                }

                if ((int) $usuarioAlvo['perfil_id'] === 1 && (int) $usuarioAlvo['ativo'] === 1) {
                    $stmt = $pdo->prepare("\n                        SELECT COUNT(*)\n                        FROM usuarios\n                        WHERE empresa_id = ?\n                          AND perfil_id = 1\n                          AND ativo = 1\n                          AND id <> ?\n                    ");
                    $stmt->execute([$empresaId, $id]);
                    if ((int) $stmt->fetchColumn() === 0) {
                        throw new RuntimeException('Não é possível desativar o último Administrador ativo desta empresa.');
                    }
                }

                $stmt = $pdo->prepare("\n                    UPDATE usuarios\n                    SET ativo = IF(ativo = 1, 0, 1)\n                    WHERE id = ? AND empresa_id = ? AND perfil_id <> 3\n                ");
                $stmt->execute([$id, $empresaId]);
                $mensagem = 'Situação do usuário alterada com sucesso!';
            } catch (RuntimeException $e) {
                $mensagem = $e->getMessage();
                $tipoMensagem = 'danger';
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $mensagem = 'Erro ao alterar a situação do usuário.';
                $tipoMensagem = 'danger';
            }
        }
    }
}

// Carrega para edição somente usuários da empresa selecionada.
if (
    isset($_GET['editar']) &&
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    $idEdicao = (int) $_GET['editar'];
    $usuarioEdicao = buscarUsuarioDaEmpresa($pdo, $idEdicao, $empresaId);
    if ($usuarioEdicao) {
        $nome = $usuarioEdicao['nome'];
        $email = $usuarioEdicao['email'];
        $perfilId = (int) $usuarioEdicao['perfil_id'];
        $ativo = (int) $usuarioEdicao['ativo'];
    } else {
        $idEdicao = 0;
        $mensagem = 'Usuário não encontrado na empresa selecionada.';
        $tipoMensagem = 'danger';
    }
}

// Perfil 3 é exclusivo do Administrador Geral e não aparece como opção.
$stmt = $pdo->query("\n    SELECT id, nome\n    FROM perfis\n    WHERE ativo = 1 AND id IN (1, 2)\n    ORDER BY nome\n");
$perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pesquisa = trim($_GET['pesquisa'] ?? '');
$sql = "\n    SELECT u.id, u.nome, u.email, u.ativo, u.criado_em, p.nome AS perfil\n    FROM usuarios u\n    INNER JOIN perfis p ON p.id = u.perfil_id\n    WHERE u.empresa_id = ?\n      AND u.perfil_id <> 3\n";
$parametros = [$empresaId];

if ($pesquisa !== '') {
    $sql .= " AND (u.nome LIKE ? OR u.email LIKE ?)";
    $parametros[] = '%' . $pesquisa . '%';
    $parametros[] = '%' . $pesquisa . '%';
}

$sql .= ' ORDER BY u.nome ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<h1 class="page-title">
    <i class="bi bi-people text-primary"></i>
    Cadastro de Usuários
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
     FORMULÁRIO
======================================= -->

<div class="card card-padrao mb-4">

    <div class="card-header bg-white py-3">

        <strong>
            <i class="bi bi-pencil-square me-2"></i>

            <?= $idEdicao > 0
                ? 'Alterar Usuário'
                : 'Novo Usuário' ?>
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
                        Nome completo *
                    </label>

                    <input type="text"
                           name="nome"
                           class="form-control"
                           maxlength="150"
                           value="<?= h($nome) ?>"
                           required>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        E-mail *
                    </label>

                    <input type="email"
                           name="email"
                           class="form-control"
                           maxlength="150"
                           value="<?= h($email) ?>"
                           required>

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Perfil de acesso *
                    </label>

                    <select name="perfil_id"
                            class="form-select"
                            required>

                        <option value="">
                            Selecione o perfil
                        </option>

                        <?php foreach ($perfis as $perfil): ?>

                            <option
                                value="<?= (int)$perfil['id'] ?>"
                                <?= $perfilId == $perfil['id'] ? 'selected' : '' ?>>

                                <?= h($perfil['nome']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Senha
                        <?= $idEdicao === 0 ? '*' : '' ?>
                    </label>

                    <input type="password"
                           name="senha"
                           class="form-control"
                           minlength="8"
                           autocomplete="new-password"
                           <?= $idEdicao === 0 ? 'required' : '' ?>>

                    <small class="text-muted">
                        <?= $idEdicao > 0
                            ? 'Deixe em branco para manter a senha atual.'
                            : 'Mínimo de 8 caracteres.' ?>
                    </small>

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Situação
                    </label>

                    <div class="form-check mt-2">

                        <input type="checkbox"
                               class="form-check-input"
                               name="ativo"
                               id="ativo"
                               value="1"
                               <?= $ativo ? 'checked' : '' ?>>

                        <label class="form-check-label" for="ativo">
                            Usuário ativo
                        </label>

                    </div>

                </div>

            </div>

            <button type="submit" class="btn btn-principal">

                <i class="bi bi-check-circle me-1"></i>
                Salvar

            </button>

            <?php if ($idEdicao > 0): ?>

                <a href="usuarios.php" class="btn btn-secondary">
                    Cancelar
                </a>

            <?php endif; ?>

        </form>

    </div>

</div>

<!-- ======================================
     LISTAGEM
======================================= -->

<div class="card card-padrao">

    <div class="card-header bg-white py-3">

        <strong>
            <i class="bi bi-list-ul me-2"></i>
            Usuários cadastrados
        </strong>

    </div>

    <div class="card-body">

        <form method="GET" class="row g-2 mb-4">

            <div class="col-md-5">

                <input type="text"
                       name="pesquisa"
                       class="form-control"
                       placeholder="Pesquisar por nome ou e-mail..."
                       value="<?= h($pesquisa) ?>">

            </div>

            <div class="col-auto">

                <button type="submit" class="btn btn-principal">
                    <i class="bi bi-search"></i>
                    Pesquisar
                </button>

            </div>

            <div class="col-auto">

                <a href="usuarios.php" class="btn btn-secondary">
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
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Situação</th>
                        <th class="text-center">Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($usuarios as $usuario): ?>

                        <tr>

                            <td><?= (int)$usuario['id'] ?></td>

                            <td>

                                <?= h($usuario['nome']) ?>

                                <?php if ((int)$usuario['id'] === (int)$_SESSION['usuario_id']): ?>

                                    <span class="badge bg-info text-dark">
                                        Você
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td><?= h($usuario['email']) ?></td>

                            <td>

                                <?php if ($usuario['perfil'] === 'Administrador'): ?>

                                    <span class="badge bg-primary">
                                        Administrador
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Operador
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($usuario['ativo']): ?>

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

                                <a href="usuarios.php?editar=<?= (int)$usuario['id'] ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Editar">

                                    <i class="bi bi-pencil"></i>

                                </a>

                                <?php if ((int)$usuario['id'] !== (int)$_SESSION['usuario_id']): ?>

                                    <form method="POST"
                                          class="d-inline"
                                          data-confirm="Deseja realmente alterar a situação deste usuário?">

                                        <input type="hidden"
                                               name="acao"
                                               value="alterar_status">

                                        <input type="hidden"
                                               name="id"
                                               value="<?= (int)$usuario['id'] ?>">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= h($_SESSION['csrf_token']) ?>">

                                        <button type="submit"
                                                class="btn btn-sm btn-outline-secondary"
                                                title="<?= $usuario['ativo'] ? 'Desativar' : 'Ativar' ?>">

                                            <i class="bi <?= $usuario['ativo']
                                                ? 'bi-toggle-on'
                                                : 'bi-toggle-off' ?>"></i>

                                        </button>

                                    </form>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$usuarios): ?>

                        <tr>

                            <td colspan="6"
                                class="text-center text-muted py-4">

                                Nenhum usuário encontrado.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="text-muted small">
            Total de usuários encontrados: <?= count($usuarios) ?>
        </div>

    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
