
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/conexao.php';

exigirAdministrador();
exigirEmpresaSelecionada();

$empresaId = (int) $_SESSION['empresa_id'];

// Proteção CSRF
if (empty($_SESSION['csrf_fornecedores'])) {
    $_SESSION['csrf_fornecedores'] = bin2hex(random_bytes(32));
}

function hFornecedor($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$mensagem = '';
$tipoMensagem = 'success';
$erros = [];

// Processamento dos formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_fornecedores'], $_POST['csrf_token'])
    ) {
        die('Requisição inválida. Atualize a página e tente novamente.');
    }

    $acao = $_POST['acao'] ?? '';

    // CADASTRAR OU EDITAR
    if ($acao === 'salvar') {

        $id = (int)($_POST['id'] ?? 0);

        $razaoSocial = trim($_POST['razao_social'] ?? '');
        $nomeFantasia = trim($_POST['nome_fantasia'] ?? '');
        $tipoPessoa = $_POST['tipo_pessoa'] ?? 'J';
        $cpfCnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $bairro = trim($_POST['bairro'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $uf = strtoupper(trim($_POST['uf'] ?? ''));
        $cep = trim($_POST['cep'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        $status = $_POST['status'] ?? 'A';


if ($razaoSocial === '') {
    $erros[] = 'Informe a razão social ou nome completo.';
}

if (!in_array($tipoPessoa, ['F', 'J'], true)) {
    $erros[] = 'Selecione um tipo de pessoa válido.';
}

if ($tipoPessoa === 'J' && $nomeFantasia === '') {
    $erros[] = 'O Nome Fantasia é obrigatório para Pessoa Jurídica.';
}

if (!preg_match('/^\d+$/', $cpfCnpj)) {
    $erros[] = 'CPF/CNPJ deve conter somente números.';
} elseif (
    ($tipoPessoa === 'F' && strlen($cpfCnpj) !== 11) ||
    ($tipoPessoa === 'J' && strlen($cpfCnpj) !== 14)
) {
    $erros[] = 'CPF deve conter 11 dígitos e CNPJ deve conter 14 dígitos, conforme o tipo de pessoa.';
}

if ($telefone === '') {
    $erros[] = 'O telefone é obrigatório.';
} elseif (!preg_match('/^[0-9().-]+$/', $telefone)) {
    $erros[] = 'O telefone contém caracteres não permitidos.';
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'Informe um endereço de e-mail válido.';
}

if ($numero !== '' && !preg_match('/^\d+$/', $numero)) {
    $erros[] = 'O campo Número deve conter somente números.';
}

$cepNumeros = preg_replace('/\D/', '', $cep);

if ($cep !== '' && strlen($cepNumeros) !== 8) {
    $erros[] = 'O CEP deve conter exatamente 8 dígitos.';
}

if ($uf !== '' && !preg_match('/^[A-Z]{2}$/', $uf)) {
    $erros[] = 'Selecione uma UF válida.';
}

if (!in_array($status, ['A', 'I'], true)) {
    $erros[] = 'Status inválido.';
}




        if (!$erros) {

            try {

                $dados = [
                    ':razao_social' => $razaoSocial,
                    ':nome_fantasia' => $nomeFantasia ?: null,
                    ':tipo_pessoa' => $tipoPessoa,
                    ':cpf_cnpj' => $cpfCnpj,
                    ':telefone' => $telefone ?: null,
                    ':email' => $email ?: null,
                    ':endereco' => $endereco ?: null,
                    ':numero' => $numero ?: null,
                    ':complemento' => $complemento ?: null,
                    ':bairro' => $bairro ?: null,
                    ':cidade' => $cidade ?: null,
                    ':uf' => $uf ?: null,
                    ':cep' => $cep ?: null,
                    ':observacoes' => $observacoes ?: null,
                    ':status' => $status
                ];

                if ($id > 0) {

                    $sql = "UPDATE fornecedores SET
                        razao_social = :razao_social,
                        nome_fantasia = :nome_fantasia,
                        tipo_pessoa = :tipo_pessoa,
                        cpf_cnpj = :cpf_cnpj,
                        telefone = :telefone,
                        email = :email,
                        endereco = :endereco,
                        numero = :numero,
                        complemento = :complemento,
                        bairro = :bairro,
                        cidade = :cidade,
                        uf = :uf,
                        cep = :cep,
                        observacoes = :observacoes,
                        status = :status
                        WHERE id = :id AND empresa_id = :empresa_id";

                    $dados[':id'] = $id;
                    $dados[':empresa_id'] = $empresaId;

                    // Confirma que o fornecedor pertence à empresa selecionada.
                    $stmtVerifica = $pdo->prepare("SELECT id FROM fornecedores WHERE id = :id AND empresa_id = :empresa_id LIMIT 1");
                    $stmtVerifica->execute([':id' => $id, ':empresa_id' => $empresaId]);
                    if (!$stmtVerifica->fetchColumn()) {
                        throw new RuntimeException('Fornecedor não encontrado na empresa selecionada.');
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($dados);

                    header('Location: fornecedores.php?msg=editado');
                    exit;

                } else {

                    $sql = "INSERT INTO fornecedores (
                        razao_social,
                        nome_fantasia,
                        tipo_pessoa,
                        cpf_cnpj,
                        telefone,
                        email,
                        endereco,
                        numero,
                        complemento,
                        bairro,
                        cidade,
                        uf,
                        cep,
                        observacoes,
                        status,
                        empresa_id
                    ) VALUES (
                        :razao_social,
                        :nome_fantasia,
                        :tipo_pessoa,
                        :cpf_cnpj,
                        :telefone,
                        :email,
                        :endereco,
                        :numero,
                        :complemento,
                        :bairro,
                        :cidade,
                        :uf,
                        :cep,
                        :observacoes,
                        :status,
                        :empresa_id
                    )";

                    $dados[':empresa_id'] = $empresaId;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($dados);

                    header('Location: fornecedores.php?msg=cadastrado');
                    exit;
                }

            } catch (RuntimeException $e) {
                $erros[] = $e->getMessage();
            } catch (PDOException $e) {

                if ($e->getCode() === '23000') {
                    $erros[] = 'Já existe um fornecedor cadastrado com este CPF/CNPJ.';
                } else {
                    $erros[] = 'Erro ao salvar fornecedor. Verifique o banco de dados.';
                }
            }
        }
    }

    // INATIVAR OU REATIVAR
    if (in_array($acao, ['inativar', 'ativar'], true)) {

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $erros[] = 'Fornecedor inválido.';
        } else {

            $novoStatus = $acao === 'inativar' ? 'I' : 'A';

            $stmt = $pdo->prepare(
                "UPDATE fornecedores SET status = :status WHERE id = :id AND empresa_id = :empresa_id"
            );

            $stmt->execute([
                ':status' => $novoStatus,
                ':id' => $id,
                ':empresa_id' => $empresaId
            ]);

            if ($stmt->rowCount() === 0) {
                $stmtConfere = $pdo->prepare("SELECT id FROM fornecedores WHERE id = :id AND empresa_id = :empresa_id LIMIT 1");
                $stmtConfere->execute([':id' => $id, ':empresa_id' => $empresaId]);
                if (!$stmtConfere->fetchColumn()) {
                    $erros[] = 'Fornecedor não encontrado na empresa selecionada.';
                }
            }

            if (!$erros) {
                header('Location: fornecedores.php?msg=' . $acao);
                exit;
            }
        }
    }
}

// Mensagens
$mensagens = [
    'cadastrado' => 'Fornecedor cadastrado com sucesso!',
    'editado' => 'Fornecedor atualizado com sucesso!',
    'inativar' => 'Fornecedor inativado com sucesso!',
    'ativar' => 'Fornecedor reativado com sucesso!'
];

if (isset($_GET['msg'], $mensagens[$_GET['msg']])) {
    $mensagem = $mensagens[$_GET['msg']];
}

// Busca para edição
$fornecedorEdicao = null;

if (isset($_GET['editar'])) {

    $idEditar = (int)$_GET['editar'];

    $stmt = $pdo->prepare(
        "SELECT * FROM fornecedores WHERE id = :id AND empresa_id = :empresa_id"
    );

    $stmt->execute([':id' => $idEditar, ':empresa_id' => $empresaId]);

    $fornecedorEdicao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fornecedorEdicao) {
        $mensagem = 'Fornecedor não encontrado.';
        $tipoMensagem = 'danger';
    }
}

// Pesquisa
$busca = trim($_GET['busca'] ?? '');
$filtroStatus = $_GET['status'] ?? 'A';

$sql = "SELECT * FROM fornecedores WHERE empresa_id = :empresa_id";
$params = [':empresa_id' => $empresaId];

if ($busca !== '') {
    $sql .= " AND (
        razao_social LIKE :busca1
        OR nome_fantasia LIKE :busca2
        OR cpf_cnpj LIKE :busca3
    )";

    $termo = '%' . $busca . '%';

    $params[':busca1'] = $termo;
    $params[':busca2'] = $termo;
    $params[':busca3'] = $termo;
}

