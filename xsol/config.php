<?php
session_start();

$host = 'localhost';
$db   = 'xsolaquecedore01';
$user = 'root';
$pass = '';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Erro ao conectar no banco: " . $e->getMessage());
}

function estaLogado() {
  return !empty($_SESSION['user_id']);
}

function exigirLogin() {
  if (!estaLogado()) {
    header("Location: ../login.php"); exit;
  }
}
?>