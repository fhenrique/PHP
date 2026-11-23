<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexao.php';

// Exige autenticação.
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Confirma a situação atual do usuário e do perfil no banco.
$stmtUsuario = $pdo->prepare("
    SELECT
        u.id,
        u.nome,
        u.email,
        u.perfil_id,
        u.empresa_id,
        u.ativo,
        p.nome AS perfil,
        p.ativo AS perfil_ativo
    FROM usuarios u
    INNER JOIN perfis p ON p.id = u.perfil_id
    WHERE u.id = ?
    LIMIT 1
");
$stmtUsuario->execute([(int) $_SESSION['usuario_id']]);
$usuarioAutenticado = $stmtUsuario->fetch();

if (
    !$usuarioAutenticado ||
    (int) $usuarioAutenticado['ativo'] !== 1 ||
    (int) $usuarioAutenticado['perfil_ativo'] !== 1
) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header('Location: login.php');
    exit;
}

// Atualiza os dados básicos da sessão com os valores atuais do banco.
$_SESSION['usuario_nome'] = $usuarioAutenticado['nome'];
$_SESSION['usuario_email'] = $usuarioAutenticado['email'];
$_SESSION['perfil_id'] = (int) $usuarioAutenticado['perfil_id'];
$_SESSION['perfil'] = $usuarioAutenticado['perfil'];

$ehAdministradorGeral = ((int) $usuarioAutenticado['perfil_id'] === 3);
$empresaIdUsuario = $usuarioAutenticado['empresa_id'] !== null
    ? (int) $usuarioAutenticado['empresa_id']
    : null;

