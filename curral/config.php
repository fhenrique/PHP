<?php
//config.php
session_start();
$host = 'localhost';
$db   = 'curral';
$user = 'root';
$pass = '';
try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  die("Erro no banco: " . $e->getMessage());
}
function estaLogado(){ return isset($_SESSION['usuario_id']); }
function exigirLogin(){ if(!estaLogado()){ header("Location: index.php"); exit; } }

// 6 AVATARES - SÓ A CABEÇA, PNG COM FUNDO TRANSPARENTE
// Sem mais quadrado branco atrás no jogo nem no lobby
$AVATARES = [
  'deputado'   => ['nome'=>'',   'emoji'=>'💼', 'cor'=>'#3498db', 'img'=>'avatares/cabeca_deputado.png'],
  'senador'    => ['nome'=>'',    'emoji'=>'👔', 'cor'=>'#e74c3c', 'img'=>'avatares/cabeca_senador.png'],
  'prefeito'   => ['nome'=>'',   'emoji'=>'🏛️', 'cor'=>'#2ecc71', 'img'=>'avatares/cabeca_prefeito.png'],
  'governador' => ['nome'=>'', 'emoji'=>'⭐', 'cor'=>'#f1c40f', 'img'=>'avatares/cabeca_governador.png'],
  'presidente' => ['nome'=>'', 'emoji'=>'🇧🇷', 'cor'=>'#9b59b6', 'img'=>'avatares/cabeca_presidente.png'],
  'juiz'       => ['nome'=>'',       'emoji'=>'⚖️', 'cor'=>'#4a4048', 'img'=>'avatares/cabeca_juiz.png'],
];
?>