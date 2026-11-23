<?php
// includes/validador_xml.php - Validador Reinf + eSocial + Totais DCTFWeb
// FASE 2 Item 1 - v5: corrige tags com atributo: <dmDev Id="..."> não é <dmDev>

function detectar_tipo_xml($conteudo) {
  if (strpos($conteudo, '<eSocial') !== false || (strpos($conteudo, '<evt') !== false && strpos($conteudo, 'eSocial') !== false)) {
    if (preg_match('/<evt\w+/', $conteudo, $m)) {
      return ['tipo' => 'eSocial', 'evento' => $m[0]];
    }
    return ['tipo' => 'eSocial', 'evento' => 'eSocial'];
  }
  if (strpos($conteudo, '<Reinf') !== false || strpos($conteudo, '<evt') !== false) {
    if (preg_match('/<evt\w+/', $conteudo, $m)) {
      return ['tipo' => 'EFD-Reinf', 'evento' => $m[0]];
    }
    return ['tipo' => 'EFD-Reinf', 'evento' => 'Reinf'];
  }
  return null;
}

// NOVO v5: acha a tag com ou sem atributo.
// <dmDev>, <dmDev Id="DMDEV1">, <dmDev/> -> tudo vale
// <dmDevOutro> NÃO vale
function temTag($conteudo, $tag) {
  return preg_match('/<' . preg_quote($tag, '/') . '[\s>\/]/', $conteudo) === 1;
}

function extrairCodigoEvento($conteudo) {
  if (preg_match('/\b(R-1000|R-1070|R-2010|R-2020|R-2030|R-2040|R-2050|R-2055|R-2060|R-2098|R-2099|R-5001|R-9000|R-9005|R-9011|R-9012)\b/', $conteudo, $m)) return $m[1];
  if (preg_match('/\b(S-1000|S-1005|S-1010|S-1020|S-1030|S-1040|S-1050|S-1070|S-1200|S-1202|S-1207|S-1210|S-1298|S-1299|S-1295|S-2200|S-2205|S-2206|S-2210|S-2220|S-2230|S-2299|S-2300|S-2306|S-2399|S-2400|S-3000|S-5001|S-5002|S-5011|S-5012)\b/', $conteudo, $m)) return $m[1];
  $mapa = [
    'evtInfoContri' => 'R-1000',
    'evtTabProcesso' => 'R-1070',
    'evtServTom' => 'R-2010',
    'evtServPrest' => 'R-2020',
    'evtAssocDespRec' => 'R-2030',
    'evtAssocDespRep' => 'R-2040',
    'evtComProd' => 'R-2050',
    'evtCPRB' => 'R-2060',
    'evtFechaEvPer' => 'R-2099',
    'evtTotal' => 'R-5001',
    'evtExclusao' => 'R-9000',
    'evtTotalContrib' => 'R-9011',
    'evtInfoEmpregador' => 'S-1000',
    'evtTabEstab' => 'S-1005',
    'evtRemun' => 'S-1200',
    'evtPgtos' => 'S-1210',
    'evtAdmissao' => 'S-2200',
    'evtDeslig' => 'S-2299',
  ];
  if (preg_match('/<(evt\w+)/', $conteudo, $m) && isset($mapa[$m[1]])) return $mapa[$m[1]];
  return '';
}

function eventoExigeTotApurReinf($codigo) {
  $com_tot = ['R-2010', 'R-2020', 'R-2030', 'R-2040', 'R-2050', 'R-2060', 'R-2099', 'R-5001', 'R-9011', 'R-9012'];
  return in_array($codigo, $com_tot, true);
}

function eventoExigeTotApurEsocial($codigo) {
  return in_array($codigo, ['S-1200', 'S-1299', 'S-5011'], true);
}

