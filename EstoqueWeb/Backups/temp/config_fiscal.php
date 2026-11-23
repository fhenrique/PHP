<?php

require_once 'includes/auth.php';
require_once 'config/conexao.php';

exigirEmpresaSelecionada();

// Só Administrador (1) e Administrador Geral (3)
if (!in_array((int) ($_SESSION['perfil_id'] ?? 0), [1, 3], true)) {
    http_response_code(403);
    exit('Acesso negado. Somente administradores podem acessar a Configuração Fiscal.');
}

$empresaId = (int) $_SESSION['empresa_id'];

$tituloPagina = 'Configuração Fiscal';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$tipoMensagem = 'success';

// Busca config da empresa
$stmt = $pdo->prepare("SELECT * FROM config_fiscal WHERE empresa_id = ? LIMIT 1");
$stmt->execute([$empresaId]);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existir, cria na hora
if (!$config) {
    $stmt = $pdo->prepare("INSERT INTO config_fiscal (empresa_id, ano_vigencia, aliquota_cbs_padrao, aliquota_ibs_padrao, cfop_padrao, cst_ibs_cbs_padrao, cclasstrib_padrao) VALUES (?, 2027, 11.30, 0.10, '5102', '000', '000001')");
    $stmt->execute([$empresaId]);
    $stmt = $pdo->prepare("SELECT * FROM config_fiscal WHERE empresa_id = ? LIMIT 1");
    $stmt->execute([$empresaId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit('Requisição inválida. Atualize a página.');
    }

    $ano = (int) ($_POST['ano_vigencia'] ?? 2027);
    $cbs = str_replace(',', '.', trim($_POST['aliquota_cbs_padrao'] ?? ''));
    $ibs = str_replace(',', '.', trim($_POST['aliquota_ibs_padrao'] ?? ''));
    $cfop = preg_replace('/\D/', '', $_POST['cfop_padrao'] ?? '');
    $cst = preg_replace('/\D/', '', $_POST['cst_ibs_cbs_padrao'] ?? '');
    $cclass = preg_replace('/\D/', '', $_POST['cclasstrib_padrao'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE config_fiscal SET
            ano_vigencia = ?,
            aliquota_cbs_padrao = ?,
            aliquota_ibs_padrao = ?,
            cfop_padrao = ?,
            cst_ibs_cbs_padrao = ?,
            cclasstrib_padrao = ?
        WHERE empresa_id = ?
    ");
    $stmt->execute([
        $ano,
        $cbs === '' ? null : $cbs,
        $ibs === '' ? null : $ibs,
        $cfop === '' ? null : $cfop,
        $cst === '' ? null : $cst,
        $cclass === '' ? null : $cclass,
        $empresaId
    ]);

    $mensagem = 'Configuração salva com sucesso!';
    // Recarrega
    $stmt = $pdo->prepare("SELECT * FROM config_fiscal WHERE empresa_id = ? LIMIT 1");
    $stmt->execute([$empresaId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

require_once 'includes/header.php';
?>

<h1 class="page-title">
    <i class="bi bi-gear text-primary"></i>
    Configuração Fiscal
</h1>
<div class="mb-3">
    <a href="ajuda_fiscal.php#config" class="btn btn-outline-info btn-sm">
        <i class="bi bi-question-circle me-1"></i>
        Ajuda Fiscal - Como funciona a Configuração?
    </a>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-<?= h($tipoMensagem) ?> alert-dismissible fade show">
        <?= h($mensagem) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card card-padrao mb-4">
    <div class="card-header bg-white py-3">
        <strong><i class="bi bi-receipt me-2"></i> Reforma Tributária - Padrão da Empresa</strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Ano de Vigência *</label>
                    <input type="number" name="ano_vigencia" class="form-control" value="<?= h($config['ano_vigencia']) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Alíquota CBS Padrão (%)</label>
                    <input type="text" name="aliquota_cbs_padrao" class="form-control" inputmode="decimal" value="<?= h($config['aliquota_cbs_padrao']) ?>">
                    <div class="form-text">Ex: 11,30 para 2027</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Alíquota IBS Padrão (%)</label>
                    <input type="text" name="aliquota_ibs_padrao" class="form-control" inputmode="decimal" value="<?= h($config['aliquota_ibs_padrao']) ?>">
                    <div class="form-text">Ex: 0,10 para 2027</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">CFOP Padrão</label>
                    <input type="text" name="cfop_padrao" class="form-control" maxlength="4" inputmode="numeric" value="<?= h($config['cfop_padrao']) ?>">
                    <div class="form-text">Usado como sugestão na venda</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">CST IBS/CBS Padrão</label>
                    <input type="text" name="cst_ibs_cbs_padrao" class="form-control" maxlength="3" inputmode="numeric" value="<?= h($config['cst_ibs_cbs_padrao']) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">cClassTrib Padrão</label>
                    <input type="text" name="cclasstrib_padrao" class="form-control" maxlength="6" inputmode="numeric" value="<?= h($config['cclasstrib_padrao']) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-principal"><i class="bi bi-check-circle me-1"></i> Salvar</button>
            <a href="produtos.php" class="btn btn-secondary">Voltar para Produtos</a>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>