<?php
require 'config.php';
if(!isset($_SESSION['usuario_id'])) exit;
$usuario_id = $_SESSION['usuario_id'];
$sala_id = (int)($_POST['sala_id'] ?? $_GET['sala_id'] ?? 0);
if($sala_id <= 0) exit;

// Marca que você está vivo AGORA nessa sala
$pdo->prepare("INSERT INTO sala_jogadores (sala_id, usuario_id, vivo, atualizado_em) VALUES (?,?,1,NOW())
ON DUPLICATE KEY UPDATE vivo=1, atualizado_em=NOW()")->execute([$sala_id, $usuario_id]);

// Se não tiver chave única, usa esse jeito mais simples - apaga e insere de novo:
// $pdo->prepare("DELETE FROM sala_jogadores WHERE sala_id=? AND usuario_id=?")->execute([$sala_id, $usuario_id]);
// $pdo->prepare("INSERT INTO sala_jogadores (sala_id, usuario_id, vivo, atualizado_em) VALUES (?,?,1,NOW())")->execute([$sala_id, $usuario_id]);
echo "ok";