
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/conexao.php';

exigirEmpresaSelecionada();

$empresaId = (int) $_SESSION['empresa_id'];

function hMovimentacao($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Protege textos exportados contra interpretação como fórmula
 * pelo Excel.
 */
function textoExcelMovimentacao($valor)
{
    $valor = (string) ($valor ?? '');

    if ($valor !== '' && preg_match('/^[\s]*[=+\-@]/u', $valor)) {
        $valor = "'" . $valor;
    }

    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna uma célula de texto formatada como texto no Excel.
 */
function celulaTextoExcel($valor)
{
    return '<td style="mso-number-format:\'@\';">'
        . textoExcelMovimentacao($valor)
        . '</td>';
}

/**
 * Retorna uma célula numérica para o Excel.
 */
function celulaNumeroExcel($valor, $decimais = 3)
{
    if ($valor === null || $valor === '') {
        return '<td></td>';
    }

    $numero = number_format((float) $valor, $decimais, '.', '');

    return '<td style="mso-number-format:\'0.'
        . str_repeat('0', $decimais)
        . '\';">'
        . $numero
        . '</td>';
}

function valorCsvMovimentacao($valor)
{
    $valor = (string) ($valor ?? '');

    // Evita que o Excel interprete textos como fórmulas.
    if ($valor !== '' && preg_match('/^[\s]*[=+\-@]/u', $valor)) {
        $valor = "'" . $valor;
    }

    return $valor;
}

// ======================================================
// FILTROS
// ======================================================

$filtroTipo = $_GET['tipo'] ?? 'T';
$filtroProduto = (int) ($_GET['produto_id'] ?? 0);
$filtroUsuario = (int) ($_GET['usuario_id'] ?? 0);
$dataInicial = trim($_GET['data_inicial'] ?? '');
$dataFinal = trim($_GET['data_final'] ?? '');
$busca = trim($_GET['busca'] ?? '');

$erros = [];

if (!in_array($filtroTipo, ['T', 'ENTRADA', 'SAIDA'], true)) {
    $filtroTipo = 'T';
}

function dataValidaMovimentacao($data)
{
    if ($data === '') {
        return true;
    }

    $objeto = DateTime::createFromFormat('Y-m-d', $data);

    return $objeto && $objeto->format('Y-m-d') === $data;
}

if (!dataValidaMovimentacao($dataInicial)) {
    $erros[] = 'Data inicial inválida.';
    $dataInicial = '';
}

if (!dataValidaMovimentacao($dataFinal)) {
    $erros[] = 'Data final inválida.';
    $dataFinal = '';
}

if (
    $dataInicial !== '' &&
    $dataFinal !== '' &&
    $dataInicial > $dataFinal
) {
    $erros[] = 'A data inicial não pode ser maior que a data final.';
}

// ======================================================
// PRODUTOS PARA O FILTRO
// ======================================================

$stmtProdutos = $pdo->prepare("
    SELECT id, codigo, nome, unidade
    FROM produtos
    WHERE empresa_id = :empresa_id
    ORDER BY nome ASC
");

$stmtProdutos->execute([
    ':empresa_id' => $empresaId
]);

$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// ======================================================
// USUÁRIOS PARA O FILTRO
// ======================================================

$stmtUsuarios = $pdo->prepare("
    SELECT DISTINCT u.id, u.nome
    FROM movimentacoes m
    INNER JOIN usuarios u
        ON u.id = m.usuario_id
    WHERE m.empresa_id = :empresa_id
    ORDER BY u.nome ASC
");

$stmtUsuarios->execute([
    ':empresa_id' => $empresaId
]);

$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

// ======================================================
// CONSULTA BASE DAS MOVIMENTAÇÕES
// ======================================================

$sqlBase = "
    FROM movimentacoes m

    INNER JOIN produtos p
        ON p.id = m.produto_id
        AND p.empresa_id = m.empresa_id

    INNER JOIN usuarios u
        ON u.id = m.usuario_id

    LEFT JOIN fornecedores f
        ON f.id = m.fornecedor_id
        AND f.empresa_id = m.empresa_id

    WHERE m.empresa_id = :empresa_id
";

$params = [
    ':empresa_id' => $empresaId
];

// Filtro por tipo
if ($filtroTipo !== 'T') {
    $sqlBase .= " AND m.tipo = :tipo";
    $params[':tipo'] = $filtroTipo;
}

// Filtro por produto
if ($filtroProduto > 0) {
    $sqlBase .= " AND m.produto_id = :produto_id";
    $params[':produto_id'] = $filtroProduto;
}

// Filtro por usuário
if ($filtroUsuario > 0) {
    $sqlBase .= " AND m.usuario_id = :usuario_id";
    $params[':usuario_id'] = $filtroUsuario;
}

// Filtro por data inicial
if ($dataInicial !== '') {
    $sqlBase .= " AND m.data_movimentacao >= :data_inicial";
    $params[':data_inicial'] = $dataInicial . ' 00:00:00';
}

// Filtro por data final
if ($dataFinal !== '') {
    $sqlBase .= " AND m.data_movimentacao <= :data_final";
    $params[':data_final'] = $dataFinal . ' 23:59:59';
}

// Pesquisa textual
if ($busca !== '') {
    $sqlBase .= "
        AND (
            p.nome LIKE :busca_produto
            OR p.codigo LIKE :busca_codigo
            OR u.nome LIKE :busca_usuario
            OR m.documento LIKE :busca_documento
            OR m.setor_destino LIKE :busca_setor
            OR m.motivo LIKE :busca_motivo
            OR f.razao_social LIKE :busca_fornecedor
            OR f.nome_fantasia LIKE :busca_fantasia
        )
    ";

    $termo = '%' . $busca . '%';

    $params[':busca_produto'] = $termo;
    $params[':busca_codigo'] = $termo;
    $params[':busca_usuario'] = $termo;
    $params[':busca_documento'] = $termo;
    $params[':busca_setor'] = $termo;
    $params[':busca_motivo'] = $termo;
    $params[':busca_fornecedor'] = $termo;
    $params[':busca_fantasia'] = $termo;
}

// ======================================================
// EXPORTAÇÃO PARA EXCEL (.XLS)
// ======================================================


// ======================================================
// EXPORTAÇÃO EXCEL (.XLS) E CSV (.CSV)
// ======================================================

if (isset($_GET['exportar']) && $_GET['exportar'] === '1') {

    if (!empty($erros)) {
        http_response_code(400);
        exit('Não foi possível exportar: verifique os filtros informados.');
    }

    $formato = $_GET['formato'] ?? 'xls';

    if (!in_array($formato, ['xls', 'csv'], true)) {
        $formato = 'xls';
    }

    $sqlExportacao = "
        SELECT
            m.id,
            m.tipo,
            m.quantidade,
            m.valor_unitario,
            m.valor_total,
            m.documento,
            m.setor_destino,
            m.responsavel_recebimento,
            m.motivo,
            m.observacao,
            m.data_movimentacao,

            p.codigo,
            p.nome AS produto_nome,
            p.unidade,

            u.nome AS usuario_nome,

            f.razao_social AS fornecedor_razao_social,
            f.nome_fantasia AS fornecedor_nome_fantasia

        $sqlBase

        ORDER BY m.data_movimentacao DESC, m.id DESC
    ";

    $stmtExportacao = $pdo->prepare($sqlExportacao);
    $stmtExportacao->execute($params);

    $registrosExportacao = $stmtExportacao->fetchAll(PDO::FETCH_ASSOC);

    $nomeEmpresa = $_SESSION['empresa_nome'] ?? 'Empresa selecionada';

    $nomeArquivo = 'movimentacoes_' . date('Y-m-d_H-i-s');

    // Limpa qualquer saída anterior.
    while (ob_get_level()) {
        ob_end_clean();
    }

    // ==================================================
    // EXPORTAÇÃO CSV
    // ==================================================

    if ($formato === 'csv') {

        $nomeArquivo .= '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM para reconhecimento de caracteres acentuados pelo Excel.
        echo "\xEF\xBB\xBF";

        $saida = fopen('php://output', 'w');

        // Identificação da empresa e data da exportação.
        fputcsv($saida, [
            'HISTÓRICO DE MOVIMENTAÇÕES DE ESTOQUE'
        ], ';', '"', '');

        fputcsv($saida, [
            'Empresa',
            valorCsvMovimentacao($nomeEmpresa)
        ], ';', '"', '');

        fputcsv($saida, [
            'Data da exportação',
            date('d/m/Y H:i:s')
        ], ';', '"', '');

        fputcsv($saida, [], ';', '"', '');

        // Cabeçalhos.
        fputcsv($saida, [
            'ID',
            'Data',
            'Tipo',
            'Código',
            'Produto',
            'Unidade',
            'Quantidade',
            'Usuário responsável',
            'Fornecedor',
            'Setor de destino',
            'Responsável pelo recebimento',
            'Documento',
            'Motivo',
            'Observações',
            'Valor unitário',
            'Valor total'
        ], ';', '"', '');

        // Registros.
        foreach ($registrosExportacao as $registro) {

            $fornecedor = $registro['fornecedor_nome_fantasia']
                ?: $registro['fornecedor_razao_social']
                ?: '';

            fputcsv($saida, [
                (int) $registro['id'],

                date(
                    'd/m/Y H:i:s',
                    strtotime($registro['data_movimentacao'])
                ),

                $registro['tipo'] === 'ENTRADA' ? 'Entrada' : 'Saída',

                valorCsvMovimentacao($registro['codigo']),
                valorCsvMovimentacao($registro['produto_nome']),
                valorCsvMovimentacao($registro['unidade']),

                number_format((float) $registro['quantidade'], 3, ',', '.'),

                valorCsvMovimentacao($registro['usuario_nome']),
                valorCsvMovimentacao($fornecedor),
                valorCsvMovimentacao($registro['setor_destino']),
                valorCsvMovimentacao($registro['responsavel_recebimento']),
                valorCsvMovimentacao($registro['documento']),
                valorCsvMovimentacao($registro['motivo']),
                valorCsvMovimentacao($registro['observacao']),

                $registro['valor_unitario'] !== null
                    ? number_format((float) $registro['valor_unitario'], 2, ',', '.')
                    : '',

                $registro['valor_total'] !== null
                    ? number_format((float) $registro['valor_total'], 2, ',', '.')
                    : ''

            ], ';', '"', '');
        }

        fclose($saida);
        exit;
    }

    // ==================================================
    // EXPORTAÇÃO EXCEL (.XLS)
    // ==================================================

    $nomeArquivo .= '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
            }

            th, td {
                border: 1px solid #cccccc;
                padding: 6px;
            }

            th {
                background-color: #D9EAF7;
                font-weight: bold;
                text-align: center;
            }

            .titulo {
                font-size: 16px;
                font-weight: bold;
            }

            .subtitulo {
                font-size: 12px;
            }
        </style>
    </head>
    <body>

        <table>

            <tr>
                <td colspan="16" class="titulo">
                    HISTÓRICO DE MOVIMENTAÇÕES DE ESTOQUE
                </td>
            </tr>

            <tr>
                <td colspan="16" class="subtitulo">
                    Empresa:
                    <?= textoExcelMovimentacao($nomeEmpresa) ?>
                </td>
            </tr>

            <tr>
                <td colspan="16" class="subtitulo">
                    Data da exportação:
                    <?= date('d/m/Y H:i:s') ?>
                </td>
            </tr>

            <tr>
                <td colspan="16"></td>
            </tr>

            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Tipo</th>
                <th>Código</th>
                <th>Produto</th>
                <th>Unidade</th>
                <th>Quantidade</th>
                <th>Usuário responsável</th>
                <th>Fornecedor</th>
                <th>Setor de destino</th>
                <th>Responsável pelo recebimento</th>
                <th>Documento</th>
                <th>Motivo</th>
                <th>Observações</th>
                <th>Valor unitário</th>
                <th>Valor total</th>
            </tr>

            <?php foreach ($registrosExportacao as $registro): ?>

                <tr>

                    <td><?= (int) $registro['id'] ?></td>

                    <td>
                        <?= date('d/m/Y H:i:s', strtotime($registro['data_movimentacao'])) ?>
                    </td>

                    <td>
                        <?= $registro['tipo'] === 'ENTRADA' ? 'Entrada' : 'Saída' ?>
                    </td>

                    <?= celulaTextoExcel($registro['codigo']) ?>
                    <?= celulaTextoExcel($registro['produto_nome']) ?>
                    <?= celulaTextoExcel($registro['unidade']) ?>
                    <?= celulaNumeroExcel($registro['quantidade'], 3) ?>
                    <?= celulaTextoExcel($registro['usuario_nome']) ?>

                    <?= celulaTextoExcel(
                        $registro['fornecedor_nome_fantasia']
                        ?: $registro['fornecedor_razao_social']
                        ?: ''
                    ) ?>

                    <?= celulaTextoExcel($registro['setor_destino']) ?>
                    <?= celulaTextoExcel($registro['responsavel_recebimento']) ?>
                    <?= celulaTextoExcel($registro['documento']) ?>
                    <?= celulaTextoExcel($registro['motivo']) ?>
                    <?= celulaTextoExcel($registro['observacao']) ?>
                    <?= celulaNumeroExcel($registro['valor_unitario'], 2) ?>
                    <?= celulaNumeroExcel($registro['valor_total'], 2) ?>

                </tr>

            <?php endforeach; ?>

        </table>

    </body>
    </html>
    <?php
    exit;
}


// ======================================================
// PAGINAÇÃO
// ======================================================

$porPagina = 20;

$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$offset = ($pagina - 1) * $porPagina;

// ======================================================
// TOTAL DE REGISTROS
// ======================================================

$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    $sqlBase
");

$stmtTotal->execute($params);

$totalRegistros = (int) $stmtTotal->fetchColumn();

$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));

$pagina = min($pagina, $totalPaginas);

$offset = ($pagina - 1) * $porPagina;

// ======================================================
// CONSULTA PRINCIPAL
// ======================================================

$sql = "
    SELECT
        m.id,
        m.tipo,
        m.quantidade,
        m.valor_unitario,
        m.valor_total,
        m.documento,
        m.setor_destino,
        m.responsavel_recebimento,
        m.motivo,
        m.observacao,
        m.data_movimentacao,

        p.codigo,
        p.nome AS produto_nome,
        p.unidade,

        u.nome AS usuario_nome,

        f.razao_social AS fornecedor_razao_social,
        f.nome_fantasia AS fornecedor_nome_fantasia

    $sqlBase

    ORDER BY m.data_movimentacao DESC, m.id DESC

    LIMIT $porPagina OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================================================
// RESUMO DOS REGISTROS FILTRADOS
// ======================================================

$sqlResumo = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN m.tipo = 'ENTRADA' THEN 1 ELSE 0 END) AS entradas,
        SUM(CASE WHEN m.tipo = 'SAIDA' THEN 1 ELSE 0 END) AS saidas,
        SUM(CASE WHEN m.tipo = 'ENTRADA' THEN m.quantidade ELSE 0 END) AS qtd_entradas,
        SUM(CASE WHEN m.tipo = 'SAIDA' THEN m.quantidade ELSE 0 END) AS qtd_saidas

    $sqlBase
