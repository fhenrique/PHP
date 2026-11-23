<?php
// C:\xampp2\htdocs\presped\pages\validacao-lote.php
// LOTE EM LEVAS DE 5
// CORRIGIDO: competencias com ON DUPLICATE KEY UPDATE + transação + sem duplicar totais ao revalidar
// CORRIGIDO 2: tabelas eSocial (S-1000, S-1010, S-1020...) não têm perApur, só iniValid -> usa competência da tela / forçada
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp2/htdocs/presped/logs/php-error.log');
error_reporting(E_ALL);
ob_start();

set_time_limit(120);
ini_set('memory_limit', '1024M');

require_once '../config.php';
require_once '../includes/validador.php';
require_once '../includes/validador_xml.php';

if (!defined('SPED_LOTE_FUNCOES_ONLY') && !estaLogado()) {
  if (isset($_GET['ajax'])) {
    if (ob_get_length()) ob_end_clean();
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
    exit;
  }
  header("Location: ../index.php?aba=login");
  exit;
}

$escritorio_id = $_SESSION['escritorio_id'] ?? 0;

if (!defined('SPED_LOTE_FUNCOES_ONLY')) {
  $stmt = $pdo->prepare("SELECT * FROM empresas WHERE escritorio_id = ? ORDER BY razao_social ASC");
  $stmt->execute([$escritorio_id]);
  $empresas = $stmt->fetchAll();
} else {
  $empresas = [];
}

function acharEmpresaPorCnpjLote($empresas, $cnpj_arquivo) {
  $cnpj_arquivo = preg_replace('/[^0-9]/', '', $cnpj_arquivo ?? '');
  if (strlen($cnpj_arquivo) !== 14) return null;
  foreach ($empresas as $e) {
    $cnpj_cad = preg_replace('/[^0-9]/', '', $e['cnpj'] ?? '');
    if ($cnpj_cad === $cnpj_arquivo) return $e;
  }
  return null;
}

function formataCnpjLote($cnpj) {
  $cnpj = preg_replace('/[^0-9]/', '', $cnpj ?? '');
  if (strlen($cnpj) !== 14) return $cnpj;
  return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
}

function detectarCompetenciaPorNome($nome_arquivo) {
  $nome = $nome_arquivo;
  if (preg_match('/(20\d{2})[-_](0[1-9]|1[0-2])/', $nome, $m)) return $m[1] . '-' . $m[2];
  if (preg_match('/(0[1-9]|1[0-2])[-_](20\d{2})/', $nome, $m)) return $m[2] . '-' . $m[1];
  if (preg_match('/(20\d{2})(0[1-9]|1[0-2])(?!\d)/', $nome, $m)) return $m[1] . '-' . $m[2];
  if (preg_match('/(?<!\d)(0[1-9]|1[0-2])(20\d{2})/', $nome, $m)) return $m[2] . '-' . $m[1];
  $meses = [
    'jan' => '01', 'janeiro' => '01', 'january' => '01',
    'fev' => '02', 'fevereiro' => '02', 'feb' => '02', 'february' => '02',
    'mar' => '03', 'marco' => '03', 'março' => '03', 'march' => '03',
    'abr' => '04', 'abril' => '04', 'apr' => '04', 'april' => '04',
    'mai' => '05', 'maio' => '05', 'may' => '05',
    'jun' => '06', 'junho' => '06', 'june' => '06',
    'jul' => '07', 'julho' => '07', 'july' => '07',
    'ago' => '08', 'agosto' => '08', 'aug' => '08', 'august' => '08',
    'set' => '09', 'setembro' => '09', 'sep' => '09', 'sept' => '09', 'september' => '09',
    'out' => '10', 'outubro' => '10', 'oct' => '10', 'october' => '10',
    'nov' => '11', 'novembro' => '11', 'november' => '11',
    'dez' => '12', 'dezembro' => '12', 'dec' => '12', 'december' => '12',
  ];
  $nome_lower = mb_strtolower($nome, 'UTF-8');
  $nome_lower = strtr($nome_lower, ['ç' => 'c', 'ã' => 'a', 'õ' => 'o', 'á' => 'a', 'à' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'ú' => 'u']);
  foreach ($meses as $nome_mes => $num_mes) {
    if (preg_match('/' . $nome_mes . '[-_ ]*(20\d{2})/', $nome_lower, $m)) return $m[1] . '-' . $num_mes;
    if (preg_match('/(20\d{2})[-_ ]*' . $nome_mes . '/', $nome_lower, $m)) return $m[1] . '-' . $num_mes;
  }
  return '';
}

