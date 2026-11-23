<?php require 'config.php'; exigirLogin();
$sala_id = (int)($_GET['sala_id'] ?? 0);
$usuario_id = $_SESSION['usuario_id'];
if(!$sala_id){ header("Location: lobby.php"); exit; }
// Conta quantos vivos tem
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sala_jogadores WHERE sala_id=? AND vivo=1");
$stmt->execute([$sala_id]); $total = (int)$stmt->fetchColumn();
if($total >= 10){ header("Location: lobby.php?msg=lotada"); exit; }
// Já está na sala? Só reativa
$stmt = $pdo->prepare("SELECT id FROM sala_jogadores WHERE sala_id=? AND usuario_id=?");
$stmt->execute([$sala_id,$usuario_id]);
if($stmt->fetch()){
  $pdo->prepare("UPDATE sala_jogadores SET vivo=1 WHERE sala_id=? AND usuario_id=?")->execute([$sala_id,$usuario_id]);
} else {
  $pdo->prepare("INSERT INTO sala_jogadores (sala_id, usuario_id, cor, avatar) VALUES (?,?,?,?)")
    ->execute([$sala_id,$usuario_id,$_SESSION['usuario_cor'] ?? '#3498db', $_SESSION['usuario_avatar'] ?? 'deputado']);
}
$_SESSION['sala_id'] = $sala_id;
// Na Parte 2 aqui vai para jogo.php
header("Location: jogo.php?sala_id=$sala_id"); exit;


?>