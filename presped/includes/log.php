<?php
// includes/log.php
function registrar_log($pdo, $acao, $descricao) {
  $usuario_id = $_SESSION['usuario_id'] ?? null;
  $usuario_nome = $_SESSION['usuario_nome'] ?? 'Sistema';
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;

  $sql = "INSERT INTO logs_atividades (usuario_id, usuario_nome, acao, descricao, ip) VALUES (?, ?, ?, ?, ?)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$usuario_id, $usuario_nome, $acao, $descricao, $ip]);
}
?>