<?php
//api_jogo.php - MAPA 60x60
require 'config.php';
exigirLogin();
$sala_id = (int)($_GET['sala_id'] ?? $_POST['sala_id'] ?? 0);
$acao = $_GET['acao'] ?? 'estado';
$uid = $_SESSION['usuario_id'];
define('MAPA_TAM', 60);
define('TOTAL_CASAS', 3600);

// =======================================================
// FECHA TERRITÓRIO SEM DEIXAR BURACO - 60x60
// =======================================================
function fecharTerritorio($grade, $uid){
  $W = MAPA_TAM; $H = MAPA_TAM;
  $visitado = array_fill(0, $H, array_fill(0, $W, false));
  $fila = [];
  for($x=0;$x<$W;$x++){ $fila[]=[$x,0]; $fila[]=[$x,$H-1]; }
  for($y=0;$y<$H;$y++){ $fila[]=[0,$y]; $fila[]=[$W-1,$y]; }

  while(count($fila) > 0){
    [$x,$y] = array_pop($fila);
    if($x<0 || $y<0 || $x>=$W || $y>=$H) continue;
    if($visitado[$y][$x]) continue;
    if($grade[$y][$x] == $uid) continue;
    $visitado[$y][$x] = true;
    $fila[]=[$x+1,$y]; $fila[]=[$x-1,$y]; $fila[]=[$x,$y+1]; $fila[]=[$x,$y-1];
  }

  for($y=0;$y<$H;$y++){
    for($x=0;$x<$W;$x++){
      if(!$visitado[$y][$x] && $grade[$y][$x] != $uid){
        $grade[$y][$x] = $uid;
      }
    }
  }
  return $grade;
}

// =======================================================
// LIMPA ILHA SOLTA DO INIMIGO - 60x60
// =======================================================
function limparIlhasDesconectadas($grade, $conquistadorId, $jogadores){
  $W = MAPA_TAM; $H = MAPA_TAM;
  foreach($jogadores as $outro){
    $oid = $outro['id'];
    if($oid == $conquistadorId) continue;
    if(!$outro['vivo']) continue;

    $visitadoGeral = array_fill(0, $H, array_fill(0, $W, false));
    $componentes = [];
    for($y=0;$y<$H;$y++){
      for($x=0;$x<$W;$x++){
        if($grade[$y][$x] != $oid) continue;
        if($visitadoGeral[$y][$x]) continue;
        $comp = [];
        $fila = [[$x,$y]];
        $visitadoGeral[$y][$x] = true;
        while(count($fila) > 0){
          [$cx,$cy] = array_pop($fila);
          $comp[] = [$cx,$cy];
          $viz = [[$cx+1,$cy],[$cx-1,$cy],[$cx,$cy+1],[$cx,$cy-1]];
          foreach($viz as [$vx,$vy]){
            if($vx<0||$vy<0||$vx>=$W||$vy>=$H) continue;
            if($visitadoGeral[$vy][$vx]) continue;
            if($grade[$vy][$vx] != $oid) continue;
            $visitadoGeral[$vy][$vx] = true;
            $fila[] = [$vx,$vy];
          }
        }
        $componentes[] = $comp;
      }
    }

    if(count($componentes) > 1){
      $maiorIdx = 0; $maiorTam = 0;
      foreach($componentes as $i=>$c){
        if(count($c) > $maiorTam){ $maiorTam = count($c); $maiorIdx = $i; }
      }
      foreach($componentes as $i=>$c){
        if($i == $maiorIdx) continue;
        foreach($c as [$px,$py]){
          $grade[$py][$px] = $conquistadorId;
        }
      }
    }
  }
  return $grade;
}

function recalcularTerritorios(&$jogadores, $grade){
  foreach($jogadores as &$j){
    $cnt = 0;
    foreach($grade as $linha) foreach($linha as $cel) if($cel == $j['id']) $cnt++;
    $j['territorio'] = $cnt;
  }
  unset($j);
}
// =======================================================

// Cria o mapa se não existir - grade 60x60
function criarMapa($pdo, $sala_id, $uid){
  $grade = array_fill(0, MAPA_TAM, array_fill(0, MAPA_TAM, 0));
  for($y=0;$y<6;$y++) for($x=0;$x<6;$x++) $grade[$y][$x] = $uid;
  // Pega o emoji do avatar que o jogador escolheu no lobby
  global $AVATARES;
  $meuAvatarKey = $_SESSION['usuario_avatar'] ?? 'governador';
  $meuEmoji = $AVATARES[$meuAvatarKey]['emoji'] ?? '🙂';
  $jogadores = [[
    'id'=>$uid, 'nome'=>$_SESSION['usuario_nome'], 'x'=>2, 'y'=>2,
    'dir'=>'direita', 'vivo'=>1, 'cor'=>$_SESSION['usuario_cor'] ?? '#3498db',
    'rastro'=>[], 'territorio'=>36, 'is_bot'=>0, 'avatar'=>$meuEmoji
  ]];
  $coresBot = ['#e74c3c','#2ecc71','#f1c40f','#9b59b6','#e67e22'];
  $nomesBot = ['Bolsonaro Bot','Lula Bot','Vereador Bot','Senador Bot','Prefeito Bot'];
  $emojisBot = ['🇧🇷','⭐','📋','👔','🏛️'];
  for($i=0;$i<5;$i++){
    $bx = 50 - $i*4; $by = 50 - $i*4;
    $bid = 1000+$i;
    for($y=$by;$y<$by+4;$y++) for($x=$bx;$x<$bx+4;$x++) if($x<MAPA_TAM && $y<MAPA_TAM) $grade[$y][$x] = $bid;
    $jogadores[] = ['id'=>$bid,'nome'=>$nomesBot[$i],'x'=>$bx,'y'=>$by,'dir'=>'esquerda','vivo'=>1,'cor'=>$coresBot[$i],'rastro'=>[],'territorio'=>16,'is_bot'=>1,'avatar'=>$emojisBot[$i]];
  }
  $pdo->prepare("INSERT INTO jogo_estado (sala_id, grade_json, jogadores_json) VALUES (?,?,?) ON DUPLICATE KEY UPDATE grade_json=VALUES(grade_json), jogadores_json=VALUES(jogadores_json)")
    ->execute([$sala_id, json_encode($grade), json_encode($jogadores)]);
  return [$grade, $jogadores];
}

