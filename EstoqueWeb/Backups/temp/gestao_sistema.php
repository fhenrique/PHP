<?php

require_once __DIR__ . '/includes/auth.php';

exigirAdministrador();

/*
|--------------------------------------------------------------------------
| Proteção CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_gestao_sistema'])) {
    $_SESSION['csrf_gestao_sistema'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Função para proteger nomes de tabelas e colunas
|--------------------------------------------------------------------------
*/

function protegerIdentificador($nome) {

    return '`' . str_replace('`', '``', $nome) . '`';

}

/*
|--------------------------------------------------------------------------
| Função para transformar valores em SQL
|--------------------------------------------------------------------------
*/

function converterValorSQL($valor, $tipo) {

    if ($valor === null) {
        return 'NULL';
    }

    // Campos binários
    if (
        strpos($tipo, 'blob') !== false ||
        strpos($tipo, 'binary') !== false
    ) {

        return '0x' . bin2hex($valor);

    }

    // Campos numéricos
    $tiposNumericos = array(
        'tinyint',
        'smallint',
        'mediumint',
        'int',
        'integer',
        'bigint',
        'decimal',
        'numeric',
        'float',
        'double',
        'real',
        'bit',
        'year'
    );

    foreach ($tiposNumericos as $tipoNumerico) {

        if (strpos($tipo, $tipoNumerico) === 0) {

            if (!is_numeric($valor)) {
                throw new Exception(
                    'Valor numérico inválido encontrado no banco.'
                );
            }

            return (string)$valor;

        }

    }

    // Demais campos: texto, datas, horários etc.
    return "'" . str_replace(
        array("\\", "'", "\0", "\n", "\r", "\x1a"),
        array("\\\\", "\\'", "\\0", "\\n", "\\r", "\\Z"),
        (string)$valor
    ) . "'";

}

/*
|--------------------------------------------------------------------------
| Funções para leitura do arquivo de backup
|--------------------------------------------------------------------------
*/

/*
 * Divide os valores de um INSERT SQL,
 * respeitando aspas e caracteres escapados.
 */

function dividirValoresBackupSQL($texto) {

    $valores = array();

    $atual = '';

    $dentroAspas = false;

    $escape = false;

    $tamanho = strlen($texto);

    for ($i = 0; $i < $tamanho; $i++) {

        $caractere = $texto[$i];

        if ($escape) {

            $atual .= $caractere;

            $escape = false;

            continue;

        }

        if ($dentroAspas && $caractere === "\\") {

            $atual .= $caractere;

            $escape = true;

            continue;

        }

        if ($caractere === "'") {

            $dentroAspas = !$dentroAspas;

            $atual .= $caractere;

            continue;

        }

        if ($caractere === ',' && !$dentroAspas) {

            $valores[] = trim($atual);

            $atual = '';

            continue;

        }

        $atual .= $caractere;

    }

    if ($dentroAspas || $escape) {

        throw new Exception(
            'Arquivo de backup inválido: texto SQL incompleto.'
        );

    }

    if (trim($atual) !== '') {

        $valores[] = trim($atual);

    }

    return $valores;

}

/*
 * Converte os valores SQL exportados
 * para valores que serão enviados ao PDO.
 */

function decodificarValorBackupSQL($valor) {

    $valor = trim($valor);

    if (strtoupper($valor) === 'NULL') {

        return null;

    }

    // Valores binários
    if (preg_match('/^0x([0-9a-f]*)$/i', $valor, $m)) {

        $binario = hex2bin($m[1]);

        if ($binario === false) {

            throw new Exception(
                'Valor binário inválido no backup.'
            );

        }

        return $binario;

    }

    // Valores numéricos
    if (
        preg_match(
            '/^-?[0-9]+(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?$/',
            $valor
        )
    ) {

        return $valor;

    }

    // Valores de texto
    if (
        strlen($valor) >= 2 &&
        $valor[0] === "'" &&
        substr($valor, -1) === "'"
    ) {

        $interior = substr($valor, 1, -1);

        return stripcslashes($interior);

    }

    throw new Exception(
        'O backup contém um valor SQL não permitido.'
    );

}

/*
|--------------------------------------------------------------------------
| Leitura e validação do backup da empresa
|--------------------------------------------------------------------------
*/

