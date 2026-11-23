
<?php
session_start();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/auth.php';

exigirEmpresaSelecionada();

$empresaId = (int) $_SESSION['empresa_id'];
$usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

if ($usuarioId <= 0) {
    header('Location: index.php');
    exit;
}

// --------------------------------------------------
// CSRF
// --------------------------------------------------

if (empty($_SESSION['csrf_saida_estoque'])) {
    $_SESSION['csrf_saida_estoque'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_saida_estoque'];

// --------------------------------------------------
// FUNÇÕES AUXILIARES
// --------------------------------------------------

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function converterDecimal($valor)
{
    $valor = trim((string) $valor);

    if ($valor === '') {
        return 0;
    }

    // Aceita tanto 10,500 quanto 10.500,000.
    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    return is_numeric($valor) ? (float) $valor : 0;
}

function validarDataSaida($data)
{
    $objeto = DateTime::createFromFormat('!Y-m-d', $data);

    return $objeto && $objeto->format('Y-m-d') === $data;
}

// --------------------------------------------------
// VARIÁVEIS
// --------------------------------------------------

$mensagem = '';
$tipoMensagem = '';

$dados = [
    'produto_id' => '',
    'quantidade' => '',
    'data_saida' => date('Y-m-d'),
    'documento' => '',
    'setor_destino' => '',
    'responsavel_recebimento' => '',
    'motivo' => '',
    'observacao' => ''
];

// --------------------------------------------------
// LISTA DE PRODUTOS DA EMPRESA
// --------------------------------------------------

$stmt = $pdo->prepare("
    SELECT
        id,
        codigo,
        nome,
        unidade,
        estoque_atual
    FROM produtos
    WHERE empresa_id = ?
      AND ativo = 1
    ORDER BY nome ASC
");

$stmt->execute([$empresaId]);

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// PRODUTO PRÉ-SELECIONADO PELA URL
// --------------------------------------------------

$produtoPreSelecionadoId = isset($_GET['produto_id'])
    ? (int) $_GET['produto_id']
    : 0;

if ($produtoPreSelecionadoId > 0) {
    $produtoEncontrado = false;

    foreach ($produtos as $produto) {
        if ((int) $produto['id'] === $produtoPreSelecionadoId) {
            $produtoEncontrado = true;
            break;
        }
    }

    if (!$produtoEncontrado) {
        $produtoPreSelecionadoId = 0;
    }
}

// --------------------------------------------------
// PROCESSAMENTO DA SAÍDA
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dados['produto_id'] = $_POST['produto_id'] ?? '';
    $dados['quantidade'] = $_POST['quantidade'] ?? '';
    $dados['data_saida'] = $_POST['data_saida'] ?? '';
    $dados['documento'] = trim($_POST['documento'] ?? '');
    $dados['setor_destino'] = trim($_POST['setor_destino'] ?? '');
    $dados['responsavel_recebimento'] = trim($_POST['responsavel_recebimento'] ?? '');
    $dados['motivo'] = trim($_POST['motivo'] ?? '');
    $dados['observacao'] = trim($_POST['observacao'] ?? '');

    try {

        // Validação CSRF
        $tokenRecebido = $_POST['csrf_token'] ?? '';

        if (
            !is_string($tokenRecebido) ||
            !hash_equals($csrfToken, $tokenRecebido)
        ) {
            throw new Exception('Sessão inválida. Atualize a página e tente novamente.');
        }

        $produtoId = filter_var(
            $dados['produto_id'],
            FILTER_VALIDATE_INT
        );

        $quantidade = converterDecimal($dados['quantidade']);

        // Validações
        if (!$produtoId || $produtoId <= 0) {
            throw new Exception('Selecione um produto válido.');
        }

        if (!is_finite($quantidade) || $quantidade <= 0) {
            throw new Exception('Informe uma quantidade válida para a saída.');
        }

        if ($quantidade > 999999999.999) {
            throw new Exception('A quantidade informada é muito elevada.');
        }

        if (!validarDataSaida($dados['data_saida'])) {
            throw new Exception('Informe uma data de saída válida.');
        }

        if ($dados['setor_destino'] === '') {
            throw new Exception('Informe o setor de destino.');
        }

        if (mb_strlen($dados['setor_destino']) > 150) {
            throw new Exception('O setor de destino deve ter no máximo 150 caracteres.');
        }

        if (mb_strlen($dados['responsavel_recebimento']) > 150) {
            throw new Exception('O responsável deve ter no máximo 150 caracteres.');
        }

        if (mb_strlen($dados['documento']) > 100) {
            throw new Exception('O documento deve ter no máximo 100 caracteres.');
        }

        if (mb_strlen($dados['motivo']) > 255) {
            throw new Exception('O motivo deve ter no máximo 255 caracteres.');
        }

        // --------------------------------------------------
        // TRANSAÇÃO
        // --------------------------------------------------

        $pdo->beginTransaction();

        // Bloqueia o produto durante a operação.
        // Também garante que ele pertence à empresa atual.
        $stmt = $pdo->prepare("
            SELECT
                id,
                nome,
                unidade,
                estoque_atual
            FROM produtos
            WHERE id = ?
              AND empresa_id = ?
              AND ativo = 1
            FOR UPDATE
        ");

        $stmt->execute([
            $produtoId,
            $empresaId
        ]);

        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            throw new Exception('Produto não encontrado ou não pertence à empresa selecionada.');
        }

        $estoqueAtual = (float) $produto['estoque_atual'];

        // Verifica se existe estoque suficiente.
        if ($quantidade > $estoqueAtual) {
            throw new Exception(
                'Estoque insuficiente. Produto: ' .
                $produto['nome'] .
                '. Estoque disponível: ' .
                number_format($estoqueAtual, 3, ',', '.') .
                ' ' .
                $produto['unidade']
            );
        }

        // Calcula o novo estoque.
        $novoEstoque = round($estoqueAtual - $quantidade, 3);

        if ($novoEstoque < 0) {
            throw new Exception('A saída não pode deixar o estoque negativo.');
        }

        // Atualiza o estoque do produto.
        $stmt = $pdo->prepare("
            UPDATE produtos
            SET estoque_atual = ?
            WHERE id = ?
              AND empresa_id = ?
              AND ativo = 1
              AND estoque_atual >= ?
        ");

        $stmt->execute([
            $novoEstoque,
            $produtoId,
            $empresaId,
            $quantidade
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new Exception('Não foi possível atualizar o estoque do produto.');
        }

        // Monta a data da movimentação.
        $dataMovimentacao = $dados['data_saida'] . ' ' . date('H:i:s');

        // Registra a movimentação de saída.
        $stmt = $pdo->prepare("
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
                setor_destino,
                responsavel_recebimento,
                motivo,
                observacao,
                data_movimentacao
            ) VALUES (
                ?, ?, ?, 'SAIDA', ?, NULL, NULL, NULL,
                ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $empresaId,
            $produtoId,
            $usuarioId,
            $quantidade,
            $dados['documento'] !== '' ? $dados['documento'] : null,
            $dados['setor_destino'],
            $dados['responsavel_recebimento'] !== ''
                ? $dados['responsavel_recebimento']
                : null,
            $dados['motivo'] !== '' ? $dados['motivo'] : null,
            $dados['observacao'] !== '' ? $dados['observacao'] : null,
            $dataMovimentacao
        ]);

        // Confirma a operação.
        $pdo->commit();

        // Renova o token CSRF.
        $_SESSION['csrf_saida_estoque'] = bin2hex(random_bytes(32));
        $csrfToken = $_SESSION['csrf_saida_estoque'];

        // Limpa os campos após o sucesso.
        $dados = [
            'produto_id' => '',
            'quantidade' => '',
            'data_saida' => date('Y-m-d'),
            'documento' => '',
            'setor_destino' => '',
            'responsavel_recebimento' => '',
            'motivo' => '',
            'observacao' => ''
        ];

        $mensagem = 'Saída de estoque registrada com sucesso!';
        $tipoMensagem = 'success';

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $mensagem = $e->getMessage();
        $tipoMensagem = 'danger';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Erro ao registrar saída de estoque: ' . $e->getMessage());

        $mensagem = 'Ocorreu um erro inesperado ao registrar a saída. Tente novamente.';
        $tipoMensagem = 'danger';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-box-arrow-up-right"></i>
                Saída de Estoque
            </h4>

            <p class="text-muted mb-0">
                Registre a retirada de produtos do estoque.
            </p>
        </div>

        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>
    </div>

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-<?= escapar($tipoMensagem) ?> alert-dismissible fade show" role="alert">
            <?= escapar($mensagem) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="saida_estoque.php">

        <input type="hidden"
               name="csrf_token"
               value="<?= escapar($csrfToken) ?>">

        <!-- DADOS DA SAÍDA -->

        <div class="card shadow-sm mb-4">

            <div class="card-header">
                <strong>
                    <i class="bi bi-file-earmark-text"></i>
                    Dados da saída
                </strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">
                            Data da saída *
                        </label>

                        <input type="date"
                               name="data_saida"
                               class="form-control"
                               value="<?= escapar($dados['data_saida']) ?>"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Documento
                        </label>

                        <input type="text"
                               name="documento"
                               class="form-control"
                               maxlength="100"
                               placeholder="Ex.: Requisição nº 001"
                               value="<?= escapar($dados['documento']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Setor de destino *
                        </label>

                        <input type="text"
                               name="setor_destino"
                               class="form-control"
                               maxlength="150"
                               placeholder="Informe o setor"
                               value="<?= escapar($dados['setor_destino']) ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Responsável pelo recebimento
                        </label>

                        <input type="text"
                               name="responsavel_recebimento"
                               class="form-control"
                               maxlength="150"
                               placeholder="Nome do responsável"
                               value="<?= escapar($dados['responsavel_recebimento']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Motivo da saída
                        </label>

                        <input type="text"
                               name="motivo"
                               class="form-control"
                               maxlength="255"
                               placeholder="Ex.: Consumo, transferência, manutenção..."
                               value="<?= escapar($dados['motivo']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            Observações
                        </label>

                        <textarea name="observacao"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Informações adicionais sobre a saída"><?= escapar($dados['observacao']) ?></textarea>
                    </div>

                </div>

            </div>
        </div>

        <!-- PRODUTO DA SAÍDA -->

        <div class="card shadow-sm mb-4">

            <div class="card-header">
                <strong>
                    <i class="bi bi-box-seam"></i>
                    Produto da saída
                </strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-8">
                        <label class="form-label">
                            Produto *
                        </label>

                        <select name="produto_id"
                                id="produto_id"
                                class="form-select"
                                required>

                            <option value="">Selecione um produto</option>

                            <?php foreach ($produtos as $produto): ?>

                                <option
                                    value="<?= (int) $produto['id'] ?>"
                                    data-estoque="<?= escapar($produto['estoque_atual']) ?>"
                                    data-unidade="<?= escapar($produto['unidade']) ?>"
                                    <?= (string) $dados['produto_id'] === (string) $produto['id'] ? 'selected' : '' ?>
                                >
                                    <?= escapar($produto['codigo']) ?>
                                    -
                                    <?= escapar($produto['nome']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Quantidade *
                        </label>

                        <input type="number"
                               name="quantidade"
                               id="quantidade"
                               class="form-control"
                               min="0.001"
                               max="999999999.999"
                               step="0.001"
                               value="<?= escapar($dados['quantidade']) ?>"
                               required>
                    </div>

                </div>

                <div class="alert alert-info mt-3 mb-0" id="informacaoEstoque">
                    <i class="bi bi-info-circle"></i>
                    Selecione um produto para consultar o estoque disponível.
                </div>

            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">

            <a href="index.php" class="btn btn-outline-secondary">
                Cancelar
            </a>

            <button type="submit" class="btn btn-danger">
                <i class="bi bi-box-arrow-up-right"></i>
                Registrar saída
            </button>

        </div>

    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const produtoSelect = document.getElementById('produto_id');
    const quantidadeInput = document.getElementById('quantidade');
    const informacaoEstoque = document.getElementById('informacaoEstoque');

    function atualizarEstoqueDisponivel() {

        const opcao = produtoSelect.options[produtoSelect.selectedIndex];

        if (!produtoSelect.value || !opcao) {

            informacaoEstoque.className = 'alert alert-info mt-3 mb-0';

            informacaoEstoque.innerHTML =
                '<i class="bi bi-info-circle"></i> ' +
                'Selecione um produto para consultar o estoque disponível.';

            quantidadeInput.removeAttribute('max');

            return;
        }

        const estoque = parseFloat(opcao.dataset.estoque || '0');
        const unidade = opcao.dataset.unidade || '';

        quantidadeInput.max = estoque;

        informacaoEstoque.className = 'alert alert-info mt-3 mb-0';

        informacaoEstoque.innerHTML =
            '<i class="bi bi-box-seam"></i> ' +
            '<strong>Estoque disponível:</strong> ' +
            estoque.toLocaleString('pt-BR', {
                minimumFractionDigits: 3,
                maximumFractionDigits: 3
            }) +
            ' ' + unidade;

        validarQuantidade();
    }

    function validarQuantidade() {

        const opcao = produtoSelect.options[produtoSelect.selectedIndex];

        if (!produtoSelect.value || !opcao || quantidadeInput.value === '') {
            return;
        }

        const estoque = parseFloat(opcao.dataset.estoque || '0');
        const quantidade = parseFloat(quantidadeInput.value || '0');

        if (quantidade > estoque) {

            informacaoEstoque.className = 'alert alert-warning mt-3 mb-0';

            informacaoEstoque.innerHTML =
                '<i class="bi bi-exclamation-triangle"></i> ' +
                '<strong>Atenção:</strong> A quantidade informada é maior ' +
                'que o estoque disponível.';

        } else {

            atualizarMensagemDisponibilidade(estoque, opcao.dataset.unidade || '');
        }
    }

    function atualizarMensagemDisponibilidade(estoque, unidade) {

        informacaoEstoque.className = 'alert alert-info mt-3 mb-0';

        informacaoEstoque.innerHTML =
            '<i class="bi bi-box-seam"></i> ' +
            '<strong>Estoque disponível:</strong> ' +
            estoque.toLocaleString('pt-BR', {
                minimumFractionDigits: 3,
                maximumFractionDigits: 3
            }) +
            ' ' + unidade;
    }

    produtoSelect.addEventListener('change', atualizarEstoqueDisponivel);

    quantidadeInput.addEventListener('input', validarQuantidade);

    atualizarEstoqueDisponivel();

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
