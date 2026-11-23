<?php require 'config.php'; exigirLogin();
//lobby.php
$usuario_id = $_SESSION['usuario_id'];

if(isset($_GET['sair'])){
  $pdo->prepare("DELETE FROM sala_jogadores WHERE usuario_id=?")->execute([$usuario_id]);
  header("Location: lobby.php");
  exit;
}

function garantirSalaExiste($pdo, $sala_id, $salasPorId, $mapasDisponiveis){
  if(isset($salasPorId[$sala_id])) return;
  $mapa = $mapasDisponiveis[($sala_id-1) % count($mapasDisponiveis)];
  $nome = ucfirst($mapa);
  $pdo->prepare("INSERT IGNORE INTO salas (id, nome, mapa, status) VALUES (?,?,?, 'ATIVA')")->execute([$sala_id, $nome, $mapa]);
}

$salasDB_tmp = $pdo->query("SELECT * FROM salas WHERE status!='FINALIZADA' ORDER BY id")->fetchAll();
$salasPorId_tmp = [];
foreach($salasDB_tmp as $s){ $salasPorId_tmp[(int)$s['id']] = $s; }
$mapasDisponiveis_tmp = ['brasil','congresso','camara','senado','prefeitura','plenario','assembleia'];

if(isset($_GET['api'])){
  header('Content-Type: application/json');
  if($_GET['api']==='ping'){
    $sala_ping = (int)($_GET['sala_id'] ?? $_POST['sala_id'] ?? 0);
    if($sala_ping > 0){
      garantirSalaExiste($pdo, $sala_ping, $salasPorId_tmp, $mapasDisponiveis_tmp);
      $ex = $pdo->prepare("SELECT id FROM sala_jogadores WHERE sala_id=? AND usuario_id=?");
      $ex->execute([$sala_ping, $usuario_id]);
      if($ex->fetch()){
        $pdo->prepare("UPDATE sala_jogadores SET vivo=1, atualizado_em=NOW() WHERE sala_id=? AND usuario_id=?")->execute([$sala_ping, $usuario_id]);
      } else {
        $pdo->prepare("INSERT INTO sala_jogadores (sala_id, usuario_id, vivo, atualizado_em) VALUES (?,?,1,NOW())")->execute([$sala_ping, $usuario_id]);
      }
      echo json_encode(['ok'=>true]); exit;
    }
  }
  if($_GET['api']==='sair'){
    $sala_sair = (int)($_GET['sala_id'] ?? $_POST['sala_id'] ?? 0);
    if($sala_sair > 0){
      $pdo->prepare("DELETE FROM sala_jogadores WHERE sala_id=? AND usuario_id=?")->execute([$sala_sair, $usuario_id]);
    } else {
      $pdo->prepare("DELETE FROM sala_jogadores WHERE usuario_id=?")->execute([$usuario_id]);
    }
    echo json_encode(['ok'=>true]); exit;
  }
  if($_GET['api']==='contagem'){
    $pdo->exec("DELETE FROM sala_jogadores WHERE vivo=1 AND (atualizado_em IS NULL OR atualizado_em < NOW() - INTERVAL 5 SECOND)");
    $out = [];
    for($i=1; $i<=30; $i++){
      $c = $pdo->prepare("SELECT COUNT(*) FROM sala_jogadores WHERE sala_id=? AND vivo=1 AND atualizado_em > NOW() - INTERVAL 5 SECOND");
      $c->execute([$i]); $out[$i] = (int)$c->fetchColumn();
    }
    echo json_encode($out); exit;
  }
  // NOVO: trocar avatar sem recarregar
  if($_GET['api']==='avatar'){
    $av = $_POST['avatar'] ?? '';
    if(isset($AVATARES[$av])){
      $pdo->prepare("UPDATE usuarios SET avatar=?, cor=? WHERE id=?")->execute([$av, $AVATARES[$av]['cor'], $usuario_id]);
      $_SESSION['usuario_avatar']=$av; $_SESSION['usuario_cor']=$AVATARES[$av]['cor'];
      echo json_encode(['ok'=>true, 'avatar'=>$av]); exit;
    }
    echo json_encode(['ok'=>false]); exit;
  }
  echo json_encode(['ok'=>false]); exit;
}

$pdo->exec("DELETE FROM sala_jogadores WHERE vivo=1 AND (atualizado_em IS NULL OR atualizado_em < NOW() - INTERVAL 5 SECOND)");