function lerBackupDadosEmpresa($caminho) {

$conteudo = file_get_contents($caminho);

if ($conteudo === false) {
    throw new Exception('Não foi possível ler o arquivo de backup.');
}

// Remover BOM UTF-8 e espaços iniciais.
$conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);
$conteudo = ltrim($conteudo);

    if (
        $conteudo === false ||
        strpos(
            $conteudo,
            '-- BACKUP DOS DADOS DO ESTOQUEWEB'
        ) !== 0
    ) {

        throw new Exception(
            'O arquivo selecionado não é um backup de dados válido do EstoqueWeb.'
        );

    }

    if (
        !preg_match(
            '/^-- ID da empresa:\s*([0-9]+)\s*$/m',
            $conteudo,
            $m
        )
    ) {

        throw new Exception(
            'Não foi possível identificar a empresa de origem do backup.'
        );

    }

    $empresaBackup = (int)$m[1];

    // Somente estas sete tabelas serão restauradas.
    $permitidas = array(
        'categorias',
        'fornecedores',
        'produtos',
        'usuarios',
        'entradas_estoque',
        'itens_entrada',
        'movimentacoes'
    );

    $dados = array();

    foreach ($permitidas as $tabela) {

        $dados[$tabela] = array();

    }

    $linhas = preg_split('/\r\n|\r|\n/', $conteudo);

    foreach ($linhas as $linha) {

        $linha = trim($linha);

        if (
            $linha === '' ||
            strpos($linha, '--') === 0 ||
            stripos($linha, 'SET FOREIGN_KEY_CHECKS') === 0
        ) {

            continue;

        }

        if (
            !preg_match(
                '/^INSERT INTO `([a-zA-Z0-9_]+)`\s*\((.*?)\)\s*VALUES\s*\((.*)\);$/i',
                $linha,
                $partes
            )
        ) {

            throw new Exception(
                'O backup contém um comando SQL não permitido.'
            );

        }

        $tabela = $partes[1];

        if (!in_array($tabela, $permitidas, true)) {

            throw new Exception(
                'O backup contém uma tabela não autorizada: ' . $tabela
            );

        }

        preg_match_all(
            '/`([a-zA-Z0-9_]+)`/',
            $partes[2],
            $colunasEncontradas
        );

        $colunas = $colunasEncontradas[1];

        $valoresBrutos = dividirValoresBackupSQL(
            $partes[3]
        );

        if (
            count($colunas) === 0 ||
            count($colunas) !== count($valoresBrutos)
        ) {

            throw new Exception(
                'O backup contém quantidade inválida de colunas ou valores.'
            );

        }

        $registro = array();

        foreach ($colunas as $indice => $coluna) {

            if (array_key_exists($coluna, $registro)) {

                throw new Exception(
                    'O backup contém coluna duplicada.'
                );

            }

            $registro[$coluna] = decodificarValorBackupSQL(
                $valoresBrutos[$indice]
            );

        }

        if (
            !isset($registro['empresa_id']) ||
            (int)$registro['empresa_id'] !== $empresaBackup
        ) {

            throw new Exception(
                'Foi encontrado registro que não pertence à empresa informada no backup.'
            );

        }

        $dados[$tabela][] = $registro;

    }

    return array(
        $empresaBackup,
        $dados
    );

}