if (in_array($filtroStatus, ['A', 'I'], true)) {
    $sql .= " AND status = :status";
    $params[':status'] = $filtroStatus;
}

$sql .= " ORDER BY razao_social ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Valores do formulário
$f = $fornecedorEdicao ?: [];

if (!$fornecedorEdicao && $_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'salvar') {
    $f = $_POST;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Fornecedores</h3>
            <p class="text-muted mb-0">
                Gerenciamento de fornecedores do estoque.
            </p>
            <p class="text-muted mb-0 mt-1">
                <i class="bi bi-building"></i>
                Empresa: <strong><?= hFornecedor($_SESSION['empresa_nome'] ?? 'Empresa selecionada') ?></strong>
            </p>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?= hFornecedor($tipoMensagem) ?> alert-dismissible fade show">
            <?= hFornecedor($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php foreach ($erros as $erro): ?>
        <div class="alert alert-danger">
            <?= hFornecedor($erro) ?>
        </div>
    <?php endforeach; ?>

    <!-- FORMULÁRIO -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white">
            <h5 class="mb-0">
                <?= $fornecedorEdicao ? 'Editar Fornecedor' : 'Cadastrar Fornecedor' ?>
            </h5>
        </div>

        <div class="card-body">

            <form method="POST" action="fornecedores.php">

                <input type="hidden" name="csrf_token"
                    value="<?= hFornecedor($_SESSION['csrf_fornecedores']) ?>">

                <input type="hidden" name="acao" value="salvar">

                <input type="hidden" name="id"
                    value="<?= hFornecedor($f['id'] ?? '') ?>">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">Tipo de Pessoa *</label>
                        
                            <select name="tipo_pessoa" id="tipo_pessoa"
                                    class="form-select" required>
                                <option value="J"
                                    <?= ($f['tipo_pessoa'] ?? 'J') === 'J' ? 'selected' : '' ?>>
                                    Pessoa Jurídica
                                </option>
                            
                                <option value="F"
                                    <?= ($f['tipo_pessoa'] ?? '') === 'F' ? 'selected' : '' ?>>
                                    Pessoa Física
                                </option>
                            </select>
                        
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Razão Social / Nome Completo *</label>
                        
                        <input type="text" name="razao_social"
                               class="form-control" maxlength="150"
                               value="<?= hFornecedor($f['razao_social'] ?? '') ?>"
                               required>
                            
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nome Fantasia</label>
                        
                        <input type="text" name="nome_fantasia"
                               id="nome_fantasia"
                               class="form-control" maxlength="150"
                               value="<?= hFornecedor($f['nome_fantasia'] ?? '') ?>">

                    </div>

                    <div class="col-md-4">
                        <label class="form-label">CPF / CNPJ *</label>
                        
                        <input type="text" name="cpf_cnpj"
                               id="cpf_cnpj"
                               class="form-control"
                               inputmode="numeric"
                               maxlength="18"
                               value="<?= hFornecedor($f['cpf_cnpj'] ?? '') ?>"
                               placeholder="Digite CPF ou CNPJ"
                               required>

                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Telefone</label>
                        
                            <input type="text" name="telefone"
                                   id="telefone"
                                   class="form-control"
                                   maxlength="20"
                                   placeholder="(00) 00000-0000"
                                   value="<?= hFornecedor($f['telefone'] ?? '') ?>"
                                   required>
                            
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">E-mail</label>
                        
                            <input type="email" name="email"
                                   class="form-control" maxlength="150"
                                   value="<?= hFornecedor($f['email'] ?? '') ?>"
                                   placeholder="email@exemplo.com">
                            
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="endereco"
                            class="form-control" maxlength="200"
                            value="<?= hFornecedor($f['endereco'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Número</label>
                        
                            <input type="text" name="numero"
                                   id="numero"
                                   class="form-control"
                                   inputmode="numeric"
                                   maxlength="20"
                                   value="<?= hFornecedor($f['numero'] ?? '') ?>">
                            
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Complemento</label>
                        <input type="text" name="complemento"
                            class="form-control" maxlength="100"
                            value="<?= hFornecedor($f['complemento'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Bairro</label>
                        <input type="text" name="bairro"
                            class="form-control" maxlength="100"
                            value="<?= hFornecedor($f['bairro'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade"
                            class="form-control" maxlength="100"
                            value="<?= hFornecedor($f['cidade'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">UF</label>
                    
                        <?php
                        $ufs = [
                            'AC' => 'Acre',
                            'AL' => 'Alagoas',
                            'AP' => 'Amapá',
                            'AM' => 'Amazonas',
                            'BA' => 'Bahia',
                            'CE' => 'Ceará',
                            'DF' => 'Distrito Federal',
                            'ES' => 'Espírito Santo',
                            'GO' => 'Goiás',
                            'MA' => 'Maranhão',
                            'MT' => 'Mato Grosso',
                            'MS' => 'Mato Grosso do Sul',
                            'MG' => 'Minas Gerais',
                            'PA' => 'Pará',
                            'PB' => 'Paraíba',
                            'PR' => 'Paraná',
                            'PE' => 'Pernambuco',
                            'PI' => 'Piauí',
                            'RJ' => 'Rio de Janeiro',
                            'RN' => 'Rio Grande do Norte',
                            'RS' => 'Rio Grande do Sul',
                            'RO' => 'Rondônia',
                            'RR' => 'Roraima',
                            'SC' => 'Santa Catarina',
                            'SP' => 'São Paulo',
                            'SE' => 'Sergipe',
                            'TO' => 'Tocantins'
                        ];
                        ?>
                    
                        <select name="uf" class="form-select">
                            <option value="">Selecione</option>
                    
                            <?php foreach ($ufs as $sigla => $estado): ?>
                                <option value="<?= $sigla ?>"
                                    <?= ($f['uf'] ?? '') === $sigla ? 'selected' : '' ?>>
                                    <?= $sigla ?> - <?= hFornecedor($estado) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">CEP</label>
                        
                            <input type="text" name="cep"
                                   id="cep"
                                   class="form-control"
                                   inputmode="numeric"
                                   maxlength="9"
                                   placeholder="00000-000"
                                   value="<?= hFornecedor($f['cep'] ?? '') ?>">
                            
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Observações</label>
                        <input type="text" name="observacoes"
                            class="form-control"
                            value="<?= hFornecedor($f['observacoes'] ?? '') ?>">
                    </div>

                    <?php if ($fornecedorEdicao): ?>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="A"
                                    <?= ($f['status'] ?? '') === 'A' ? 'selected' : '' ?>>
                                    Ativo
                                </option>
                                <option value="I"
                                    <?= ($f['status'] ?? '') === 'I' ? 'selected' : '' ?>>
                                    Inativo
                                </option>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="status" value="A">
                    <?php endif; ?>

                </div>

                <div class="mt-4">

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        <?= $fornecedorEdicao ? 'Atualizar Fornecedor' : 'Salvar Fornecedor' ?>
                    </button>

                    <?php if ($fornecedorEdicao): ?>
                        <a href="fornecedores.php" class="btn btn-secondary">
                            Cancelar
                        </a>
                    <?php endif; ?>

                </div>

            </form>

        </div>
    </div>

    <!-- PESQUISA -->

    <div class="card shadow-sm">

        <div class="card-header bg-white">
            <h5 class="mb-0">Fornecedores Cadastrados</h5>
        </div>

        <div class="card-body">

            <form method="GET" class="row g-2 mb-4">

                <div class="col-md-6">
                    <input type="text" name="busca"
                        class="form-control"
                        placeholder="Pesquisar por nome ou CPF/CNPJ..."
                        value="<?= hFornecedor($busca) ?>">
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="A" <?= $filtroStatus === 'A' ? 'selected' : '' ?>>
                            Somente ativos
                        </option>
                        <option value="I" <?= $filtroStatus === 'I' ? 'selected' : '' ?>>
                            Somente inativos
                        </option>
                        <option value="T" <?= $filtroStatus === 'T' ? 'selected' : '' ?>>
                            Todos
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Pesquisar
                    </button>

                    <a href="fornecedores.php" class="btn btn-secondary">
                        Limpar
                    </a>
                </div>

            </form>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fornecedor</th>
                            <th>CPF / CNPJ</th>
                            <th>Telefone</th>
                            <th>Cidade / UF</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($fornecedores) > 0): ?>

                        <?php foreach ($fornecedores as $fornecedor): ?>

                            <tr>
                                <td><?= (int)$fornecedor['id'] ?></td>

                                <td>
                                    <strong>
                                        <?= hFornecedor($fornecedor['razao_social']) ?>
                                    </strong>

                                    <?php if (!empty($fornecedor['nome_fantasia'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= hFornecedor($fornecedor['nome_fantasia']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td><?= hFornecedor($fornecedor['cpf_cnpj']) ?></td>

                                <td><?= hFornecedor($fornecedor['telefone']) ?></td>

                                <td>
                                    <?= hFornecedor($fornecedor['cidade']) ?>
                                    <?= !empty($fornecedor['uf']) ? '/' . hFornecedor($fornecedor['uf']) : '' ?>
                                </td>

                                <td>
                                    <?php if ($fornecedor['status'] === 'A'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">

                                    <a href="fornecedores.php?editar=<?= (int)$fornecedor['id'] ?>"
                                        class="btn btn-sm btn-warning"
                                        title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form method="POST"
                                        action="fornecedores.php"
                                        class="d-inline"
                                        data-confirm="<?= $fornecedor['status'] === 'A'
                                            ? 'Deseja realmente inativar este fornecedor?'
                                            : 'Deseja realmente reativar este fornecedor?' ?>">

                                        <input type="hidden" name="csrf_token"
                                            value="<?= hFornecedor($_SESSION['csrf_fornecedores']) ?>">

                                        <input type="hidden" name="id"
                                            value="<?= (int)$fornecedor['id'] ?>">

                                        <?php if ($fornecedor['status'] === 'A'): ?>

                                            <input type="hidden" name="acao" value="inativar">

                                            <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Inativar">
                                                <i class="bi bi-person-x"></i>
                                            </button>

                                        <?php else: ?>

                                            <input type="hidden" name="acao" value="ativar">

                                            <button type="submit"
                                                class="btn btn-sm btn-success"
                                                title="Reativar">
                                                <i class="bi bi-person-check"></i>
                                            </button>

                                        <?php endif; ?>

                                    </form>

                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Nenhum fornecedor encontrado.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="text-muted small">
                Total de registros encontrados: <?= count($fornecedores) ?>
            </div>

        </div>
    </div>

</div>


<script>
    

// ======================================================
// FOCO AUTOMÁTICO NO CAMPO CEP
// ======================================================

document.addEventListener('DOMContentLoaded', function () {

    const campoCep = document.querySelector('[name="cep"]');

    if (campoCep) {

        // Destaca o campo em verde ao abrir a tela
        campoCep.style.backgroundColor = '#e8f5e9';
        campoCep.style.borderColor = '#198754';

        // Coloca o cursor no campo CEP
        campoCep.focus();
        campoCep.select();

        // Ao sair do campo, retorna ao branco
        campoCep.addEventListener('blur', function () {
            campoCep.style.backgroundColor = '#ffffff';
            campoCep.style.borderColor = '';
        });

        // Se voltar ao campo, destaca novamente
        campoCep.addEventListener('focus', function () {
            campoCep.style.backgroundColor = '#e8f5e9';
            campoCep.style.borderColor = '#198754';
        });
    }

});

    
document.addEventListener('DOMContentLoaded', function () {

    const tipoPessoa = document.getElementById('tipo_pessoa');
    const nomeFantasia = document.getElementById('nome_fantasia');
    const cpfCnpj = document.getElementById('cpf_cnpj');
    const telefone = document.getElementById('telefone');
    const numero = document.getElementById('numero');
    const cep = document.getElementById('cep');

    // Nome Fantasia obrigatório somente para Pessoa Jurídica
    function ajustarNomeFantasia() {
        const pessoaJuridica = tipoPessoa.value === 'J';

        nomeFantasia.required = pessoaJuridica;

        nomeFantasia.closest('.col-md-4')
            .querySelector('label').innerHTML =
            pessoaJuridica
                ? 'Nome Fantasia *'
                : 'Nome Fantasia';
    }

    tipoPessoa.addEventListener('change', ajustarNomeFantasia);
    ajustarNomeFantasia();

    // Máscara de CPF e CNPJ
    function aplicarMascaraDocumento(valor) {

        let numeros = valor.replace(/\D/g, '').substring(0, 14);

        if (numeros.length <= 11) {

            numeros = numeros
                .replace(/^(\d{3})(\d)/, '$1.$2')
                .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');

        } else {

            numeros = numeros
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        }

        return numeros;
    }

    cpfCnpj.addEventListener('input', function () {
        this.value = aplicarMascaraDocumento(this.value);
    });

    // Telefone: somente números, parênteses, ponto e traço
    telefone.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9().-]/g, '');
    });

    // Número: somente números
    numero.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    // CEP: máscara 00000-000
    function aplicarMascaraCEP(valor) {

        let numeros = valor.replace(/\D/g, '').substring(0, 8);

        if (numeros.length > 5) {
            numeros = numeros.replace(/^(\d{5})(\d)/, '$1-$2');
        }

        return numeros;
    }

    cep.addEventListener('input', function () {
        this.value = aplicarMascaraCEP(this.value);
    });

    // Aplicar máscaras também ao editar registros
    cpfCnpj.value = aplicarMascaraDocumento(cpfCnpj.value);
    cep.value = aplicarMascaraCEP(cep.value);

});



// ======================================================
// CONSULTA AUTOMÁTICA DE CEP - API APICEP
// COM INDICADOR DE CARREGAMENTO
// ======================================================

const campoCep = document.querySelector('[name="cep"]');
const campoEndereco = document.querySelector('[name="endereco"]');
const campoBairro = document.querySelector('[name="bairro"]');
const campoCidade = document.querySelector('[name="cidade"]');
const campoUf = document.querySelector('[name="uf"]');
const campoNumero = document.querySelector('[name="numero"]');

let ultimoCepConsultado = '';

if (campoCep) {

    // Cria o indicador de carregamento
    const indicadorCep = document.createElement('div');

    indicadorCep.className = 'text-primary small mt-2';
    indicadorCep.style.display = 'none';
    indicadorCep.setAttribute('role', 'status');
    indicadorCep.setAttribute('aria-live', 'polite');

    indicadorCep.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"
              aria-hidden="true"></span>
        Aguarde, consultando CEP...
    `;

    campoCep.insertAdjacentElement('afterend', indicadorCep);

    async function consultarCep() {

        const cep = campoCep.value.replace(/\D/g, '');

        if (cep.length !== 8) {
            return;
        }

        if (cep === ultimoCepConsultado) {
            return;
        }

        // Exibe o indicador
        indicadorCep.style.display = 'block';

        try {

            const cepFormatado = cep.substring(0, 5) + '-' + cep.substring(5);

            const resposta = await fetch(
                `https://cdn.apicep.com/file/apicep/${cepFormatado}.json`
            );

            if (!resposta.ok) {
                return;
            }

            const dados = await resposta.json();

            if (
                !dados ||
                dados.status !== 200 ||
                !dados.address ||
                !dados.district ||
                !dados.city ||
                !dados.state
            ) {
                return;
            }

            // Preenche os campos
            campoEndereco.value = dados.address;
            campoBairro.value = dados.district;
            campoCidade.value = dados.city;
            campoUf.value = dados.state;

            // Dispara eventos change
            [
                campoEndereco,
                campoBairro,
                campoCidade,
                campoUf
            ].forEach(campo => {
                campo.dispatchEvent(
                    new Event('change', { bubbles: true })
                );
            });

            ultimoCepConsultado = cep;

            // Foco no campo Número
            if (campoNumero) {
                campoNumero.focus();
            }

        } catch (erro) {

            console.warn('Não foi possível consultar o CEP:', erro);

        } finally {

            // Esconde o indicador após a resposta ou erro
            indicadorCep.style.display = 'none';
        }
    }

    campoCep.addEventListener('blur', consultarCep);
    campoCep.addEventListener('change', consultarCep);
}


</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