// --- CORRIGIDO: agora entende tabela (iniValid) e usa competência da tela quando for tabela ---
function detectarCompetenciaPorConteudoXml($conteudo, $competencia_tela = '') {
  $competencia_tela = str_replace('/', '-', trim((string)$competencia_tela));
  $tela_valida = preg_match('/^\d{4}-\d{2}$/', $competencia_tela) ? $competencia_tela : '';
  $limpo = preg_replace('/<([\/]?)[a-zA-Z0-9]+:/', '<$1', $conteudo);
  $padroes = [
    '/<perApur>\s*(20\d{2})[-_\/.]?(0[1-9]|1[0-2])\s*<\/perApur>/i',
    '/<dtPerApur>\s*(20\d{2})[-_\/.]?(0[1-9]|1[0-2])\s*<\/dtPerApur>/i',
    '/<perApuracao>\s*(20\d{2})[-_\/.]?(0[1-9]|1[0-2])\s*<\/perApuracao>/i',
    '/<dtApuracao>\s*(20\d{2})[-_\/.]?(0[1-9]|1[0-2])\s*<\/dtApuracao>/i',
    '/<competencia>\s*(20\d{2})[-_\/.]?(0[1-9]|1[0-2])\s*<\/competencia>/i',
    '/<anoMes>\s*(20\d{2})[-_\/.]?(0[1-9]|1[0-2])\s*<\/anoMes>/i',
    '/<perApur>\s*(0[1-9]|1[0-2])[\/\-](20\d{2})\s*<\/perApur>/i',
    '/<dtPerApur>\s*(0[1-9]|1[0-2])[\/\-](20\d{2})\s*<\/dtPerApur>/i',
  ];
  foreach ($padroes as $i => $padrao) {
    if (preg_match($padrao, $limpo, $m)) {
      if ($i >= 6) return $m[2] . '-' . $m[1];
      return $m[1] . '-' . $m[2];
    }
  }
  if (preg_match('/perApur[^0-9]{0,5}(20\d{2})[-_\/.]?(0[1-9]|1[0-2])/', $limpo, $m)) return $m[1] . '-' . $m[2];
  if (preg_match('/perApur[^0-9]{0,5}(0[1-9]|1[0-2])[\/\-](20\d{2})/', $limpo, $m)) return $m[2] . '-' . $m[1];

  // NOVO 1: Tabelas eSocial - S-1000, S-1010, S-1020, S-1030, S-1040, S-1050, S-1060, S-1070, S-1080
  // Elas não têm competência de movimento, só iniValid. Usa a competência da tela.
  if (preg_match('/<iniValid>\s*(20\d{2})[-_\/.]?(0[1-9]|1[0-2])/', $limpo, $m)) {
    if ($tela_valida) return $tela_valida;
    return $m[1] . '-' . $m[2];
  }
  // NOVO 2: Admissão / Desligamento sem perApur
  if (preg_match('/<dtAdm>\s*(20\d{2})-(0[1-9]|1[0-2])-\d{2}/', $limpo, $m)) return $m[1] . '-' . $m[2];
  if (preg_match('/<dtDeslig>\s*(20\d{2})-(0[1-9]|1[0-2])-\d{2}/', $limpo, $m)) return $m[1] . '-' . $m[2];
  // NOVO 3: Se for evento de tabela mas sem iniValid (variação), usa a tela pra não dar erro
  if ($tela_valida && (
    stripos($limpo, 'evtInfoEmpregador') !== false ||
    stripos($limpo, 'evtTabRubrica') !== false ||
    stripos($limpo, 'evtTabLotacao') !== false ||
    stripos($limpo, 'evtTabCargo') !== false ||
    stripos($limpo, 'evtTabFuncao') !== false ||
    stripos($limpo, 'evtTabHorario') !== false ||
    stripos($limpo, 'evtTabAmbiente') !== false ||
    stripos($limpo, 'evtTabProcesso') !== false ||
    stripos($limpo, 'evtTabOperPort') !== false
  )) {
    return $tela_valida;
  }
  return '';
}