function totApurEstaZerado($conteudo_limpo, $blocos) {
  $achou_bloco = false;
  $soma = 0;
  foreach ((array)$blocos as $b) {
    // CORRIGIDO v5: aceita <totApur>, <totApur Id="...">, etc.
    if (preg_match_all('/<' . $b . '[\s>](.*?)<\/' . $b . '>/is', $conteudo_limpo, $m) && !empty($m[1])) {
      $achou_bloco = true;
      foreach ($m[1] as $dentro) {
        if (preg_match_all('/<(vlr\w+|vlrTotal\w*)>\s*([0-9\.,\-]+)\s*<\/\w+>/i', $dentro, $nums)) {
          foreach ($nums[2] as $v) $soma += abs((float)str_replace(',', '.', $v));
        }
      }
    }
  }
  if (!$achou_bloco) return null;
  return $soma == 0;
}

function extrairTotaisDCTF($conteudo) {
  $limpo = preg_replace('/<!--.*?-->/s', '', $conteudo);
  $limpo = preg_replace('/<([\/]?)[a-zA-Z0-9]+:/', '<$1', $limpo);

  $somarEm = function($texto, $tags) {
    $total = 0;
    foreach ((array)$tags as $tag) {
      if (preg_match_all('/<' . $tag . '>\s*([0-9\.,\-]+)\s*<\/' . $tag . '>/i', $texto, $m)) {
        foreach ($m[1] as $v) $total += (float)str_replace(',', '.', $v);
      }
    }
    return $total;
  };

  $bloco = function($texto, $blocos) {
    foreach ((array)$blocos as $b) {
      if (preg_match_all('/<' . $b . '[\s>](.*?)<\/' . $b . '>/is', $texto, $m) && !empty($m[1])) {
        return implode("\n", $m[1]);
      }
    }
    return '';
  };

  $tipo = detectar_tipo_xml($limpo);
  $tipo_nome = $tipo['tipo'] ?? '';

  if ($tipo_nome === 'eSocial') {
    $escopo = $bloco($limpo, ['totApurMen', 'totApurTrib', 'totApur', 'infoCpCalc']);
    if ($escopo === '') return ['vlr_inss_segurado' => 0, 'vlr_inss_patronal' => 0, 'vlr_rat' => 0, 'vlr_terceiros' => 0, 'vlr_retencao' => 0, 'vlr_total' => 0];
    $segurado = $somarEm($escopo, ['vlrCpSegCalc']);
    if ($segurado == 0) $segurado = $somarEm($escopo, ['vlrCpSeg', 'vlrSeg']);
    $patronal = $somarEm($escopo, ['vlrCpPatrCalc']);
    if ($patronal == 0) $patronal = $somarEm($escopo, ['vlrCpPatr', 'vlrContribPatr']);
    $rat = $somarEm($escopo, ['vlrRatCalc']);
    if ($rat == 0) $rat = $somarEm($escopo, ['vlrRat', 'vlrRatAjust']);
    $senar = $somarEm($escopo, ['vlrSenarCalc']);
    if ($senar == 0) $senar = $somarEm($escopo, ['vlrSenar']);
    $terc = $somarEm($escopo, ['vlrTerceirosCalc']);
    if ($terc == 0) $terc = $somarEm($escopo, ['vlrTerceiros']);
    $terceiros = $senar + $terc;
    $total = $segurado + $patronal + $rat + $terceiros;
    return ['vlr_inss_segurado' => round($segurado, 2), 'vlr_inss_patronal' => round($patronal, 2), 'vlr_rat' => round($rat, 2), 'vlr_terceiros' => round($terceiros, 2), 'vlr_retencao' => 0, 'vlr_total' => round($total, 2)];
  }

  if ($tipo_nome === 'EFD-Reinf') {
    $escopo = $bloco($limpo, ['totApur', 'totApurDev']);
    if ($escopo === '') return ['vlr_inss_segurado' => 0, 'vlr_inss_patronal' => 0, 'vlr_rat' => 0, 'vlr_terceiros' => 0, 'vlr_retencao' => 0, 'vlr_total' => 0];
    $retencao = $somarEm($escopo, ['vlrTotalRet', 'vlrTotalNRet', 'vlrTotalRetCalc']);
    if ($retencao == 0) $retencao = $somarEm($escopo, ['vlrRet', 'vlrNRet', 'vlrRetCalc', 'vlrNRetCalc']);
    $segurado = $somarEm($escopo, ['vlrCpSegCalc', 'vlrCpSeg']);
    $patronal = $somarEm($escopo, ['vlrCpPatrCalc', 'vlrCpPatr']);
    $rat = $somarEm($escopo, ['vlrRatCalc', 'vlrRat']);
    $terceiros = $somarEm($escopo, ['vlrSenarCalc', 'vlrSenar', 'vlrTerceirosCalc', 'vlrTerceiros']);
    $total = $segurado + $patronal + $rat + $terceiros + $retencao;
    return ['vlr_inss_segurado' => round($segurado, 2), 'vlr_inss_patronal' => round($patronal, 2), 'vlr_rat' => round($rat, 2), 'vlr_terceiros' => round($terceiros, 2), 'vlr_retencao' => round($retencao, 2), 'vlr_total' => round($total, 2)];
  }

  return ['vlr_inss_segurado' => round($somarEm($limpo, ['vlrCpSegCalc', 'vlrCpSeg']), 2), 'vlr_inss_patronal' => round($somarEm($limpo, ['vlrCpPatrCalc', 'vlrCpPatr']), 2), 'vlr_rat' => round($somarEm($limpo, ['vlrRatCalc', 'vlrRat']), 2), 'vlr_terceiros' => round($somarEm($limpo, ['vlrSenarCalc', 'vlrSenar', 'vlrTerceirosCalc', 'vlrTerceiros']), 2), 'vlr_retencao' => round($somarEm($limpo, ['vlrTotalRet', 'vlrRet', 'vlrNRet']), 2), 'vlr_total' => 0];
}

