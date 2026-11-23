<?php
session_start();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/auth.php';

/*
|--------------------------------------------------------------------------
| Este módulo permite acesso aos dois perfis:
| Administrador e Operador.
|
| Não utilizar exigirAdministrador() nesta página.
|--------------------------------------------------------------------------
*/

// Garante que o usuário esteja autenticado.
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Garante que exista uma empresa selecionada.
exigirEmpresaSelecionada();

// Empresa selecionada na sessão.
$empresaId = (int) $_SESSION['empresa_id'];

$erro = '';
$sucesso = isset($_GET['sucesso']) ? true : false;
$idEntradaRegistrada = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Token CSRF
if (empty($_SESSION['csrf_entrada_estoque'])) {
    $_SESSION['csrf_entrada_estoque'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_entrada_estoque'];

/*
|--------------------------------------------------------------------------
| Funções auxiliares
|--------------------------------------------------------------------------
*/

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function converterDecimal($valor)
{
    $valor = trim((string) $valor);
    $valor = str_replace(' ', '', $valor);

    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    if ($valor === '' || !is_numeric($valor)) {
        return null;
    }

    return (float) $valor;
}

function validarDataEntrada($data)
{
    $partes = explode('-', $data);

    if (count($partes) !== 3) {
        return false;
    }

    return checkdate(
        (int) $partes[1],
        (int) $partes[2],
        (int) $partes[0]
    );
}

/*
|--------------------------------------------------------------------------
| Carrega fornecedores e produtos ativos da empresa selecionada
|--------------------------------------------------------------------------
*/

try {

    $stmtFornecedores = $pdo->prepare("
        SELECT id, razao_social, nome_fantasia
        FROM fornecedores
        WHERE empresa_id = ?
          AND status = 'A'
        ORDER BY razao_social
    ");

    $stmtFornecedores->execute([$empresaId]);

    $fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

    $stmtProdutos = $pdo->prepare("
        SELECT id, codigo, nome, unidade, estoque_atual
        FROM produtos
        WHERE empresa_id = ?
          AND ativo = 1
        ORDER BY nome
    ");

    $stmtProdutos->execute([$empresaId]);

    $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
    
// Produto enviado pela tela de estoque baixo.
$produtoPreSelecionadoId = (int) ($_GET['produto_id'] ?? 0);

// Confirma que o produto pertence à empresa selecionada
// e está entre os produtos ativos disponíveis.
if ($produtoPreSelecionadoId > 0) {

    $produtoEncontrado = false;

    foreach ($produtos as $produto) {

        if ((int) $produto['id'] === $produtoPreSelecionadoId) {
            $produtoEncontrado = true;
            break;
        }
    }

    // Se não pertencer à empresa ou estiver inativo,
    // não permite sua pré-seleção.
    if (!$produtoEncontrado) {
        $produtoPreSelecionadoId = 0;
    }
}    
    

} catch (PDOException $e) {

    die('Erro ao carregar fornecedores e produtos.');

}

/*
|--------------------------------------------------------------------------
| Processamento do formulário
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        // Verificação CSRF
        if (
            empty($_POST['csrf_token']) ||
            !hash_equals(
                $_SESSION['csrf_entrada_estoque'],
                $_POST['csrf_token']
            )
        ) {
            throw new Exception(
                'Sessão expirada ou formulário inválido. Atualize a página.'
            );
        }

        $fornecedorId = (int) ($_POST['fornecedor_id'] ?? 0);
        $dataEntrada = trim($_POST['data_entrada'] ?? '');
        $tipoDocumento = trim($_POST['tipo_documento'] ?? '');
        $numeroDocumento = trim($_POST['numero_documento'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        $usuarioId = (int) $_SESSION['usuario_id'];
        $responsavel = trim($_SESSION['usuario_nome'] ?? '');

        // Validações do cabeçalho
        if ($fornecedorId <= 0) {
            throw new Exception('Selecione um fornecedor.');
        }

        if (!validarDataEntrada($dataEntrada)) {
            throw new Exception('Informe uma data de entrada válida.');
        }

        $tiposPermitidos = [
            'Nota Fiscal',
            'Cupom Fiscal',
            'Outro'
        ];

        if (!in_array($tipoDocumento, $tiposPermitidos, true)) {
            throw new Exception('Selecione um tipo de documento válido.');
        }

        if ($numeroDocumento === '') {
            throw new Exception('Informe o número do documento.');
        }

        if (strlen($numeroDocumento) > 50) {
            throw new Exception(
                'O número do documento deve ter no máximo 50 caracteres.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Valida o fornecedor dentro da empresa selecionada
        |--------------------------------------------------------------------------
        */

        $stmtFornecedor = $pdo->prepare("
            SELECT id
            FROM fornecedores
            WHERE id = ?
              AND empresa_id = ?
              AND status = 'A'
        ");

        $stmtFornecedor->execute([
            $fornecedorId,
            $empresaId
        ]);

        if (!$stmtFornecedor->fetch()) {
            throw new Exception(
                'Fornecedor não encontrado ou inativo na empresa selecionada.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Recebe os produtos do formulário
        |--------------------------------------------------------------------------
        */

        $idsProdutos = $_POST['produto_id'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];
        $valoresUnitarios = $_POST['valor_unitario'] ?? [];

        if (
            !is_array($idsProdutos) ||
            !is_array($quantidades) ||
            !is_array($valoresUnitarios)
        ) {
            throw new Exception('Os dados dos produtos são inválidos.');
        }

        if (
            count($idsProdutos) === 0 ||
            count($idsProdutos) !== count($quantidades) ||
            count($idsProdutos) !== count($valoresUnitarios)
        ) {
            throw new Exception(
                'Adicione pelo menos um produto à entrada.'
            );
        }

        $itens = [];
        $valorTotalEntrada = 0;

        foreach ($idsProdutos as $indice => $idProdutoInformado) {

            $produtoId = (int) $idProdutoInformado;

            $quantidade = converterDecimal(
                $quantidades[$indice] ?? ''
            );

            $valorUnitario = converterDecimal(
                $valoresUnitarios[$indice] ?? ''
            );

            if ($produtoId <= 0) {
                throw new Exception(
                    'Selecione um produto em todas as linhas.'
                );
            }

            if ($quantidade === null || $quantidade <= 0) {
                throw new Exception(
                    'Informe uma quantidade válida para todos os produtos.'
                );
            }

            if ($valorUnitario === null || $valorUnitario < 0) {
                throw new Exception(
                    'Informe um valor unitário válido para todos os produtos.'
                );
            }

            // Precisão compatível com as colunas DECIMAL
            $quantidade = round($quantidade, 3);
            $valorUnitario = round($valorUnitario, 2);

            if ($quantidade <= 0) {
                throw new Exception(
                    'A quantidade informada é inválida.'
                );
            }

            $valorTotalItem = round(
                $quantidade * $valorUnitario,
                2
            );

            $itens[] = [
                'produto_id' => $produtoId,
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotalItem
            ];

            $valorTotalEntrada += $valorTotalItem;
        }

        $valorTotalEntrada = round($valorTotalEntrada, 2);

        /*
        |--------------------------------------------------------------------------
        | Gravação em transação
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | Confirma novamente o fornecedor dentro da transação
        |--------------------------------------------------------------------------
        */

        $stmtFornecedor = $pdo->prepare("
            SELECT id
            FROM fornecedores
            WHERE id = ?
              AND empresa_id = ?
              AND status = 'A'
            FOR UPDATE
        ");

        $stmtFornecedor->execute([
            $fornecedorId,
            $empresaId
        ]);

        if (!$stmtFornecedor->fetch()) {
            throw new Exception(
                'O fornecedor foi inativado ou não está disponível na empresa selecionada.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Registra o cabeçalho da entrada
        |--------------------------------------------------------------------------
        */

        $stmtEntrada = $pdo->prepare("
            INSERT INTO entradas_estoque (
                empresa_id,
                fornecedor_id,
                usuario_id,
                data_entrada,
                tipo_documento,
                numero_documento,
                valor_total,
                observacoes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtEntrada->execute([
            $empresaId,
            $fornecedorId,
            $usuarioId,
            $dataEntrada,
            $tipoDocumento,
            $numeroDocumento,
            $valorTotalEntrada,
            $observacoes
        ]);

        $entradaId = (int) $pdo->lastInsertId();

        /*
        |--------------------------------------------------------------------------
        | Preparação dos comandos de gravação
        |--------------------------------------------------------------------------
        */

        $stmtProduto = $pdo->prepare("
            SELECT id, nome, estoque_atual
            FROM produtos
            WHERE id = ?
              AND empresa_id = ?
              AND ativo = 1
            FOR UPDATE
        ");

        $stmtItem = $pdo->prepare("
            INSERT INTO itens_entrada (
                empresa_id,
                entrada_id,
                produto_id,
                quantidade,
                valor_unitario,
                valor_total
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtAtualizarEstoque = $pdo->prepare("
            UPDATE produtos
            SET estoque_atual = estoque_atual + ?
            WHERE id = ?
              AND empresa_id = ?
              AND ativo = 1
        ");

        $stmtMovimentacao = $pdo->prepare("
            INSERT INTO movimentacoes (
                empresa_id,
                produto_id,
                usuario_id,
                tipo,
                quantidade,
                valor_unitario,
                valor_total,
                fornecedor_id,
                documento,
                data_movimentacao,
                motivo,
                observacao,
                responsavel_recebimento,
                setor_destino
            ) VALUES (?, ?, ?, 'ENTRADA', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $documento = $tipoDocumento . ' - ' . $numeroDocumento;

        $dataMovimentacao = $dataEntrada . ' ' . date('H:i:s');

        /*
        |--------------------------------------------------------------------------
        | Processa cada produto da entrada
        |--------------------------------------------------------------------------
        */

        foreach ($itens as $item) {

            // Bloqueia e valida o produto dentro da empresa selecionada.
            $stmtProduto->execute([
                $item['produto_id'],
                $empresaId
            ]);

            $produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);

            if (!$produto) {
                throw new Exception(
                    'Um dos produtos selecionados não existe, está inativo ou não pertence à empresa selecionada.'
                );
            }

            // Registra o item da entrada.
            $stmtItem->execute([
                $empresaId,
                $entradaId,
                $item['produto_id'],
                $item['quantidade'],
                $item['valor_unitario'],
                $item['valor_total']
            ]);

            // Atualiza o estoque somente na empresa selecionada.
            $stmtAtualizarEstoque->execute([
                $item['quantidade'],
                $item['produto_id'],
                $empresaId
            ]);

            if ($stmtAtualizarEstoque->rowCount() !== 1) {
                throw new Exception(
                    'Não foi possível atualizar o estoque de um dos produtos.'
                );
            }

            // Registra a movimentação de entrada.
            $stmtMovimentacao->execute([
                $empresaId,
                $item['produto_id'],
                $usuarioId,
                $item['quantidade'],
                $item['valor_unitario'],
                $item['valor_total'],
                $fornecedorId,
                $documento,
                $dataMovimentacao,
                'Entrada de estoque',
                $observacoes,
                $responsavel,
                ''
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Confirma todas as operações
        |--------------------------------------------------------------------------
        */

        $pdo->commit();

        // Renova o token e redireciona para evitar reenvio do formulário.
        $_SESSION['csrf_entrada_estoque'] = bin2hex(random_bytes(32));

        header(
            'Location: entrada_estoque.php?sucesso=1&id=' . $entradaId
        );

        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $erro = $e instanceof PDOException
            ? 'Não foi possível registrar a entrada. Verifique os dados e a estrutura do banco.'
            : $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| Dados para o formulário
|--------------------------------------------------------------------------
*/

$dataAtual = date('Y-m-d');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Entrada de Estoque</h3>
            <p class="text-muted mb-0">
                Registre notas fiscais e entradas de produtos no estoque.
            </p>
        </div>

        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <?php if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Entrada registrada com sucesso!</strong>

            <?php if ($idEntradaRegistrada > 0): ?>
                Número do registro: #<?= escapar($idEntradaRegistrada) ?>.
            <?php endif; ?>

            O estoque dos produtos foi atualizado.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= escapar($erro) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" id="formEntrada" autocomplete="off">

        <input type="hidden" name="csrf_token"
               value="<?= escapar($csrfToken) ?>">

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-white">
                <h5 class="mb-0">Dados da entrada</h5>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Fornecedor *</label>

                        <select name="fornecedor_id" class="form-select" required>
                            <option value="">Selecione o fornecedor</option>

                            <?php foreach ($fornecedores as $fornecedor): ?>
                                <option value="<?= (int) $fornecedor['id'] ?>"
                                    <?= (
                                        isset($_POST['fornecedor_id']) &&
                                        (int) $_POST['fornecedor_id'] === (int) $fornecedor['id']
                                    ) ? 'selected' : '' ?>>

                                    <?= escapar($fornecedor['razao_social']) ?>

                                    <?php if (!empty($fornecedor['nome_fantasia'])): ?>
                                        - <?= escapar($fornecedor['nome_fantasia']) ?>
                                    <?php endif; ?>

                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Data da entrada *</label>

                        <input type="date"
                               name="data_entrada"
                               class="form-control"
                               value="<?= escapar($_POST['data_entrada'] ?? $dataAtual) ?>"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo de documento *</label>

                        <select name="tipo_documento" class="form-select" required>
                            <option value="">Selecione</option>

                            <?php
                            $tiposDocumento = [
                                'Nota Fiscal',
                                'Cupom Fiscal',
                                'Outro'
                            ];

                            foreach ($tiposDocumento as $tipo):
                            ?>
                                <option value="<?= escapar($tipo) ?>"
                                    <?= (
                                        ($_POST['tipo_documento'] ?? '') === $tipo
                                    ) ? 'selected' : '' ?>>
                                    <?= escapar($tipo) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Número do documento *</label>

                        <input type="text"
                               name="numero_documento"
                               class="form-control"
                               maxlength="50"
                               value="<?= escapar($_POST['numero_documento'] ?? '') ?>"
                               required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Observações</label>

                        <input type="text"
                               name="observacoes"
                               class="form-control"
                               value="<?= escapar($_POST['observacoes'] ?? '') ?>">
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Produtos da entrada</h5>

                <button type="button" class="btn btn-primary btn-sm" id="btnAdicionarProduto">
                    <i class="bi bi-plus-circle"></i> Adicionar produto
                </button>
            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered align-middle" id="tabelaProdutos">

                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 260px;">Produto</th>
                                <th style="min-width: 110px;">Quantidade</th>
                                <th style="min-width: 150px;">Valor unitário (R$)</th>
                                <th style="min-width: 150px;">Total do item (R$)</th>
                                <th style="width: 60px;">Ação</th>
                            </tr>
                        </thead>

                        <tbody id="listaProdutos">
                            <!-- Linhas adicionadas pelo JavaScript -->
                        </tbody>

                    </table>

                </div>

                <div class="row justify-content-end mt-3">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-end">
                                <span class="text-muted">Valor total da entrada</span>

                                <h3 class="mb-0 text-success" id="valorTotalEntrada">
                                    R$ 0,00
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">

            <a href="index.php" class="btn btn-outline-secondary">
                Cancelar
            </a>

            <button type="submit" class="btn btn-success" id="btnSalvar">
                <i class="bi bi-check-circle"></i>
                Registrar entrada
            </button>

        </div>

    </form>
</div>

<!-- Modelo de linha de produto -->
<template id="modeloProduto">

    <tr class="linha-produto">

        <td>
            <select name="produto_id[]" class="form-select campo-produto" required>
                <option value="">Selecione um produto</option>

                <?php foreach ($produtos as $produto): ?>
                    <option value="<?= (int) $produto['id'] ?>">
                        <?= escapar($produto['codigo']) ?>
                        - <?= escapar($produto['nome']) ?>
                        (<?= escapar($produto['unidade']) ?>)
                    </option>
                <?php endforeach; ?>

            </select>
        </td>

        <td>
            <input type="text"
                   name="quantidade[]"
                   class="form-control campo-quantidade"
                   inputmode="decimal"
                   placeholder="0,000"
                   required>
        </td>

        <td>
            <input type="text"
                   name="valor_unitario[]"
                   class="form-control campo-valor"
                   inputmode="decimal"
                   placeholder="0,00"
                   required>
        </td>

        <td>
            <input type="text"
                   class="form-control campo-total"
                   value="R$ 0,00"
                   readonly>
        </td>

        <td class="text-center">
            <button type="button"
                    class="btn btn-outline-danger btn-sm btn-remover"
                    title="Remover produto">
                <i class="bi bi-trash"></i>
            </button>
        </td>

    </tr>

</template>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const listaProdutos = document.getElementById('listaProdutos');
    const modeloProduto = document.getElementById('modeloProduto');
    const btnAdicionar = document.getElementById('btnAdicionarProduto');
    const valorTotalEntrada = document.getElementById('valorTotalEntrada');
    const formEntrada = document.getElementById('formEntrada');

    function converterNumero(valor) {
        valor = String(valor || '').trim().replace(/\s/g, '');

        if (valor.includes(',')) {
            valor = valor.replace(/\./g, '').replace(',', '.');
        }

        const numero = parseFloat(valor);

        return Number.isFinite(numero) ? numero : 0;
    }

    function formatarMoeda(valor) {
        return valor.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    function calcularTotais() {

        let totalGeral = 0;

        document.querySelectorAll('.linha-produto').forEach(function (linha) {

            const quantidade = converterNumero(
                linha.querySelector('.campo-quantidade').value
            );

            const valorUnitario = converterNumero(
                linha.querySelector('.campo-valor').value
            );

            const totalItem = quantidade * valorUnitario;

            linha.querySelector('.campo-total').value =
                formatarMoeda(totalItem);

            totalGeral += totalItem;
        });

        valorTotalEntrada.textContent = formatarMoeda(totalGeral);
    }

function adicionarProduto(produtoId = '') {

    const fragmento = modeloProduto.content.cloneNode(true);

    listaProdutos.appendChild(fragmento);

    // Seleciona o produto informado, quando houver.
    const linha = listaProdutos.lastElementChild;

    if (produtoId) {
        linha.querySelector('.campo-produto').value = produtoId;
    }

    calcularTotais();
}

    btnAdicionar.addEventListener('click', adicionarProduto);

    listaProdutos.addEventListener('input', function (event) {

        if (
            event.target.classList.contains('campo-quantidade') ||
            event.target.classList.contains('campo-valor')
        ) {
            calcularTotais();
        }
    });

    listaProdutos.addEventListener('click', function (event) {

        const botao = event.target.closest('.btn-remover');

        if (botao) {
            botao.closest('.linha-produto').remove();
            calcularTotais();
        }
    });

    formEntrada.addEventListener('submit', function (event) {

        const linhas = document.querySelectorAll('.linha-produto');

        if (linhas.length === 0) {
            event.preventDefault();
            alert('Adicione pelo menos um produto à entrada.');
            return;
        }

        let valido = true;

        linhas.forEach(function (linha) {

            const produto = linha.querySelector('.campo-produto').value;

            const quantidade = converterNumero(
                linha.querySelector('.campo-quantidade').value
            );

            const valor = converterNumero(
                linha.querySelector('.campo-valor').value
            );

            if (!produto || quantidade <= 0 || valor < 0) {
                valido = false;
            }
        });

        if (!valido) {
            event.preventDefault();
            alert('Verifique os produtos, as quantidades e os valores informados.');
            return;
        }

        const botaoSalvar = document.getElementById('btnSalvar');

        botaoSalvar.disabled = true;
        botaoSalvar.innerHTML = 'Registrando...';
    });

// Produto recebido pela URL, se houver.
const produtoPreSelecionadoId = <?= (int) $produtoPreSelecionadoId ?>;

// Começa com uma linha de produto.
// Se veio da tela de estoque baixo, já seleciona o produto.
adicionarProduto(
    produtoPreSelecionadoId > 0
        ? String(produtoPreSelecionadoId)
        : ''
);

});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>