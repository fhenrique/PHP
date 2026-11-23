<?php

require_once 'includes/auth.php';
require_once 'config/conexao.php';

exigirEmpresaSelecionada();

$tituloPagina = 'Ajuda - Fiscal / Reforma Tributária';

require_once 'includes/header.php';

?>

<h1 class="page-title">
    <i class="bi bi-question-circle text-primary"></i>
    Ajuda - Fiscal / Reforma Tributária
</h1>

<p class="text-muted">
    Entenda como funciona a parte fiscal do EstoqueWeb, mesmo sem emitir nota fiscal.
</p>

<!-- ======================================
     MENU RÁPIDO
======================================= -->

<div class="card card-padrao mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="#produto" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-box-seam me-1"></i>
                Fiscal no Produto
            </a>
            <a href="#config" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-gear me-1"></i>
                Configuração Fiscal
            </a>
            <a href="#exemplos" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-lightbulb me-1"></i>
                Exemplos práticos
            </a>
            <a href="#duvidas" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-chat-dots me-1"></i>
                Dúvidas frequentes
            </a>
        </div>
    </div>
</div>

<!-- ======================================
     1. FISCAL NO PRODUTO
======================================= -->

<div class="card card-padrao mb-4" id="produto">
    <div class="card-header bg-white py-3">
        <strong>
            <i class="bi bi-box-seam me-2"></i>
            1. Fiscal - Reforma Tributária no Cadastro de Produto
        </strong>
        <div class="text-muted small fw-normal">
            Onde fica: Cadastros &gt; Produtos &gt; Novo Produto / Alterar Produto
        </div>
    </div>
    <div class="card-body">

        <p>
            É a parte do produto que define como ele será tributado na Reforma Tributária (IBS e CBS).
            Esses campos <strong>não mudam o estoque</strong>, só mudam como o imposto é calculado quando houver venda.
        </p>

        <h6 class="mt-4">Campos</h6>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 18%">Campo</th>
                        <th>O que é</th>
                        <th style="width: 18%">Exemplo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>NCM</strong></td>
                        <td>Código que classifica o produto na tabela do governo. É o "RG" do produto. Tem 8 dígitos.</td>
                        <td><code>61091000</code><br><small class="text-muted">Camiseta de algodão</small></td>
                    </tr>
                    <tr>
                        <td><strong>CST IBS/CBS</strong></td>
                        <td>Código de 3 dígitos que resume como o produto é tributado.</td>
                        <td><code>000</code> - Integral<br><code>010</code> - Com redução<br><code>400</code> - Isento</td>
                    </tr>
                    <tr>
                        <td><strong>cClassTrib</strong></td>
                        <td>Código de 6 dígitos que detalha a regra de tributação. É o campo mais importante da Reforma.</td>
                        <td><code>000001</code> - Integral<br><code>200001</code> - Redução 60%<br><code>400001</code> - Isento</td>
                    </tr>
                    <tr>
                        <td><strong>Alíquota CBS (%)</strong></td>
                        <td>Percentual da CBS, imposto federal.</td>
                        <td><code>11,30</code></td>
                    </tr>
                    <tr>
                        <td><strong>Alíquota IBS (%)</strong></td>
                        <td>Percentual do IBS, imposto estadual / municipal.</td>
                        <td><code>0,10</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info">
            <strong><i class="bi bi-info-circle me-1"></i> Regra mais importante - Alíquota em branco:</strong><br>
            Se você deixar <strong>Alíquota CBS</strong> ou <strong>Alíquota IBS</strong> em branco no produto,
            o sistema <strong>não considera como zero</strong>. Ele usa automaticamente o valor padrão que está em
            <strong>Configuração Fiscal</strong>.<br><br>
            <strong>Exemplo:</strong> Se na Configuração Fiscal a CBS padrão é <code>11,30</code> e você deixar o produto em branco,
            o sistema vai usar <code>11,30</code> para aquele produto.<br>
            Só preencha a alíquota no produto se aquele produto tiver uma alíquota diferente do padrão, o que é raro.
        </div>

        <div class="alert alert-warning">
            <strong><i class="bi bi-exclamation-triangle me-1"></i> CST e cClassTrib são do produto:</strong><br>
            Produtos diferentes podem ter códigos diferentes. Na mesma empresa, a camiseta pode ser <code>000001</code>
            e o arroz pode ser <code>200001</code>. Por isso esses campos ficam no produto.<br>
            Se você deixar em branco, o sistema usa o padrão da Configuração Fiscal apenas como sugestão.
            O ideal é preencher por produto para não tributar errado.
        </div>

        <p class="text-muted small mb-0">
            <strong>Na listagem:</strong> Quando a alíquota está em branco, aparece a palavra <span class="badge bg-light text-dark border">Padrão</span>.
            Isso significa: "esse produto está usando a alíquota da Configuração Fiscal".
        </p>

    </div>
