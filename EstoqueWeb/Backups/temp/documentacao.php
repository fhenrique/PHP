
<?php

require_once 'includes/auth.php';
require_once 'config/conexao.php';

exigirEmpresaSelecionada();

$tituloPagina = 'Documentação do Sistema';

require_once 'includes/header.php';

?>

<style>
    .doc-intro {
        background: linear-gradient(135deg, #17365d, #2874a6);
        color: #fff;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
    }

    .doc-intro h2 {
        font-weight: 700;
        margin-bottom: 10px;
    }

    .doc-intro p {
        margin-bottom: 0;
        opacity: .92;
    }

    .doc-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .doc-card .accordion-button {
        font-weight: 600;
        color: #17365d;
        padding: 16px 18px;
    }

    .doc-card .accordion-button:not(.collapsed) {
        background-color: #edf4fa;
        color: #17365d;
        box-shadow: none;
    }

    .doc-card .accordion-body {
        padding: 22px;
        line-height: 1.7;
    }

    .doc-card h6 {
        color: #1f4e79;
        font-weight: 700;
        margin-top: 18px;
    }

    .doc-card li {
        margin-bottom: 5px;
    }

    .doc-alert {
        background: #eef6fc;
        border-left: 4px solid #2874a6;
        padding: 13px 16px;
        border-radius: 5px;
        margin: 15px 0;
    }

    .doc-warning {
        background: #fff8e6;
        border-left: 4px solid #e0a800;
        padding: 13px 16px;
        border-radius: 5px;
        margin: 15px 0;
    }

    .doc-table th {
        background: #edf4fa;
        color: #17365d;
    }

    .doc-search {
        margin-bottom: 20px;
    }

    .doc-search input {
        border-radius: 8px;
        padding: 12px;
    }

    .doc-section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #edf4fa;
        color: #2874a6;
        margin-right: 10px;
    }

    @media print {
        .sidebar,
        .topbar,
        .doc-search,
        .doc-buttons,
        .accordion-button::after {
            display: none !important;
        }

        .accordion-collapse {
            display: block !important;
            height: auto !important;
        }

        .doc-card {
            break-inside: avoid;
        }

        .main-content {
            margin: 0 !important;
            padding: 0 !important;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

    <h1 class="page-title mb-0">
        <i class="bi bi-book text-primary"></i>
        Documentação do Sistema
    </h1>

    <div class="doc-buttons">
        <button type="button"
                class="btn btn-outline-primary btn-sm"
                onclick="window.print()">
            <i class="bi bi-printer me-1"></i>
            Imprimir documentação
        </button>
    </div>

</div>

<div class="doc-intro">

    <h2>
        <i class="bi bi-box-seam me-2"></i>
        Bem-vindo ao EstoqueWeb
    </h2>

    <p>
        Consulte aqui as orientações de utilização dos módulos,
        funcionalidades, regras operacionais e configurações do sistema.
    </p>

</div>

<div class="doc-search">
    <input type="text"
           id="pesquisarDocumentacao"
           class="form-control"
           placeholder="Pesquisar na documentação...">
</div>

<div class="d-flex flex-wrap gap-2 mb-3 doc-buttons">

    <button type="button" class="btn btn-outline-secondary btn-sm"
            onclick="abrirTodos()">
        <i class="bi bi-arrows-expand me-1"></i>
        Expandir todos
    </button>

    <button type="button" class="btn btn-outline-secondary btn-sm"
            onclick="fecharTodos()">
        <i class="bi bi-arrows-collapse me-1"></i>
        Recolher todos
    </button>

</div>

<div class="accordion" id="documentacaoAccordion">

    <!-- 1. APRESENTAÇÃO -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc1">
                <span class="doc-section-icon">
                    <i class="bi bi-info-circle"></i>
                </span>
                1. Apresentação do EstoqueWeb
            </button>
        </h2>

        <div id="doc1" class="accordion-collapse collapse show"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O EstoqueWeb é um sistema de controle de estoque e
                    almoxarifado desenvolvido para administrar produtos,
                    fornecedores, categorias, usuários e movimentações.
                </p>

            </div>
        </div>
    </div>

    <!-- 2. ACESSO E NAVEGAÇÃO -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc2">

                <span class="doc-section-icon">
                    <i class="bi bi-door-open"></i>
                </span>

                2. Acesso e navegação

            </button>
        </h2>

        <div id="doc2" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O acesso ao sistema é realizado por meio de e-mail
                    e senha cadastrados.
                </p>

                <h6>Menu principal</h6>

                <ul>
                    <li><strong>Painel inicial:</strong> apresenta um resumo da situação do estoque.</li>
                    <li><strong>Documentação do Sistema:</strong> apresenta este guia de utilização.</li>
                </ul>

                <h6>Cadastros</h6>

                <ul>
                    <li>Categorias;</li>
                    <li>Produtos;</li>
                    <li>Fornecedores;</li>
                    <li>Usuários.</li>
                </ul>

                <h6>Estoque</h6>

                <ul>
                    <li>Entrada de estoque;</li>
                    <li>Saída de estoque;</li>
                    <li>Movimentações.</li>
                </ul>

                <h6>Administração</h6>

                <ul>
                    <li>Gestão do Sistema;</li>
                    <li>Configuração Fiscal.</li>
                </ul>

                <p>
                    O cabeçalho apresenta a data, o horário, o usuário
                    conectado e a empresa ativa.
                </p>


            </div>
        </div>
    </div>

    <!-- 3. PERFIS -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc3">

                <span class="doc-section-icon">
                    <i class="bi bi-people"></i>
                </span>

                3. Perfis e permissões

            </button>
        </h2>

        <div id="doc3" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <div class="table-responsive">

                    <table class="table table-bordered doc-table">

                        <thead>
                            <tr>
                                <th>Perfil</th>
                                <th>Permissões</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td><strong>Operador (2)</strong></td>
                                <td>
                                    Acessa o painel, realiza entradas e
                                    saídas, consulta movimentações e
                                    informações de estoque.
                                </td>
                            </tr>

                            <tr>
                                <td><strong>Administrador (1)</strong></td>
                                <td>
                                    Possui as funcionalidades do operador
                                    e administra cadastros e usuários
                                    da empresa. Também acessa Gestão do
                                    Sistema e Configuração Fiscal.
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

                <div class="doc-alert">
                    <strong>Documentação do Sistema:</strong>
                    este módulo está disponível para todos os perfis.
                </div>

            </div>
        </div>
    </div>

    <!-- 4. PAINEL -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc4">

                <span class="doc-section-icon">
                    <i class="bi bi-speedometer2"></i>
                </span>

                4. Painel inicial

            </button>
        </h2>

        <div id="doc4" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O painel inicial apresenta um resumo das informações
                    da empresa.
                </p>

                <ul>
                    <li>Quantidade de produtos ativos;</li>
                    <li>Produtos com estoque baixo;</li>
                    <li>Total de movimentações;</li>
                    <li>Resumo de entradas e saídas;</li>
                    <li>Atalhos para operações frequentes.</li>
                </ul>

            </div>
        </div>
    </div>

    <!-- 6. CATEGORIAS -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc6">

                <span class="doc-section-icon">
                    <i class="bi bi-tags"></i>
                </span>

                6. Categorias

            </button>
        </h2>

        <div id="doc6" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    As categorias permitem organizar os produtos em
                    grupos, facilitando pesquisas e relatórios.
                </p>

                <h6>Procedimentos</h6>

                <ol>
                    <li>Acesse Cadastros → Categorias.</li>
                    <li>Informe o nome da categoria.</li>
                    <li>Informe uma descrição, se necessário.</li>
                    <li>Salve o cadastro.</li>
                </ol>

                <ul>
                    <li>O nome deve ser único dentro da empresa.</li>
                    <li>Uma categoria inativa não deve ser utilizada em novos produtos.</li>
                    <li>Inativar uma categoria não apaga os produtos associados a ela.</li>
                </ul>

            </div>
        </div>
    </div>

    <!-- 7. PRODUTOS -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc7">

                <span class="doc-section-icon">
                    <i class="bi bi-box-seam"></i>
                </span>

                7. Produtos

            </button>
        </h2>

        <div id="doc7" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O cadastro de produtos reúne as informações de
                    identificação, classificação e controle de estoque.
                </p>

                <h6>Procedimentos</h6>

                <ol>
                    <li>Acesse Cadastros → Produtos.</li>
                    <li>Informe o código do produto.</li>
                    <li>Informe o nome e selecione uma categoria ativa.</li>
                    <li>Informe a unidade de medida.</li>
                    <li>Preencha custos, preços e estoque mínimo, quando aplicável.</li>
                    <li>Informe a descrição, se necessário.</li>
                    <li>Salve o cadastro.</li>
                </ol>

                <div class="doc-warning">
                    O estoque inicial pode ser informado na criação
                    do produto. Depois disso, o saldo deve ser atualizado
                    por meio das operações de entrada e saída.
                </div>

                <h6>Regras importantes</h6>

                <ul>
                    <li>O código do produto deve ser único dentro da empresa.</li>
                    <li>Produtos inativos não devem ser utilizados em novas movimentações.</li>
                    <li>Produtos com estoque baixo recebem destaque na listagem.</li>
                </ul>

                <h6>Informações fiscais do produto</h6>

                <div class="table-responsive">

                    <table class="table table-bordered doc-table">

                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td><strong>NCM</strong></td>
                                <td>
                                    Código de oito dígitos que classifica
                                    o produto na tabela fiscal.
                                </td>
                            </tr>

                            <tr>
                                <td><strong>CST IBS/CBS</strong></td>
                                <td>
                                    Código de três dígitos que identifica
                                    o tratamento tributário.
                                </td>
                            </tr>

                            <tr>
                                <td><strong>cClassTrib</strong></td>
                                <td>
                                    Código de seis dígitos que detalha
                                    a classificação tributária.
                                </td>
                            </tr>

                            <tr>
                                <td><strong>Alíquota CBS (%)</strong></td>
                                <td>Percentual da CBS.</td>
                            </tr>

                            <tr>
                                <td><strong>Alíquota IBS (%)</strong></td>
                                <td>Percentual do IBS.</td>
                            </tr>

                        </tbody>

                    </table>

                </div>

                <div class="doc-alert">
                    Quando a alíquota CBS ou IBS estiver em branco no
                    produto, a ajuda fiscal informa que o sistema utiliza
                    o valor padrão da Configuração Fiscal.
                </div>

                <div class="doc-warning">
                    Os códigos CST e cClassTrib devem ser conferidos
                    individualmente para cada produto. Consulte a
                    contabilidade antes de preencher essas informações.
                </div>

            </div>
        </div>
    </div>

    <!-- 8. FORNECEDORES -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc8">

                <span class="doc-section-icon">
                    <i class="bi bi-truck"></i>
                </span>

                8. Fornecedores

            </button>
        </h2>

        <div id="doc8" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O módulo permite cadastrar e administrar os
                    fornecedores utilizados nas entradas de estoque.
                </p>

                <h6>Procedimentos</h6>

                <ol>
                    <li>Acesse Cadastros → Fornecedores.</li>
                    <li>Selecione pessoa física ou jurídica.</li>
                    <li>Informe nome ou razão social.</li>
                    <li>Informe CPF ou CNPJ.</li>
                    <li>Preencha telefone e demais dados solicitados.</li>
                    <li>Para pessoa jurídica, informe também o nome fantasia.</li>
                    <li>Salve o cadastro.</li>
                </ol>

                <ul>
                    <li>O CPF/CNPJ deve ser válido.</li>
                    <li>O documento deve ser único dentro da empresa.</li>
                    <li>Fornecedores inativos não ficam disponíveis para novas entradas.</li>
                </ul>

            </div>
        </div>
    </div>

    <!-- 9. USUÁRIOS -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc9">

                <span class="doc-section-icon">
                    <i class="bi bi-person-gear"></i>
                </span>

                9. Usuários

            </button>
        </h2>

        <div id="doc9" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O módulo permite gerenciar os usuários vinculados
                    à empresa.
                </p>

                <h6>Procedimentos</h6>

                <ol>
                    <li>Acesse Cadastros → Usuários.</li>
                    <li>Informe nome e e-mail.</li>
                    <li>Selecione o perfil permitido.</li>
                    <li>Informe a senha inicial.</li>
                    <li>Salve o cadastro.</li>
                </ol>

                <h6>Regras importantes</h6>

                <ul>
                    <li>O e-mail deve ser único no sistema.</li>
                    <li>Na edição, deixar a senha em branco mantém a senha atual.</li>
                    <li>Não é permitido desativar a própria conta.</li>
                    <li>Não é permitido alterar o próprio perfil.</li>
                    <li>Existe proteção para impedir a remoção do último administrador ativo da empresa.</li>
                </ul>

            </div>
        </div>
    </div>

    <!-- 10. ENTRADA -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc10">

                <span class="doc-section-icon">
                    <i class="bi bi-box-arrow-in-down"></i>
                </span>

                10. Entrada de estoque

            </button>
        </h2>

        <div id="doc10" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    A entrada de estoque registra o recebimento de
                    mercadorias e aumenta o saldo dos produtos.
                </p>

                <h6>Procedimentos</h6>

                <ol>
                    <li>Acesse Estoque → Entrada de estoque.</li>
                    <li>Informe a data da entrada.</li>
                    <li>Selecione o fornecedor ativo.</li>
                    <li>Informe o número do documento, se houver.</li>
                    <li>Adicione os produtos recebidos.</li>
                    <li>Informe quantidade e custo unitário.</li>
                    <li>Confira os valores calculados.</li>
                    <li>Informe observações, se necessário.</li>
                    <li>Confirme a operação.</li>
                </ol>

                <h6>Regras importantes</h6>

                <ul>
                    <li>É possível adicionar vários produtos em uma mesma entrada.</li>
                    <li>O sistema calcula os totais automaticamente.</li>
                    <li>Quando o mesmo produto é lançado mais de uma vez, as quantidades são somadas, conforme a regra documentada.</li>
                    <li>A operação deve ser gravada integralmente.</li>
                    <li>O estoque é atualizado após a confirmação.</li>
                </ul>

                <div class="doc-warning">
                    O campo CFOP foi tratado na configuração do banco
                    de dados, mas a presença e o preenchimento desse
                    campo na tela de entrada dependem da implementação
                    atual de entrada_estoque.php.
                </div>

            </div>
        </div>
    </div>

    <!-- 11. SAÍDA -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc11">

                <span class="doc-section-icon">
                    <i class="bi bi-box-arrow-up"></i>
                </span>

                11. Saída de estoque

            </button>
        </h2>

        <div id="doc11" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    A saída de estoque registra a retirada de produtos
                    e reduz o saldo disponível.
                </p>

                <h6>Procedimentos</h6>

                <ol>
                    <li>Acesse Estoque → Saída de estoque.</li>
                    <li>Informe a data.</li>
                    <li>Selecione o produto ativo.</li>
                    <li>Informe a quantidade retirada.</li>
                    <li>Informe o setor de destino.</li>
                    <li>Preencha recebedor, motivo, documento e observações, quando aplicável.</li>
                    <li>Confira os dados e confirme a operação.</li>
                </ol>

                <h6>Regras importantes</h6>

                <ul>
                    <li>O sistema não deve permitir saída superior ao saldo disponível.</li>
                    <li>A operação reduz o estoque.</li>
                    <li>É registrada uma movimentação do tipo SAÍDA.</li>
                </ul>

                <div class="doc-alert">
                    A ajuda fiscal descreve o CFOP padrão como uma
                    sugestão na tela de saída. A implementação deve
                    ser conferida na versão atual de saida_estoque.php.
                </div>

            </div>
        </div>
    </div>

    <!-- 12. MOVIMENTAÇÕES -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc12">

                <span class="doc-section-icon">
                    <i class="bi bi-clock-history"></i>
                </span>

                12. Movimentações

            </button>
        </h2>

        <div id="doc12" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O módulo apresenta o histórico de entradas e saídas
                    realizadas na empresa.
                </p>

                <h6>Funcionalidades</h6>

                <ul>
                    <li>Filtro por tipo de movimentação;</li>
                    <li>Filtro por produto;</li>
                    <li>Filtro por usuário;</li>
                    <li>Pesquisa por texto;</li>
                    <li>Filtro por período;</li>
                    <li>Consulta de detalhes;</li>
                    <li>Resumo conforme os filtros aplicados.</li>
                </ul>

                <p>
                    Possui exportação de Excel(.xls) e CSV separado por ponto e vírgula.
                </p>

            </div>
        </div>
    </div>

    <!-- 13. RELATÓRIO -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc13">

                <span class="doc-section-icon">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                </span>

                13. Relatório de estoque

            </button>
        </h2>

        <div id="doc13" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O relatório apresenta os saldos e valores dos
                    produtos cadastrados.
                </p>

                <h6>Filtros e informações</h6>

                <ul>
                    <li>Filtro por produto;</li>
                    <li>Filtro por categoria;</li>
                    <li>Filtro por situação do estoque;</li>
                    <li>Ordenação dos resultados;</li>
                    <li>Estoque atual;</li>
                    <li>Estoque mínimo;</li>
                    <li>Preços e valores totais.</li>
                </ul>

                <div class="doc-alert">
                    O valor total do estoque descrito na documentação
                    anterior é calculado multiplicando o estoque atual
                    pelo custo do produto.
                </div>

                <p>
                    O resultado filtrado pode ser exportado, conforme
                    as opções disponíveis no módulo.
                </p>

            </div>
        </div>
    </div>

    <!-- 14. ESTOQUE BAIXO -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc14">

                <span class="doc-section-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </span>

                14. Estoque baixo

            </button>
        </h2>

        <div id="doc14" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    A visualização de estoque baixo identifica produtos
                    ativos cujo saldo está igual ou abaixo do estoque
                    mínimo cadastrado.
                </p>

                <h6>Como utilizar</h6>

                <ol>
                    <li>Acesse a visualização pelo painel ou atalho disponível.</li>
                    <li>Analise os produtos apresentados.</li>
                    <li>Utilize o atalho de edição para ajustar os dados do produto.</li>
                    <li>Utilize o atalho de entrada para registrar o recebimento de mercadorias.</li>
                </ol>

            </div>
        </div>
    </div>

    <!-- 15. GESTÃO DO SISTEMA -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc15">

                <span class="doc-section-icon">
                    <i class="bi bi-gear-wide-connected"></i>
                </span>

                15. Gestão do Sistema

            </button>
        </h2>

        <div id="doc15" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    A opção <strong>Gestão do Sistema</strong> está disponível
                    no grupo Administração para usuários com perfil
                    <strong>Administrador</strong>. O módulo reúne ferramentas
                    para gerar backups e restaurar os dados operacionais da
                    empresa selecionada.
                </p>

                <h6>15.1. Backup da estrutura do banco de dados</h6>

                <p>
                    Gera um arquivo SQL contendo a estrutura das tabelas do
                    banco de dados, incluindo definições de tabelas, chaves,
                    índices e configurações de auto incremento, conforme
                    implementado no módulo.
                </p>

                <div class="doc-warning">
                    Esse arquivo contém a estrutura, não os registros
                    operacionais da empresa. O script de estrutura pode conter
                    comandos de remoção e recriação de tabelas. Portanto, não
                    deve ser importado em um banco de produção sem análise
                    prévia e sem um backup adequado dos dados.
                </div>

                <h6>15.2. Backup dos dados da empresa</h6>

                <p>
                    Gera um arquivo SQL com os dados operacionais vinculados
                    à empresa.
                </p>

                <p>O backup contempla as seguintes tabelas:</p>

                <div class="table-responsive">
                    <table class="table table-bordered doc-table">
                        <thead>
                            <tr>
                                <th>Tabela</th>
                                <th>Conteúdo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>categorias</strong></td>
                                <td>Categorias cadastradas para a empresa.</td>
                            </tr>
                            <tr>
                                <td><strong>fornecedores</strong></td>
                                <td>Fornecedores cadastrados para a empresa.</td>
                            </tr>
                            <tr>
                                <td><strong>produtos</strong></td>
                                <td>Produtos e respectivos dados de estoque.</td>
                            </tr>
                            <tr>
                                <td><strong>usuarios</strong></td>
                                <td>Usuários vinculados à empresa.</td>
                            </tr>
                            <tr>
                                <td><strong>entradas_estoque</strong></td>
                                <td>Registros das entradas de estoque.</td>
                            </tr>
                            <tr>
                                <td><strong>itens_entrada</strong></td>
                                <td>Itens e quantidades vinculados às entradas.</td>
                            </tr>
                            <tr>
                                <td><strong>movimentacoes</strong></td>
                                <td>Histórico de movimentações da empresa.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p>
                    As tabelas <strong>empresas</strong> e
                    <strong>perfis</strong> não fazem parte desse backup de
                    dados operacionais. O arquivo identifica a empresa de
                    origem e registra informações da geração do backup.
                </p>

                <h6>15.3. Restauração dos dados da empresa</h6>

                <p>
                    Permite restaurar os dados operacionais de um arquivo SQL
                    gerado pelo recurso de backup de dados do EstoqueWeb.
                </p>

                <ol>
                    <li>
                        <strong>Selecionar o arquivo:</strong> escolha o
                        arquivo de backup com extensão SQL. O módulo aplica
                        validações de envio e limite de tamanho de arquivo.
                    </li>
                    <li>
                        <strong>Conferir a prévia:</strong> o sistema apresenta
                        informações de identificação do backup e uma contagem
                        dos registros encontrados nas tabelas contempladas.
                    </li>
                    <li>
                        <strong>Verificar a empresa:</strong> confira o nome e
                        o identificador da empresa registrados no arquivo. O
                        backup deve corresponder à empresa atualmente
                        selecionada.
                    </li>
                    <li>
                        <strong>Confirmar a operação:</strong> leia o aviso de
                        substituição dos dados e confirme somente se deseja
                        prosseguir.
                    </li>
                    <li>
                        <strong>Aguardar a conclusão:</strong> acompanhe o
                        processamento e aguarde a mensagem de resultado.
                    </li>
                </ol>

                <div class="doc-warning">
                    <strong>Atenção:</strong> a restauração não mescla os
                    registros do arquivo com os dados atuais. Ela substitui
                    os dados operacionais da empresa nas tabelas contempladas
                    pelo backup. Registros criados depois da geração do arquivo
                    podem ser perdidos.
                </div>

                <h6>15.4. Dados afetados pela restauração</h6>

                <p>
                    A substituição abrange as sete tabelas relacionadas no
                    tópico 15.2: categorias, fornecedores, produtos, usuários,
                    entradas de estoque, itens de entrada e movimentações.
                    As tabelas <strong>empresas</strong> e
                    <strong>perfis</strong> não são substituídas por essa
                    operação.
                </p>

                <div class="doc-alert">
                    Exemplo: se a empresa possui atualmente 100 produtos e o
                    backup selecionado contém 80 produtos, após uma restauração
                    concluída serão recuperados os 80 produtos do arquivo; os
                    registros atuais não serão somados aos registros do backup.
                </div>

                <h6>15.5. Validações e proteções</h6>

                <ul>
                    <li>O acesso à funcionalidade é restrito ao perfil autorizado.</li>
                    <li>As requisições de alteração utilizam validação de token CSRF.</li>
                    <li>O arquivo enviado passa por verificações de extensão, envio, tamanho e formato esperado pelo sistema.</li>
                    <li>O sistema confere a identificação da empresa contida no backup antes de permitir a restauração.</li>
                    <li>São verificadas tabelas, colunas e referências necessárias à importação.</li>
                    <li>Há verificações para evitar conflitos de identificadores pertencentes a outras empresas.</li>
                    <li>A operação de restauração utiliza transação no banco de dados; em caso de falha tratada durante o processamento, é executado o cancelamento da transação (ROLLBACK).</li>
                </ul>

                <div class="doc-warning">
                    A prévia exibida antes da confirmação é uma leitura inicial
                    do arquivo. A contagem apresentada não substitui todas as
                    validações realizadas pelo servidor durante a restauração.
                    O módulo espera o formato de backup produzido pelo próprio
                    EstoqueWeb; não se deve presumir compatibilidade com
                    qualquer arquivo SQL externo.
                </div>

                <h6>15.6. Recomendações de segurança</h6>

                <ul>
                    <li>Gere backups periodicamente, principalmente antes de operações importantes.</li>
                    <li>Armazene cópias em local seguro e, preferencialmente, fora do servidor do sistema.</li>
                    <li>Antes de restaurar, confira o nome e o identificador da empresa.</li>
                    <li>Antes de substituir os dados atuais, gere um backup atualizado da empresa.</li>
                    <li>Não interrompa o processamento da restauração.</li>
                    <li>Guarde os arquivos de backup com identificação da empresa e da data de geração.</li>
                </ul>

                <div class="doc-alert">
                    <strong>Diferença entre os backups:</strong> o backup da
                    estrutura preserva definições do banco de dados; o backup
                    dos dados da empresa preserva os registros operacionais
                    daquela empresa. Um não substitui o outro.
                </div>

            </div>
        </div>
    </div>

    <!-- 16. CONFIGURAÇÃO FISCAL -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc16">

                <span class="doc-section-icon">
                    <i class="bi bi-receipt"></i>
                </span>

                16. Configuração Fiscal

            </button>
        </h2>

        <div id="doc16" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    O módulo permite configurar os valores fiscais
                    padrão da empresa, relacionados à Reforma Tributária.
                </p>

                <p>
                    Acesse Administração → Configuração Fiscal.
                </p>

                <p>
                    O acesso é permitido ao Administrador.
                </p>


                <h6>Configuração inicial automática</h6>

                <p>
                    Quando ainda não existe uma configuração fiscal
                    para a empresa, o sistema cria automaticamente
                    os seguintes valores iniciais:
                </p>

                <div class="table-responsive">

                    <table class="table table-bordered doc-table">

                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Valor inicial</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Ano de Vigência</td>
                                <td>2027</td>
                            </tr>
                            <tr>
                                <td>Alíquota CBS</td>
                                <td>11,30%</td>
                            </tr>
                            <tr>
                                <td>Alíquota IBS</td>
                                <td>0,10%</td>
                            </tr>
                            <tr>
                                <td>CFOP Padrão</td>
                                <td>5102</td>
                            </tr>
                            <tr>
                                <td>CST IBS/CBS</td>
                                <td>000</td>
                            </tr>
                            <tr>
                                <td>cClassTrib</td>
                                <td>000001</td>
                            </tr>
                        </tbody>

                    </table>

                </div>

                <div class="doc-warning">
                    Os valores iniciais são parâmetros cadastrados
                    automaticamente pelo sistema. Antes de utilizá-los
                    em operações fiscais, confirme os valores corretos
                    com a contabilidade.
                </div>

                <h6>Descrição dos campos</h6>

                <ul>
                    <li><strong>Ano de Vigência:</strong> ano ao qual a configuração se aplica. É obrigatório.</li>
                    <li><strong>Alíquota CBS Padrão:</strong> percentual padrão da CBS.</li>
                    <li><strong>Alíquota IBS Padrão:</strong> percentual padrão do IBS.</li>
                    <li><strong>CFOP Padrão:</strong> código descrito na ajuda como sugestão para a operação de saída.</li>
                    <li><strong>CST IBS/CBS Padrão:</strong> código sugerido no cadastro de novos produtos.</li>
                    <li><strong>cClassTrib Padrão:</strong> código sugerido no cadastro de novos produtos.</li>
                </ul>

                <h6>Como salvar a configuração</h6>

                <ol>
                    <li>Acesse Administração → Configuração Fiscal.</li>
                    <li>Confira o ano de vigência.</li>
                    <li>Confira as alíquotas CBS e IBS.</li>
                    <li>Confira o CFOP padrão.</li>
                    <li>Confira o CST e o cClassTrib padrão.</li>
                    <li>Altere os campos necessários conforme orientação contábil.</li>
                    <li>Clique em Salvar.</li>
                </ol>

                <p>
                    Após salvar, o sistema apresenta a mensagem:
                    <strong>Configuração salva com sucesso!</strong>
                </p>

                <h6>Prioridade das alíquotas</h6>

                <p>
                    A ajuda fiscal descreve a seguinte ordem de prioridade:
                </p>

                <ol>
                    <li>Primeiro, o valor preenchido no cadastro do produto.</li>
                    <li>Se o produto estiver em branco, utiliza o valor padrão da Configuração Fiscal.</li>
                    <li>Se ambos estiverem em branco, a ajuda informa que o sistema não calcula o imposto e apresenta um aviso.</li>
                </ol>

                <div class="doc-alert">
                    Para CST e cClassTrib, os valores da Configuração
                    Fiscal são sugestões para o cadastro do produto.
                    A classificação deve ser conferida individualmente.
                </div>

                <h6>CFOP padrão</h6>

                <p>
                    O CFOP identifica a natureza da operação.
                    A ajuda fiscal explica que o mesmo produto pode
                    utilizar códigos diferentes conforme a operação.
                </p>

                <p>
                    Exemplos apresentados na ajuda:
                </p>

                <ul>
                    <li>5102: venda dentro do estado;</li>
                    <li>6102: venda fora do estado.</li>
                </ul>

                <p>
                    O CFOP padrão é descrito como uma sugestão para
                    evitar a digitação repetida. A operação deve
                    utilizar o código adequado à situação real.
                </p>

            </div>
        </div>
    </div>

    <!-- 17. AJUDA FISCAL -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc17">

                <span class="doc-section-icon">
                    <i class="bi bi-question-circle"></i>
                </span>

                17. Ajuda Fiscal / Reforma Tributária

            </button>
        </h2>

        <div id="doc17" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <p>
                    A página Ajuda Fiscal apresenta explicações
                    complementares sobre a utilização dos campos
                    fiscais do sistema.
                </p>

                <p>
                    Ela pode ser acessada pelo botão
                    <strong>Ajuda Fiscal - Como funciona a Configuração?</strong>,
                    disponível na tela Configuração Fiscal.
                </p>

                <h6>Conteúdo da ajuda</h6>

                <ul>
                    <li>Fiscal no Produto;</li>
                    <li>Configuração Fiscal;</li>
                    <li>Exemplos práticos;</li>
                    <li>Dúvidas frequentes.</li>
                </ul>

                <h6>Dúvidas frequentes</h6>

                <p>
                    <strong>O EstoqueWeb emite nota fiscal?</strong><br>
                    Não. A ajuda fiscal informa que o sistema ainda
                    não emite nota fiscal. Os campos servem para
                    classificação, organização e preparação para
                    uma futura integração com emissor de NF-e.
                </p>

                <p>
                    <strong>Se deixar a alíquota em branco, o imposto
                    fica zero?</strong><br>
                    Não. Em branco significa utilizar o padrão da
                    Configuração Fiscal, quando houver um valor
                    configurado.
                </p>

                <p>
                    <strong>Preciso preencher CST e cClassTrib
                    em todos os produtos?</strong><br>
                    A ajuda recomenda preencher esses campos por
                    produto, pois produtos diferentes podem possuir
                    tratamentos tributários diferentes.
                </p>

                <p>
                    <strong>CFOP é do produto?</strong><br>
                    Não. A ajuda explica que o CFOP é relacionado
                    à operação, e não ao produto.
                </p>

                <div class="doc-warning">
                    Os exemplos fiscais apresentados são ilustrativos.
                    Consulte a contabilidade para determinar os códigos
                    e percentuais corretos para cada produto e operação.
                </div>

            </div>
        </div>
    </div>

    <!-- 18. RECOMENDAÇÕES -->

    <div class="accordion-item doc-card">

        <h2 class="accordion-header">
            <button class="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#doc18">

                <span class="doc-section-icon">
                    <i class="bi bi-shield-check"></i>
                </span>

                18. Recomendações e cuidados

            </button>
        </h2>

        <div id="doc18" class="accordion-collapse collapse"
             data-bs-parent="#documentacaoAccordion">

            <div class="accordion-body">

                <ul>
                    <li>Mantenha os cadastros atualizados.</li>
                    <li>Registre entradas e saídas assim que ocorrerem.</li>
                    <li>Confira produto e quantidade antes de confirmar uma movimentação.</li>
                    <li>Confira fornecedor, destino e documento informado.</li>
                    <li>Não altere códigos fiscais ou alíquotas com base apenas em exemplos.</li>
                    <li>Consulte a contabilidade sobre classificações tributárias.</li>
                    <li>Encerre a sessão pelo botão Sair ao terminar o uso.</li>
                </ul>

            </div>
        </div>
    </div>

</div>

<div class="text-center text-muted small mt-4 mb-3">

    <i class="bi bi-book me-1"></i>

    EstoqueWeb — Documentação do Sistema

</div>

<script>

function abrirTodos() {

    var elementos = document.querySelectorAll(
        '#documentacaoAccordion .accordion-collapse'
    );

    elementos.forEach(function (elemento) {

        var instancia = bootstrap.Collapse.getOrCreateInstance(
            elemento,
            { toggle: false }
        );

        instancia.show();

    });

}

function fecharTodos() {

    var elementos = document.querySelectorAll(
        '#documentacaoAccordion .accordion-collapse'
    );

    elementos.forEach(function (elemento) {

        var instancia = bootstrap.Collapse.getOrCreateInstance(
            elemento,
            { toggle: false }
        );

        instancia.hide();

    });

}


// Pesquisa dentro da documentação

document.getElementById('pesquisarDocumentacao')
    .addEventListener('input', function () {

        var termo = this.value.toLowerCase().trim();

        var itens = document.querySelectorAll(
            '#documentacaoAccordion .accordion-item'
        );

        itens.forEach(function (item) {

            var conteudo = item.innerText.toLowerCase();

            if (conteudo.indexOf(termo) !== -1) {

                item.style.display = '';

            } else {

                item.style.display = 'none';

            }

        });

    });

</script>

<?php require_once 'includes/footer.php'; ?>
