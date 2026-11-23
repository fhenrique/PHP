
<?php

$host = '127.0.0.1';
$porta = 3306;
$banco = 'u248075441_estoqueweb';
$usuario = 'u248075441_estoqueweb';
$senha = 'Senhaestoqueweb3762'; 

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Erro ao conectar ao banco de dados.');
}
