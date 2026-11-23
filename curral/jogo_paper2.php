<?php require 'config.php'; exigirLogin();
// jogo_paper2.php - SEM MEIA-LUA NA CABEÇA + SEM LINHAS DENTRO DO TERRITÓRIO + JOGADOR LIVRE + RASTRO LISO
$u = $pdo->prepare("SELECT * FROM usuarios WHERE id=?"); $u->execute([$_SESSION['usuario_id']]); $meuUser = $u->fetch();
$meuNome = $meuUser['nome'] ?? $_SESSION['usuario_nome'];
$meuAvatarKey = $meuUser['avatar'] ?? 'deputado';
if(!isset($AVATARES[$meuAvatarKey])) $meuAvatarKey = 'deputado';
$meuAvatarImg = $AVATARES[$meuAvatarKey]['img'];
$meuCor = $AVATARES[$meuAvatarKey]['cor'] ?? '#f59e0b';
$avatarMap = [];
foreach($AVATARES as $k=>$v){ $avatarMap[$k] = $v['img']; }
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Paper 2 - <?= htmlspecialchars($meuNome) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
html,body{height:100%;margin:0}
body{background:#e8f8f0;color:#222;overflow:hidden;touch-action:none;font-family:Arial;height:100dvh}
#wrap{max-width:480px;margin:0 auto;padding:8px;height:100dvh;display:flex;flex-direction:column;box-sizing:border-box}
#mapWrap{flex:1;min-height:0;margin-top:8px;position:relative}
#gameCanvas{background:#f0fdf4;border-radius:18px;width:100%;height:100%;touch-action:none;box-shadow:0 4px 20px rgba(0,0,0,.15);display:block}
.barra{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.08)}
.cubo{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;position:relative;background:transparent;border:none}
.cubo img{width:100%;height:100%;object-fit:cover;object-position:top center;transform:scale(1.85) translateY(14%);display:block}
.zoom-controle{position:absolute;right:10px;bottom:10px;z-index:20;display:flex;flex-direction:column;gap:6px}
.zoom-controle button{width:44px;height:44px;border-radius:50%;border:none;background:#fff;font-size:22px;font-weight:900;box-shadow:0 3px 10px rgba(0,0,0,.3);line-height:1}
.zoom-controle button:active{transform:scale(.92);background:#f59e0b}
#zoomLabel{position:absolute;left:10px;bottom:10px;z-index:20;background:rgba(0,0,0,.7);color:#fff;font-size:12px;font-weight:700;padding:5px 10px;border-radius:99px}
</style></head>
<body><div id="wrap">
<div class="barra p-2 d-flex justify-content-between align-items-center">
<div class="d-flex align-items-center gap-2"><div class="cubo"><img src="<?= $meuAvatarImg ?>" alt="Você"></div><div><b><?= htmlspecialchars($meuNome) ?> (Você)</b><br><small><span id="meuPct">0%</span> • <span id="tempo">02:00</span> • <span id="zoomInfo"></span><br><span id="jogadoresMapa">👥 Jogadores no mapa: 10/10</span></small></div></div>
<button id="btnSair" type="button" class="btn btn-sm btn-outline-dark">Sair</button>
</div>
<div id="mapWrap"><canvas id="gameCanvas" width="600" height="600"></canvas>
<div id="zoomLabel">Zoom 2.5x</div>
<div class="zoom-controle">
<button type="button" id="btnZoomMais" title="Aproximar">+</button>
<button type="button" id="btnZoomMenos" title="Afastar">−</button>
</div>
</div>
<p class="text-center small text-secondary mt-1 mb-0">🏆 Ganhe matando todos ou tendo a maior % em 2 minutos!<br>👆 Arraste o dedo pra qualquer direção - pode fazer curva!</p>
</div>
<div id="telaMorte" style="position:fixed;inset:0;background:rgba(0,0,0,.85);display:none;align-items:center;justify-content:center;z-index:99;text-align:center;color:#fff"><div><h1 style="font-size:60px">💀</h1><h3>Seu rastro foi cortado!</h3><button type="button" class="btn btn-warning fw-bold mt-2 btn-voltar-lobby">Voltar pro Lobby</button></div></div>
<div id="telaVitoria" style="position:fixed;inset:0;background:rgba(0,0,0,.85);display:none;align-items:center;justify-content:center;z-index:99;text-align:center;color:#fff"><div><h1 style="font-size:60px">🏆</h1><h3 id="tituloVitoria">VOCÊ VENCEU!</h3><p id="textoVitoria">Você dominou o mapa!</p><button type="button" class="btn btn-warning fw-bold mt-2 btn-voltar-lobby">Voltar pro Lobby</button></div></div>
<div id="telaDerrota" style="position:fixed;inset:0;background:rgba(0,0,0,.85);display:none;align-items:center;justify-content:center;z-index:99;text-align:center;color:#fff"><div><h1 style="font-size:60px">😢</h1><h3>Tempo acabou!</h3><p id="textoDerrota">O vencedor foi outro jogador.</p><button type="button" class="btn btn-warning fw-bold mt-2 btn-voltar-lobby">Voltar pro Lobby</button></div></div>
<script>
const SALA_ID_ATUAL = new URLSearchParams(window.location.search).get('sala_id') || 1;
let jaSaiu = false;
let jogoAcabou = false;
function sairParaLobby(){
  if(jaSaiu) return;
  jaSaiu = true; jogoAcabou = true;
  try{ navigator.sendBeacon('api_sair.php', 'sala_id=' + SALA_ID_ATUAL); }catch(e){}
  try{ navigator.sendBeacon('lobby.php?api=sair&sala_id=' + SALA_ID_ATUAL); }catch(e){}
  window.location.replace('lobby.php');
}
document.getElementById('btnSair').addEventListener('click', sairParaLobby);
document.querySelectorAll('.btn-voltar-lobby').forEach(b => b.addEventListener('click', sairParaLobby));
function avisarPresenca(){
  if(jaSaiu) return;
  fetch('api_presenca.php', {method: 'POST',headers: {'Content-Type':'application/x-www-form-urlencoded'},body: 'sala_id=' + SALA_ID_ATUAL});
}
avisarPresenca();
setInterval(avisarPresenca, 5000);
window.addEventListener('beforeunload', () => { if(!jaSaiu) navigator.sendBeacon('api_sair.php', 'sala_id=' + SALA_ID_ATUAL); });

const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const W = 600, H = 600;
const MUNDO_W = 1800, MUNDO_H = 1800;
const COLS = 540, ROWS = 540;
const CELL = MUNDO_W / COLS;
const TOTAL = COLS * ROWS;
const PAREDE = 25;
let zoomSalvo = parseFloat(localStorage.getItem('zoomMapa') || '2.5');
if(isNaN(zoomSalvo) || zoomSalvo < 1) zoomSalvo = 2.5;
if(zoomSalvo > 4) zoomSalvo = 4;
let ZOOM = zoomSalvo;
let VIEW_W = W / ZOOM;
let VIEW_H = H / ZOOM;
function aplicarZoom(novoZoom){
  ZOOM = Math.min(4, Math.max(1, novoZoom));
  VIEW_W = W / ZOOM;
  VIEW_H = H / ZOOM;
  localStorage.setItem('zoomMapa', ZOOM);
  document.getElementById('zoomInfo').textContent = 'Zoom ' + ZOOM.toFixed(1) + 'x';
  document.getElementById('zoomLabel').textContent = 'Zoom ' + ZOOM.toFixed(1) + 'x';
}
document.getElementById('btnZoomMais').addEventListener('click', ()=> aplicarZoom(ZOOM + 0.5));
document.getElementById('btnZoomMenos').addEventListener('click', ()=> aplicarZoom(ZOOM - 0.5));
aplicarZoom(ZOOM);

const MEU_NOME = <?= json_encode($meuNome) ?>;
const MEU_AVATAR_KEY = <?= json_encode($meuAvatarKey) ?>;
const MEU_COR = <?= json_encode($meuCor) ?>;
const AVATAR_MAP = <?= json_encode($avatarMap) ?>;
const VEL_JOGADOR = 205;
const VEL_BOTS = 145;
let camX = 0, camY = 0;
let ultimoTempo = performance.now();
let grid = [];
for(let y=0;y<ROWS;y++){ grid[y]=new Array(COLS).fill(0); }
let rastroGrid = [];
for(let y=0;y<ROWS;y++){ rastroGrid[y]=new Array(COLS).fill(0); }
let mapaJogadores = {};
const avatarImgs = {};
Object.keys(AVATAR_MAP).forEach(k=>{
  const im = new Image();
  im.src = AVATAR_MAP[k];
  avatarImgs[k] = im;
});
// SEM MEIA-LUA: tirei o brilho branco que ficava em cima da cabeça
function desenharCabeca3D(img, x, y, tamanho){
  if(!img || !img.complete || img.naturalWidth===0) return;
  const sw = img.naturalWidth;
  const sh = img.naturalHeight * 0.52;
  ctx.save();
  ctx.beginPath();
  ctx.arc(x, y, tamanho/2, 0, Math.PI*2);
  ctx.clip();
  ctx.drawImage(img, 0, 0, sw, sh, x - tamanho/2, y - tamanho/2, tamanho, tamanho);
  ctx.restore();
  // REMOVIDO: o bloco abaixo era a meia-lua
  // ctx.save();
  // ctx.beginPath(); ctx.arc(x, y, tamanho/2, 0, Math.PI*2); ctx.clip();
  // ctx.fillStyle = 'rgba(255,255,255,0.18)';
  // ctx.fillRect(x - tamanho/2, y - tamanho/2, tamanho, tamanho*0.35);
  // ctx.restore();
}
function clarear(cor, qtd){
  let c = cor.replace('#','');
  if(c.length===3) c = c.split('').map(x=>x+x).join('');
  let r = parseInt(c.substr(0,2),16), g = parseInt(c.substr(2,2),16), b = parseInt(c.substr(4,2),16);
  r = Math.round(r + (255-r)*qtd); g = Math.round(g + (255-g)*qtd); b = Math.round(b + (255-b)*qtd);
  return `rgb(${r},${g},${b})`;
}
function pxParaCelula(px, py){ return {cx: Math.max(0, Math.min(COLS-1, Math.floor(px/CELL))), cy: Math.max(0, Math.min(ROWS-1, Math.floor(py/CELL)))}; }
function novoJogador(id,nome,cor,avatarKey,x,y,vel){
  let c = pxParaCelula(x,y);
  let areaInicial = 0;
  for(let yy=c.cy-9;yy<=c.cy+9;yy++) for(let xx=c.cx-9;xx<=c.cx+9;xx++){
    if(xx>=0&&xx<COLS&&yy>=0&&yy<ROWS){ grid[yy][xx]=id; areaInicial++; }
  }
  let j = {id,nome,cor,avatar:avatarKey,x,y,angulo:0,vivo:true,fora:false,vel:vel,iaAngulo:Math.random()*Math.PI*2,iaTimer:0,area:areaInicial,rastroCelulas:[],rastroSet:new Set(),rastroPontos:[],ultimaCelula:{cx:c.cx,cy:c.cy},ultimoPontoRastro:{x,y}};
  mapaJogadores[id]=j;
  return j;
}
let jogadores = [
  novoJogador(1, MEU_NOME, MEU_COR, MEU_AVATAR_KEY, 330, 330, VEL_JOGADOR),
  novoJogador(2, 'Greg', '#f59e0b', 'governador', 1470, 330, VEL_BOTS),
  novoJogador(3, 'Lula Bot', '#2ecc71', 'prefeito', 1470, 1470, VEL_BOTS),
  novoJogador(4, 'Vereador', '#e74c3c', 'senador', 330, 1470, VEL_BOTS),
  novoJogador(5, 'Prefeito', '#9b59b6', 'presidente', 900, 900, VEL_BOTS),
  novoJogador(6, 'Deputado', '#3498db', 'deputado', 900, 330, VEL_BOTS),
  novoJogador(7, 'Juiz', '#4a4048', 'juiz', 900, 1470, VEL_BOTS),
  novoJogador(8, 'Senador', '#e67e22', 'senador', 330, 900, VEL_BOTS),
  novoJogador(9, 'Governador', '#1abc9c', 'governador', 1470, 900, VEL_BOTS),
  novoJogador(10, 'Marina', '#ec87bf', 'presidente', 600, 600, VEL_BOTS),
];
let eu = jogadores[0];
let tempoRestante = 120, jaMostrouVitoria = false;
let alvoAngulo = 0, temAlvo = false;
let joystickAtivo = false;
let joystickOrigemX = 0, joystickOrigemY = 0;
canvas.addEventListener('touchstart', e=>{
  joystickAtivo = true;
  joystickOrigemX = e.touches[0].clientX;
  joystickOrigemY = e.touches[0].clientY;
},{passive:true});
canvas.addEventListener('touchmove', e=>{
  e.preventDefault();
  if(!joystickAtivo) return;
  let tx = e.touches[0].clientX;
  let ty = e.touches[0].clientY;
  let dx = tx - joystickOrigemX;
  let dy = ty - joystickOrigemY;
  let dist = Math.sqrt(dx*dx + dy*dy);
  if(dist < 8) return;
  alvoAngulo = Math.atan2(dy, dx);
  temAlvo = true;
  if(dist > 80){
    joystickOrigemX = tx - Math.cos(alvoAngulo) * 80;
    joystickOrigemY = ty - Math.sin(alvoAngulo) * 80;
  }
},{passive:false});
canvas.addEventListener('touchend', ()=>{ joystickAtivo = false; },{passive:true});
canvas.addEventListener('touchcancel', ()=>{ joystickAtivo = false; },{passive:true});
let mouseAtivo = false;
canvas.addEventListener('mousedown', e=>{
  mouseAtivo = true;
  joystickOrigemX = e.clientX;
  joystickOrigemY = e.clientY;
});
window.addEventListener('mousemove', e=>{
  if(!mouseAtivo) return;
  let dx = e.clientX - joystickOrigemX;
  let dy = e.clientY - joystickOrigemY;
  if(Math.sqrt(dx*dx+dy*dy) < 8) return;
  alvoAngulo = Math.atan2(dy, dx);
  temAlvo = true;
});
window.addEventListener('mouseup', ()=>{ mouseAtivo = false; });
document.addEventListener('keydown', e=>{
  if(e.key==='ArrowUp') alvoAngulo=-Math.PI/2;
  if(e.key==='ArrowDown') alvoAngulo=Math.PI/2;
  if(e.key==='ArrowLeft') alvoAngulo=Math.PI;
  if(e.key==='ArrowRight') alvoAngulo=0;
  temAlvo=true;
});
function atualizarJogadoresMapa(){
  let vivos = jogadores.filter(j=>j.vivo).length;
  document.getElementById('jogadoresMapa').textContent = '👥 Jogadores no mapa: ' + vivos + '/10';
}
atualizarJogadoresMapa();
function recalcularAreas(){
  let cont = {1:0,2:0,3:0,4:0,5:0,6:0,7:0,8:0,9:0,10:0};
  for(let y=0;y<ROWS;y++) for(let x=0;x<COLS;x++){ let d=grid[y][x]; if(d!==0) cont[d]++; }
  jogadores.forEach(j=>{ j.area = cont[j.id] || 0; });
}
function mostrarVitoria(titulo, texto){
  if(jaMostrouVitoria) return;
  jaMostrouVitoria=true; jogoAcabou=true;
  document.getElementById('tituloVitoria').textContent=titulo;
  document.getElementById('textoVitoria').textContent=texto;
  document.getElementById('telaVitoria').style.display='flex';
}
function mostrarDerrota(texto){
  if(jogoAcabou) return;
  jogoAcabou=true;
  document.getElementById('textoDerrota').textContent=texto;
  document.getElementById('telaDerrota').style.display='flex';
}
function verificarVitoriaPorEliminacao(){
  if(jogoAcabou) return;
  let vivos = jogadores.filter(j=>j.vivo);
  if(vivos.length===1){
    let vencedor = vivos[0];
    if(vencedor.id===1){ mostrarVitoria('VOCÊ VENCEU!', 'Você eliminou todos os adversários! 🏆'); }
    else { mostrarDerrota('🏆 ' + vencedor.nome + ' eliminou todos e venceu a partida!'); }
  }
}
function matarJogador(vitima, assassino){
  if(!vitima || !vitima.vivo) return;
  vitima.vivo=false;
  for(let y=0;y<ROWS;y++) for(let x=0;x<COLS;x++) if(grid[y][x]===vitima.id) grid[y][x]=assassino.id;
  limparRastro(vitima);
  recalcularAreas();
  atualizarJogadoresMapa();
  if(vitima.id===1){ document.getElementById('telaMorte').style.display='flex'; jogoAcabou=true; }
  else { verificarVitoriaPorEliminacao(); }
}
function pintarLinhaRastro(x0,y0,x1,y1,id){
  let dx = Math.abs(x1-x0), dy = Math.abs(y1-y0);
  let sx = x0<x1?1:-1, sy = y0<y1?1:-1;
  let err = dx-dy;
  let x=x0, y=y0;
  let passos=0;
  let jogador = mapaJogadores[id];
  let vitimaEncontrada = null;
  while(true){
    for(let oy=0;oy<2;oy++) for(let ox=0;ox<2;ox++){
      let xx=x+ox, yy=y+oy;
      if(xx>=0&&xx<COLS&&yy>=0&&yy<ROWS){
        let donoAntigo = rastroGrid[yy][xx];
        if(donoAntigo!==0 && donoAntigo!==id && !vitimaEncontrada){
          let vitima = mapaJogadores[donoAntigo];
          if(vitima && vitima.vivo) vitimaEncontrada = vitima;
        }
        if(rastroGrid[yy][xx]!==id) rastroGrid[yy][xx]=id;
        let chave = yy*COLS+xx;
        if(!jogador.rastroSet.has(chave)){ jogador.rastroSet.add(chave); jogador.rastroCelulas.push({cx:xx,cy:yy}); }
      }
    }
    if(x===x1 && y===y1) break;
    let e2 = 2*err;
    if(e2 > -dy){ err -= dy; x += sx; }
    if(e2 < dx){ err += dx; y += sy; }
    passos++; if(passos>500) break;
  }
  return vitimaEncontrada;
}
function fecharVolta(j){
  if(j.rastroCelulas.length < 5) { limparRastro(j); return; }
  j.rastroCelulas.forEach(c=>{ grid[c.cy][c.cx]=j.id; });
  limparRastro(j);
  let visitado = [];
  for(let y=0;y<ROWS;y++) visitado[y]=new Array(COLS).fill(false);
  let fila = [];
  for(let x=0;x<COLS;x++){
    if(grid[0][x]!==j.id){ visitado[0][x]=true; fila.push({x,y:0}); }
    if(grid[ROWS-1][x]!==j.id){ visitado[ROWS-1][x]=true; fila.push({x,y:ROWS-1}); }
  }
  for(let y=0;y<ROWS;y++){
    if(grid[y][0]!==j.id){ visitado[y][0]=true; fila.push({x:0,y}); }
    if(grid[y][COLS-1]!==j.id){ visitado[y][COLS-1]=true; fila.push({x:COLS-1,y}); }
  }
  while(fila.length){
    let c = fila.pop();
    let viz = [[c.x+1,c.y],[c.x-1,c.y],[c.x,c.y+1],[c.x,c.y-1]];
    viz.forEach(v=>{
      let x=v[0], y=v[1];
      if(x<0||x>=COLS||y<0||y>=ROWS) return;
      if(visitado[y][x]) return;
      if(grid[y][x]===j.id) return;
      visitado[y][x]=true; fila.push({x,y});
    });
  }
  for(let y=0;y<ROWS;y++) for(let x=0;x<COLS;x++){ if(!visitado[y][x] && grid[y][x]!==j.id) grid[y][x]=j.id; }
  recalcularAreas();
}
function limparRastro(j){
  j.rastroCelulas.forEach(c=>{ if(rastroGrid[c.cy] && rastroGrid[c.cy][c.cx]===j.id) rastroGrid[c.cy][c.cx]=0; });
  j.rastroCelulas=[]; j.rastroSet.clear(); j.rastroPontos=[]; j.fora=false;
}
function moverJogador(j, dt){
  if(!j.vivo) return;
  if(j.id===1){
    if(!temAlvo) return;
    j.angulo = alvoAngulo;
  } else {
    j.iaTimer -= dt*60;
    if(j.iaTimer<=0){ j.iaTimer=60+Math.random()*120; j.iaAngulo=Math.random()*Math.PI*2; }
    if(j.rastroCelulas.length>150){
      let p = j.rastroCelulas[0];
      j.iaAngulo = Math.atan2(p.cy*CELL - j.y, p.cx*CELL - j.x);
    }
    if(j.x < 270) j.iaAngulo = 0 + (Math.random()-0.5)*1.2;
    else if(j.x > MUNDO_W-270) j.iaAngulo = Math.PI + (Math.random()-0.5)*1.2;
    if(j.y < 270) j.iaAngulo = Math.PI/2 + (Math.random()-0.5)*1.2;
    else if(j.y > MUNDO_H-270) j.iaAngulo = -Math.PI/2 + (Math.random()-0.5)*1.2;
    let diff = j.iaAngulo - j.angulo;
    while(diff>Math.PI) diff-=Math.PI*2; while(diff<-Math.PI) diff+=Math.PI*2;
    j.angulo += diff * Math.min(1, 0.07 * dt * 60);
  }
  let passo = j.vel * dt;
  let nx = j.x + Math.cos(j.angulo)*passo;
  let ny = j.y + Math.sin(j.angulo)*passo;
  if(nx < PAREDE) nx = PAREDE;
  if(nx > MUNDO_W-PAREDE) nx = MUNDO_W-PAREDE;
  if(ny < PAREDE) ny = PAREDE;
  if(ny > MUNDO_H-PAREDE) ny = MUNDO_H-PAREDE;
  let c = pxParaCelula(nx, ny);
  let donoCelula = grid[c.cy][c.cx];
  let estavaFora = j.fora;
  if(rastroGrid[c.cy][c.cx]!==0 && rastroGrid[c.cy][c.cx]!==j.id){
    let vitima = mapaJogadores[rastroGrid[c.cy][c.cx]];
    if(vitima && vitima.vivo) matarJogador(vitima, j);
  }
  if(donoCelula!==j.id && !estavaFora){
    j.fora=true;
    j.rastroPontos = [{x:j.x, y:j.y}];
    j.ultimoPontoRastro = {x:j.x, y:j.y};
  }
  if(j.fora){
    let vitimaCortada = pintarLinhaRastro(j.ultimaCelula.cx, j.ultimaCelula.cy, c.cx, c.cy, j.id);
    if(vitimaCortada) matarJogador(vitimaCortada, j);
    if(donoCelula===j.id){ fecharVolta(j); }
  }
  j.x=nx; j.y=ny; j.ultimaCelula=c;
  if(j.fora){
    let dx = nx - j.ultimoPontoRastro.x;
    let dy = ny - j.ultimoPontoRastro.y;
    if(Math.sqrt(dx*dx + dy*dy) >= 6){
      j.rastroPontos.push({x:nx, y:ny});
      j.ultimoPontoRastro = {x:nx, y:ny};
    }
  }
}
function atualizarCamera(dt){
  let alvoX = eu.x - VIEW_W/2;
  let alvoY = eu.y - VIEW_H/2;
  alvoX = Math.max(0, Math.min(MUNDO_W - VIEW_W, alvoX));
  alvoY = Math.max(0, Math.min(MUNDO_H - VIEW_H, alvoY));
  camX = alvoX;
  camY = alvoY;
}
function desenharRastroSemZigueZague(j){
  let pts = (j.rastroPontos && j.rastroPontos.length >= 2) ? [...j.rastroPontos] : null;
  if(!pts){
    if(j.rastroCelulas.length < 2) return;
    let filtrados = [];
    for(let i=0;i<j.rastroCelulas.length;i+=2) filtrados.push(j.rastroCelulas[i]);
    pts = filtrados.map(c => ({x: (c.cx+0.5)*CELL, y: (c.cy+0.5)*CELL}));
  }
  pts.push({x: j.x, y: j.y});
  if(pts.length < 3) return;
  ctx.save();
  ctx.strokeStyle = j.cor;
  ctx.lineWidth = CELL * 2.6;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.beginPath();
  ctx.moveTo(pts[0].x, pts[0].y);
  for(let i=0;i<pts.length-1;i++){
    let p0 = pts[Math.max(0,i-1)];
    let p1 = pts[i];
    let p2 = pts[i+1];
    let p3 = pts[Math.min(pts.length-1,i+2)];
    let c1x = p1.x + (p2.x - p0.x)/6;
    let c1y = p1.y + (p2.y - p0.y)/6;
    let c2x = p2.x - (p3.x - p1.x)/6;
    let c2y = p2.y - (p3.y - p1.y)/6;
    ctx.bezierCurveTo(c1x, c1y, c2x, c2y, p2.x, p2.y);
  }
  ctx.stroke();
  ctx.fillStyle = j.cor;
  ctx.beginPath(); ctx.arc(pts[0].x, pts[0].y, CELL*1.3, 0, Math.PI*2); ctx.fill();
  ctx.beginPath(); ctx.arc(j.x, j.y, CELL*1.3, 0, Math.PI*2); ctx.fill();
  ctx.restore();
}
let canvasTerritorio = document.createElement('canvas');
canvasTerritorio.width = COLS;
canvasTerritorio.height = ROWS;
let ctxTerritorio = canvasTerritorio.getContext('2d');
function desenharTerritorioSemLinhasInternas(){
  ctxTerritorio.clearRect(0,0,COLS,ROWS);
  for(let y=0;y<ROWS;y++){
    for(let x=0;x<COLS;x++){
      let dono = grid[y][x];
      if(dono===0) continue;
      let jogador = mapaJogadores[dono];
      if(!jogador) continue;
      ctxTerritorio.fillStyle = clarear(jogador.cor, 0.35);
      ctxTerritorio.fillRect(x, y, 1, 1);
    }
  }
  ctx.save();
  ctx.imageSmoothingEnabled = true;
  ctx.imageSmoothingQuality = 'high';
  ctx.drawImage(canvasTerritorio, 0, 0, MUNDO_W, MUNDO_H);
  ctx.restore();
}
function desenhar(){
  ctx.setTransform(1,0,0,1,0,0);
  ctx.clearRect(0,0,W,H);
  ctx.fillStyle='#f0fdf4'; ctx.fillRect(0,0,W,H);
  let xInicio = Math.max(0, Math.floor(camX / CELL) - 1);
  let xFim = Math.min(COLS-1, Math.ceil((camX + VIEW_W) / CELL) + 1);
  let yInicio = Math.max(0, Math.floor(camY / CELL) - 1);
  let yFim = Math.min(ROWS-1, Math.ceil((camY + VIEW_H) / CELL) + 1);
  ctx.save();
  ctx.scale(ZOOM, ZOOM);
  ctx.translate(-camX, -camY);
  ctx.fillStyle='rgba(0,0,0,0.04)';
  for(let x=xInicio;x<=xFim;x+=5) for(let y=yInicio;y<=yFim;y+=5){
    ctx.beginPath(); ctx.arc(x*CELL, y*CELL, 1, 0, Math.PI*2); ctx.fill();
  }
  desenharTerritorioSemLinhasInternas();
  jogadores.forEach(j=>{
    if(j.vivo) desenharRastroSemZigueZague(j);
  });
  ctx.strokeStyle='rgba(0,0,0,0.3)';
  ctx.lineWidth=4;
  ctx.strokeRect(0,0,MUNDO_W,MUNDO_H);
  jogadores.forEach(j=>{
    if(!j.vivo) return;
    ctx.save();
    ctx.fillStyle='rgba(0,0,0,0.22)';
    ctx.beginPath(); ctx.ellipse(j.x+1, j.y+14, 15, 5, 0, 0, Math.PI*2); ctx.fill();
    ctx.restore();
    desenharCabeca3D(avatarImgs[j.avatar], j.x, j.y, 38);
    ctx.font='bold 13px Arial'; ctx.textAlign='center'; ctx.fillStyle='#333'; ctx.strokeStyle='#fff'; ctx.lineWidth=3; ctx.strokeText(j.nome, j.x, j.y-28); ctx.fillText(j.nome, j.x, j.y-28);
  });
  ctx.restore();
}
function loop(agora){
  let dt = Math.min(0.05, (agora - ultimoTempo) / 1000);
  ultimoTempo = agora;
  if(!jogoAcabou){
    jogadores.forEach(j=>moverJogador(j, dt));
    atualizarCamera(dt);
    desenhar();
    let pct = Math.min(100, (eu.area/TOTAL)*100);
    document.getElementById('meuPct').textContent = pct.toFixed(1) + '%';
  }
  requestAnimationFrame(loop);
}
requestAnimationFrame(loop);
setInterval(()=>{
  if(jogoAcabou) return;
  tempoRestante--;
  let m=String(Math.floor(tempoRestante/60)).padStart(2,'0'), s=String(tempoRestante%60).padStart(2,'0');
  document.getElementById('tempo').textContent=m+':'+s;
  if(tempoRestante<=0){
    recalcularAreas();
    let rank=[...jogadores].sort((a,b)=>b.area-a.area);
    let vencedor = rank[0];
    let pctVencedor = Math.min(100,(vencedor.area/TOTAL)*100).toFixed(1);
    if(vencedor.id===1){ mostrarVitoria('VOCÊ VENCEU!', 'Tempo acabou! Você ficou com a maior %: ' + pctVencedor + '% 🏆'); }
    else { mostrarDerrota('🏆 ' + vencedor.nome + ' venceu com ' + pctVencedor + '% do mapa! Seu % foi ' + Math.min(100,(eu.area/TOTAL)*100).toFixed(1) + '%.'); }
  }
},1000);
</script></body></html>