</div>

<!-- ======================================
     2. CONFIGURAÇÃO FISCAL
======================================= -->

<div class="card card-padrao mb-4" id="config">
    <div class="card-header bg-white py-3">
        <strong>
            <i class="bi bi-gear me-2"></i>
            2. Módulo Configuração Fiscal
        </strong>
        <div class="text-muted small fw-normal">
            Onde fica: Configurações &gt; Configuração Fiscal
        </div>
    </div>
    <div class="card-body">

        <p>
            É onde você define os valores padrão de tributação da empresa.
            É como se fosse a "configuração mãe". Tudo que você não preencher no produto,
            o sistema vai buscar aqui.
        </p>
        <p>
            Só existe <strong>uma</strong> Configuração Fiscal por empresa. Ela vale para todos os produtos.
        </p>

        <h6 class="mt-4">Campos</h6>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%">Campo</th>
                        <th>O que é</th>
                        <th style="width: 18%">Exemplo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Alíquota CBS Padrão (%)</strong></td>
                        <td>CBS padrão da empresa. Será usada quando o produto estiver com a alíquota em branco.</td>
                        <td><code>11,30</code></td>
                    </tr>
                    <tr>
                        <td><strong>Alíquota IBS Padrão (%)</strong></td>
                        <td>IBS padrão da empresa. Será usada quando o produto estiver com a alíquota em branco.</td>
                        <td><code>0,10</code></td>
                    </tr>
                    <tr>
                        <td><strong>CST IBS/CBS Padrão</strong></td>
                        <td>CST que será sugerido quando você cadastrar um produto novo.</td>
                        <td><code>000</code></td>
                    </tr>
                    <tr>
                        <td><strong>cClassTrib Padrão</strong></td>
                        <td>cClassTrib que será sugerido quando você cadastrar um produto novo.</td>
                        <td><code>000001</code></td>
                    </tr>
                    <tr>
                        <td><strong>CFOP Padrão</strong></td>
                        <td>CFOP sugerido nas movimentações de saída. Não é do produto, é da operação.</td>
                        <td><code>5102</code><br><small class="text-muted">Venda dentro do estado</small></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h6 class="mt-4">Hierarquia de prioridade</h6>

        <div class="alert alert-light border">
            <strong>1º - O que está no PRODUTO</strong> <span class="text-muted">(se estiver preenchido, ele ganha)</span><br>
            <strong>2º - O que está na CONFIGURAÇÃO FISCAL</strong> <span class="text-muted">(se o produto estiver em branco)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Situação</th>
                        <th>O que o sistema usa?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Produto com CBS <code>5,00</code> / Config com CBS <code>11,30</code></td>
                        <td><strong>Usa <code>5,00</code> do produto</strong></td>
                    </tr>
                    <tr>
                        <td>Produto com CBS em branco / Config com CBS <code>11,30</code></td>
                        <td><strong>Usa <code>11,30</code> da config</strong></td>
                    </tr>
                    <tr>
                        <td>Produto com CBS em branco / Config com CBS em branco</td>
                        <td>Não calcula imposto / mostra aviso</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info">
            <strong><i class="bi bi-info-circle me-1"></i> CFOP Padrão:</strong><br>
            O CFOP não é do produto e nem da empresa, é da <strong>operação</strong>.
            Vender dentro do estado é <code>5102</code>, vender fora é <code>6102</code>.<br>
            O CFOP Padrão serve apenas para já vir preenchido como <code>5102</code> na tela de saída de estoque,
            para você não ter que digitar toda hora. Se a operação for diferente, você troca na hora da movimentação.
        </div>

        <p class="text-muted small mb-0">
            <strong>Quando alterar?</strong> Altere apenas quando o governo mudar a alíquota oficial, ou quando o contador orientar.
            Não altere por produto. Se um produto específico for diferente, altere só naquele produto.
        </p>

    </div>
