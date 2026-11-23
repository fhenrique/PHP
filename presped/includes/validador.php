<?php
// C:\xampp2\htdocs\presped\includes\validador.php
// Lê C100 x C170 + C100 x C190 de verdade

function spedValor($v) {
  $v = trim($v);
  if ($v === '') return 0;
  return (float) str_replace(',', '.', $v);
}

function validarSped($caminho) {
  $erros = [];
  $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

  $c100_atual = null;
  $soma_itens = 0; // C170
  $soma_c190 = 0;  // C190 - VL_OPR
  $num_linha = 0;

  // Função interna para fechar um C100 e conferir ele
  $fecharC100 = function() use (&$erros, &$c100_atual, &$soma_itens, &$soma_c190) {
    if ($c100_atual === null) return;

    // 1. Confere C100 x C170
    $dif170 = round($c100_atual['vl_doc'] - $soma_itens, 2);
    if (abs($dif170) > 0.01) {
      $erros[] = [
        'nota' => $c100_atual['nota'],
        'serie' => $c100_atual['serie'],
        'data_doc' => $c100_atual['data_doc'],
        'vl_c100' => $c100_atual['vl_doc'],
        'soma_c170' => $soma_itens,
        'diferenca' => $dif170,
        'linha' => $c100_atual['linha'],
        'tipo' => $soma_itens == 0 ? 'C100 sem C170' : 'Divergência C100 x C170',
        'mensagem' => $soma_itens == 0
          ? 'Está no C100 mas não tem nenhum C170. O item não foi escriturado.'
          : 'Soma dos itens (C170) diferente do valor da capa (C100).'
      ];
    }

    // 2. Confere C100 x C190 - NOVO
    // C190 só existe se a nota tiver resumo. Se não tiver nenhum C190, também é erro.
    $dif190 = round($c100_atual['vl_doc'] - $soma_c190, 2);
    if (abs($dif190) > 0.01) {
      $erros[] = [
        'nota' => $c100_atual['nota'],
        'serie' => $c100_atual['serie'],
        'data_doc' => $c100_atual['data_doc'],
        'vl_c100' => $c100_atual['vl_doc'],
        'soma_c170' => $soma_c190, // Aqui a gente reaproveita a coluna para mostrar a soma do C190
        'diferenca' => $dif190,
        'linha' => $c100_atual['linha'],
        'tipo' => $soma_c190 == 0 ? 'C100 sem C190' : 'Divergência C100 x C190',
        'mensagem' => $soma_c190 == 0
          ? 'Está no C100 mas não tem nenhum C190. Resumo por CFOP não foi gerado. SEFAZ rejeita com 999.'
          : 'Soma do VL_OPR do C190 diferente do valor da capa (C100). Confira o CFOP.'
      ];
    }
  };

  foreach ($linhas as $linha_txt) {
    $num_linha++;
    $partes = explode('|', $linha_txt);
    if (count($partes) < 3) continue;
    $reg = trim($partes[1]);

    if ($reg === 'C100') {
      // Fecha o C100 anterior antes de começar o novo
      $fecharC100();

      // C100: |1=C100|2=IND_OPER|3=IND_EMIT|4=COD_PART|5=COD_MOD|6=COD_SIT|7=SER|8=NUM_DOC|9=CHV|10=DT_DOC|11=DT_E_S|12=VL_DOC|
      $c100_atual = [
        'serie' => $partes[7] ?? '',
        'nota' => $partes[8] ?? '',
        'data_doc' => $partes[10] ?? '',
        'vl_doc' => spedValor($partes[12] ?? '0'),
        'linha' => $num_linha
      ];
      $soma_itens = 0;
      $soma_c190 = 0;
    }

    if ($reg === 'C170' && $c100_atual !== null) {
      // C170: |1=C170|2=NUM_ITEM|3=COD_ITEM|4=DESCR|5=QTD|6=UNID|7=VL_ITEM|
      $soma_itens += spedValor($partes[7] ?? '0');
    }

    if ($reg === 'C190' && $c100_atual !== null) {
      // C190: |1=C190|2=CST_ICMS|3=CFOP|4=ALIQ_ICMS|5=VL_OPR|6=VL_BC_ICMS|7=VL_ICMS|...
      $soma_c190 += spedValor($partes[5] ?? '0');
    }
  }

  // Fecha o último C100 do arquivo
  $fecharC100();

  return $erros;
}
?>