
<?php
require_once __DIR__ . '/includes/auth.php';

// Esta tela é exclusiva do Administrador Geral (perfil_id = 3).
// A verificação ocorre antes de qualquer processamento POST.
if ((int) ($_SESSION['perfil_id'] ?? 0) !== 3) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Acesso Negado - EstoqueWeb</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <style>
            body{background:#f1f4f9;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:Arial,sans-serif}
            .acesso-card{max-width:480px;width:90%;background:#fff;border-radius:15px;padding:40px 30px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08)}
            .icone-erro{font-size:65px;color:#dc3545;margin-bottom:15px}
            .contador{font-size:48px;font-weight:bold;color:#08749b;margin:15px 0}
            .btn-voltar{background:#08749b;color:#fff;padding:10px 25px;border-radius:7px;text-decoration:none}
            .btn-voltar:hover{background:#065c7c;color:#fff}
        </style>
    </head>
    <body>
        <div class="acesso-card">
            <div class="icone-erro"><i class="bi bi-shield-lock-fill"></i></div>
            <h3 class="mb-3">Acesso Negado!</h3>
            <p class="text-muted">Você não possui permissão para acessar esta funcionalidade.</p>
            <hr>
            <p class="mb-1">Você será redirecionado para o Painel Inicial em:</p>
            <div class="contador" id="contador">5</div>
            <p class="text-muted small">Aguarde, estamos retornando ao sistema...</p>
            <a href="index.php" class="btn-voltar"><i class="bi bi-house-door me-1"></i>Voltar agora</a>
        </div>
        <script>
            let segundos = 5;
            const contador = document.getElementById('contador');
            const intervalo = setInterval(function () {
                segundos--;
                contador.textContent = segundos;
                if (segundos <= 0) {
                    clearInterval(intervalo);
                    window.location.replace('index.php');
                }
            }, 1000);
        </script>
    </body>
    </html>
    <?php
    exit;
}

$tituloPagina = 'Gerenciamento de Empresas';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$tipoMensagem = 'success';
$idEdicao = 0;
$nome = '';
$ativo = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecebido = $_POST['csrf_token'] ?? '';

    if (!is_string($tokenRecebido) || !hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
        http_response_code(403);
        exit('Requisição inválida. Atualize a página e tente novamente.');
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        $id = ($id !== false && $id > 0) ? $id : 0;
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $idEdicao = $id;

        if ($nome === '' || mb_strlen($nome, 'UTF-8') > 150) {
            $mensagem = 'Informe um nome válido para a empresa (até 150 caracteres).';
            $tipoMensagem = 'danger';
        } else {
            try {
                if ($id > 0) {
                    $stmtExiste = $pdo->prepare("SELECT id FROM empresas WHERE id = ? LIMIT 1");
                    $stmtExiste->execute([$id]);

                    if (!$stmtExiste->fetchColumn()) {
                        $mensagem = 'Empresa não encontrada.';
                        $tipoMensagem = 'danger';
                    } else {
                        // Não permite desativar a última empresa ativa.
                        if ($ativo === 0) {
                            $stmtAtivas = $pdo->query("SELECT COUNT(*) FROM empresas WHERE ativo = 1");
                            if ((int) $stmtAtivas->fetchColumn() <= 1) {
                                $mensagem = 'Não é possível desativar a última empresa ativa.';
                                $tipoMensagem = 'danger';
                            }
                        }

                        if ($tipoMensagem !== 'danger') {
                            $stmt = $pdo->prepare("UPDATE empresas SET nome = ?, ativo = ? WHERE id = ?");
                            $stmt->execute([$nome, $ativo, $id]);
                            $mensagem = 'Empresa atualizada com sucesso!';
                            $idEdicao = 0;
                            $nome = '';
                            $ativo = 1;
                        }
                    }
                } else {
                    // Uma nova empresa sempre começa ativa.
                    $stmt = $pdo->prepare("INSERT INTO empresas (nome, ativo) VALUES (?, 1)");
                    $stmt->execute([$nome]);
                    $mensagem = 'Empresa cadastrada com sucesso!';
                    $nome = '';
                    $ativo = 1;
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $mensagem = 'Já existe uma empresa cadastrada com esse nome.';
                } else {
                    error_log('Erro em empresas.php ao salvar: ' . $e->getMessage());
                    $mensagem = 'Não foi possível salvar a empresa. Consulte o registro de erros.';
                }
                $tipoMensagem = 'danger';
            }
        }
    } elseif ($acao === 'alterar_status') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $mensagem = 'Empresa inválida.';
            $tipoMensagem = 'danger';
        } else {
            $stmt = $pdo->prepare("SELECT id, ativo FROM empresas WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $empresaStatus = $stmt->fetch();

            if (!$empresaStatus) {
                $mensagem = 'Empresa não encontrada.';
                $tipoMensagem = 'danger';
            } elseif ((int) $empresaStatus['ativo'] === 1) {
                $stmtAtivas = $pdo->query("SELECT COUNT(*) FROM empresas WHERE ativo = 1");
                if ((int) $stmtAtivas->fetchColumn() <= 1) {
                    $mensagem = 'Não é possível desativar a última empresa ativa.';
                    $tipoMensagem = 'danger';
                } else {
                    $stmt = $pdo->prepare("UPDATE empresas SET ativo = 0 WHERE id = ?");
                    $stmt->execute([$id]);
                    $mensagem = 'Empresa desativada com sucesso!';
                }
            } else {
                $stmt = $pdo->prepare("UPDATE empresas SET ativo = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $mensagem = 'Empresa ativada com sucesso!';
            }
        }
    }
}

if (isset($_GET['editar'])) {
    $idEdicao = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
    $idEdicao = ($idEdicao !== false && $idEdicao > 0) ? $idEdicao : 0;

    if ($idEdicao > 0) {
        $stmt = $pdo->prepare("SELECT id, nome, ativo FROM empresas WHERE id = ? LIMIT 1");
        $stmt->execute([$idEdicao]);
        $empresaEdicao = $stmt->fetch();

        if ($empresaEdicao) {
            $nome = $empresaEdicao['nome'];
            $ativo = (int) $empresaEdicao['ativo'];
        } else {
            $idEdicao = 0;
            $mensagem = 'Empresa não encontrada.';
            $tipoMensagem = 'danger';
        }
    }
}

$stmt = $pdo->query("SELECT id, nome, ativo, criado_em FROM empresas ORDER BY nome ASC");
$empresas = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">
    <i class="bi bi-buildings text-primary"></i>
    Gerenciamento de Empresas
</h1>

<p class="text-muted mb-4">Cadastre e gerencie as empresas que utilizarão o EstoqueWeb.</p>

<?php if ($mensagem): ?>
    <div class="alert alert-<?= h($tipoMensagem) ?> alert-dismissible fade show" role="alert">
        <?= h($mensagem) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>

<div class="card card-padrao mb-4">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-pencil-square me-2"></i><?= $idEdicao > 0 ? 'Alterar Empresa' : 'Nova Empresa' ?></strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" value="<?= (int) $idEdicao ?>">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

            <div class="row align-items-end">
                <div class="col-md-8 mb-3">
                    <label for="nome" class="form-label">Nome da empresa *</label>
                    <input type="text" id="nome" name="nome" class="form-control"
                           maxlength="150" value="<?= h($nome) ?>" required>
                </div>

                <?php if ($idEdicao > 0): ?>
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="ativo"
                                   id="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Empresa ativa</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-principal">
                <i class="bi bi-check-circle me-1"></i>Salvar
            </button>

            <?php if ($idEdicao > 0): ?>
                <a href="empresas.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card card-padrao">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-list-ul me-2"></i>Empresas cadastradas</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Empresa</th>
                        <th>Data de cadastro</th>
                        <th>Situação</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empresas as $empresa): ?>
                        <tr>
                            <td><?= (int) $empresa['id'] ?></td>
                            <td><?= h($empresa['nome']) ?></td>
                            <td><?= $empresa['criado_em'] ? date('d/m/Y H:i', strtotime($empresa['criado_em'])) : '-' ?></td>
                            <td>
                                <?php if ((int) $empresa['ativo'] === 1): ?>
                                    <span class="badge bg-success">Ativa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="empresas.php?editar=<?= (int) $empresa['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form method="POST" class="d-inline"
                                      data-confirm="<?= (int) $empresa['ativo'] === 1
                                          ? 'Deseja realmente desativar esta empresa?'
                                          : 'Deseja realmente ativar esta empresa?' ?>">
                                    <input type="hidden" name="acao" value="alterar_status">
                                    <input type="hidden" name="id" value="<?= (int) $empresa['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            title="<?= (int) $empresa['ativo'] === 1 ? 'Desativar' : 'Ativar' ?>">
                                        <i class="bi <?= (int) $empresa['ativo'] === 1 ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$empresas): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma empresa cadastrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-muted small">Total de empresas: <?= count($empresas) ?></div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