";

$stmtResumo = $pdo->prepare($sqlResumo);
$stmtResumo->execute($params);

$resumo = $stmtResumo->fetch(PDO::FETCH_ASSOC);

// ======================================================
// CABEÇALHO
// ======================================================

require_once __DIR__ . '/includes/header.php';

?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="mb-1">Movimentações de Estoque</h3>

            <p class="text-muted mb-0">
                Histórico de entradas e saídas de produtos.
            </p>

            <p class="text-muted mb-0 mt-1">
                <i class="bi bi-building"></i>
                Empresa:
                <strong>
                    <?= hMovimentacao($_SESSION['empresa_nome'] ?? 'Empresa selecionada') ?>
                </strong>
            </p>
        </div>

    </div>

    <?php foreach ($erros as $erro): ?>

        <div class="alert alert-danger">
            <?= hMovimentacao($erro) ?>
        </div>

    <?php endforeach; ?>

    <!-- RESUMO -->

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card card-dashboard h-100">
                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="card-icon icon-azul me-3">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>

                        <div>
                            <small class="text-muted">Movimentações</small>
                            <h4 class="mb-0">
                                <?= (int) ($resumo['total'] ?? 0) ?>
                            </h4>
                        </div>

                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-4">

            <div class="card card-dashboard h-100">
                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="card-icon icon-verde me-3">
                            <i class="bi bi-box-arrow-in-down"></i>
                        </div>

                        <div>
                            <small class="text-muted">Entradas</small>
                            <h4 class="mb-0">
                                <?= (int) ($resumo['entradas'] ?? 0) ?>
                            </h4>
                            <small class="text-muted">
                                Quantidade:
                                <?= number_format((float) ($resumo['qtd_entradas'] ?? 0), 3, ',', '.') ?>
                            </small>
                        </div>

                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-4">

            <div class="card card-dashboard h-100">
                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="card-icon icon-vermelho me-3">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </div>

                        <div>
                            <small class="text-muted">Saídas</small>
                            <h4 class="mb-0">
                                <?= (int) ($resumo['saidas'] ?? 0) ?>
                            </h4>
                            <small class="text-muted">
                                Quantidade:
                                <?= number_format((float) ($resumo['qtd_saidas'] ?? 0), 3, ',', '.') ?>
                            </small>
                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>

    <!-- FILTROS -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="bi bi-funnel"></i>
                Filtros de pesquisa
            </h5>
        </div>

        <div class="card-body">

            <form method="GET" action="movimentacoes.php">

                <div class="row g-3">

                    <div class="col-md-3">

                        <label class="form-label">Tipo de movimentação</label>

                        <select name="tipo" class="form-select">

                            <option value="T"
                                <?= $filtroTipo === 'T' ? 'selected' : '' ?>>
                                Todas
                            </option>

                            <option value="ENTRADA"
                                <?= $filtroTipo === 'ENTRADA' ? 'selected' : '' ?>>
                                Entradas
                            </option>

                            <option value="SAIDA"
                                <?= $filtroTipo === 'SAIDA' ? 'selected' : '' ?>>
                                Saídas
                            </option>

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">Produto</label>

                        <select name="produto_id" class="form-select">

                            <option value="0">Todos os produtos</option>

                            <?php foreach ($produtos as $produto): ?>

                                <option value="<?= (int) $produto['id'] ?>"
                                    <?= $filtroProduto === (int) $produto['id'] ? 'selected' : '' ?>>

                                    <?= hMovimentacao($produto['codigo']) ?>
                                    -
                                    <?= hMovimentacao($produto['nome']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">Usuário responsável</label>

                        <select name="usuario_id" class="form-select">

                            <option value="0">Todos os usuários</option>

                            <?php foreach ($usuarios as $usuario): ?>

                                <option value="<?= (int) $usuario['id'] ?>"
                                    <?= $filtroUsuario === (int) $usuario['id'] ? 'selected' : '' ?>>

                                    <?= hMovimentacao($usuario['nome']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">Pesquisar</label>

                        <input type="text"
                            name="busca"
                            class="form-control"
                            placeholder="Produto, documento, setor..."
                            value="<?= hMovimentacao($busca) ?>">

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">Data inicial</label>

                        <input type="date"
                            name="data_inicial"
                            class="form-control"
                            value="<?= hMovimentacao($dataInicial) ?>">

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">Data final</label>

                        <input type="date"
                            name="data_final"
                            class="form-control"
                            value="<?= hMovimentacao($dataFinal) ?>">

                    </div>

                </div>

                <div class="mt-4">

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Pesquisar
                    </button>

                    <a href="movimentacoes.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i>
                        Limpar filtros
                    </a>

                </div>

            </form>

        </div>

    </div>

    <!-- TABELA -->

    <div class="card shadow-sm">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <h5 class="mb-0">
                    Histórico de movimentações
                </h5>

                <!-- EXPORTAÇÃO -->

                <form method="GET"
                    action="movimentacoes.php"
                    class="d-flex align-items-center gap-2">

                    <input type="hidden" name="tipo"
                        value="<?= hMovimentacao($filtroTipo) ?>">

                    <input type="hidden" name="produto_id"
                        value="<?= (int) $filtroProduto ?>">

                    <input type="hidden" name="usuario_id"
                        value="<?= (int) $filtroUsuario ?>">

                    <input type="hidden" name="data_inicial"
                        value="<?= hMovimentacao($dataInicial) ?>">

                    <input type="hidden" name="data_final"
                        value="<?= hMovimentacao($dataFinal) ?>">

                    <input type="hidden" name="busca"
                        value="<?= hMovimentacao($busca) ?>">

                    <input type="hidden" name="exportar" value="1">

<select name="formato" class="form-select"
    aria-label="Formato de exportação">

    <option value="xls">Excel (.xls)</option>

    <option value="csv">CSV (.csv) - Separado por ;</option>

</select>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i>
                        Exportar
                    </button>

                </form>

            </div>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Responsável</th>
                            <th>Fornecedor / Destino</th>
                            <th>Documento / Motivo</th>
                            <th>Valor total</th>
                            <th class="text-center">Detalhes</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($movimentacoes) > 0): ?>

                        <?php foreach ($movimentacoes as $mov): ?>

                            <tr>

                                <td>
                                    <?= (int) $mov['id'] ?>
                                </td>

                                <td>
                                    <?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?>
                                </td>

                                <td>

                                    <?php if ($mov['tipo'] === 'ENTRADA'): ?>

                                        <span class="badge bg-success">
                                            <i class="bi bi-box-arrow-in-down"></i>
                                            Entrada
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                            Saída
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <strong>
                                        <?= hMovimentacao($mov['produto_nome']) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Código:
                                        <?= hMovimentacao($mov['codigo']) ?>
                                    </small>

                                </td>

                                <td>
                                    <?= number_format((float) $mov['quantidade'], 3, ',', '.') ?>
                                    <?= hMovimentacao($mov['unidade']) ?>
                                </td>

                                <td>
                                    <?= hMovimentacao($mov['usuario_nome']) ?>
                                </td>

                                <td>

                                    <?php if ($mov['tipo'] === 'ENTRADA'): ?>

                                        <?= hMovimentacao(
                                            $mov['fornecedor_nome_fantasia']
                                            ?: $mov['fornecedor_razao_social']
                                            ?: 'Não informado'
                                        ) ?>

                                    <?php else: ?>

                                        <?= hMovimentacao(
                                            $mov['setor_destino'] ?: 'Não informado'
                                        ) ?>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?= hMovimentacao($mov['documento'] ?: '-') ?>

                                    <?php if (!empty($mov['motivo'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= hMovimentacao($mov['motivo']) ?>
                                        </small>
                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ($mov['valor_total'] !== null): ?>

                                        R$
                                        <?= number_format((float) $mov['valor_total'], 2, ',', '.') ?>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </td>

                                <td class="text-center">

                                    <button type="button"
                                        class="btn btn-sm btn-info text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalMovimentacao<?= (int) $mov['id'] ?>"
                                        title="Ver detalhes">

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="10"
                                class="text-center text-muted py-4">

                                Nenhuma movimentação encontrada.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- MODAIS DE DETALHES -->

            <?php foreach ($movimentacoes as $mov): ?>

                <div class="modal fade"
                    id="modalMovimentacao<?= (int) $mov['id'] ?>"
                    tabindex="-1"
                    aria-hidden="true">

                    <div class="modal-dialog modal-dialog-centered modal-lg">

                        <div class="modal-content">

                            <div class="modal-header">

                                <h5 class="modal-title">
                                    Detalhes da movimentação #<?= (int) $mov['id'] ?>
                                </h5>

                                <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Fechar"></button>

                            </div>

                            <div class="modal-body">

                                <div class="row g-3">

                                    <div class="col-md-6">

                                        <strong>Produto:</strong>

                                        <p>
                                            <?= hMovimentacao($mov['produto_nome']) ?>
                                        </p>

                                    </div>

                                    <div class="col-md-6">

                                        <strong>Código:</strong>

                                        <p>
                                            <?= hMovimentacao($mov['codigo']) ?>
                                        </p>

                                    </div>

                                    <div class="col-md-4">

                                        <strong>Tipo:</strong>

                                        <p>
                                            <?= $mov['tipo'] === 'ENTRADA' ? 'Entrada' : 'Saída' ?>
                                        </p>

                                    </div>

                                    <div class="col-md-4">

                                        <strong>Quantidade:</strong>

                                        <p>
                                            <?= number_format((float) $mov['quantidade'], 3, ',', '.') ?>
                                            <?= hMovimentacao($mov['unidade']) ?>
                                        </p>

                                    </div>

                                    <div class="col-md-4">

                                        <strong>Data:</strong>

                                        <p>
                                            <?= date('d/m/Y H:i:s', strtotime($mov['data_movimentacao'])) ?>
                                        </p>

                                    </div>

                                    <div class="col-md-6">

                                        <strong>Usuário responsável:</strong>

                                        <p>
                                            <?= hMovimentacao($mov['usuario_nome']) ?>
                                        </p>

                                    </div>

                                    <div class="col-md-6">

                                        <strong>Documento:</strong>

                                        <p>
                                            <?= hMovimentacao($mov['documento'] ?: 'Não informado') ?>
                                        </p>

                                    </div>

                                    <?php if ($mov['tipo'] === 'ENTRADA'): ?>

                                        <div class="col-md-6">

                                            <strong>Fornecedor:</strong>

                                            <p>
                                                <?= hMovimentacao(
                                                    $mov['fornecedor_nome_fantasia']
                                                    ?: $mov['fornecedor_razao_social']
                                                    ?: 'Não informado'
                                                ) ?>
                                            </p>

                                        </div>

                                    <?php else: ?>

                                        <div class="col-md-6">

                                            <strong>Setor de destino:</strong>

                                            <p>
                                                <?= hMovimentacao($mov['setor_destino'] ?: 'Não informado') ?>
                                            </p>

                                        </div>

                                        <div class="col-md-6">

                                            <strong>Responsável pelo recebimento:</strong>

                                            <p>
                                                <?= hMovimentacao($mov['responsavel_recebimento'] ?: 'Não informado') ?>
                                            </p>

                                        </div>

                                    <?php endif; ?>

                                    <div class="col-md-6">

                                        <strong>Valor unitário:</strong>

                                        <p>

                                            <?= $mov['valor_unitario'] !== null
                                                ? 'R$ ' . number_format((float) $mov['valor_unitario'], 2, ',', '.')
                                                : 'Não informado' ?>

                                        </p>

                                    </div>

                                    <div class="col-md-6">

                                        <strong>Valor total:</strong>

                                        <p>

                                            <?= $mov['valor_total'] !== null
                                                ? 'R$ ' . number_format((float) $mov['valor_total'], 2, ',', '.')
                                                : 'Não informado' ?>

                                        </p>

                                    </div>

                                    <div class="col-md-12">

                                        <strong>Motivo:</strong>

                                        <p>
                                            <?= nl2br(hMovimentacao($mov['motivo'] ?: 'Não informado')) ?>
                                        </p>

                                    </div>

                                    <div class="col-md-12">

                                        <strong>Observações:</strong>

                                        <p>
                                            <?= nl2br(hMovimentacao($mov['observacao'] ?: 'Nenhuma observação.')) ?>
                                        </p>

                                    </div>

                                </div>

                            </div>

                            <div class="modal-footer">

                                <button type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal">

                                    Fechar

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

            <!-- PAGINAÇÃO -->

            <?php if ($totalPaginas > 1): ?>

                <nav aria-label="Paginação das movimentações">

                    <ul class="pagination justify-content-center mt-4">

                        <?php

                        $parametros = $_GET;
                        unset($parametros['pagina']);
                        unset($parametros['exportar']);
                        unset($parametros['formato']);

                        ?>

                        <?php if ($pagina > 1): ?>

                            <?php
                            $parametros['pagina'] = $pagina - 1;
                            ?>

                            <li class="page-item">

                                <a class="page-link"
                                    href="movimentacoes.php?<?= hMovimentacao(http_build_query($parametros)) ?>">

                                    Anterior

                                </a>

                            </li>

                        <?php endif; ?>

                        <li class="page-item disabled">

                            <span class="page-link">
                                Página <?= $pagina ?> de <?= $totalPaginas ?>
                            </span>

                        </li>

                        <?php if ($pagina < $totalPaginas): ?>

                            <?php
                            $parametros['pagina'] = $pagina + 1;
                            ?>

                            <li class="page-item">

                                <a class="page-link"
                                    href="movimentacoes.php?<?= hMovimentacao(http_build_query($parametros)) ?>">

                                    Próxima

                                </a>

                            </li>

                        <?php endif; ?>

                    </ul>

                </nav>

            <?php endif; ?>

            <div class="text-muted small">

                Total de registros encontrados:
                <?= $totalRegistros ?>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
