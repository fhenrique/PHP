<?php
require_once '../config.php';
if (!estaLogado()) exit('login');
require_once '../includes/validador_xml.php';

$validacao_id = $_GET['id'] ?? 159; // 159 é a última da sua foto
$stmt = $pdo->prepare("SELECT v.*, a.caminho, a.tipo, a.nome_arquivo FROM validacoes v JOIN arquivos a ON a.id = v.arquivo_id WHERE v.id = ?");
$stmt->execute([$validacao_id]);
$v = $stmt->fetch();
if (!$v) exit('validacao nao encontrada');

echo "<h3>Arquivo: ".htmlspecialchars($v['nome_arquivo'])." - Tipo: ".htmlspecialchars($v['tipo'])."</h3>";
echo "<p>Caminho: ".htmlspecialchars($v['caminho'])."</p>";

$conteudo = file_get_contents($v['caminho']);
echo "<h3>1. O que o extrator calculou:</h3><pre>";
print_r(extrairTotaisDCTF($conteudo));
echo "</pre>";

// Limpa namespace igual a função faz
$limpo = preg_replace('/<!--.*?-->/s', '', $conteudo);
$limpo = preg_replace('/<([\/]?)[a-zA-Z0-9]+:/', '<$1', $limpo);

echo "<h3>2. TODAS as tags que começam com vlr / tot no seu XML:</h3><pre>";
if (preg_match_all('/<(vlr\w+|tot\w+|infoCpCalc|totApur\w*)[^>]*>([^<]*)<\//i', $limpo, $m)) {
  foreach ($m[1] as $i => $tag) {
    echo str_pad($tag, 25) . " = " . $m[2][$i] . "\n";
  }
} else {
  echo "NENHUMA tag vlr* / tot* encontrada!\n";
}
echo "</pre>";

echo "<h3>3. Trecho em volta de totApur / perApur:</h3><pre>";
$pos = stripos($limpo, 'totApur');
if ($pos === false) $pos = stripos($limpo, 'perApur');
if ($pos !== false) {
  echo htmlspecialchars(substr($limpo, max(0, $pos - 500), 2000));
} else {
  echo "Não achei totApur nem perApur no arquivo!\n";
  echo htmlspecialchars(substr($limpo, 0, 3000));
}
echo "</pre>";