$stmt = $pdo->prepare("SELECT * FROM jogo_estado WHERE sala_id=?");
$stmt->execute([$sala_id]);
$estado = $stmt->fetch();

if(!$estado){
  [$grade, $jogadores] = criarMapa($pdo, $sala_id, $uid);
} else {
  $grade = json_decode($estado['grade_json'], true);
  $jogadores = json_decode($estado['jogadores_json'], true);
  // Se o jogo salvo ainda é do mapa antigo 30x30, recria em 60x60
  if(!is_array($grade) || count($grade) != MAPA_TAM || !isset($grade[0]) || count($grade[0]) != MAPA_TAM){
    [$grade, $jogadores] = criarMapa($pdo, $sala_id, $uid);
  }
}

if($acao === 'mover'){
  $dir = $_POST['dir'] ?? 'direita';
  $dirs = ['cima'=>[0,-1],'baixo'=>[0,1],'esquerda'=>[-1,0],'direita'=>[1,0]];
  if(!isset($dirs[$dir])) $dir='direita';

  foreach($jogadores as &$j){
    if($j['id'] == $uid && $j['vivo']){
      $j['dir'] = $dir;
      $nx = $j['x'] + $dirs[$dir][0];
      $ny = $j['y'] + $dirs[$dir][1];
      if($nx<0||$nx>=MAPA_TAM||$ny<0||$ny>=MAPA_TAM){ continue; }
      $dono = $grade[$ny][$nx] ?? 0;
      if($dono != $uid){
        $j['rastro'][] = ['x'=>$nx,'y'=>$ny];
      }
      $j['x']=$nx; $j['y']=$ny;
      if($dono == $uid && !empty($j['rastro'])){
        foreach($j['rastro'] as $c){ $grade[$c['y']][$c['x']] = $uid; }
        $j['rastro'] = [];
        $grade = fecharTerritorio($grade, $uid);
        $grade = limparIlhasDesconectadas($grade, $uid, $jogadores);
        recalcularTerritorios($jogadores, $grade);
      }
      foreach($jogadores as $outro){
        if($outro['id']!=$uid && $outro['vivo']){
          foreach($outro['rastro'] as $rc){
            if($rc['x']==$nx && $rc['y']==$ny){
              foreach($grade as $yy=>$linha) foreach($linha as $xx=>$cel) if($cel==$outro['id']) $grade[$yy][$xx]=$uid;
              foreach($jogadores as &$jj) if($jj['id']==$outro['id']){ $jj['vivo']=0; $jj['rastro']=[]; }
              recalcularTerritorios($jogadores, $grade);
            }
          }
        }
      }
    }
    if($j['is_bot'] && $j['vivo']){
      $ops = ['cima','baixo','esquerda','direita'];
      if(rand(0,100)<30) $j['dir']=$ops[array_rand($ops)];
      $nx=$j['x']+$dirs[$j['dir']][0]; $ny=$j['y']+$dirs[$j['dir']][1];
      if($nx>=0&&$nx<MAPA_TAM&&$ny>=0&&$ny<MAPA_TAM){
        $dono=$grade[$ny][$nx] ?? 0;
        if($dono!=$j['id']) $j['rastro'][]=['x'=>$nx,'y'=>$ny];
        $j['x']=$nx; $j['y']=$ny;
        if($dono==$j['id'] && !empty($j['rastro'])){
          foreach($j['rastro'] as $c) $grade[$c['y']][$c['x']]=$j['id'];
          $j['rastro']=[];
          $grade = fecharTerritorio($grade, $j['id']);
          $grade = limparIlhasDesconectadas($grade, $j['id'], $jogadores);
          recalcularTerritorios($jogadores, $grade);
        }
        foreach($jogadores as &$eu){
          if($eu['id']==$uid && $eu['vivo']){
            foreach($eu['rastro'] as $rc){
              if($rc['x']==$nx && $rc['y']==$ny){
                $eu['vivo']=0; $eu['rastro']=[];
                foreach($grade as $yy=>$linha) foreach($linha as $xx=>$cel) if($cel==$uid) $grade[$yy][$xx]=0;
                recalcularTerritorios($jogadores, $grade);
              }
            }
          }
        }
      }
    }
  }
  unset($j);
  $pdo->prepare("UPDATE jogo_estado SET grade_json=?, jogadores_json=? WHERE sala_id=?")
    ->execute([json_encode($grade), json_encode($jogadores), $sala_id]);
}

header('Content-Type: application/json');
echo json_encode(['grade'=>$grade, 'jogadores'=>$jogadores, 'meu_id'=>$uid]);