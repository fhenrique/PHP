<?php
// C:\xampp\htdocs\presped\config.php
date_default_timezone_set('America/Sao_Paulo');
session_start();

$host = 'localhost';
$db   = 'spedfacil_db';
$user = 'root';
$pass = '';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
  die("Erro de conexão com o spedfacil_db: " . $e->getMessage());
}

function estaLogado() {
  return isset($_SESSION['escritorio_id']);
}

function exigirLogin() {
  if (!estaLogado()) {
    header("Location: /presped/index.php");
    exit;
  }
}

// --- CONTROLE DE USUARIOS E PERFIS ---

function perfilAtual() {
  return $_SESSION['usuario_perfil'] ?? 'visualizador';
}

function ehAdmin() {
  return perfilAtual() === 'admin';
}

function exigePerfil($perfis_permitidos) {
  exigirLogin();
  $perfil = perfilAtual();
  if (!in_array($perfil, (array)$perfis_permitidos)) {
    header("Location: /presped/pages/dashboard.php?erro=sem_permissao");
    exit;
  }
}

function pode($perfis_permitidos) {
  return in_array(perfilAtual(), (array)$perfis_permitidos);
}

function nomeUsuarioLogado() {
  return $_SESSION['usuario_nome'] ?? $_SESSION['escritorio_nome'] ?? 'Usuário';
}