function extrairCnpjXml($conteudo, $empresas = []) {
  $limpo = preg_replace('/<([\/]?)[a-zA-Z0-9]+:/', '<$1', $conteudo);
  if (preg_match('/<ide(?:Contri|Empregador)>.*?<nrInsc>(\d{14})<\/nrInsc>/s', $limpo, $m)) return $m[1];
  if (preg_match('/<(?:nrInsc|cnpj|nrInscContrib)>(\d{14})<\/(?:nrInsc|cnpj|nrInscContrib)>/', $limpo, $m)) return $m[1];
  if (preg_match('/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/', $limpo, $m)) return preg_replace('/[^0-9]/', '', $m[1]);
  if (preg_match('/nrInsc[^0-9]{0,10}(\d{14})/', $limpo, $m)) return $m[1];
  if (preg_match('/<nrInsc>(\d{8})<\/nrInsc>/', $limpo, $m8)) {
    foreach ($empresas as $e) {
      $cnpj_cad = preg_replace('/[^0-9]/', '', $e['cnpj'] ?? '');
      if (substr($cnpj_cad, 0, 8) === $m8[1]) return $cnpj_cad;
    }
  }
  return '';
}

function processarUmArquivo($pdo, $empresas, $escritorio_id, $nome_original, $arquivo_tmp, $erro_upload, $indice, $competencia_forcada = '') {
  $item = [
    'arquivo' => $nome_original,
    'empresa' => '-',
    'tipo' => '-',
    'competencia' => '-',
    'status' => 'erro',
    'mensagem' => '',
    'validacao_id' => null,
    'cnpj_nao_cadastrado' => '',
    'vlr_total' => 0,
  ];
  try {
    return processarUmArquivoInterno($pdo, $empresas, $escritorio_id, $nome_original, $arquivo_tmp, $erro_upload, $indice, $item, $competencia_forcada);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[presped] processarUmArquivo ' . $nome_original . ' : ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    $item['mensagem'] = 'Erro interno ao processar: ' . $e->getMessage();
    return $item;
  }
}