function validar_xml_sped($caminho_arquivo, $cnpj_empresa) {
  $erros = [];
  $avisos = [];
  $conteudo = file_get_contents($caminho_arquivo);
  $conteudo = preg_replace('/<!--.*?-->/s', '', $conteudo);
  $limpo = preg_replace('/<([\/]?)[a-zA-Z0-9]+:/', '<$1', $conteudo);

  libxml_use_internal_errors(true);
  $xml = simplexml_load_string($conteudo);
  if ($xml === false) {
    foreach (libxml_get_errors() as $e) {
      $erros[] = "XML mal formado na linha {$e->line}: " . trim($e->message);
    }
    return ['valido' => false, 'erros' => $erros, 'avisos' => $avisos, 'totais' => extrairTotaisDCTF($conteudo)];
  }

  $detectado = detectar_tipo_xml($conteudo);
  if (!$detectado) {
    $erros[] = "Não consegui identificar se é Reinf ou eSocial. Verifique se o arquivo é um XML do SPED.";
    return ['valido' => false, 'erros' => $erros, 'avisos' => $avisos, 'tipo' => 'Desconhecido', 'totais' => extrairTotaisDCTF($conteudo)];
  }

  $codigo = extrairCodigoEvento($conteudo);
  if ($codigo === '' || $codigo === 'R-2099' || $codigo === 'S-1299') {
    if (temTag($conteudo, 'evtFechaEvPer')) {
      $codigo = ($detectado['tipo'] === 'eSocial') ? 'S-1299' : 'R-2099';
    }
  }
  $evento = $codigo !== '' ? $codigo : ($detectado['evento'] ?? '');

  if (!temTag($conteudo, 'Signature')) {
    $avisos[] = "Arquivo sem assinatura digital. Você pode validar assim, mas NÃO transmite sem assinar com o certificado digital da empresa, senão a Receita rejeita.";
  }

  $cnpj_xml = '';
  if (preg_match('/<ide(?:Contri|Empregador)>.*?<nrInsc>(\d+)<\/nrInsc>/s', $limpo, $m)) {
    $cnpj_xml = preg_replace('/\D/', '', $m[1]);
  } elseif (preg_match('/<nrInsc>(\d+)<\/nrInsc>/', $conteudo, $m)) {
    $cnpj_xml = preg_replace('/\D/', '', $m[1]);
  }
  if ($cnpj_xml !== '') {
    $cnpj_empresa_limpo = preg_replace('/\D/', '', $cnpj_empresa);
    if ($cnpj_xml !== $cnpj_empresa_limpo) {
      $erros[] = "CNPJ do arquivo ($cnpj_xml) é diferente do CNPJ da empresa ($cnpj_empresa_limpo).";
    }
  } else {
    $avisos[] = "Não encontrei <nrInsc> no XML. Confira se o evento está completo.";
  }

  if ($detectado['tipo'] === 'EFD-Reinf') {
    if (!preg_match('/<perApur>(\d{4}-\d{2})<\/perApur>/', $conteudo, $p)) {
      $erros[] = "Reinf sem <perApur> (período de apuração). Ex: 2026-11";
    }
    if ($codigo === 'R-1000' && !temTag($conteudo, 'infoContri')) {
      $erros[] = "Evento R-1000 sem bloco <infoContri>.";
    }
    if (eventoExigeTotApurReinf($codigo)) {
      if (!temTag($limpo, 'totApur') && !temTag($limpo, 'totApurDev')) {
        $erros[] = "Evento $codigo sem bloco <totApur>. Esse bloco é obrigatório, é ele que carrega o valor que vai para a DCTFWeb. Gere o evento de novo no seu sistema fiscal com o totalizador.";
      } else {
        $zerado = totApurEstaZerado($limpo, ['totApur', 'totApurDev']);
        if ($zerado === true) {
          $erros[] = "Evento $codigo com <totApur> todo zerado. Confira os valores no seu sistema fiscal, se for enviar zerado a DCTFWeb vai zerada.";
        }
      }
    }
  }

  if ($detectado['tipo'] === 'eSocial') {
    if (!temTag($conteudo, 'ideEvento')) {
      $erros[] = "eSocial sem bloco <ideEvento>.";
    }
    if ($codigo === 'S-1200' && !temTag($conteudo, 'dmDev')) {
      $erros[] = "Evento S-1200 sem <dmDev> (demonstrativo de valores).";
    }
    if ($codigo === 'S-1200') {
      if (temTag($limpo, 'dmDev') && !temTag($limpo, 'infoCpCalc') && !temTag($limpo, 'totApur')) {
        $erros[] = "Evento S-1200 com <dmDev> mas sem <infoCpCalc> / <totApur>. Sem os valores de contribuição o evento está incompleto.";
      }
    }
    if ($codigo === 'S-1299' || $codigo === 'S-5011') {
      if (!temTag($limpo, 'totApurMen') && !temTag($limpo, 'totApurTrib') && !temTag($limpo, 'totApur')) {
        $erros[] = "Evento $codigo sem bloco <totApurMen> / <totApurTrib>. Esse bloco é obrigatório, é ele que fecha a folha e leva o valor para a DCTFWeb.";
      } else {
        $zerado = totApurEstaZerado($limpo, ['totApurMen', 'totApurTrib', 'totApur']);
        if ($zerado === true) {
          $erros[] = "Evento $codigo com totalizador todo zerado. Confira os valores no seu sistema de folha, se for enviar zerado a DCTFWeb vai zerada.";
        }
      }
    }
  }

  return ['valido' => empty($erros), 'erros' => $erros, 'avisos' => $avisos, 'tipo' => $detectado['tipo'], 'evento' => $evento, 'totais' => extrairTotaisDCTF($conteudo)];
}