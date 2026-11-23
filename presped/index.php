<?php
// C:\xampp\htdocs\presped\index.php
require 'config.php';

// Se já está logado, vai para o dashboard
if (estaLogado()) {
    header("Location: pages/dashboard.php");
    exit;
}

$erro = '';
$sucesso = '';

$abas_validas = ['login', 'cadastro', 'esqueci-senha', 'redefinir-senha'];
$aba = $_GET['aba'] ?? 'login';

if (!in_array($aba, $abas_validas)) {
    $aba = 'login';
}

if (!empty($_SESSION['flash'])) {
    $sucesso = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if (!empty($_SESSION['flash_erro'])) {
    $erro = $_SESSION['flash_erro'];
    unset($_SESSION['flash_erro']);
}

// --- ESQUECI MINHA SENHA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'esqueci-senha') {

    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id FROM escritorios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        $pdo->prepare("
            INSERT INTO password_resets (email, token, expira_em)
            VALUES (?,?,?)
        ")->execute([$email, $token, $expira]);

        $link = "http://" . $_SERVER['HTTP_HOST'] .
            dirname($_SERVER['PHP_SELF']) .
            "/index.php?aba=redefinir-senha&token=" . $token;

        $_SESSION['link_redefinicao_dev'] = $link;
    }

    $_SESSION['flash'] = 'Se esse e-mail existir, enviamos o link de redefinição. Verifique sua caixa de entrada e spam.';

    header('Location: index.php?aba=esqueci-senha');
    exit;
}

// --- REDEFINIR SENHA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'redefinir-senha') {

    $token = $_POST['token'] ?? '';
    $nova = $_POST['nova_senha'] ?? '';
    $confirma = $_POST['confirma_senha'] ?? '';

    if ($nova !== $confirma || strlen($nova) < 6) {

        $_SESSION['flash_erro'] = 'As senhas não conferem ou têm menos de 6 caracteres.';

        header('Location: index.php?aba=redefinir-senha&token=' . urlencode($token));
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM password_resets
        WHERE token = ? AND usado = 0 AND expira_em > NOW()
    ");

    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {

        $_SESSION['flash_erro'] = 'Link inválido ou expirado. Peça um novo link.';

        header('Location: index.php?aba=esqueci-senha');
        exit;
    }

    $hash = password_hash($nova, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE escritorios SET senha = ? WHERE email = ?");
    $stmt->execute([$hash, $reset['email']]);

    if ($stmt->rowCount() === 0) {
        $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = ?")
            ->execute([$hash, $reset['email']]);
    }

    $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE id = ?")
        ->execute([$reset['id']]);

    $_SESSION['flash'] = 'Senha alterada com sucesso! Faça login com sua nova senha.';

    header('Location: index.php?aba=login');
    exit;
}

// ===== CADASTRO DE ESCRITÓRIO =====
$nome_cad = '';
$email_cad = '';

$planos_validos = ['teste', 'start', 'pro', 'escritorio', 'enterprise'];

$plano_url = $_GET['plano'] ?? '';
$plano_cad = in_array($plano_url, $planos_validos) ? $plano_url : 'teste';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'cadastro') {

    $nome_cad = trim($_POST['nome'] ?? '');
    $email_cad = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $plano_cad = $_POST['plano'] ?? 'teste';

    if (!in_array($plano_cad, $planos_validos)) {
        $plano_cad = 'teste';
    }

    if ($nome_cad === '' || $email_cad === '' || $senha === '') {

        $erro = 'Preencha nome, e-mail e senha.';
        $aba = 'cadastro';

    } elseif (strlen($senha) < 6) {

        $erro = 'A senha precisa ter no mínimo 6 caracteres.';
        $aba = 'cadastro';

    } else {

        $check = $pdo->prepare("SELECT id FROM escritorios WHERE email = ?");
        $check->execute([$email_cad]);

        if ($check->fetch()) {

            $erro = 'Este e-mail já está cadastrado. Faça login.';
            $aba = 'cadastro';

        } else {

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $expira = null;

            if ($plano_cad === 'teste') {
                $expira = date('Y-m-d', strtotime('+30 days'));
            }

            $stmt = $pdo->prepare("
                INSERT INTO escritorios
                (nome, email, senha, plano, plano_expira_em)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $nome_cad,
                $email_cad,
                $hash,
                $plano_cad,
                $expira
            ]);

            $sucesso = 'Escritório criado com sucesso! Agora faça login.';
            $aba = 'login';

            $nome_cad = '';
            $email_cad = '';
            $plano_cad = 'teste';
        }
    }
}

