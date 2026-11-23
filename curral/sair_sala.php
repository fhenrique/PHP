<?php // sair_sala.php
require 'config.php'; exigirLogin();
if(isset($_SESSION['sala_id'])){
  $pdo->prepare("UPDATE sala_jogadores SET vivo=0 WHERE sala_id=? AND usuario_id=?")->execute([$_SESSION['sala_id'], $_SESSION['usuario_id']]);
  unset($_SESSION['sala_id']);
}
header("Location: lobby.php?msg=morreu"); exit;
?>