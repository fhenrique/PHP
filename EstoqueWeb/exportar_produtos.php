
<?php

require_once 'includes/auth.php';
require_once 'config/conexao.php';

exigirEmpresaSelecionada();

$empresaId = (int) $_SESSION['empresa_id'];
$tituloPagina = 'Exportar Produtos';

function hExportarProdutos($valor)
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function textoSeguroExcelProdutos($valor)
{
    $valor = (string) ($valor ?? '');

    if ($valor !== '' && preg_match('/^[\s]*[=+\-@]/u', $valor)) {
        $valor = "'" . $valor;
    }

    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function celulaTextoExcelProdutos($valor)
{
    return '<td style="mso-number-format:\'@\';">'
        . textoSeguroExcelProdutos($valor)
        . '</td>';
}

function celulaNumeroExcelProdutos($valor)
{
    if ($valor === null || $valor === '') {
        return '<td></td>';
    }

    $numero = number_format((float) $valor, 3, '.', '');

    return '<td style="mso-number-format:\'0.000\';">'
        . $numero
        . '</td>';
}

function valorCsvProdutos($valor)
{
    $valor = (string) ($valor ?? '');

    if ($valor !== '' && preg_match('/^[\s]*[=+\-@]/u', $valor)) {
        $valor = "'" . $valor;
    }

    return $valor;
}

// ==========================================
// LISTA DE CÓDIGOS DOS PRODUTOS DA EMPRESA
// ==========================================

$stmt = $pdo->prepare("
    SELECT codigo, nome
    FROM produtos
    WHERE empresa_id = ?
    ORDER BY codigo ASC
");

$stmt->execute([$empresaId]);

$listaCodigos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// FILTROS RECEBIDOS
// ==========================================

$codigoFiltro = trim($_GET['codigo'] ?? '');
$categoriaId = (int) ($_GET['categoria_id'] ?? 0);
$unidade = strtoupper(trim($_GET['unidade'] ?? ''));
$situacao = $_GET['situacao'] ?? 'todos';
$estoque = $_GET['estoque'] ?? 'todos';

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

if (!isset($unidades[$unidade])) {
    $unidade = '';
}

if (!in_array($situacao, ['todos', 'ativo', 'inativo'], true)) {
    $situacao = 'todos';
}

if (!in_array($estoque, ['todos', 'positivo', 'zerado', 'abaixo_minimo'], true)) {
    $estoque = 'todos';
}

// ==========================================
// CATEGORIAS DA EMPRESA
// ==========================================

$stmt = $pdo->prepare("
    SELECT id, nome
    FROM categorias
    WHERE empresa_id = ?
    ORDER BY nome
");

$stmt->execute([$empresaId]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// CONSULTA DOS PRODUTOS
// ==========================================

$produtos = [];
$totalProdutos = 0;
$consultaRealizada = isset($_GET['filtrar']) || isset($_GET['exportar']);

$sql = "
    SELECT
        p.id,
        p.codigo,
        p.nome,
        p.descricao,
        p.unidade,
        p.estoque_atual,
        p.estoque_minimo,
        p.ativo,
        c.nome AS categoria
    FROM produtos p
    INNER JOIN categorias c
        ON c.id = p.categoria_id
       AND c.empresa_id = p.empresa_id
    WHERE p.empresa_id = :empresa_id
";

$params = [
    ':empresa_id' => $empresaId
];

if ($codigoFiltro !== '') {
    $sql .= " AND p.codigo = :codigo ";
    $params[':codigo'] = $codigoFiltro;
}

if ($categoriaId > 0) {
    $sql .= " AND p.categoria_id = :categoria_id ";
    $params[':categoria_id'] = $categoriaId;
}

if ($unidade !== '') {
    $sql .= " AND p.unidade = :unidade ";
    $params[':unidade'] = $unidade;
}

if ($situacao === 'ativo') {
    $sql .= " AND p.ativo = 1 ";
} elseif ($situacao === 'inativo') {
    $sql .= " AND p.ativo = 0 ";
}

if ($estoque === 'positivo') {
    $sql .= " AND p.estoque_atual > 0 ";
} elseif ($estoque === 'zerado') {
    $sql .= " AND p.estoque_atual = 0 ";
} elseif ($estoque === 'abaixo_minimo') {
    $sql .= " AND p.estoque_atual < p.estoque_minimo ";
}

$sql .= " ORDER BY p.nome ASC ";

if ($consultaRealizada) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalProdutos = count($produtos);
}

// ==========================================
// EXPORTAÇÃO
// ==========================================

if (isset($_GET['exportar'])) {

    $formato = $_GET['formato'] ?? '';

    if (!in_array($formato, ['xls', 'csv'], true)) {
        http_response_code(400);
        exit('Formato de exportação inválido.');
    }

    $nomeArquivo = 'produtos_' . date('Y-m-d_H-i-s');

    // ======================================
    // EXPORTAÇÃO CSV
    // ======================================

    if ($formato === 'csv') {

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";

        $saida = fopen('php://output', 'w');

        fputcsv($saida, [
            'Código',
            'Produto',
            'Categoria',
            'Descrição',
            'Unidade',
            'Estoque atual',
            'Estoque mínimo',
            'Situação'
        ], ';', '"', '');

        foreach ($produtos as $produto) {

            fputcsv($saida, [
                valorCsvProdutos($produto['codigo']),
                valorCsvProdutos($produto['nome']),
                valorCsvProdutos($produto['categoria']),
                valorCsvProdutos($produto['descricao']),
                valorCsvProdutos($produto['unidade']),
                number_format((float) $produto['estoque_atual'], 3, ',', '.'),
                number_format((float) $produto['estoque_minimo'], 3, ',', '.'),
                (int) $produto['ativo'] === 1 ? 'Ativo' : 'Inativo'
            ], ';', '"', '');
        }

        fclose($saida);
        exit;
    }

    // ======================================
    // EXPORTAÇÃO EXCEL
    // ======================================

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nomeArquivo . '.xls"');
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
                border: 1px solid #000;
                padding: 6px;
            }

            th {
                background-color: #D9EAF7;
                font-weight: bold;
            }
        </style>
    </head>
    <body>

    <table>
        <tr>
            <th colspan="8">RELATÓRIO DE PRODUTOS</th>
        </tr>
        <tr>
            <td colspan="8">
                Empresa: <?= textoSeguroExcelProdutos($_SESSION['empresa_nome'] ?? '') ?>
            </td>
        </tr>
        <tr>
            <td colspan="8">
                Data da exportação: <?= date('d/m/Y H:i:s') ?>
            </td>
        </tr>
        <tr>
            <td colspan="8">
                Total de produtos: <?= count($produtos) ?>
            </td>
        </tr>
        <tr>
            <td colspan="8"></td>
        </tr>

        <tr>
            <th>Código</th>
            <th>Produto</th>
            <th>Categoria</th>
            <th>Descrição</th>
            <th>Unidade</th>
            <th>Estoque atual</th>
            <th>Estoque mínimo</th>
            <th>Situação</th>
        </tr>

        <?php foreach ($produtos as $produto): ?>
            <tr>
                <?= celulaTextoExcelProdutos($produto['codigo']) ?>
                <?= celulaTextoExcelProdutos($produto['nome']) ?>
                <?= celulaTextoExcelProdutos($produto['categoria']) ?>
                <?= celulaTextoExcelProdutos($produto['descricao']) ?>
                <?= celulaTextoExcelProdutos($produto['unidade']) ?>
                <?= celulaNumeroExcelProdutos($produto['estoque_atual']) ?>
                <?= celulaNumeroExcelProdutos($produto['estoque_minimo']) ?>
                <?= celulaTextoExcelProdutos(
                    (int) $produto['ativo'] === 1 ? 'Ativo' : 'Inativo'
                ) ?>
            </tr>
        <?php endforeach; ?>
    </table>

    </body>
    </html>
    <?php
    exit;
}

require_once 'includes/header.php';

?>

<h1 class="page-title">
    <i class="bi bi-file-earmark-spreadsheet text-primary"></i>
    Exportar Produtos
</h1>

<div class="card card-padrao mb-4">

    <div class="card-header bg-white py-3">
        <strong>
            <i class="bi bi-funnel me-2"></i>
            Filtros para exportação
        </strong>
    </div>

    <div class="card-body">

        <form method="GET" action="exportar_produtos.php">

            <input type="hidden" name="filtrar" value="1">

            <div class="row">

<div class="col-md-6 mb-3">

    <label class="form-label">Código do produto</label>

    <select name="codigo" class="form-select">

        <option value="">Todos os produtos</option>

        <?php foreach ($listaCodigos as $item): ?>

            <option
                value="<?= hExportarProdutos($item['codigo']) ?>"
                <?= $codigoFiltro === $item['codigo'] ? 'selected' : '' ?>>

                <?= hExportarProdutos($item['codigo'] . ' - ' . $item['nome']) ?>

            </option>

        <?php endforeach; ?>

    </select>

</div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Categoria</label>

                    <select name="categoria_id" class="form-select">
                        <option value="0">Todas as categorias</option>

                        <?php foreach ($categorias as $categoria): ?>
                            <option
                                value="<?= (int) $categoria['id'] ?>"
                                <?= $categoriaId === (int) $categoria['id'] ? 'selected' : '' ?>>
                                <?= hExportarProdutos($categoria['nome']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Unidade de medida</label>

                    <select name="unidade" class="form-select">
                        <option value="">Todas as unidades</option>

                        <?php foreach ($unidades as $sigla => $descricaoUnidade): ?>
                            <option
                                value="<?= hExportarProdutos($sigla) ?>"
                                <?= $unidade === $sigla ? 'selected' : '' ?>>
                                <?= hExportarProdutos($descricaoUnidade) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Situação</label>

                    <select name="situacao" class="form-select">
                        <option value="todos" <?= $situacao === 'todos' ? 'selected' : '' ?>>
                            Todos
                        </option>
                        <option value="ativo" <?= $situacao === 'ativo' ? 'selected' : '' ?>>
                            Ativos
                        </option>
                        <option value="inativo" <?= $situacao === 'inativo' ? 'selected' : '' ?>>
                            Inativos
                        </option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Estoque</label>

                    <select name="estoque" class="form-select">
                        <option value="todos" <?= $estoque === 'todos' ? 'selected' : '' ?>>
                            Todos
                        </option>
                        <option value="positivo" <?= $estoque === 'positivo' ? 'selected' : '' ?>>
                            Com estoque positivo
                        </option>
                        <option value="zerado" <?= $estoque === 'zerado' ? 'selected' : '' ?>>
                            Estoque zerado
                        </option>
                        <option value="abaixo_minimo" <?= $estoque === 'abaixo_minimo' ? 'selected' : '' ?>>
                            Abaixo do estoque mínimo
                        </option>
                    </select>
                </div>

            </div>

            <button type="submit" class="btn btn-principal">
                <i class="bi bi-search me-1"></i>
                Aplicar filtros
            </button>

            <a href="exportar_produtos.php" class="btn btn-secondary">
                Limpar filtros
            </a>

            <a href="produtos.php" class="btn btn-outline-secondary">
                Voltar ao cadastro
            </a>

        </form>

    </div>
</div>

<?php if ($consultaRealizada): ?>

    <div class="card card-padrao">

        <div class="card-header bg-white py-3">
            <strong>
                <i class="bi bi-list-ul me-2"></i>
                Resultado da pesquisa
            </strong>
        </div>

        <div class="card-body">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

                <div class="text-muted">
                    Total de produtos encontrados:
                    <strong><?= $totalProdutos ?></strong>
                </div>

                <div class="d-flex flex-wrap gap-2">

                    <form method="GET" action="exportar_produtos.php">
                        <?php foreach ($_GET as $chave => $valor): ?>
                            <?php if ($chave !== 'exportar' && $chave !== 'formato'): ?>
                                <input
                                    type="hidden"
                                    name="<?= hExportarProdutos($chave) ?>"
                                    value="<?= hExportarProdutos($valor) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <input type="hidden" name="exportar" value="1">
                        <input type="hidden" name="formato" value="xls">

                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel me-1"></i>
                            Exportar Excel
                        </button>
                    </form>

                    <form method="GET" action="exportar_produtos.php">
                        <?php foreach ($_GET as $chave => $valor): ?>
                            <?php if ($chave !== 'exportar' && $chave !== 'formato'): ?>
                                <input
                                    type="hidden"
                                    name="<?= hExportarProdutos($chave) ?>"
                                    value="<?= hExportarProdutos($valor) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <input type="hidden" name="exportar" value="1">
                        <input type="hidden" name="formato" value="csv">

                        <button type="submit" class="btn btn-outline-success">
                            <i class="bi bi-filetype-csv me-1"></i>
                            Exportar CSV
                        </button>
                    </form>

                </div>
            </div>

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
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($produtos as $produto): ?>

                            <tr>
                                <td><?= hExportarProdutos($produto['codigo']) ?></td>
                                <td><?= hExportarProdutos($produto['nome']) ?></td>
                                <td><?= hExportarProdutos($produto['categoria']) ?></td>
                                <td><?= hExportarProdutos($produto['unidade']) ?></td>

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
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        <?php if (!$produtos): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nenhum produto encontrado com os filtros informados.
                                </td>
                            </tr>
                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