if(isset($_GET['entrar'])){
  $sala_entrar = (int)$_GET['entrar'];
  if($sala_entrar > 0 && $sala_entrar <= 30){
    garantirSalaExiste($pdo, $sala_entrar, $salasPorId_tmp, $mapasDisponiveis_tmp);
    $pdo->prepare("DELETE FROM jogo_estado WHERE sala_id=?")->execute([$sala_entrar]);
    $pdo->prepare("DELETE FROM sala_jogadores WHERE sala_id=? AND usuario_id=?")->execute([$sala_entrar, $usuario_id]);
    $pdo->prepare("INSERT INTO sala_jogadores (sala_id, usuario_id, vivo, atualizado_em) VALUES (?,?,1,NOW())")->execute([$sala_entrar, $usuario_id]);
    header("Location: jogo_paper2.php?sala_id=".$sala_entrar);
    exit;
  }
}

// Mantido só como reserva, se o celular estiver sem JS
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['avatar']) && !isset($_GET['api'])){
  $av = $_POST['avatar'];
  if(isset($AVATARES[$av])){
    $pdo->prepare("UPDATE usuarios SET avatar=?, cor=? WHERE id=?")->execute([$av, $AVATARES[$av]['cor'], $usuario_id]);
    $_SESSION['usuario_avatar']=$av; $_SESSION['usuario_cor']=$AVATARES[$av]['cor'];
  }
  header("Location: lobby.php"); exit;
}
$u = $pdo->prepare("SELECT * FROM usuarios WHERE id=?"); $u->execute([$usuario_id]); $meu = $u->fetch();
$salasDB = $pdo->query("SELECT * FROM salas WHERE status!='FINALIZADA' ORDER BY id")->fetchAll();
$msg = $_GET['msg'] ?? '';

$salasPorId = [];
foreach($salasDB as $s){ $salasPorId[(int)$s['id']] = $s; }