function processarUmArquivoInterno($pdo, $empresas, $escritorio_id, $nome_original, $arquivo_tmp, $erro_upload, $indice, $item, $competencia_forcada = '') {
  $ext = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

  if ($erro_upload !== UPLOAD_ERR_OK || !$arquivo_tmp || !file_exists($arquivo_tmp)) {
    $item['mensagem'] = 'Falha ao enviar o arquivo.';
    return $item;
  }
  if (!in_array($ext, ['txt', 'xml'])) {
    $item['mensagem'] = 'Só aceito .txt ou .xml';
    return $item;
  }

  // Competência que veio da tela (upload.php) - tabelas usam ela
  $competencia_tela = str_replace('/', '-', trim((string)($competencia_forcada ?: ($_POST['competencia'] ?? $_GET['competencia'] ?? ''))));
  if (!preg_match('/^\d{4}-\d{2}$/', $competencia_tela)) $competencia_tela = '';

  $cnpj_detectado = '';
  $competencia_detectada = '';
  $tipo_detectado = '';

  if ($ext === 'xml') {
    $conteudo_tmp = file_get_contents($arquivo_tmp);
    if ($conteudo_tmp === false) $conteudo_tmp = '';
  } else {
    $handle = fopen($arquivo_tmp, 'r');
    $conteudo_tmp = fread($handle, 2 * 1024 * 1024);
    fclose($handle);
    if (strpos($conteudo_tmp, '|0000|') === false) {
      $handle = fopen($arquivo_tmp, 'r');
      while (($linha = fgets($handle)) !== false) {
        if (strpos($linha, '|0000|') !== false) { $conteudo_tmp .= $linha; break; }
        if (ftell($handle) > 500000) break;
      }
      fclose($handle);
    }
  }

  if ($ext === 'xml') {
    $nome_lower = mb_strtolower($nome_original, 'UTF-8');
    if (strpos($nome_lower, 'reinf') !== false) {
      $tipo_detectado = 'EFD-Reinf';
    } elseif (strpos($nome_lower, 'esocial') !== false || strpos($nome_lower, 'e-social') !== false) {
      $tipo_detectado = 'eSocial';
    } elseif (stripos($conteudo_tmp, 'Reinf') !== false || stripos($conteudo_tmp, 'evtInfoContri') !== false || stripos($conteudo_tmp, 'evtTotal') !== false || stripos($conteudo_tmp, 'evtRet') !== false || stripos($conteudo_tmp, 'evtServTom') !== false || stripos($conteudo_tmp, 'R-1000') !== false || stripos($conteudo_tmp, 'R-2000') !== false || stripos($conteudo_tmp, 'R-2010') !== false || stripos($conteudo_tmp, 'R-4000') !== false) {
      $tipo_detectado = 'EFD-Reinf';
    } elseif (stripos($conteudo_tmp, 'eSocial') !== false || stripos($conteudo_tmp, 'evtAdmissao') !== false || stripos($conteudo_tmp, 'evtInfoEmpregador') !== false || stripos($conteudo_tmp, 'evtRemun') !== false || stripos($conteudo_tmp, 'evtDeslig') !== false || stripos($conteudo_tmp, 'evtPgtos') !== false || stripos($conteudo_tmp, 'evtTabRubrica') !== false || stripos($conteudo_tmp, 'evtTabLotacao') !== false || stripos($conteudo_tmp, 'evtTabCargo') !== false || stripos($conteudo_tmp, 'evtTabFuncao') !== false || stripos($conteudo_tmp, 'evtTabHorario') !== false || stripos($conteudo_tmp, 'evtTabAmbiente') !== false || stripos($conteudo_tmp, 'evtTabProcesso') !== false || stripos($conteudo_tmp, 'evtTabOperPort') !== false || stripos($conteudo_tmp, 'S-1000') !== false || stripos($conteudo_tmp, 'S-1200') !== false) {
      $tipo_detectado = 'eSocial';
    }
    $cnpj_detectado = extrairCnpjXml($conteudo_tmp, $empresas);
    $competencia_detectada = detectarCompetenciaPorConteudoXml($conteudo_tmp, $competencia_tela);
    if (!$competencia_detectada) $competencia_detectada = detectarCompetenciaPorNome($nome_original);
  } else {
    if (strpos($conteudo_tmp, '|A100|') !== false || strpos($conteudo_tmp, '|F100|') !== false || strpos($conteudo_tmp, '|M100|') !== false || strpos($conteudo_tmp, '|P100|') !== false) {
      $tipo_detectado = 'EFD Contribuições';
    } else {
      $tipo_detectado = 'EFD ICMS/IPI';
    }
    if (preg_match('/\|0000\|[^|]*\|[^|]*\|(\d{8})\|(\d{8})\|[^|]*\|(\d{14})\|/', $conteudo_tmp, $m)) {
      $competencia_detectada = substr($m[1], 4, 4) . '-' . substr($m[1], 2, 2);
      $cnpj_detectado = $m[3];
    }
    if (!$cnpj_detectado && preg_match('/\|0000\|([^\r\n]*)/', $conteudo_tmp, $m0000)) {
      $linha0000 = $m0000[1];
      if (preg_match('/\|(\d{8})\|/', $linha0000, $mData)) $competencia_detectada = substr($mData[1], 4, 4) . '-' . substr($mData[1], 2, 2);
      if (preg_match('/\|(\d{14})\|/', $linha0000, $mCnpj)) $cnpj_detectado = $mCnpj[1];
    }
    if (!$cnpj_detectado && preg_match('/(\d{14})/', $conteudo_tmp, $mQualquer)) {
      if ($mQualquer[1] !== '00000000000000') $cnpj_detectado = $mQualquer[1];
    }
    if (!$competencia_detectada) $competencia_detectada = detectarCompetenciaPorNome($nome_original);
  }

  $empresa_detectada = $cnpj_detectado ? acharEmpresaPorCnpjLote($empresas, $cnpj_detectado) : null;
  if (!$empresa_detectada) {
    $item['mensagem'] = $cnpj_detectado ? 'CNPJ ' . formataCnpjLote($cnpj_detectado) . ' não cadastrado.' : 'Não consegui detectar o CNPJ do arquivo.';
    $item['cnpj_nao_cadastrado'] = $cnpj_detectado;
    return $item;
  }
  if (!$competencia_detectada) {
    $item['empresa'] = $empresa_detectada['razao_social'];
    $item['tipo'] = $tipo_detectado ?: '-';
    $item['mensagem'] = 'Não consegui detectar a competência do arquivo.';
    return $item;
  }

  $empresa_id = $empresa_detectada['id'];
  $competencia = $competencia_detectada;
  $tipo = $tipo_detectado ?: 'EFD ICMS/IPI';
  $item['empresa'] = $empresa_detectada['razao_social'];
  $item['tipo'] = $tipo;
  $item['competencia'] = $competencia;

  $subpasta = ($ext === 'xml') ? 'xml/' : 'txt/';
  $pasta = '../arquivos/' . $subpasta;
  if (!is_dir($pasta)) mkdir($pasta, 0777, true);
  $cnpj_limpo = preg_replace('/[^0-9]/', '', $empresa_detectada['cnpj'] ?? '');
  if (!$cnpj_limpo) $cnpj_limpo = 'SEM-CNPJ';
  $sufixo_hora = date('His') . '_' . $indice . '_' . uniqid();
  $nome_novo = 'SPED_' . $cnpj_limpo . '_TEMP_' . str_replace('/', '', $competencia) . '_' . $sufixo_hora . '.' . $ext;
  $caminho = $pasta . $nome_novo;
  if (!move_uploaded_file($arquivo_tmp, $caminho)) {
    if (!copy($arquivo_tmp, $caminho)) {
      $item['mensagem'] = 'Erro ao salvar o arquivo.';
      return $item;
    }
  }

  // ===== TRANSAÇÃO: ou salva tudo, ou não salva nada. Nunca mais dado pela metade no Simulador =====
  $pdo->beginTransaction();
  try {
    $tamanho = filesize($caminho);
    $stmt = $pdo->prepare("INSERT INTO arquivos (escritorio_id, empresa_id, tipo, competencia, nome_arquivo, caminho, tamanho) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$escritorio_id, $empresa_id, $tipo, $competencia, $nome_original, $caminho, $tamanho]);
    $arquivo_id = $pdo->lastInsertId();

    $erros = [];
    $erros_xml_formatado = [];
    $resumo = '';
    $tipo_final = $tipo;
    $resultado_xml = null;

    if ($ext === 'xml') {
      try {
        $resultado_xml = validar_xml_sped($caminho, $empresa_detectada['cnpj'] ?? '');
      } catch (Throwable $e) {
        error_log('[presped] validar_xml_sped ' . $nome_original . ' : ' . $e->getMessage());
        $resultado_xml = ['tipo' => $tipo, 'evento' => $tipo, 'erros' => ['Erro interno do validador XML: ' . $e->getMessage()], 'avisos' => [], 'totais' => null];
      }
      if (!empty($resultado_xml['tipo']) && $resultado_xml['tipo'] !== 'Desconhecido') $tipo_final = $resultado_xml['tipo'];
      $erros = $resultado_xml['erros'] ?? [];
      $avisos = $resultado_xml['avisos'] ?? [];
      foreach ($erros as $msg_erro) {
        $erros_xml_formatado[] = ['nota' => $resultado_xml['evento'] ?? $tipo_final, 'serie' => '', 'data_doc' => $competencia, 'vl_c100' => 0, 'soma_c170' => 0, 'diferenca' => 0, 'linha' => 0, 'tipo' => 'XML', 'mensagem' => $msg_erro];
      }
      foreach ($avisos as $msg_aviso) {
        $erros_xml_formatado[] = ['nota' => $resultado_xml['evento'] ?? $tipo_final, 'serie' => '', 'data_doc' => $competencia, 'vl_c100' => 0, 'soma_c170' => 0, 'diferenca' => 0, 'linha' => 0, 'tipo' => 'AVISO', 'mensagem' => $msg_aviso];
      }
      $total_erros = count($erros);
      $status = $total_erros > 0 ? 'erro' : 'ok';
      $resumo = $total_erros > 0 ? $total_erros . ' erro(s) no XML - ' . $tipo_final . ' ' . ($resultado_xml['evento'] ?? '') : 'XML válido - ' . $tipo_final . ' ' . ($resultado_xml['evento'] ?? '') . (count($avisos) > 0 ? ' (' . count($avisos) . ' aviso(s))' : '');
      $erros = $erros_xml_formatado;
    } else {
      $tipo_final = $tipo;
      $erros = validarSped($caminho);
      $total_erros = count($erros);
      $status = $total_erros > 0 ? 'erro' : 'ok';
      $resumo = $total_erros > 0 ? $total_erros . ' nota(s) com divergência C100 x C170 / C190' : 'Sem divergências C100 x C170 / C190';
    }

    $item['tipo'] = $tipo_final;
    if ($tipo_final === 'EFD-Reinf') $sigla_final = 'REINF';
    elseif ($tipo_final === 'eSocial') $sigla_final = 'ESOCIAL';
    else $sigla_final = 'EFD';
    $nome_final = 'SPED_' . $cnpj_limpo . '_' . $sigla_final . '_' . str_replace('/', '', $competencia) . '_' . $sufixo_hora . '.' . $ext;
    $caminho_final = $pasta . $nome_final;
    if ($nome_final !== $nome_novo && rename($caminho, $caminho_final)) $caminho = $caminho_final;

    $stmt_upd = $pdo->prepare("UPDATE arquivos SET tipo = ?, caminho = ? WHERE id = ?");
    $stmt_upd->execute([$tipo_final, $caminho, $arquivo_id]);

    $stmt = $pdo->prepare("INSERT INTO validacoes (arquivo_id, empresa_id, status, total_erros, resumo) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$arquivo_id, $empresa_id, $status, $total_erros, $resumo]);
    $validacao_id = $pdo->lastInsertId();

    if ($ext === 'xml' && !empty($resultado_xml['totais'])) {
      $t = $resultado_xml['totais'];
      $item['vlr_total'] = (float)($t['vlr_total'] ?? 0);
      $stmt_del = $pdo->prepare("DELETE vt FROM validacoes_totais vt JOIN validacoes v ON v.id = vt.validacao_id WHERE v.empresa_id = ? AND vt.competencia = ? AND vt.tipo = ? AND vt.validacao_id <> ?");
      $stmt_del->execute([$empresa_id, $competencia, $tipo_final, $validacao_id]);
      $stmt = $pdo->prepare("INSERT INTO validacoes_totais (validacao_id, empresa_id, competencia, tipo, vlr_inss_segurado, vlr_inss_patronal, vlr_rat, vlr_terceiros, vlr_retencao, vlr_total) VALUES (?,?,?,?,?,?,?,?,?,?)");
      $stmt->execute([$validacao_id, $empresa_id, $competencia, $tipo_final, $t['vlr_inss_segurado'] ?? 0, $t['vlr_inss_patronal'] ?? 0, $t['vlr_rat'] ?? 0, $t['vlr_terceiros'] ?? 0, $t['vlr_retencao'] ?? 0, $t['vlr_total'] ?? 0]);
    }

    foreach ($erros as $e) {
      $stmt = $pdo->prepare("INSERT INTO validacoes_erros (validacao_id, nota, serie, data_doc, vl_c100, soma_c170, diferenca, linha, tipo, mensagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->execute([$validacao_id, $e['nota'], $e['serie'], $e['data_doc'], $e['vl_c100'], $e['soma_c170'], $e['diferenca'], $e['linha'], $e['tipo'], $e['mensagem']]);
    }

    $partes_comp = explode('-', str_replace('/', '-', $competencia));
    $ano_comp = (int)($partes_comp[0] ?? date('Y'));
    $mes_comp = (int)($partes_comp[1] ?? date('m'));
    $stmt = $pdo->prepare("INSERT INTO competencias (empresa_id, mes, ano, status, atualizado_em) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), atualizado_em = NOW()");
    $stmt->execute([$empresa_id, $mes_comp, $ano_comp, $status]);

    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    if (file_exists($caminho)) unlink($caminho);
    throw $e;
  }

  $item['status'] = $status;
  $item['mensagem'] = $resumo;
  $item['validacao_id'] = $validacao_id;
  return $item;
}

if (defined('SPED_LOTE_FUNCOES_ONLY') && SPED_LOTE_FUNCOES_ONLY) {
  return;
}

if (isset($_GET['ajax']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivos'])) {
  set_error_handler(function($sev, $msg, $file, $line) {
    if (!(error_reporting() & $sev)) return false;
    throw new ErrorException($msg, 0, $sev, $file, $line);
  });
  $resultados = [];
  $total = count($_FILES['arquivos']['name']);
  for ($i = 0; $i < $total; $i++) {
    try {
      $resultados[] = processarUmArquivo($pdo, $empresas, $escritorio_id, $_FILES['arquivos']['name'][$i], $_FILES['arquivos']['tmp_name'][$i], $_FILES['arquivos']['error'][$i], time() . '_' . $i);
    } catch (Throwable $e) {
      error_log('[presped] lote ajax ' . $_FILES['arquivos']['name'][$i] . ' : ' . $e->getMessage());
      $resultados[] = ['arquivo' => $_FILES['arquivos']['name'][$i], 'empresa' => '-', 'tipo' => '-', 'competencia' => '-', 'status' => 'erro', 'mensagem' => 'Erro interno: ' . $e->getMessage(), 'validacao_id' => null, 'cnpj_nao_cadastrado' => '', 'vlr_total' => 0];
    }
    gc_collect_cycles();
  }
  restore_error_handler();
  if (ob_get_length()) ob_end_clean();
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true, 'resultados' => $resultados]);
  exit;
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="d-flex flex-wrap gap-3">
      <a href="dashboard.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Voltar para Dashboard</a>
    </div>
    <h2 class="fw-bold mb-0 mt-1">Validação em lote</h2>
    <p class="text-muted mb-0">Selecione vários arquivos .txt ou .xml de uma vez. O sistema detecta empresa, tipo e competência de cada um sozinho.</p>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-4">
    <form id="formLote" method="POST" enctype="multipart/form-data">
      <label class="form-label fw-bold">Arquivos SPED (.txt ou .xml) — pode selecionar vários</label>
      <input type="file" id="inputArquivos" name="arquivos[]" class="form-control" accept=".txt,.xml" multiple required>
      <small class="text-muted">Dica: no Windows, segure <strong>Ctrl</strong> e clique em vários arquivos, ou <strong>Ctrl + A</strong> pra selecionar a pasta toda.</small>
      <div id="areaProgresso" class="d-none mt-3">
        <div class="d-flex justify-content-between small mb-1">
          <span id="textoProgresso" class="fw-bold">Enviando...</span>
          <span id="porcentagemProgresso">0%</span>
        </div>
        <div class="progress" style="height: 22px;">
          <div id="barraProgresso" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div>
        </div>
      </div>
      <button type="submit" id="btnEnviar" class="btn btn-primary btn-lg w-100 mt-3"><i class="bi bi-cloud-upload"></i> Validar todos os arquivos</button>
    </form>
  </div>
</div>

<div id="areaResultado"></div>

<script>
document.getElementById('formLote').addEventListener('submit', async function(e) {
  e.preventDefault();
  const input = document.getElementById('inputArquivos');
  const arquivos = Array.from(input.files);
  if (arquivos.length === 0) { alert('Selecione pelo menos 1 arquivo.'); return; }
  const btn = document.getElementById('btnEnviar');
  const areaProg = document.getElementById('areaProgresso');
  const barra = document.getElementById('barraProgresso');
  const textoProg = document.getElementById('textoProgresso');
  const porcProg = document.getElementById('porcentagemProgresso');
  const areaResultado = document.getElementById('areaResultado');
  btn.disabled = true;
  btn.innerHTML = 'Validando... aguarde';
  areaProg.classList.remove('d-none');
  areaResultado.innerHTML = '';
  document.getElementById('tabelaResultado')?.remove();
  const TAMANHO_LEVA = 5;
  let todosResultados = [];
  let processados = 0;
  for (let inicio = 0; inicio < arquivos.length; inicio += TAMANHO_LEVA) {
    const leva = arquivos.slice(inicio, inicio + TAMANHO_LEVA);
    const formData = new FormData();
    leva.forEach(f => formData.append('arquivos[]', f));
    textoProg.textContent = `Validando leva ${Math.floor(inicio / TAMANHO_LEVA) + 1} de ${Math.ceil(arquivos.length / TAMANHO_LEVA)} — ${leva.map(f => f.name).join(', ')}`;
    try {
      const resp = await fetch('validacao-lote.php?ajax=1', { method: 'POST', body: formData });
      const texto = await resp.text();
      let json;
      try { json = JSON.parse(texto); } catch (err) { throw new Error('Servidor devolveu HTML em vez de JSON. Veja o log em presped/logs/php-error.log. Trecho: ' + texto.substring(0, 300)); }
      if (!json.ok) throw new Error(json.erro || 'Erro no servidor');
      todosResultados = todosResultados.concat(json.resultados);
    } catch (err) {
      leva.forEach(f => todosResultados.push({ arquivo: f.name, empresa: '-', tipo: '-', competencia: '-', status: 'erro', mensagem: 'Erro ao processar leva: ' + err.message, validacao_id: null, cnpj_nao_cadastrado: '', vlr_total: 0 }));
    }
    processados += leva.length;
    const pct = Math.round((processados / arquivos.length) * 100);
    barra.style.width = pct + '%';
    barra.textContent = pct + '%';
    porcProg.textContent = pct + '%';
  }
  const qtd_ok = todosResultados.filter(r => r.status === 'ok').length;
  const qtd_erro = todosResultados.length - qtd_ok;
  const totalLote = todosResultados.reduce((s, r) => s + (parseFloat(r.vlr_total) || 0), 0);
  function esc(s){ const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
  function brl(v){ return 'R$ ' + (parseFloat(v) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  let html = `
  <div class="alert alert-${qtd_erro > 0 ? 'warning' : 'success'} rounded-4">
    <strong>${todosResultados.length} arquivos processados:</strong> ${qtd_ok} OK e ${qtd_erro} com erro / não validado.
    ${totalLote > 0 ? `<br><strong>Total somado no lote: ${brl(totalLote)}</strong>` : ''}
  </div>
  <div class="card border-0 shadow-sm rounded-4" id="tabelaResultado">
    <div class="card-header bg-white"><span class="fw-bold">Resultado do lote</span></div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Arquivo</th><th>Empresa</th><th>Tipo</th><th>Competência</th><th>Valor</th><th>Resultado</th><th>Ação</th></tr></thead>
        <tbody>`;
  todosResultados.forEach(r => {
    const salvo = !!r.validacao_id;
    const badge = r.status === 'ok'
      ? `<span class="badge bg-success">OK</span><br><small class="text-muted">${esc(r.mensagem)}</small>`
      : salvo
        ? `<span class="badge bg-warning text-dark">Salvo com erro</span><br><small class="text-muted">${esc(r.mensagem)}</small>`
        : `<span class="badge bg-danger">Erro / Não validado</span><br><small class="text-muted">${esc(r.mensagem)}</small>`;
    let acao = `<span class="text-muted small">-</span>`;
    if (r.validacao_id) {
      acao = `<a href="validacao-detalhe.php?id=${r.validacao_id}&competencia=${encodeURIComponent(r.competencia)}&origem=lote" class="btn btn-sm btn-outline-primary">Detalhe</a>`;
    } else if (r.cnpj_nao_cadastrado) {
      acao = `<a href="empresas.php?cnpj=${encodeURIComponent(r.cnpj_nao_cadastrado)}&origem=lote" class="btn btn-sm btn-success">Cadastrar empresa</a>`;
    }
    html += `<tr><td class="fw-semibold">${esc(r.arquivo)}</td><td>${esc(r.empresa)}</td><td>${esc(r.tipo)}</td><td>${esc(r.competencia)}</td><td>${r.vlr_total > 0 ? '<strong>' + brl(r.vlr_total) + '</strong>' : '<span class="text-muted">-</span>'}</td><td>${badge}</td><td>${acao}</td></tr>`;
  });
  html += `</tbody></table></div></div>
  <div class="d-flex gap-2 mt-3">
    <a href="dashboard.php" class="btn btn-primary">Ver no Dashboard</a>
    <a href="simulador-dctfweb.php" class="btn btn-outline-primary">Ver no Simulador DCTFWeb</a>
    <a href="validacao-lote.php" class="btn btn-outline-secondary">Validar outro lote</a>
  </div>`;
  areaResultado.innerHTML = html;
  textoProg.textContent = 'Concluído!';
  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-cloud-upload"></i> Validar todos os arquivos';
});
</script>

<?php require_once '../includes/footer.php'; ?>