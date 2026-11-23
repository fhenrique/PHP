<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.nome,
            u.email,
            u.senha,
            u.perfil_id,
            u.empresa_id,
            p.nome AS perfil
        FROM usuarios u
        INNER JOIN perfis p ON p.id = u.perfil_id
        WHERE u.email = ?
          AND u.ativo = 1
          AND p.ativo = 1
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $empresa = null;
        $perfilId = (int) $usuario['perfil_id'];

        if ($perfilId === 3) {
            if (!empty($usuario['empresa_id'])) {
                $stmtEmpresa = $pdo->prepare("SELECT id, nome FROM empresas WHERE id = ? AND ativo = 1 LIMIT 1");
                $stmtEmpresa->execute([(int) $usuario['empresa_id']]);
                $empresa = $stmtEmpresa->fetch();
            }
            if (!$empresa) {
                $stmtEmpresa = $pdo->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome LIMIT 1");
                $empresa = $stmtEmpresa->fetch();
            }
        } else {
            if (empty($usuario['empresa_id'])) {
                $erro = 'Este usuário não possui uma empresa vinculada. Entre em contato com o administrador.';
            } else {
                $stmtEmpresa = $pdo->prepare("SELECT id, nome FROM empresas WHERE id = ? AND ativo = 1 LIMIT 1");
                $stmtEmpresa->execute([(int) $usuario['empresa_id']]);
                $empresa = $stmtEmpresa->fetch();
                if (!$empresa) {
                    $erro = 'A empresa vinculada a este usuário está inativa ou não existe.';
                }
            }
        }

        if ($erro === '') {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['perfil_id'] = $perfilId;
            $_SESSION['perfil'] = $usuario['perfil'];
            $_SESSION['empresa_id'] = $empresa ? (int) $empresa['id'] : null;
            $_SESSION['empresa_nome'] = $empresa ? $empresa['nome'] : null;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        }
    } elseif ($erro === '') {
        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EstoqueWeb - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/estilo.css?v=10" rel="stylesheet">
    <style>
        body { background: #f1f4f9; }
        .login-card {
            max-width: 420px;
            margin: 100px auto;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }
        .btn-login { background: #174a78; color: white; }
        .btn-login:hover { background: #103657; color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow login-card">
        <div class="card-body p-5">
            
            <div class="login-logo-wrap">
                
                <img src="assets/img/logo.jpg" alt="Logotipo EstoqueWeb" class="login-logo" style="width: 280px !important; max-width: 90% !important; height: auto !important;">                
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-control" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-login w-100">Entrar</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>