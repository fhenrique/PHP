<?php
//api_sair.php
require 'config.php';
if(!isset($_SESSION['usuario_id'])) exit;
$sala_id = (int)($_POST['sala_id'] ?? 0);
if($sala_id > 0){
  $pdo->prepare("DELETE FROM sala_jogadores WHERE sala_id=? AND usuario_id=?")->execute([$sala_id, $_SESSION['usuario_id']]);
}