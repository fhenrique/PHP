<?php
require_once 'config.php';

// Gera o hash certo para 123456
$senha_correta = password_hash('123456', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE spas SET senha = ? WHERE email = ?");
$stmt->execute([$senha_correta, 'teste@serenita.com']);

echo "Senha resetada com sucesso!<br>";
echo "Agora entra com: teste@serenita.com / 123456<br>";
echo "APAGUE esse arquivo reset.php depois!";