// Usuários comuns sempre ficam vinculados à própria empresa.
// Não é permitido trocar a empresa alterando apenas a sessão ou a URL.
if (!$ehAdministradorGeral) {
    if ($empresaIdUsuario === null) {
        http_response_code(403);
        exit('Acesso bloqueado: usuário sem empresa vinculada.');
    }

    $stmtEmpresa = $pdo->prepare("
        SELECT id, nome
        FROM empresas
        WHERE id = ? AND ativo = 1
        LIMIT 1
    ");
    $stmtEmpresa->execute([$empresaIdUsuario]);
    $empresaUsuario = $stmtEmpresa->fetch();

    if (!$empresaUsuario) {
        http_response_code(403);
        exit('Acesso bloqueado: a empresa vinculada ao usuário está inativa ou não existe.');
    }

    $_SESSION['empresa_id'] = (int) $empresaUsuario['id'];
    $_SESSION['empresa_nome'] = $empresaUsuario['nome'];
} else {
    // O Administrador Geral pode selecionar qualquer empresa ativa.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Processa a troca de empresa antes de qualquer saída HTML.
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['acao']) &&
        $_POST['acao'] === 'selecionar_empresa'
    ) {
        $tokenRecebido = $_POST['csrf_token'] ?? '';

        if (
            !is_string($tokenRecebido) ||
            !hash_equals($_SESSION['csrf_token'], $tokenRecebido)
        ) {
            http_response_code(403);
            exit('Solicitação inválida. Atualize a página e tente novamente.');
        }

        $novaEmpresaId = filter_var(
            $_POST['empresa_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$novaEmpresaId || $novaEmpresaId < 1) {
            http_response_code(400);
            exit('Empresa selecionada inválida.');
        }

        $stmtNovaEmpresa = $pdo->prepare("
            SELECT id, nome
            FROM empresas
            WHERE id = ? AND ativo = 1
            LIMIT 1
        ");
        $stmtNovaEmpresa->execute([$novaEmpresaId]);
        $novaEmpresa = $stmtNovaEmpresa->fetch();

        if (!$novaEmpresa) {
            http_response_code(404);
            exit('A empresa selecionada não existe ou está inativa.');
        }

        $_SESSION['empresa_id'] = (int) $novaEmpresa['id'];
        $_SESSION['empresa_nome'] = $novaEmpresa['nome'];

        // Redireciona para a mesma página, evitando reenviar o formulário.
        
        $uri = $_SERVER['REQUEST_URI'] ?? 'index.php';
        $caminho = parse_url($uri, PHP_URL_PATH);
        
        $destino = $caminho ?: 'index.php';
        
        header('Location: ' . $destino);
        exit;        
        
        
    }

    // Se ainda não houver empresa escolhida, seleciona a primeira ativa.
    if (empty($_SESSION['empresa_id'])) {
        $stmtPrimeiraEmpresa = $pdo->query("
            SELECT id, nome
            FROM empresas
            WHERE ativo = 1
            ORDER BY nome
            LIMIT 1
        ");
        $primeiraEmpresa = $stmtPrimeiraEmpresa->fetch();

        if ($primeiraEmpresa) {
            $_SESSION['empresa_id'] = (int) $primeiraEmpresa['id'];
            $_SESSION['empresa_nome'] = $primeiraEmpresa['nome'];
        } else {
            $_SESSION['empresa_id'] = null;
            $_SESSION['empresa_nome'] = null;
        }
    } else {
        // Confere se a empresa atualmente selecionada continua ativa.
        $stmtEmpresaSelecionada = $pdo->prepare("
            SELECT id, nome
            FROM empresas
            WHERE id = ? AND ativo = 1
            LIMIT 1
        ");
        $stmtEmpresaSelecionada->execute([(int) $_SESSION['empresa_id']]);
        $empresaSelecionada = $stmtEmpresaSelecionada->fetch();

        if ($empresaSelecionada) {
            $_SESSION['empresa_id'] = (int) $empresaSelecionada['id'];
            $_SESSION['empresa_nome'] = $empresaSelecionada['nome'];
        } else {
            $_SESSION['empresa_id'] = null;
            $_SESSION['empresa_nome'] = null;
        }
    }
}

// Dados de uso comum nas páginas do sistema.
$empresaId = !empty($_SESSION['empresa_id'])
    ? (int) $_SESSION['empresa_id']
    : null;

$empresaNome = $_SESSION['empresa_nome'] ?? null;

/**
 * Permite acesso a páginas administrativas.
 * Administrador Geral também pode acessar essas páginas.
 */
function exigirAdministrador()
{
    $perfilId = (int) ($_SESSION['perfil_id'] ?? 0);

    if (!in_array($perfilId, [1, 3], true)) {
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
                body {
                    background: #f1f4f9;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: Arial, sans-serif;
                }
                .acesso-card {
                    max-width: 480px;
                    width: 90%;
                    background: white;
                    border-radius: 15px;
                    padding: 40px 30px;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                }
                .icone-erro {
                    font-size: 65px;
                    color: #dc3545;
                    margin-bottom: 15px;
                }
                .contador {
                    font-size: 48px;
                    font-weight: bold;
                    color: #08749b;
                    margin: 15px 0;
                }
                .btn-voltar {
                    background: #08749b;
                    color: white;
                    padding: 10px 25px;
                    border-radius: 7px;
                    text-decoration: none;
                }
                .btn-voltar:hover {
                    background: #065c7c;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="acesso-card">
                <div class="icone-erro">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h3 class="mb-3">Acesso Negado!</h3>
                <p class="text-muted">
                    Você não possui permissão para acessar esta funcionalidade.
                    Somente Administradores podem acessar esta página.
                </p>
                <hr>
                <p class="mb-1">Você será redirecionado para o Painel Inicial em:</p>
                <div class="contador" id="contador">10</div>
                <p class="text-muted small">Aguarde, estamos retornando ao sistema...</p>
                <a href="index.php" class="btn-voltar">
                    <i class="bi bi-house-door me-1"></i>
                    Voltar agora
                </a>
            </div>
            <script>
                let segundos = 10;
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
}

/**
 * Impede que páginas que dependem de uma empresa sejam abertas
 * sem que exista uma empresa ativa selecionada.
 */
function exigirEmpresaSelecionada()
{
    if (empty($_SESSION['empresa_id'])) {
        http_response_code(403);
        exit('Nenhuma empresa ativa está disponível para esta operação.');
    }
}