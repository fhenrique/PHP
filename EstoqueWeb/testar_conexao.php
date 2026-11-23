
<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

$host = '127.0.0.1';
$porta = 3306;
$banco = 'u248075441_estoqueweb';
$usuario = 'u248075441_estoqueweb';

// Copie aqui a senha atual do config/conexao.php
$senha = 'Senhaestoqueweb3762';

echo '<h2>Diagnóstico de conexão - EstoqueWeb</h2>';

echo '<p>PHP: ' . PHP_VERSION . '</p>';
echo '<p>Extensão PDO: ' .
    (extension_loaded('pdo_mysql') ? 'Disponível' : 'Não disponível') .
    '</p>';

try {

    $inicio = microtime(true);

    $pdoTeste = new PDO(
        "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]
    );

    $tempo = round(microtime(true) - $inicio, 3);

    $resultado = $pdoTeste->query(
        "SELECT VERSION() AS versao, DATABASE() AS banco"
    )->fetch(PDO::FETCH_ASSOC);

    echo '<h3 style="color:green">CONEXÃO REALIZADA COM SUCESSO!</h3>';

    echo '<p>Banco: ' .
        htmlspecialchars($resultado['banco']) . '</p>';

    echo '<p>Versão MySQL/MariaDB: ' .
        htmlspecialchars($resultado['versao']) . '</p>';

    echo '<p>Tempo de conexão: ' . $tempo . ' segundos</p>';

} catch (PDOException $e) {

    echo '<h3 style="color:red">FALHA NA CONEXÃO!</h3>';

    echo '<p><strong>Mensagem:</strong> ' .
        htmlspecialchars($e->getMessage()) . '</p>';

    echo '<p><strong>Código:</strong> ' .
        htmlspecialchars((string)$e->getCode()) . '</p>';
}
