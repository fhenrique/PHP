<?php require 'config.php'; exigirLogin();
//jogo.php - MAPA 60x60 + AVATAR + DESLIZE SUAVE
$sala_id = (int)($_GET['sala_id'] ?? 0);
if(!$sala_id){ header("Location: lobby.php"); exit; }
$s = $pdo->prepare("SELECT * FROM salas WHERE id=?"); $s->execute([$sala_id]); $sala=$s->fetch();
$u = $pdo->prepare("SELECT * FROM usuarios WHERE id=?"); $u->execute([$_SESSION['usuario_id']]); $meuUser = $u->fetch();
$meuAvatarEmoji = $AVATARES[$meuUser['avatar']]['emoji'] ?? '🙂';
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Jogo - <?= htmlspecialchars($sala['nome']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0f172a;color:#fff;overflow:hidden;touch-action:none}
#gameCanvas{background:#1e293b;border:3px solid #f59e0b;border-radius:12px;width:100%;max-width:420px;aspect-ratio:1;touch-action:none}
.barra-topo{background:#1e293b;border-radius:12px}
.jogador-morto{position:fixed;inset:0;background:rgba(0,0,0,.85);display:none;align-items:center;justify-content:center;z-index:99;text-align:center}
.modal-content{background:#1e293b;color:#fff;border:none;border-radius:16px}
.btn-jogar{background:#f59e0b;color:#000;font-weight:800}
.btn-jogar:hover{background:#d97706;color:#000}
</style></head>
<body><div class="container py-2" style="max-width:450px">
<div class="barra-topo p-2 d-flex justify-content-between align-items-center">
<div><b>🗺️ <?= htmlspecialchars($sala['nome']) ?></b><br><small>Você: <?= $meuAvatarEmoji ?> <span id="meuTerritorio">0%</span> • <span id="tempo">10:00</span></small></div>
<a href="sair_sala.php" class="btn btn-sm btn-outline-light">Sair</a>
</div>

<div class="text-center mt-2"><canvas id="gameCanvas" width="300" height="300"></canvas></div>
<p class="text-center small text-secondary mt-1">👆 Deslize o dedo para mover • Saia e volte para conquistar • Não deixe cortarem seu rastro!</p>

<div class="card bg-dark text-white p-2"><b>🏆 Ranking</b><div id="ranking" class="small"></div></div>
</div>

<div id="telaMorte" class="jogador-morto"><div><h1>💀</h1><h3>Seu rastro foi cortado!</h3><p>Você foi eliminado.</p><a href="lobby.php?msg=morreu" class="btn btn-warning fw-bold">Voltar para o Lobby</a></div></div>

<div class="modal fade" id="modalVitoria" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content text-center p-4"><div class="modal-body">
    <div style="font-size:60px">🏆</div><h4 class="fw-bold mt-2">VOCÊ CONQUISTOU 100%!</h4><p class="text-secondary">Você dominou todo o território. VOCÊ VENCEU!</p><p class="fw-bold">Seu território: <span id="pctVitoria">100%</span></p><a href="lobby.php" class="btn btn-jogar w-100 py-2 mt-2">VOLTAR PARA O LOBBY →</a>
  </div></div></div>
</div>

<div class="modal fade" id="modalTempo" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content text-center p-4"><div class="modal-body">
    <div style="font-size:60px">⏰</div><h4 class="fw-bold mt-2">Tempo acabou!</h4><p class="text-secondary">Quem tem mais território vence!</p><p class="fw-bold">Seu território: <span id="pctTempo">0%</span></p><div id="vencedorTempo" class="mb-2"></div><a href="lobby.php" class="btn btn-jogar w-100 py-2 mt-2">VOLTAR PARA O LOBBY →</a>
  </div></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const salaId = <?= $sala_id ?>;
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
let grade = [], jogadores = [], meuId = 0, minhaDir = 'direita';
let tempoRestante = 600;
let jogoAcabou = false;
const TOTAL_CASAS = 60*60;
const meuAvatarEmoji = <?= json_encode($meuAvatarEmoji) ?>;

// === NOVO: POSIÇÃO VISUAL PARA DESLIZAR ===
// O jogador LÓGICO está em j.x, j.y (vem do servidor)
// O jogador VISUAL está em posVisual[id] e vai deslizando até lá
let posVisual = {};

function mostrarModal(id){ var modal = new bootstrap.Modal(document.getElementById(id)); modal.show(); }

let startX=0, startY=0;
canvas.addEventListener('touchstart', e=>{ startX=e.touches[0].clientX; startY=e.touches[0].clientY; }, {passive:true});
canvas.addEventListener('touchmove', e=>{
  e.preventDefault();
  let dx = e.touches[0].clientX - startX; let dy = e.touches[0].clientY - startY;
  if(Math.abs(dx) < 20 && Math.abs(dy) < 20) return;
  if(Math.abs(dx) > Math.abs(dy)) minhaDir = dx>0 ? 'direita' : 'esquerda';
  else minhaDir = dy>0 ? 'baixo' : 'cima';
  startX = e.touches[0].clientX; startY = e.touches[0].clientY;
}, {passive:false});
document.addEventListener('keydown', e=>{
  if(e.key==='ArrowUp') minhaDir='cima';
  if(e.key==='ArrowDown') minhaDir='baixo';
  if(e.key==='ArrowLeft') minhaDir='esquerda';
  if(e.key==='ArrowRight') minhaDir='direita';
});

function desenhar(){
  const cell = 300/60;
  ctx.clearRect(0,0,300,300);
  for(let y=0;y<60;y++) for(let x=0;x<60;x++){
    let dono = grade[y] ? grade[y][x] : 0;
    if(dono!==0){
      let jog = jogadores.find(j=>j.id==dono);
      ctx.fillStyle = jog ? jog.cor : '#334155';
      ctx.fillRect(x*cell, y*cell, cell, cell);
    } else {
      ctx.fillStyle = '#0f172a';
      ctx.fillRect(x*cell, y*cell, cell, cell);
    }
    ctx.strokeStyle='rgba(255,255,255,0.05)';
    ctx.strokeRect(x*cell, y*cell, cell, cell);
  }
  jogadores.forEach(j=>{
    if(!j.vivo) return;
    ctx.fillStyle = j.cor;
    j.rastro.forEach(c=>{
      ctx.globalAlpha=0.7;
      ctx.fillRect(c.x*cell, c.y*cell, cell, cell);
      ctx.globalAlpha=1;
    });

    // === DESLIZE: usa a posição VISUAL, não a lógica ===
    if(!posVisual[j.id]) posVisual[j.id] = {x: j.x, y: j.y};
    // Se o jogador andou 1 casa, a posição visual persegue ele
    // 0.35 = velocidade do deslize. Maior = mais seco, menor = mais liso
    posVisual[j.id].x += (j.x - posVisual[j.id].x) * 0.35;
    posVisual[j.id].y += (j.y - posVisual[j.id].y) * 0.35;
    // Se está muito perto, cola pra não ficar tremendo
    if(Math.abs(j.x - posVisual[j.id].x) < 0.02) posVisual[j.id].x = j.x;
    if(Math.abs(j.y - posVisual[j.id].y) < 0.02) posVisual[j.id].y = j.y;

    let px = posVisual[j.id].x * cell + cell/2;
    let py = posVisual[j.id].y * cell + cell/2;

    ctx.beginPath();
    ctx.arc(px, py, cell*1.4, 0, Math.PI*2);
    ctx.fillStyle = '#fff';
    ctx.fill();
    ctx.strokeStyle = j.cor; ctx.lineWidth = 1; ctx.stroke();
    let emoji = j.avatar || (j.id==meuId ? meuAvatarEmoji : '🤖');
    ctx.font = (cell*2.2) + 'px Arial';
    ctx.textAlign='center'; ctx.textBaseline='middle';
    ctx.fillText(emoji, px, py+0.5);
  });
}

// Loop de animação - redesenha 60x por segundo, por isso desliza
function loopAnimacao(){
  if(!jogoAcabou) desenhar();
  requestAnimationFrame(loopAnimacao);
}
loopAnimacao();

async function sincronizar(){
  if(jogoAcabou) return;
  try{
    const form = new FormData();
    form.append('sala_id', salaId); form.append('dir', minhaDir);
    const res = await fetch('api_jogo.php?acao=mover&sala_id='+salaId, {method:'POST', body:form});
    const data = await res.json();
    grade = data.grade; jogadores = data.jogadores; meuId = data.meu_id;
    // NÃO desenha aqui mais, o loopAnimacao já desenha sozinho
    let eu = jogadores.find(j=>j.id==meuId);
    if(eu){
      let pct = ((eu.territorio/TOTAL_CASAS)*100).toFixed(1);
      document.getElementById('meuTerritorio').textContent = pct + '%';
      if(!eu.vivo){ document.getElementById('telaMorte').style.display='flex'; return; }
      if(pct>=100){ jogoAcabou = true; document.getElementById('pctVitoria').textContent = pct + '%'; mostrarModal('modalVitoria'); return; }
    }
    let rank = [...jogadores].sort((a,b)=>b.territorio-a.territorio);
    document.getElementById('ranking').innerHTML = rank.map((j,i)=>{
      let pct=((j.territorio/TOTAL_CASAS)*100).toFixed(1);
      let em = j.avatar || (j.id==meuId ? meuAvatarEmoji : '🤖');
      return `<div>${i+1}º ${em} ${j.nome} ${j.id==meuId?'(VOCÊ)':''} - ${pct}% ${!j.vivo?'💀':''}</div>`;
    }).join('');
  }catch(e){}
}
setInterval(sincronizar, 50);
sincronizar();
setInterval(()=>{
  if(jogoAcabou) return;
  tempoRestante--;
  let m = String(Math.floor(tempoRestante/60)).padStart(2,'0');
  let s = String(tempoRestante%60).padStart(2,'0');
  document.getElementById('tempo').textContent = m+':'+s;
  if(tempoRestante<=0){
    jogoAcabou = true;
    let eu = jogadores.find(j=>j.id==meuId);
    let meuPct = eu ? ((eu.territorio/TOTAL_CASAS)*100).toFixed(1) + '%' : '0%';
    document.getElementById('pctTempo').textContent = meuPct;
    let rank = [...jogadores].sort((a,b)=>b.territorio-a.territorio);
    if(rank.length > 0){
      let vencedor = rank[0];
      let pctVenc = ((vencedor.territorio/TOTAL_CASAS)*100).toFixed(1);
      if(vencedor.id == meuId){ document.getElementById('vencedorTempo').innerHTML = '<span class="badge bg-success fs-6">🏆 VOCÊ VENCEU!</span>'; }
      else { document.getElementById('vencedorTempo').innerHTML = 'Vencedor: <b>' + vencedor.nome + '</b> com ' + pctVenc + '%'; }
    }
    mostrarModal('modalTempo');
  }
},1000);
</script></body></html>