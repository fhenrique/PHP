<?php // logout.php
require 'config.php';
if(isset($_SESSION['sala_id']) && isset($_SESSION['usuario_id'])){
  $pdo->prepare("UPDATE sala_jogadores SET vivo=0 WHERE sala_id=? AND usuario_id=?")->execute([$_SESSION['sala_id'], $_SESSION['usuario_id']]);
}
session_destroy(); header("Location: index.php"); exit;
?>