$mapasDisponiveis = ['brasil','congresso','camara','senado','prefeitura','plenario','assembleia'];
$listaSalas = [];
for($i=1; $i<=30; $i++){
  if(isset($salasPorId[$i])){
    $s = $salasPorId[$i];
    $nome = $s['nome'];
    $mapa = $s['mapa'];
  } else {
    $mapa = $mapasDisponiveis[($i-1) % count($mapasDisponiveis)];
    $nome = ucfirst($mapa);
  }
  $c = $pdo->prepare("SELECT COUNT(*) FROM sala_jogadores WHERE sala_id=? AND vivo=1 AND atualizado_em > NOW() - INTERVAL 5 SECOND");
  $c->execute([$i]); $total = (int)$c->fetchColumn();
  $lotada = $total >= 10;
  $listaSalas[] = ['id' => $i,'nome' => $nome,'mapa' => $mapa,'total' => $total,'lotada' => $lotada];
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Lobby - Conquista Política</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0f172a;color:#fff; -webkit-tap-highlight-color: transparent; padding-bottom: 30px;}
.card-sala{background:#1e293b;border-radius:16px;border:1px solid #334155}
/* AJUSTADO PRA GALINHA FICAR NO CENTRO - altura menor, sem espaço sobrando */
.avatar-btn{border:2px solid transparent;border-radius:14px;background:#334155;color:#fff;padding:4px 6px 6px;cursor:pointer;min-height:112px;width:100%;transition:.15s;display:flex;flex-direction:column;align-items:center;justify-content:center}
.avatar-btn:active{transform:scale(.96)}
.avatar-btn.ativo{border-color:#f59e0b;background:#475569;box-shadow:0 0 0 2px #f59e0b55}
.avatar-3d{width:100%;height:78px;object-fit:contain;filter:drop-shadow(0 6px 8px rgba(0,0,0,.45));display:block}
.avatar-btn.ativo .avatar-3d{transform:scale(1.08)}
.avatar-nome{font-size:14px;font-weight:700;line-height:1.2;display:block;margin-top:2px}
.barra{background:#334155;height:12px;border-radius:99px;overflow:hidden}
.barra > div{height:100%}
.btn-entrar{padding:13px 10px !important;font-size:16px;border-radius:12px}
.topo{position:sticky;top:0;z-index:10;background:#0f172aee;backdrop-filter:blur(8px);padding:12px 0;margin:0 -12px;padding-left:12px;padding-right:12px}
select.form-select{background:#1e293b;color:#fff;border:1px solid #334155;font-size:17px;font-weight:700;padding:14px}
select.form-select option:disabled{color:#64748b;background:#1e293b}
.info-sala{background:#1e293b;border-radius:16px;border:1px solid #334155}
input[type=range]{width:100%;accent-color:#f59e0b;height:30px}
.zoom-btn{border:1px solid #334155;background:#0f172a;color:#fff;border-radius:99px;padding:8px 14px;font-size:13px;font-weight:700}
.zoom-btn.ativo{background:#f59e0b;color:#000;border-color:#f59e0b}
@media (min-width: 768px){
  .avatar-btn{min-height:125px}
  .avatar-3d{height:88px}
}
</style>
</head><body><div class="container py-3">

<div class="topo d-flex justify-content-between align-items-center">
<div><h5 class="mb-0 fw-bold">Olá, <?= htmlspecialchars($meu['nome']) ?> 👋</h5><small class="text-secondary">Escolha seu avatar e sua sala</small></div>
<a href="logout.php" class="btn btn-outline-light px-3 py-2">Sair</a>
</div>

<?php if($msg==='lotada'): ?><div class="alert alert-warning mt-3">Esta sala já está com 10 jogadores. Escolha outra sala.</div><?php endif; ?>
<?php if($msg==='morreu'): ?><div class="alert alert-danger mt-3">💀 Seu rastro foi cortado! Você foi eliminado. Tente de novo em outra sala.</div><?php endif; ?>

<h5 class="mt-4 fw-bold">1. Escolha seu Avatar <small class="text-success" id="avatarSalvo" style="display:none">✅ Salvo!</small></h5>

<div class="row g-2" id="listaAvatares">
	<?php foreach($AVATARES as $key=>$a): ?>
	<div class="col-4 col-md-2">
		<button type="button" data-avatar="<?= $key ?>" class="avatar-btn <?= $meu['avatar']==$key?'ativo':'' ?>">
			<img src="<?= $a['img'] ?>" alt="<?= $a['nome'] ?>" class="avatar-3d">
			<span class="avatar-nome"><?= $a['nome'] ?></span>
		</button>
	</div>
	<?php endforeach; ?>
</div>

<h5 class="mt-4 fw-bold">2. Escolha a Sala</h5>

<div class="row g-3 align-items-stretch">
  <div class="col-12 col-md-5">
    <label class="small text-secondary mb-1">Selecione uma sala (1 a 30) <span id="liveDot" style="color:#22c55e">● ao vivo</span></label>
    <select id="selectSala" class="form-select" onchange="atualizarInfoSala()">
      <?php foreach($listaSalas as $s): ?>
        <option value="<?= $s['id'] ?>" data-total="<?= $s['total'] ?>" <?= $s['lotada'] ? 'disabled' : '' ?> <?= $s['id']==1 ? 'selected' : '' ?>>
          Sala <?= $s['id'] ?> - <?= htmlspecialchars($s['nome']) ?> - <?= $s['total'] ?>/10 Jogadores<?= $s['lotada'] ? ' (LOTADA)' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="text-secondary d-block mt-2">Atualiza sozinho a cada 2 segundos, sem precisar dar F5.</small>
  </div>

  <div class="col-12 col-md-7">
    <div class="info-sala p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h5 class="fw-bold mb-1" id="infoNome">🗺️ <?= htmlspecialchars($listaSalas[0]['nome']) ?></h5>
          <p class="text-secondary mb-2 small" id="infoMapa">Mapa: <?= htmlspecialchars($listaSalas[0]['mapa']) ?> • Tempo: 2 min • Solo vs Bots</p>
        </div>
        <span class="badge bg-success" id="infoStatus">Vagas abertas</span>
      </div>
      <div class="d-flex justify-content-between"><b id="infoJogadores">0 / 10 jogadores</b><span class="small text-secondary">Sala <span id="infoId">1</span> de 30</span></div>
      <div class="barra my-2"><div id="infoBarra" style="width:0%; background:#22c55e"></div></div>
      <a id="btnEntrar" href="lobby.php?entrar=1" class="btn btn-warning w-100 fw-bold btn-entrar">ENTRAR NO JOGO →</a>
    </div>
  </div>
</div>

<h5 class="mt-4 fw-bold">3. Configurações ⚙️</h5>
<div class="card card-sala p-3">
  <div class="d-flex justify-content-between align-items-center">
    <b>🔍 Zoom no mapa</b>
    <span class="badge bg-warning text-dark" id="zoomValor" style="font-size:15px">2.5x</span>
  </div>
  <div class="d-flex gap-2 flex-wrap mb-2">
    <button type="button" class="zoom-btn" data-zoom="1">1x - Mais longe</button>
    <button type="button" class="zoom-btn" data-zoom="2">2x - Longe</button>
    <button type="button" class="zoom-btn" data-zoom="2.5">2.5x - Normal</button>
    <button type="button" class="zoom-btn" data-zoom="3.5">3.5x - Perto</button>
    <button type="button" class="zoom-btn" data-zoom="4">4x - Gigante</button>
  </div>
  <input type="range" id="zoomSlider" min="1" max="4" step="0.5" value="2.5">
  <div class="d-flex justify-content-between small text-secondary"><span>◀ 1x longe</span><span>4x perto ▶</span></div>
  <small class="text-success mt-2" id="zoomSalvo" style="display:none">✅ Salvo! Vai valer na próxima partida.</small>
</div>

<div class="card card-sala p-3 mt-4"><h6 class="fw-bold">🏆 Como jogar</h6><ul class="mb-0 text-secondary" style="font-size:14px;line-height:1.6"><li>Você nasce com um pedacinho de território</li><li>Deslize o dedo pra sair e voltar pra conquistar mais</li><li>Se cortarem seu rastro, você morre e volta pra cá</li><li>Se você cortar o rastro de outro, você mata ele e ganha seu território.</li><li>🏆 Ganha quem matar todos os adversários OU quem tiver mais território quando os 2 minutos acabarem</li></ul></div>
</div>

<script>
const salas = <?= json_encode($listaSalas) ?>;
function atualizarInfoSala(){
  const select = document.getElementById('selectSala');
  const id = parseInt(select.value);
  const sala = salas.find(s => s.id === id);
  if(!sala) return;
  document.getElementById('infoNome').textContent = '🗺️ ' + sala.nome;
  document.getElementById('infoMapa').textContent = 'Mapa: ' + sala.mapa + ' • Tempo: 2 min • Solo vs Bots';
  document.getElementById('infoId').textContent = sala.id;
  document.getElementById('infoJogadores').textContent = sala.total + ' / 10 jogadores';
  document.getElementById('infoBarra').style.width = (sala.total/10*100) + '%';
  document.getElementById('infoBarra').style.background = sala.lotada ? '#ef4444' : '#22c55e';
  const badge = document.getElementById('infoStatus');
  badge.textContent = sala.lotada ? 'LOTADA' : 'Vagas abertas';
  badge.className = 'badge ' + (sala.lotada ? 'bg-danger' : 'bg-success');
  const btn = document.getElementById('btnEntrar');
  btn.href = 'lobby.php?entrar=' + sala.id;
  if(sala.lotada){ btn.classList.add('disabled'); btn.textContent = 'SALA LOTADA'; } else { btn.classList.remove('disabled'); btn.textContent = 'ENTRAR NO JOGO →'; }
}
atualizarInfoSala();
async function atualizarContagem(){
  try{
    const r = await fetch('lobby.php?api=contagem');
    const dados = await r.json();
    const select = document.getElementById('selectSala');
    const salaAtualId = parseInt(select.value);
    for(const opt of select.options){
      const id = parseInt(opt.value);
      const total = dados[id] ?? 0;
      const sala = salas.find(s => s.id === id);
      if(!sala) continue;
      sala.total = total; sala.lotada = total >= 10;
      opt.textContent = `Sala ${id} - ${sala.nome} - ${total}/10 Jogadores${sala.lotada ? ' (LOTADA)' : ''}`;
      opt.disabled = sala.lotada;
    }
    if(salaAtualId) atualizarInfoSala();
  }catch(e){}
}
atualizarContagem();
setInterval(atualizarContagem, 2000);

// === AVATAR SEM RECARREGAR - A CORREÇÃO ===
document.querySelectorAll('[data-avatar]').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    // 1. Troca a bordinha amarela na hora, sem esperar o servidor
    document.querySelectorAll('[data-avatar]').forEach(b=> b.classList.remove('ativo'));
    btn.classList.add('ativo');
    // 2. Manda pro servidor em segundo plano
    try{
      await fetch('lobby.php?api=avatar', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'avatar=' + encodeURIComponent(btn.dataset.avatar)
      });
      const aviso = document.getElementById('avatarSalvo');
      aviso.style.display = 'inline';
      setTimeout(()=> aviso.style.display = 'none', 1500);
    }catch(e){}
  });
});

// === ZOOM ===
const zoomSlider = document.getElementById('zoomSlider');
const zoomValor = document.getElementById('zoomValor');
const zoomSalvo = document.getElementById('zoomSalvo');
let zoomAtual = parseFloat(localStorage.getItem('zoomMapa') || '2.5');
if(zoomAtual < 1 || zoomAtual > 4) zoomAtual = 2.5;
zoomSlider.value = zoomAtual;
function aplicarZoomUI(z){
  zoomValor.textContent = parseFloat(z).toFixed(1) + 'x';
  document.querySelectorAll('.zoom-btn').forEach(b=>{
    b.classList.toggle('ativo', parseFloat(b.dataset.zoom) === parseFloat(z));
  });
}
function salvarZoom(z){
  zoomAtual = parseFloat(z);
  localStorage.setItem('zoomMapa', zoomAtual);
  aplicarZoomUI(zoomAtual);
  zoomSalvo.style.display = 'block';
  setTimeout(()=>{ zoomSalvo.style.display = 'none'; }, 2500);
}
zoomSlider.addEventListener('input', e=> salvarZoom(e.target.value));
document.querySelectorAll('.zoom-btn').forEach(b=>{
  b.addEventListener('click', ()=>{ zoomSlider.value = b.dataset.zoom; salvarZoom(b.dataset.zoom); });
});
aplicarZoomUI(zoomAtual);
</script>
</body></html>