</div>

<!-- ======================================
     3. EXEMPLOS PRÁTICOS
======================================= -->

<div class="card card-padrao mb-4" id="exemplos">
    <div class="card-header bg-white py-3">
        <strong>
            <i class="bi bi-lightbulb me-2"></i>
            3. Exemplos práticos
        </strong>
    </div>
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th>NCM</th>
                        <th>CST</th>
                        <th>cClassTrib</th>
                        <th>Alíquota</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Camiseta</td>
                        <td><code>61091000</code></td>
                        <td><code>000</code></td>
                        <td><code>000001</code></td>
                        <td>Em branco = usa Padrão</td>
                    </tr>
                    <tr>
                        <td>Arroz cesta básica</td>
                        <td><code>10063021</code></td>
                        <td><code>010</code></td>
                        <td><code>200001</code></td>
                        <td>Em branco = usa Padrão</td>
                    </tr>
                    <tr>
                        <td>Livro</td>
                        <td><code>49011000</code></td>
                        <td><code>400</code></td>
                        <td><code>400001</code></td>
                        <td>Em branco = usa Padrão</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-muted small mb-0">
            Repare que nos 3 exemplos a alíquota ficou em branco. Isso é o normal.
            A alíquota é a mesma, o que muda é o CST e o cClassTrib.
        </p>

    </div>
</div>

<!-- ======================================
     4. DÚVIDAS FREQUENTES
======================================= -->

<div class="card card-padrao mb-4" id="duvidas">
    <div class="card-header bg-white py-3">
        <strong>
            <i class="bi bi-chat-dots me-2"></i>
            4. Dúvidas frequentes
        </strong>
    </div>
    <div class="card-body">

        <p><strong>O EstoqueWeb emite nota fiscal?</strong><br>
        <span class="text-muted">Não, ainda não. Esses campos servem para classificar, organizar e preparar o sistema para o cálculo correto do imposto e para uma futura integração com emissor de NF-e.</span></p>

        <p><strong>Se eu deixar a alíquota em branco, o imposto fica zero?</strong><br>
        <span class="text-muted">Não. Em branco significa "usar o padrão da Configuração Fiscal". Zero seria se você digitar 0,00.</span></p>

        <p><strong>Preciso preencher CST e cClassTrib em todos os produtos?</strong><br>
        <span class="text-muted">O ideal é sim. Se deixar em branco, o sistema usa o padrão da config, mas pode tributar errado um produto que era isento ou com redução.</span></p>

        <p class="mb-0"><strong>CFOP é do produto?</strong><br>
        <span class="text-muted">Não. CFOP é da operação. O mesmo produto pode ser vendido com CFOP 5102 (dentro do estado) ou 6102 (fora do estado).</span></p>

    </div>
</div>

<div class="d-flex gap-2">
    <a href="produtos.php" class="btn btn-principal">
        <i class="bi bi-box-seam me-1"></i>
        Ir para Produtos
    </a>
    <a href="config_fiscal.php" class="btn btn-secondary">
        <i class="bi bi-gear me-1"></i>
        Ir para Configuração Fiscal
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>