// ===== LOGIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'login') {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM escritorios WHERE email = ?");
    $stmt->execute([$email]);

    $escritorio = $stmt->fetch();

    if ($escritorio && password_verify($senha, $escritorio['senha'])) {

        if (
            $escritorio['plano'] === 'teste' &&
            !empty($escritorio['plano_expira_em'])
        ) {
            if (date('Y-m-d') > $escritorio['plano_expira_em']) {
                $erro = 'Seu período de teste de 1 mês terminou. Escolha um plano para continuar.';
                $aba = 'login';
            }
        }

        if (!$erro) {

            $_SESSION['escritorio_id'] = $escritorio['id'];
            $_SESSION['escritorio_nome'] = $escritorio['nome'];
            $_SESSION['escritorio_plano'] = $escritorio['plano'];
            $_SESSION['escritorio_email'] = $escritorio['email'];
            $_SESSION['plano_expira_em'] = $escritorio['plano_expira_em'] ?? null;

            $_SESSION['usuario_id'] = 0;
            $_SESSION['usuario_nome'] = $escritorio['nome'];
            $_SESSION['usuario_perfil'] = 'admin';
            $_SESSION['usuario_eh_dono'] = true;

            header("Location: pages/dashboard.php");
            exit;
        }

    } else {

        $stmt = $pdo->prepare("
            SELECT
                u.*,
                e.nome AS escritorio_nome,
                e.plano AS escritorio_plano,
                e.email AS escritorio_email,
                e.plano_expira_em
            FROM usuarios u
            JOIN escritorios e ON e.id = u.escritorio_id
            WHERE u.email = ? AND u.ativo = 1
        ");

        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {

            if (
                $usuario['escritorio_plano'] === 'teste' &&
                !empty($usuario['plano_expira_em']) &&
                date('Y-m-d') > $usuario['plano_expira_em']
            ) {

                $erro = 'Seu período de teste de 1 mês terminou. Peça ao administrador para escolher um plano.';
                $aba = 'login';

            } else {

                $_SESSION['escritorio_id'] = $usuario['escritorio_id'];
                $_SESSION['escritorio_nome'] = $usuario['escritorio_nome'];
                $_SESSION['escritorio_plano'] = $usuario['escritorio_plano'];
                $_SESSION['escritorio_email'] = $usuario['escritorio_email'];
                $_SESSION['plano_expira_em'] = $usuario['plano_expira_em'] ?? null;

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_perfil'] = $usuario['perfil'];
                $_SESSION['usuario_eh_dono'] = false;

                header("Location: pages/dashboard.php");
                exit;
            }

        } else {

            $erro = 'E-mail ou senha incorretos.';
            $aba = 'login';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pré-SPED Seguro | Validador Fiscal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>

        :root {
            --cor-marinho: #12283d;
            --cor-marinho-claro: #193850;
            --cor-azul: #0d6efd;
            --cor-azul-hover: #0b5ed7;
            --cor-verde: #198754;
            --cor-verde-hover: #157347;
            --cor-texto: #25364a;
            --cor-texto-suave: #748296;
            --cor-borda: #e2e8f0;
            --cor-fundo: #f4f7fb;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
        }

        body {
            font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--cor-texto);
            background:
                radial-gradient(
                    circle at 15% 15%,
                    rgba(13, 110, 253, 0.13),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 85% 85%,
                    rgba(25, 135, 84, 0.10),
                    transparent 32%
                ),
                var(--cor-marinho);

            background-attachment: fixed;
        }

        /* ÁREA PRINCIPAL */

        .wrap-login {
            min-height: 100vh;
            min-height: 100dvh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px 16px;
            position: relative;
            overflow-y: auto;
        }

        /* CARTÃO PRINCIPAL */

        .card-login {
            width: 100%;
            max-width: 490px;

            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 22px;

            background: #ffffff;

            overflow: hidden;

            box-shadow:
                0 25px 70px rgba(0, 0, 0, 0.28),
                0 5px 15px rgba(0, 0, 0, 0.08);

            animation: aparecer 0.45s ease-out;
        }

        @keyframes aparecer {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-login .card-body {
            padding: 30px 34px 32px !important;
        }

        /* LOGOTIPO */

        .logo-wrap {
            background: var(--cor-marinho);

            border-radius: 15px;

            height: 145px;

            display: flex;
            justify-content: center;
            align-items: center;

            overflow: hidden;

            margin-bottom: 14px;

            padding: 10px;

            position: relative;
        }

        .logo-img {
            max-width: 100%;
            max-height: 125px;

            width: auto;
            height: auto;

            object-fit: contain;
            display: block;
        }

        .slogan {
			text-align: center;
            color: var(--cor-texto-suave);
            font-size: 0.91rem;
            font-weight: 500;
            letter-spacing: 0.2px;
            margin-bottom: 20px;
        }

        /* ABAS */

        .nav-pills {
            display: flex;
            gap: 8px;

            padding: 5px;

            background: #f0f4f9;
            border-radius: 12px;

            margin-bottom: 22px !important;
        }

        .nav-pills .nav-item {
            flex: 1;
        }

        .nav-pills .nav-link {
            display: block;

            text-align: center;

            padding: 11px 12px;

            border-radius: 9px;

            font-size: 0.94rem;
            font-weight: 600;

            color: #62748a;

            transition: all 0.2s ease;
        }

        .nav-pills .nav-link:hover {
            color: var(--cor-azul);
            background: rgba(13, 110, 253, 0.06);
        }

        .nav-pills .nav-link.active {
            color: #ffffff;
            background: var(--cor-azul);

            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.22);
        }

        /* FORMULÁRIOS */

        .form-label {
            color: #34465c;

            font-size: 0.91rem;
            font-weight: 600;

            margin-bottom: 7px;
        }

        .form-control-lg,
        .form-select-lg {
            min-height: 49px !important;

            font-size: 0.94rem !important;

            padding: 11px 14px !important;

            border: 1px solid var(--cor-borda);

            border-radius: 10px;

            color: var(--cor-texto);

            background-color: #ffffff;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .form-control::placeholder {
            color: #a0acba;
        }

        .form-control-lg:focus,
        .form-select-lg:focus {
            border-color: var(--cor-azul);

            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.11);
        }

        .form-check-input {
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--cor-azul);
            border-color: var(--cor-azul);
        }

        .form-check-label {
            color: #64748b;
            cursor: pointer;
        }

        /* BOTÕES */

        .btn-lg {
            min-height: 49px;

            padding: 11px 16px !important;

            border-radius: 10px;

            font-size: 0.96rem !important;
            font-weight: 600 !important;

            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--cor-azul);
            border-color: var(--cor-azul);

            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.15);
        }

        .btn-primary:hover {
            background: var(--cor-azul-hover);
            border-color: var(--cor-azul-hover);

            transform: translateY(-1px);

            box-shadow: 0 6px 14px rgba(13, 110, 253, 0.22);
        }

        .btn-success {
            background: var(--cor-verde);
            border-color: var(--cor-verde);

            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.15);
        }

        .btn-success:hover {
            background: var(--cor-verde-hover);
            border-color: var(--cor-verde-hover);

            transform: translateY(-1px);

            box-shadow: 0 6px 14px rgba(25, 135, 84, 0.22);
        }

        .btn-dark {
            background: var(--cor-marinho);
            border-color: var(--cor-marinho);
        }

        .btn-dark:hover {
            background: var(--cor-marinho-claro);
            border-color: var(--cor-marinho-claro);
        }

        .btn i {
            margin-right: 5px;
        }

        /* LINKS */

        a {
            transition: color 0.2s ease;
        }

        .link-auxiliar {
            color: var(--cor-azul);
            font-weight: 500;
        }

        .link-auxiliar:hover {
            color: var(--cor-azul-hover);
            text-decoration: underline !important;
        }

        /* ALERTAS */

        .alert {
            border: 0;
            border-radius: 10px;

            padding: 12px 14px;

            font-size: 0.88rem;
            line-height: 1.5;

            margin-bottom: 16px;
        }

        .alert-danger {
            background: #fff0f0;
            color: #b42332;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: #eaf7ef;
            color: #146c43;
            border-left: 4px solid var(--cor-verde);
        }

        .alert-warning {
            background: #fff8e6;
            color: #805b00;
            border-left: 4px solid #ffc107;
        }

        /* SEPARADOR E RODAPÉ */

        .login-footer {
            margin-top: 24px;
            padding-top: 17px;

            border-top: 1px solid #edf0f4;

            text-align: center;

            color: #94a0af;
            font-size: 0.77rem;
            letter-spacing: 0.3px;
        }

        .login-footer i {
            color: var(--cor-verde);
            margin-right: 4px;
        }

        /* ESPAÇAMENTO DOS CAMPOS */

        .campo-formulario {
            margin-bottom: 17px;
        }

        /* TÍTULOS AUXILIARES */

        .titulo-formulario {
            color: var(--cor-marinho);
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .descricao-formulario {
            color: var(--cor-texto-suave);
            font-size: 0.87rem;
            margin-bottom: 19px;
            line-height: 1.5;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 576px) {

            .wrap-login {
                padding: 16px 12px;
                align-items: flex-start;
            }

            .card-login {
                max-width: 100%;
                border-radius: 17px;
            }

            .card-login .card-body {
                padding: 20px 19px 23px !important;
            }

            .logo-wrap {
                height: 120px;
                border-radius: 12px;
            }

            .logo-img {
                max-height: 105px;
            }

            .slogan {
                font-size: 0.82rem;
                margin-bottom: 16px;
            }

            .nav-pills {
                margin-bottom: 19px !important;
            }

            .form-control-lg,
            .form-select-lg {
                min-height: 46px !important;
            }

            .campo-formulario {
                margin-bottom: 15px;
            }

        }

        /* TELAS COM POUCA ALTURA */

        @media (max-height: 650px) and (min-width: 577px) {

            .wrap-login {
                align-items: flex-start;
                padding-top: 15px;
                padding-bottom: 15px;
            }

            .card-login .card-body {
                padding-top: 18px !important;
                padding-bottom: 20px !important;
            }

            .logo-wrap {
                height: 105px;
                margin-bottom: 8px;
            }

            .logo-img {
                max-height: 95px;
            }

            .slogan {
                margin-bottom: 12px;
            }

            .nav-pills {
                margin-bottom: 14px !important;
            }

            .campo-formulario {
                margin-bottom: 12px;
            }

        }

    </style>

</head>

<body>

<div class="wrap-login">

    <div class="card card-login">

				<!-- LOGOTIPO -->
                <div class="logo-wrap">
                    <img
                        src="imagens/logo_login_v7.png"
                        alt="Pré-SPED Seguro - Validador Fiscal"
                        class="logo-img"
                    >
                </div>

        <div class="card-body">


            <!-- ABAS -->
            <ul class="nav nav-pills nav-fill" role="tablist">

                <li class="nav-item">

                    <a
                        class="nav-link <?= in_array($aba, ['login', 'esqueci-senha', 'redefinir-senha']) ? 'active' : '' ?>"
                        href="?aba=login"
                    >
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Entrar
                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link <?= $aba === 'cadastro' ? 'active' : '' ?>"
                        href="?aba=cadastro"
                    >
                        <i class="bi bi-person-plus me-1"></i>
                        Criar conta
                    </a>

                </li>

            </ul>

            <!-- LOGIN -->
            <?php if ($aba === 'login'): ?>

                <form method="POST">

                    <input type="hidden" name="acao" value="login">

                    <div class="campo-formulario">

                        <label class="form-label">
                            E-mail
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control form-control-lg"
                            placeholder="voce@seuescritorio.com.br"
                            autocomplete="username"
                            required
                        >

                    </div>

                    <div class="campo-formulario">

                        <label class="form-label">
                            Senha
                        </label>

                        <input
                            type="password"
                            name="senha"
                            class="form-control form-control-lg"
                            placeholder="Sua senha"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <div class="form-check">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="lembrar"
                                id="lembrar"
                            >

                            <label class="form-check-label small" for="lembrar">
                                Lembrar de mim
                            </label>

                        </div>

                        <a
                            href="index.php?aba=esqueci-senha"
                            class="small text-decoration-none link-auxiliar"
                        >
                            Esqueci minha senha
                        </a>

                    </div>

                    <button class="btn btn-primary btn-lg w-100">

                        <i class="bi bi-box-arrow-in-right"></i>
                        Fazer Login

                    </button>

                </form>

            <?php endif; ?>

            <!-- CADASTRO -->
            <?php if ($aba === 'cadastro'): ?>

                <form method="POST" id="formCadastro">

                    <input type="hidden" name="acao" value="cadastro">

                    <div class="campo-formulario">

                        <label class="form-label">
                            Nome do escritório
                        </label>

                        <input
                            type="text"
                            name="nome"
                            class="form-control form-control-lg"
                            value="<?= htmlspecialchars($nome_cad) ?>"
                            placeholder="Ex: Contabilidade Garanhuns"
                            required
                        >

                    </div>

                    <div class="campo-formulario">

                        <label class="form-label">
                            E-mail
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control form-control-lg"
                            value="<?= htmlspecialchars($email_cad) ?>"
                            placeholder="contato@seuescritorio.com.br"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <div class="campo-formulario">

                        <label class="form-label">
                            Senha
                        </label>

                        <input
                            type="password"
                            name="senha"
                            id="senhaCadastro"
                            class="form-control form-control-lg"
                            placeholder="Mínimo 6 caracteres"
                            autocomplete="new-password"
                            required
                            <?= $erro ? 'autofocus' : '' ?>
                        >

                    </div>

                    <div class="campo-formulario">

                        <label class="form-label">
                            Plano
                        </label>

                        <select
                            name="plano"
                            class="form-select form-select-lg"
                        >

                            <option value="teste" <?= $plano_cad === 'teste' ? 'selected' : '' ?>>
                                Teste - 1 mês grátis - até 5 empresas
                            </option>

                            <option value="start" <?= $plano_cad === 'start' ? 'selected' : '' ?>>
                                Start - até 10 empresas - R$ 100/mês
                            </option>

                            <option value="pro" <?= $plano_cad === 'pro' ? 'selected' : '' ?>>
                                Pro - até 30 empresas - R$ 200/mês
                            </option>

                            <option value="escritorio" <?= $plano_cad === 'escritorio' ? 'selected' : '' ?>>
                                Escritório - até 100 empresas - R$ 400/mês
                            </option>

                            <option value="enterprise" <?= $plano_cad === 'enterprise' ? 'selected' : '' ?>>
                                Enterprise - até 300 empresas - R$ 550/mês
                            </option>

                        </select>

                    </div>

                    <button class="btn btn-success btn-lg w-100">

                        <i class="bi bi-check-circle"></i>
                        Criar minha conta

                    </button>

                </form>

            <?php endif; ?>

            <!-- ESQUECI SENHA -->
            <?php if ($aba === 'esqueci-senha'): ?>

                <h5 class="titulo-formulario">
                    <i class="bi bi-key me-1"></i>
                    Esqueci minha senha
                </h5>

                <p class="descricao-formulario">
                    Digite o e-mail da sua conta. Vamos enviar um link para criar uma nova senha.
                </p>

                <?php if (!empty($_SESSION['link_redefinicao_dev'])): ?>

                    <div class="alert alert-warning">

                        <strong>
                            <i class="bi bi-info-circle me-1"></i>
                            MODO DEV - XAMPP não envia e-mail:
                        </strong>

                        <br>

                        Clique aqui para redefinir agora:

                        <br>

                        <a href="<?= htmlspecialchars($_SESSION['link_redefinicao_dev']) ?>">
                            Redefinir minha senha
                        </a>

                        <?php unset($_SESSION['link_redefinicao_dev']); ?>

                    </div>

                <?php endif; ?>

                <form method="POST">

                    <input type="hidden" name="acao" value="esqueci-senha">

                    <div class="campo-formulario">

                        <label class="form-label">
                            E-mail
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control form-control-lg"
                            placeholder="voce@seuescritorio.com.br"
                            required
                        >

                    </div>

                    <button class="btn btn-dark btn-lg w-100">

                        <i class="bi bi-envelope"></i>
                        Enviar link de redefinição

                    </button>

                    <a
                        href="index.php?aba=login"
                        class="btn btn-link w-100 mt-2 text-decoration-none"
                    >
                        <i class="bi bi-arrow-left"></i>
                        Voltar para o login
                    </a>

                </form>

            <?php endif; ?>

            <!-- REDEFINIR SENHA -->
            <?php if ($aba === 'redefinir-senha'): ?>

                <?php

                $token_get = $_GET['token'] ?? '';

                $stmt = $pdo->prepare("
                    SELECT *
                    FROM password_resets
                    WHERE token = ? AND usado = 0 AND expira_em > NOW()
                ");

                $stmt->execute([$token_get]);
                $valido = $stmt->fetch();

                ?>

                <?php if (!$valido): ?>

                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Link inválido ou expirado. Peça um novo link.
                    </div>

                    <a
                        href="index.php?aba=esqueci-senha"
                        class="btn btn-dark btn-lg w-100"
                    >
                        Pedir novo link
                    </a>

                <?php else: ?>

                    <h5 class="titulo-formulario">
                        <i class="bi bi-shield-lock me-1"></i>
                        Criar nova senha
                    </h5>

                    <p class="descricao-formulario">
                        Crie uma senha nova para
                        <strong><?= htmlspecialchars($valido['email']) ?></strong>
                    </p>

                    <form method="POST">

                        <input type="hidden" name="acao" value="redefinir-senha">

                        <input
                            type="hidden"
                            name="token"
                            value="<?= htmlspecialchars($token_get) ?>"
                        >

                        <div class="campo-formulario">

                            <label class="form-label">
                                Nova senha
                            </label>

                            <input
                                type="password"
                                name="nova_senha"
                                class="form-control form-control-lg"
                                placeholder="Mínimo 6 caracteres"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                        <div class="campo-formulario">

                            <label class="form-label">
                                Confirmar nova senha
                            </label>

                            <input
                                type="password"
                                name="confirma_senha"
                                class="form-control form-control-lg"
                                placeholder="Repita a senha"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                        <button class="btn btn-success btn-lg w-100">

                            <i class="bi bi-check-circle"></i>
                            Salvar nova senha

                        </button>

                    </form>

                <?php endif; ?>

            <?php endif; ?>

            <!-- RODAPÉ -->
            <div class="login-footer">

                <i class="bi bi-shield-check"></i>

                Pré-SPED Seguro - Validador Fiscal

                <br>

                <span>
                    Ambiente seguro de validação fiscal
                </span>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

    const erroCadastro = <?= json_encode($erro && $aba === 'cadastro') ?>;

    if (erroCadastro) {

        const senhaInput = document.getElementById('senhaCadastro');

        if (senhaInput) {

            senhaInput.value = '';
            senhaInput.focus();

        }
    }

</script>

</body>
</html>