/*
|--------------------------------------------------------------------------
| Processamento das exportações e restauração
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['acao'])) {

    /*
    |--------------------------------------------------------------------------
    | VALIDACAO CSRF
    |--------------------------------------------------------------------------
    */

    if (
        empty($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_gestao_sistema'],
            $_POST['csrf_token']
        )
    ) {

        http_response_code(403);

        exit(
            'Requisição inválida. Atualize a página e tente novamente.'
        );

    }

    try {

        /*
        |--------------------------------------------------------------------------
        | RESTAURACAO SOMENTE DOS DADOS OPERACIONAIS
        |--------------------------------------------------------------------------
        */

        if ($_POST['acao'] === 'restaurar_dados') {

            $empresaId = isset($_SESSION['empresa_id'])
                ? (int)$_SESSION['empresa_id']
                : 0;

            if ($empresaId <= 0) {
                throw new Exception('Nenhuma empresa está selecionada.');
            }

            if (
                !isset($_FILES['arquivo_backup']) ||
                $_FILES['arquivo_backup']['error'] !== UPLOAD_ERR_OK
            ) {
                throw new Exception(
                    'Selecione um arquivo SQL válido para restauração.'
                );
            }

            if (!is_uploaded_file($_FILES['arquivo_backup']['tmp_name'])) {
                throw new Exception('O arquivo enviado não é válido.');
            }

            if ($_FILES['arquivo_backup']['size'] > 25 * 1024 * 1024) {
                throw new Exception('O arquivo ultrapassa o limite de 25 MB.');
            }

            $nomeOriginal = $_FILES['arquivo_backup']['name'];

            if (
                strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION)) !== 'sql'
            ) {
                throw new Exception(
                    'O arquivo de restauração deve possuir extensão .sql.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | LEITURA DO BACKUP
            |--------------------------------------------------------------------------
            */

            list($empresaBackup, $dadosBackup) =
                lerBackupDadosEmpresa(
                    $_FILES['arquivo_backup']['tmp_name']
                );

            if ((int)$empresaBackup !== (int)$empresaId) {
                throw new Exception(
                    'O backup pertence a outra empresa. Nenhum dado foi alterado.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR EMPRESA ATUAL
            |--------------------------------------------------------------------------
            */

            $stmtEmpresa = $pdo->prepare(
                'SELECT id FROM empresas WHERE id = ?'
            );

            $stmtEmpresa->execute(array($empresaId));

            if (!$stmtEmpresa->fetchColumn()) {
                throw new Exception(
                    'A empresa selecionada não existe no banco atual.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | TABELAS DA RESTAURACAO
            |--------------------------------------------------------------------------
            */

            $tabelasRestauracao = array(
                'usuarios',
                'categorias',
                'fornecedores',
                'produtos',
                'entradas_estoque',
                'itens_entrada',
                'movimentacoes'
            );

            /*
            |--------------------------------------------------------------------------
            | VALIDAR EXISTENCIA DAS TABELAS E COLUNAS
            |--------------------------------------------------------------------------
            */

            foreach ($tabelasRestauracao as $tabela) {

                $stmtExiste = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = ?
                ");

                $stmtExiste->execute(array($tabela));

                if (!(int)$stmtExiste->fetchColumn()) {
                    throw new Exception(
                        'A tabela necessária não existe: ' . $tabela
                    );
                }

                foreach ($dadosBackup[$tabela] as $registro) {

                    foreach (array_keys($registro) as $coluna) {

                        $stmtColuna = $pdo->prepare("
                            SELECT COUNT(*)
                            FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE()
                              AND TABLE_NAME = ?
                              AND COLUMN_NAME = ?
                        ");

                        $stmtColuna->execute(
                            array($tabela, $coluna)
                        );

                        if (!(int)$stmtColuna->fetchColumn()) {
                            throw new Exception(
                                'Coluna não encontrada na estrutura atual: ' .
                                $tabela . '.' . $coluna
                            );
                        }

                    }

                }

            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR PERFIS
            |--------------------------------------------------------------------------
            |
            | A tabela perfis não será restaurada.
            | Os perfis necessários devem existir no banco atual.
            |--------------------------------------------------------------------------
            */

            $perfisNecessarios = array();

            foreach ($dadosBackup['usuarios'] as $usuarioBackup) {

                if (isset($usuarioBackup['perfil_id'])) {

                    $perfisNecessarios[] =
                        (int)$usuarioBackup['perfil_id'];

                }

            }

            $perfisNecessarios = array_unique($perfisNecessarios);

            foreach ($perfisNecessarios as $perfilId) {

                $stmtPerfil = $pdo->prepare(
                    'SELECT id FROM perfis WHERE id = ?'
                );

                $stmtPerfil->execute(array($perfilId));

                if (!$stmtPerfil->fetchColumn()) {

                    throw new Exception(
                        'O perfil ID ' . $perfilId .
                        ' utilizado no backup não existe no banco atual. ' .
                        'Restauração cancelada.'
                    );

                }

            }

            /*
            |--------------------------------------------------------------------------
            | VERIFICAR CONFLITOS DE IDS
            |--------------------------------------------------------------------------
            |
            | Os IDs existentes em outras empresas não podem ser sobrescritos.
            |--------------------------------------------------------------------------
            */

            foreach ($tabelasRestauracao as $tabela) {

                foreach ($dadosBackup[$tabela] as $registro) {

                    if (!isset($registro['id'])) {
                        throw new Exception(
                            'Registro sem chave ID encontrado na tabela ' .
                            $tabela . '.'
                        );
                    }

                    $stmtConflito = $pdo->prepare(
                        'SELECT empresa_id FROM ' .
                        protegerIdentificador($tabela) .
                        ' WHERE id = ?'
                    );

                    $stmtConflito->execute(
                        array($registro['id'])
                    );

                    $empresaIdExistente = $stmtConflito->fetchColumn();

                    if (
                        $empresaIdExistente !== false &&
                        (int)$empresaIdExistente !== (int)$empresaId
                    ) {

                        throw new Exception(
                            'Conflito de ID na tabela ' . $tabela .
                            ': o ID ' . $registro['id'] .
                            ' já pertence a outra empresa. ' .
                            'Nenhum dado foi alterado.'
                        );

                    }

                }

            }

            /*
            |--------------------------------------------------------------------------
            | INICIAR TRANSACAO
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();

            try {

                /*
                |--------------------------------------------------------------------------
                | EXCLUIR DADOS ATUAIS DA EMPRESA
                |--------------------------------------------------------------------------
                |
                | A exclusão respeita a dependência entre as tabelas.
                | Não utilizamos TRUNCATE.
                |--------------------------------------------------------------------------
                */

                $ordemExclusao = array(
                    'movimentacoes',
                    'itens_entrada',
                    'entradas_estoque',
                    'produtos',
                    'categorias',
                    'fornecedores',
                    'usuarios'
                );

                foreach ($ordemExclusao as $tabela) {

                    $stmtDelete = $pdo->prepare(
                        'DELETE FROM ' .
                        protegerIdentificador($tabela) .
                        ' WHERE empresa_id = ?'
                    );

                    $stmtDelete->execute(array($empresaId));

                }

                /*
                |--------------------------------------------------------------------------
                | INSERIR OS DADOS DO BACKUP
                |--------------------------------------------------------------------------
                */

                foreach ($tabelasRestauracao as $tabela) {

                    foreach ($dadosBackup[$tabela] as $registro) {

                        $colunas = array_keys($registro);

                        $nomesSQL = array();

                        $marcadores = array();

                        foreach ($colunas as $coluna) {

                            $nomesSQL[] =
                                protegerIdentificador($coluna);

                            $marcadores[] = '?';

                        }

                        $sqlInsert =
                            'INSERT INTO ' .
                            protegerIdentificador($tabela) .
                            ' (' . implode(', ', $nomesSQL) . ')' .
                            ' VALUES (' .
                            implode(', ', $marcadores) .
                            ')';

                        $stmtInsert = $pdo->prepare($sqlInsert);

                        $stmtInsert->execute(
                            array_values($registro)
                        );

                    }

                }

                /*
                |--------------------------------------------------------------------------
                | CONFIRMAR TRANSACAO
                |--------------------------------------------------------------------------
                */

                $pdo->commit();

            } catch (Exception $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;

            }

            $_SESSION['mensagem_gestao_sistema'] =
                'Restauração concluída. Somente os dados operacionais da empresa foram substituídos.';

            header('Location: gestao_sistema.php?restauracao=sucesso');

            exit;

        /*
        |--------------------------------------------------------------------------
        | EXPORTACAO 1 - ESTRUTURA
        |--------------------------------------------------------------------------
        */

        } elseif ($_POST['acao'] === 'exportar_estrutura') {

            $banco = $pdo->query(
                'SELECT DATABASE()'
            )->fetchColumn();

            if (!$banco) {
                throw new Exception(
                    'Não foi possível identificar o banco de dados.'
                );
            }

            $resultadoTabelas = $pdo->query(
                'SHOW FULL TABLES'
            );

            $tabelasEncontradas = array();

            while ($linha = $resultadoTabelas->fetch(PDO::FETCH_NUM)) {

                if (
                    isset($linha[1]) &&
                    $linha[1] === 'BASE TABLE'
                ) {

                    $tabelasEncontradas[] = $linha[0];

                }

            }

            /*
            |--------------------------------------------------------------------------
            | ORDEM DE CRIACAO DAS TABELAS
            |--------------------------------------------------------------------------
            */

            $ordemCriacao = array(
                'empresas',
                'perfis',
                'categorias',
                'fornecedores',
                'usuarios',
                'produtos',
                'entradas_estoque',
                'itens_entrada',
                'movimentacoes'
            );

            $tabelas = array();

            foreach ($ordemCriacao as $nomeTabela) {

                if (
                    in_array(
                        $nomeTabela,
                        $tabelasEncontradas,
                        true
                    )
                ) {

                    $tabelas[] = $nomeTabela;

                }

            }

            foreach ($tabelasEncontradas as $nomeTabela) {

                if (!in_array($nomeTabela, $tabelas, true)) {

                    $tabelas[] = $nomeTabela;

                }

            }

            if (empty($tabelas)) {
                throw new Exception(
                    'Nenhuma tabela foi encontrada.'
                );
            }

            $conteudo = '';

            $conteudo .= "-- BACKUP DA ESTRUTURA DO ESTOQUEWEB\n";
            $conteudo .= "-- Banco: " . $banco . "\n";
            $conteudo .= "-- Data: " . date('d/m/Y H:i:s') . "\n\n";

            $conteudo .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
            $conteudo .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tabelas as $tabela) {

                $tabelaProtegida =
                    protegerIdentificador($tabela);

                $resultadoCreate = $pdo->query(
                    "SHOW CREATE TABLE " . $tabelaProtegida
                );

                $dadosCreate =
                    $resultadoCreate->fetch(PDO::FETCH_NUM);

                if (!isset($dadosCreate[1])) {

                    throw new Exception(
                        'Não foi possível obter a estrutura da tabela: ' .
                        $tabela
                    );

                }

                $conteudo .= "-- TABELA: " . $tabela . "\n";

                $conteudo .=
                    "DROP TABLE IF EXISTS " .
                    $tabelaProtegida . ";\n";

                $conteudo .= $dadosCreate[1] . ";\n\n";

            }

            $conteudo .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            $conteudo .= "-- FIM DO BACKUP DA ESTRUTURA\n";

            $nomeArquivo =
                'estrutura_estoqueweb_' .
                date('Ymd_His') . '.sql';

        /*
        |--------------------------------------------------------------------------
        | EXPORTACAO 2 - DADOS DA EMPRESA
        |--------------------------------------------------------------------------
        */

        } elseif ($_POST['acao'] === 'exportar_dados') {

            $empresaId = isset($_SESSION['empresa_id'])
                ? (int)$_SESSION['empresa_id']
                : 0;

            if ($empresaId <= 0) {
                throw new Exception(
                    'Nenhuma empresa está selecionada.'
                );
            }

            $stmtEmpresa = $pdo->prepare(
                'SELECT id, nome FROM empresas WHERE id = ?'
            );

            $stmtEmpresa->execute(array($empresaId));

            $empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

            if (!$empresa) {
                throw new Exception(
                    'A empresa selecionada não foi encontrada.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SOMENTE AS SETE TABELAS OPERACIONAIS
            |--------------------------------------------------------------------------
            */

            $tabelasExportacao = array(
                'categorias',
                'fornecedores',
                'produtos',
                'usuarios',
                'entradas_estoque',
                'itens_entrada',
                'movimentacoes'
            );

            $tabelasEmpresa = $tabelasExportacao;

            $tabelasBanco = $pdo->query(
                'SHOW FULL TABLES'
            )->fetchAll(PDO::FETCH_NUM);

            $existentes = array();

            foreach ($tabelasBanco as $linha) {

                if (
                    isset($linha[1]) &&
                    $linha[1] === 'BASE TABLE'
                ) {

                    $existentes[] = $linha[0];

                }

            }

            foreach ($tabelasExportacao as $tabela) {

                if (!in_array($tabela, $existentes, true)) {

                    throw new Exception(
                        'A tabela necessária não foi encontrada: ' .
                        $tabela
                    );

                }

            }

            $conteudo = '';

            $conteudo .= "-- BACKUP DOS DADOS DO ESTOQUEWEB\n";
            $conteudo .= "-- Empresa: " . $empresa['nome'] . "\n";
            $conteudo .= "-- ID da empresa: " . $empresa['id'] . "\n";
            $conteudo .= "-- Data: " . date('d/m/Y H:i:s') . "\n\n";

            $conteudo .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tabelasExportacao as $tabela) {

                $tabelaProtegida =
                    protegerIdentificador($tabela);

                $sql = "SELECT * FROM " .
                    $tabelaProtegida .
                    " WHERE empresa_id = ?";

                $stmt = $pdo->prepare($sql);

                $stmt->execute(array($empresaId));

                $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $conteudo .= "-- TABELA: " . $tabela . "\n";

                if (empty($registros)) {

                    $conteudo .=
                        "-- TABELA: " . $tabela .
                        " (sem registros)\n\n";

                    continue;

                }

                $stmtColunas = $pdo->prepare("
                    SELECT COLUMN_NAME, DATA_TYPE
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = ?
                    ORDER BY ORDINAL_POSITION
                ");

                $stmtColunas->execute(array($tabela));

                $tiposColunas = array();

                while (
                    $coluna = $stmtColunas->fetch(PDO::FETCH_ASSOC)
                ) {

                    $tiposColunas[$coluna['COLUMN_NAME']] =
                        strtolower($coluna['DATA_TYPE']);

                }

                $colunas = array_keys($registros[0]);

                $colunasSQL = array();

                foreach ($colunas as $coluna) {

                    $colunasSQL[] =
                        protegerIdentificador($coluna);

                }

                foreach ($registros as $registro) {

                    $valoresSQL = array();

                    foreach ($colunas as $coluna) {

                        $tipo = isset($tiposColunas[$coluna])
                            ? $tiposColunas[$coluna]
                            : 'varchar';

                        $valoresSQL[] = converterValorSQL(
                            $registro[$coluna],
                            $tipo
                        );

                    }

                    $conteudo .=
                        "INSERT INTO " .
                        $tabelaProtegida .
                        " (" . implode(', ', $colunasSQL) .
                        ") VALUES (" .
                        implode(', ', $valoresSQL) .
                        ");\n";

                }

                $conteudo .= "\n";

            }

            $conteudo .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            $conteudo .= "-- FIM DO BACKUP DOS DADOS\n";

            $nomeArquivo =
                'dados_empresa_' .
                $empresaId . '_' .
                date('Ymd_His') . '.sql';

        } else {

            throw new Exception(
                'Ação inválida.'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD DO ARQUIVO SQL
        |--------------------------------------------------------------------------
        */

        header('Content-Type: application/sql; charset=utf-8');

        header(
            'Content-Disposition: attachment; filename="' .
            $nomeArquivo . '"'
        );

        header('Content-Length: ' . strlen($conteudo));

        header('Cache-Control: no-store, no-cache, must-revalidate');

        header('Pragma: no-cache');

        echo $conteudo;

        exit;

    } catch (Exception $e) {

        error_log(
            'Erro na Gestão do Sistema: ' . $e->getMessage()
        );

        if (
            isset($_POST['acao']) &&
            $_POST['acao'] === 'restaurar_dados'
        ) {

            $_SESSION['erro_gestao_sistema'] =
                $e->getMessage();

            header(
                'Location: gestao_sistema.php?restauracao=erro'
            );

            exit;

        }

        http_response_code(500);

        exit(
            'Não foi possível gerar o arquivo SQL. ' .
            'Verifique os registros de erro do servidor.'
        );

    }

}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid">

    <!-- TÍTULO DA PÁGINA -->

    <div class="row mb-4">

        <div class="col-12">

            <h4 class="mb-1">
                <i class="bi bi-database-gear"></i>
                Gestão do Sistema
            </h4>

            <p class="text-muted mb-0">
                Backup e restauração do banco de dados.
            </p>

        </div>

    </div>


    <div class="row g-4">

        <!-- =====================================================
             CARD 1 - BACKUP DA ESTRUTURA
        ====================================================== -->

        <div class="col-md-6">

            <div class="card shadow-sm h-100">

                <div class="card-header bg-primary text-white">

                    <h5 class="mb-0">
                        <i class="bi bi-diagram-3"></i>
                        Estrutura do Banco de Dados
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        Gera um arquivo SQL contendo a estrutura
                        completa do banco de dados.
                    </p>

                    <ul>
                        <li>Criação das tabelas;</li>
                        <li>Chaves primárias e estrangeiras;</li>
                        <li>Índices e AUTO_INCREMENT.</li>
                    </ul>

                    <div class="alert alert-info">
                        Este arquivo contém somente estruturas.
                        Nenhum registro será exportado.
                    </div>

                    <form method="POST" action="gestao_sistema.php">

                        <input type="hidden"
                               name="acao"
                               value="exportar_estrutura">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo htmlspecialchars($_SESSION['csrf_gestao_sistema'], ENT_QUOTES, 'UTF-8'); ?>">

                        <button type="submit"
                                class="btn btn-primary w-100">

                            <i class="bi bi-download"></i>
                            Gerar arquivo da estrutura

                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- =====================================================
             CARD 2 - BACKUP DOS DADOS OPERACIONAIS
        ====================================================== -->

        <div class="col-md-6">

            <div class="card shadow-sm h-100">

                <div class="card-header bg-success text-white">

                    <h5 class="mb-0">
                        <i class="bi bi-database"></i>
                        Backup dos Dados da Empresa
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        Gera um arquivo SQL contendo somente
                        os dados operacionais da empresa.
                    </p>

                    <ul>
                        <li>Categorias, fornecedores e produtos;</li>
                        <li>Usuários;</li>
                        <li>Entradas e respectivos itens;</li>
                        <li>Movimentações de estoque.</li>
                    </ul>

                    <div class="alert alert-transparent-success" role="alert">
                        ㅤ
                        ㅤ
                    </div>

                    <form method="POST" action="gestao_sistema.php">

                        <input type="hidden"
                               name="acao"
                               value="exportar_dados">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo htmlspecialchars($_SESSION['csrf_gestao_sistema'], ENT_QUOTES, 'UTF-8'); ?>">

                        <button type="submit"
                                class="btn btn-success w-100">

                            <i class="bi bi-download"></i>
                            Gerar arquivo dos dados da empresa

                        </button>

                    </form>

                </div>

            </div>

        </div>


        <!-- =====================================================
             CARD 3 - RESTAURACAO DOS DADOS
        ====================================================== -->

        <div class="col-12">

            <div class="card shadow-sm">

                <div class="card-header bg-warning">

                    <h5 class="mb-0">
                        <i class="bi bi-database-up"></i>
                        Restaurar somente os dados da empresa
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        Substitui os dados operacionais da empresa,
                        mantendo a estrutura atual do banco.
                    </p>

                    <ul>
                        <li>Categorias, fornecedores e produtos;</li>
                        <li>Usuários;</li>
                        <li>Entradas de estoque e respectivos itens;</li>
                        <li>Movimentações de estoque.</li>
                    </ul>

                    <div class="alert alert-warning">

                        Os cadastros "Empresa" e "Perfis" não serão
                        importados nem excluídos.

                        A operação não poderá ser desfeita.

                    </div>


                    <!-- MENSAGEM DE SUCESSO -->

                    <?php if (!empty($_SESSION['mensagem_gestao_sistema'])): ?>

                        <div class="alert alert-success alert-dismissible fade show">

                            <?php
                            echo htmlspecialchars(
                                $_SESSION['mensagem_gestao_sistema'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            unset($_SESSION['mensagem_gestao_sistema']);
                            ?>

                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="alert"
                                    aria-label="Fechar">
                            </button>

                        </div>

                    <?php endif; ?>


                    <!-- MENSAGEM DE ERRO -->

                    <?php if (!empty($_SESSION['erro_gestao_sistema'])): ?>

                        <div class="alert alert-danger alert-dismissible fade show">

                            <?php
                            echo htmlspecialchars(
                                $_SESSION['erro_gestao_sistema'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            unset($_SESSION['erro_gestao_sistema']);
                            ?>

                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="alert"
                                    aria-label="Fechar">
                            </button>

                        </div>

                    <?php endif; ?>


                    <!-- FORMULÁRIO DE RESTAURAÇÃO -->

                    <form id="formRestaurarDados"
                          method="POST"
                          action="gestao_sistema.php"
                          enctype="multipart/form-data">

                        <input type="hidden"
                               name="acao"
                               value="restaurar_dados">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo htmlspecialchars($_SESSION['csrf_gestao_sistema'], ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="mb-3">

                            <label for="arquivo_backup"
                                   class="form-label">

                                Arquivo SQL do backup de dados (criado aqui no EstoqueWeb)

                            </label>

                            <input type="file"
                                   class="form-control"
                                   id="arquivo_backup"
                                   name="arquivo_backup"
                                   accept=".sql"
                                   required>

                            <small class="text-muted">
                                Tamanho máximo: 25 MB.
                            </small>

                        </div>

                        <button type="button"
                                class="btn btn-warning w-100"
                                id="btnAbrirConfirmacaoRestauracao">

                            <i class="bi bi-arrow-counterclockwise"></i>

                            Restaurar somente dados mantendo a estrutura atual do banco

                        </button>

                    </form>

                </div>
                

            </div>
            
        </div>

<div class="alert alert-danger">
  <strong>Atenção!</strong> Mantenha o hábito de gerar com frequência, o backup dos dados da empresa. Por exemplo 1 vez por dia, ou a depender da vontade que o admin possua de guardar uma cópia dos dados da empresa.
</div>

    </div>

</div>


<!-- =========================================================
     MODAL 1 - PREVIA DOS DADOS DO ARQUIVO
========================================================= -->

<div class="modal fade"
     id="modalConfirmacaoRestauracao"
     tabindex="-1"
     aria-labelledby="tituloConfirmacaoRestauracao"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header bg-info text-white">

                <h5 class="modal-title"
                    id="tituloConfirmacaoRestauracao">

                    <i class="bi bi-file-earmark-check"></i>
                    Prévia dos dados que serão importados

                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Fechar">
                </button>

            </div>


            <div class="modal-body">

                <p>
                    Confira as informações identificadas no arquivo
                    antes de prosseguir.
                </p>


                <!-- INFORMAÇÕES DO ARQUIVO -->

                <div class="border rounded p-3 mb-3 bg-light">

                    <div class="mb-2">

                        <strong>Arquivo:</strong>

                        <span id="nomeArquivoRestauracao">
                            -
                        </span>

                    </div>

                    <div class="mb-2">

                        <strong>Tamanho:</strong>

                        <span id="tamanhoArquivoRestauracao">
                            -
                        </span>

                    </div>

                    <div class="mb-2">

                        <strong>Empresa do backup:</strong>

                        <span id="empresaArquivoRestauracao">
                            -
                        </span>

                    </div>

                    <div>

                        <strong>ID da empresa:</strong>

                        <span id="idEmpresaArquivoRestauracao">
                            -
                        </span>

                    </div>

                </div>


                <!-- QUANTIDADE DE REGISTROS -->

                <h6 class="mb-3">
                    Quantidade de registros encontrados
                </h6>

                <div class="table-responsive">

                    <table class="table table-sm table-striped table-bordered align-middle">

                        <thead class="table-light">

                            <tr>

                                <th>Dados</th>

                                <th class="text-end">
                                    Registros
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>
                                <td>Categorias</td>
                                <td class="text-end" id="qtdCategoriasBackup">0</td>
                            </tr>

                            <tr>
                                <td>Fornecedores</td>
                                <td class="text-end" id="qtdFornecedoresBackup">0</td>
                            </tr>

                            <tr>
                                <td>Produtos</td>
                                <td class="text-end" id="qtdProdutosBackup">0</td>
                            </tr>

                            <tr>
                                <td>Usuários</td>
                                <td class="text-end" id="qtdUsuariosBackup">0</td>
                            </tr>

                            <tr>
                                <td>Entradas de estoque</td>
                                <td class="text-end" id="qtdEntradasBackup">0</td>
                            </tr>

                            <tr>
                                <td>Itens de entrada</td>
                                <td class="text-end" id="qtdItensBackup">0</td>
                            </tr>

                            <tr>
                                <td>Movimentações de estoque</td>
                                <td class="text-end" id="qtdMovimentacoesBackup">0</td>
                            </tr>

                            <tr class="table-secondary fw-bold">

                                <td>Total</td>

                                <td class="text-end"
                                    id="qtdTotalBackup">

                                    0

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>


                <!-- AVISO -->

                <div class="alert alert-warning mb-0">

                    Esta é uma prévia do conteúdo identificado
                    no arquivo.

                    A validação completa será realizada pelo servidor
                    antes de qualquer alteração no banco de dados.

                    Empresas e perfis não serão importados.

                </div>


                <!-- ERRO DE LEITURA -->

                <div class="alert alert-danger mt-3 mb-0 d-none"
                     id="erroLeituraBackup">
                </div>

            </div>


            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                    Cancelar

                </button>

                <button type="button"
                        class="btn btn-primary"
                        id="btnSegundaConfirmacao"
                        disabled>

                    Prosseguir para confirmação final

                </button>

            </div>

        </div>

    </div>

</div>
<!-- =========================================================
     MODAL 2 - CONFIRMACAO FINAL DA RESTAURACAO
========================================================= -->

<div class="modal fade"
     id="modalConfirmacaoFinalRestauracao"
     tabindex="-1"
     aria-labelledby="tituloConfirmacaoFinalRestauracao"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header bg-danger text-white">

                <h5 class="modal-title"
                    id="tituloConfirmacaoFinalRestauracao">

                    <i class="bi bi-exclamation-triangle"></i>
                    Confirmação final

                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Fechar">
                </button>

            </div>

            <div class="modal-body">

                <strong>Atenção!</strong>

                <p class="mt-2">

                    Os dados atuais de categorias, fornecedores,
                    produtos, usuários, entradas, itens e movimentações
                    desta empresa serão excluídos e substituídos
                    pelos dados do backup.

                </p>

                <p>

                    A estrutura do banco será preservada.

                </p>

                <p>

                    Empresas e perfis não serão alterados.

                </p>

                <div class="alert alert-danger mb-0">

                    Esta operação não pode ser desfeita.

                    <strong>Confirma a restauração?</strong>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                    Não, cancelar

                </button>

                <button type="button"
                        class="btn btn-danger"
                        id="btnExecutarRestauracao">

                    <i class="bi bi-arrow-counterclockwise"></i>
                    Sim, restaurar agora

                </button>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT - LEITURA DO BACKUP E CONFIRMACOES
========================================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    var formulario = document.getElementById('formRestaurarDados');

    var arquivo = document.getElementById('arquivo_backup');

    var botaoAbrir = document.getElementById(
        'btnAbrirConfirmacaoRestauracao'
    );

    var modalInicialEl = document.getElementById(
        'modalConfirmacaoRestauracao'
    );

    var modalFinalEl = document.getElementById(
        'modalConfirmacaoFinalRestauracao'
    );

    var botaoProsseguir = document.getElementById(
        'btnSegundaConfirmacao'
    );

    var botaoExecutar = document.getElementById(
        'btnExecutarRestauracao'
    );

    var erroEl = document.getElementById(
        'erroLeituraBackup'
    );


    if (
        !formulario ||
        !arquivo ||
        !botaoAbrir ||
        !modalInicialEl ||
        !modalFinalEl ||
        !window.bootstrap
    ) {
        return;
    }


    var modalInicial = new bootstrap.Modal(modalInicialEl);

    var modalFinal = new bootstrap.Modal(modalFinalEl);


    /*
    |--------------------------------------------------------------------------
    | TABELAS QUE SERÃO CONTABILIZADAS
    |--------------------------------------------------------------------------
    */

    var tabelas = {

        categorias: 'qtdCategoriasBackup',

        fornecedores: 'qtdFornecedoresBackup',

        produtos: 'qtdProdutosBackup',

        usuarios: 'qtdUsuariosBackup',

        entradas_estoque: 'qtdEntradasBackup',

        itens_entrada: 'qtdItensBackup',

        movimentacoes: 'qtdMovimentacoesBackup'

    };


    /*
    |--------------------------------------------------------------------------
    | FORMATAR TAMANHO DO ARQUIVO
    |--------------------------------------------------------------------------
    */

    function formatarTamanho(bytes) {

        if (bytes < 1024) {

            return bytes + ' bytes';

        }

        if (bytes < 1024 * 1024) {

            return (bytes / 1024).toFixed(2) + ' KB';

        }

        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';

    }


    /*
    |--------------------------------------------------------------------------
    | EXIBIR ERRO
    |--------------------------------------------------------------------------
    */

    function mostrarErro(mensagem) {

        erroEl.textContent = mensagem;

        erroEl.classList.remove('d-none');

        botaoProsseguir.disabled = true;

    }


    /*
    |--------------------------------------------------------------------------
    | ZERAR CONTADORES
    |--------------------------------------------------------------------------
    */

    function zerarContadores() {

        Object.keys(tabelas).forEach(function (tabela) {

            document.getElementById(
                tabelas[tabela]
            ).textContent = '0';

        });

        document.getElementById(
            'qtdTotalBackup'
        ).textContent = '0';

    }


    /*
    |--------------------------------------------------------------------------
    | ABRIR PRÉVIA
    |--------------------------------------------------------------------------
    */

    botaoAbrir.addEventListener('click', async function () {

        if (!arquivo.files || !arquivo.files.length) {

            arquivo.reportValidity();

            return;

        }


        var file = arquivo.files[0];


        erroEl.classList.add('d-none');

        erroEl.textContent = '';

        botaoProsseguir.disabled = true;


        zerarContadores();


        document.getElementById(
            'nomeArquivoRestauracao'
        ).textContent = file.name;


        document.getElementById(
            'tamanhoArquivoRestauracao'
        ).textContent = formatarTamanho(file.size);


        document.getElementById(
            'empresaArquivoRestauracao'
        ).textContent = 'Lendo arquivo...';


        document.getElementById(
            'idEmpresaArquivoRestauracao'
        ).textContent = '-';


        modalInicial.show();


        try {

            /*
            |--------------------------------------------------------------------------
            | VALIDAR EXTENSÃO
            |--------------------------------------------------------------------------
            */

            if (!/\.sql$/i.test(file.name)) {

                throw new Error(
                    'Selecione um arquivo com extensão .sql.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | VALIDAR TAMANHO
            |--------------------------------------------------------------------------
            */

            if (file.size > 25 * 1024 * 1024) {

                throw new Error(
                    'O arquivo ultrapassa o limite de 25 MB.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | LER ARQUIVO
            |--------------------------------------------------------------------------
            */

            var conteudo = await file.text();


/*
|--------------------------------------------------------------------------
| VALIDAR CABEÇALHO
|--------------------------------------------------------------------------
*/

// Remover BOM UTF-8 e espaços antes do cabeçalho.
var conteudoValidado = conteudo
    .replace(/^\uFEFF/, '')
    .trimStart();

if (
    !conteudoValidado.startsWith(
        '-- BACKUP DOS DADOS DO ESTOQUEWEB'
    )
) {
    throw new Error(
        'O arquivo não possui o cabeçalho esperado do backup do EstoqueWeb.'
    );
}

// Utilizar o conteúdo normalizado nas verificações seguintes.
conteudo = conteudoValidado;


            /*
            |--------------------------------------------------------------------------
            | IDENTIFICAR EMPRESA
            |--------------------------------------------------------------------------
            */

            var empresaMatch = conteudo.match(
                /^-- Empresa:\s*(.*)$/m
            );

            var idMatch = conteudo.match(
                /^-- ID da empresa:\s*(\d+)\s*$/m
            );


            if (!idMatch) {

                throw new Error(
                    'Não foi possível identificar o ID da empresa no arquivo.'
                );

            }


            document.getElementById(
                'empresaArquivoRestauracao'
            ).textContent = empresaMatch
                ? empresaMatch[1]
                : 'Não informado';


            document.getElementById(
                'idEmpresaArquivoRestauracao'
            ).textContent = idMatch[1];


            /*
            |--------------------------------------------------------------------------
            | CONTABILIZAR REGISTROS
            |--------------------------------------------------------------------------
            */

            var totais = {};

            Object.keys(tabelas).forEach(function (tabela) {

                totais[tabela] = 0;

            });


            var linhas = conteudo.split(/\r\n|\r|\n/);


            linhas.forEach(function (linha) {

                var correspondencia = linha.match(
                    /^INSERT INTO `([a-zA-Z0-9_]+)`\s*\(/i
                );


                if (
                    correspondencia &&
                    Object.prototype.hasOwnProperty.call(
                        totais,
                        correspondencia[1]
                    )
                ) {

                    totais[correspondencia[1]]++;

                }

            });


            /*
            |--------------------------------------------------------------------------
            | ATUALIZAR CONTADORES
            |--------------------------------------------------------------------------
            */

            var total = 0;


            Object.keys(tabelas).forEach(function (tabela) {

                document.getElementById(
                    tabelas[tabela]
                ).textContent = totais[tabela];


                total += totais[tabela];

            });


            document.getElementById(
                'qtdTotalBackup'
            ).textContent = total;


            /*
            |--------------------------------------------------------------------------
            | VALIDAR SE EXISTEM REGISTROS
            |--------------------------------------------------------------------------
            */

            if (total === 0) {

                throw new Error(
                    'Nenhum registro das tabelas operacionais foi encontrado no arquivo.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | LIBERAR CONTINUAÇÃO
            |--------------------------------------------------------------------------
            */

            botaoProsseguir.disabled = false;

        } catch (erro) {

            mostrarErro(
                erro.message ||
                'Não foi possível ler o arquivo selecionado.'
            );

        }

    });


    /*
    |--------------------------------------------------------------------------
    | ABRIR CONFIRMAÇÃO FINAL
    |--------------------------------------------------------------------------
    */

    botaoProsseguir.addEventListener('click', function () {

        modalInicial.hide();

        modalInicialEl.addEventListener(
            'hidden.bs.modal',
            function abrirModalFinal() {

                modalInicialEl.removeEventListener(
                    'hidden.bs.modal',
                    abrirModalFinal
                );

                modalFinal.show();

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | EXECUTAR RESTAURAÇÃO
    |--------------------------------------------------------------------------
    */

    botaoExecutar.addEventListener('click', function () {

        botaoExecutar.disabled = true;

        botaoExecutar.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>' +
            'Restaurando...';

        formulario.submit();

    });


    /*
    |--------------------------------------------------------------------------
    | REINICIAR BOTÃO CASO O USUÁRIO TROQUE O ARQUIVO
    |--------------------------------------------------------------------------
    */

    arquivo.addEventListener('change', function () {

        botaoProsseguir.disabled = true;

        erroEl.classList.add('d-none');

        erroEl.textContent = '';

        zerarContadores();

    });

});

</script>


<?php

require_once __DIR__ . '/includes/footer.php';

?>