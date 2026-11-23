<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

$host = 'localhost';
$db   = 'serenita';
$user = 'root';
$pass = '';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Erro ao conectar: " . $e->getMessage());
}

function estaLogado() {
  return !empty($_SESSION['spa_id']);
}

function exigeLogin() {
  if (!estaLogado()) {
    // Se estiver dentro de pages/, sobe um nível. Se estiver na raiz, fica na raiz.
    // Isso evita o erro de ir para ../index.php que não existe
    $na_pasta_pages = str_contains($_SERVER['PHP_SELF'], '/pages/');
    header("Location: " . ($na_pasta_pages ? '../login.php' : 'login.php'));
    exit;
  }
}

function perfilAtual() {
  return $_SESSION['perfil'] ?? 'Administrador';
}

function exigePerfil($perfisPermitidos) {
  $perfil = perfilAtual();
  if (!in_array($perfil, $perfisPermitidos)) {
    header("Location: dashboard.php?erro=sem_permissao");
    exit;
  }
}

function soLeitura() {
  return perfilAtual() === 'Visualizador';
}

function podeEditar() {
  return !soLeitura();
}

function eAdmin() {
  return perfilAtual() === 'Administrador';
}

function eProfissional() {
  return perfilAtual() === 'Profissional';
}