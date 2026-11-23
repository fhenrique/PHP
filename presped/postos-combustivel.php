<?php // C:\xampp\htdocs\presped\postos-combustivel.php ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SPEDFácil para Postos de Combustível - Garanhuns e região</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root{ --azul:#0A1628; --laranja:#F39C12; --verde:#1E9E4A; }
  .hero{ background:linear-gradient(160deg,#0A1628 0%, #122A4D 55%, #0A1628 100%); }
  .btn-laranja{ background:var(--laranja); color:#0A1628; font-weight:700; border:none; }
  .btn-laranja:hover{ background:#E67E22; color:#fff; }
  .btn-azul{ background:var(--azul); color:#fff; font-weight:700; border:none; }
  .badge-sped{ background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.25); color:#fff; }
</style>
</head>
<body>

<nav class="navbar bg-white shadow-sm sticky-top">
  <div class="container">
    <a href="como-funciona.php"><img src="assets/img/logo.png" style="height:42px;" alt="SPEDFácil"></a>
    <div class="d-flex gap-2">
      <a href="index.php?aba=login" class="btn btn-outline-dark">Entrar</a>
      <a href="index.php?aba=cadastro&plano=teste" class="btn btn-laranja">Testar 30 dias grátis</a>
    </div>
  </div>
</nav>

<!-- HERO POSTO -->
<section class="hero text-white py-5">
  <div class="container py-3">
    <span class="badge bg-warning text-dark mb-3"><i class="bi bi-fuel-pump"></i> FEITO PARA CONTADORES QUE ATENDEM POSTOS</span>
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <h1 class="fw-bold display-5">Posto não para.<br>Seu SPED também não pode travar.</h1>
        <p class="fs-5 text-white-50">Se a SEFAZ rejeitar o SPED do posto no último dia do prazo, você não precisa abrir 5.000 abastecimentos. O SPEDFácil te mostra as 2 notas que estão erradas.</p>
        <a href="index.php?aba=cadastro" class="btn btn-laranja btn-lg px-4">Testar com um posto real - 30 dias grátis</a>
        <p class="small text-white-50 mt-3">✓ Garanhuns, Bom Conselho, Lajedo, São João, Canhotinho e região &nbsp; ✓ Sem cartão</p>
      </div>
      <div class="col-lg-6">
        <div class="card border-0 shadow-lg rounded-4"><div class="card-body p-4">
		  <div class="d-flex justify-content-between mb-2"><strong>Cenário exemplo:</strong></div>
          <div class="d-flex justify-content-between mb-2"><strong>Posto Garanhuns Combustíveis - 11/2026</strong><span class="badge bg-danger">Rejeitado SEFAZ</span></div>
          <div class="alert alert-danger py-2 mb-3"><strong>Rejeição 999 - Divergência C190</strong><br><small>C100: R$ 152.340,00 • C170: R$ 151.980,00 • Diferença: R$ 360,00</small></div>
          <table class="table table-sm mb-0">
            <tr><th>Nota</th><th>Data</th><th>Diferença</th></tr>
            <tr><td class="fw-bold">Nota 1248</td><td>28/11/2026</td><td class="text-danger fw-bold">R$ 180,00</td></tr>
            <tr><td class="fw-bold">Nota 1251</td><td>28/11/2026</td><td class="text-danger fw-bold">R$ 180,00</td></tr>
          </table>
          <div class="alert alert-success py-2 mt-3 mb-0"><i class="bi bi-check-circle"></i> Você corrige só essas 2 notas. Não o mês inteiro.</div>
        </div></div>
      </div>
    </div>
  </div>
</section>

<!-- O CENÁRIO REAL -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-lg-6">
        <span class="badge bg-danger mb-2">O CENÁRIO QUE TODO CONTADOR DE POSTO JÁ VIVEU</span>
        <h2 class="fw-bold">Enviou no último dia.<br>SEFAZ rejeitou.<br>Prazo estourou.</h2>
        <p class="text-muted">O cliente é um posto. O contador enviou o SPED no dia final. A SEFAZ rejeitou o arquivo. Agora ele tem que abrir no sistema todos os abastecimentos para bater os valores e gerar de novo.</p>
        <p class="text-muted">Enquanto isso, o posto continua vendendo, o estoque muda todo dia, e o SPED do próximo mês já está chegando.</p>
      </div>
      <div class="col-lg-6">
        <div class="card border-0 shadow rounded-4">
          <div class="card-header bg-dark text-white py-3"><strong>Sem SPEDFácil vs Com SPEDFácil</strong></div>
          <div class="card-body p-0"><table class="table mb-0">
            <tr><td>Achar o erro</td><td class="text-danger fw-bold">6 a 8 horas abrindo abastecimento por abastecimento</td><td class="text-success fw-bold">15 min - vai direto nas 2 notas</td></tr>
            <tr><td>Corrigir</td><td class="text-danger fw-bold">Mês inteiro</td><td class="text-success fw-bold">Só dia 28 - Bico 02</td></tr>
            <tr><td>Reenviar</td><td class="text-danger fw-bold">Risco de ser rejeitado de novo</td><td class="text-success fw-bold">Valida antes de enviar</td></tr>
          </table></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- O QUE ELE MOSTRA -->
<section class="py-5">
  <div class="container">
    <h2 class="fw-bold text-center mb-1">O SPEDFácil não fala só "tem erro"</h2>
    <p class="text-center text-muted mb-4">Ele fala onde, em qual nota, em qual linha e como corrigir.</p>
    <div class="row g-3">
      <div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body p-4"><div class="fs-2">🧾</div><h5 class="fw-bold mt-2">Qual nota está errada</h5><p class="text-muted">Nota 1248 e 1251, série 1, dia 28/11. Número, data, valor e linha no arquivo.</p></div></div></div>
      <div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body p-4"><div class="fs-2">⛽</div><h5 class="fw-bold mt-2">Qual bico e qual dia</h5><p class="text-muted">Bloco 1350 - Estoque de Gasolina diverge 152,30 L. Conferir Bico 02 do dia 28/11.</p></div></div></div>
      <div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body p-4"><div class="fs-2">✅</div><h5 class="fw-bold mt-2">Como corrigir</h5><p class="text-muted">Passo a passo em português, sem código da Receita. Corrigiu, subiu de novo, validou em 30 segundos.</p></div></div></div>
    </div>
  </div>
</section>

<!-- VALIDAÇÕES PARA POSTO -->
<section class="py-5 bg-white">
  <div class="container">
    <h2 class="fw-bold text-center">Validações que o posto mais precisa</h2>
    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
      <span class="badge bg-dark rounded-pill px-3 py-2">C100 x C170 - Nota x Itens</span>
      <span class="badge bg-dark rounded-pill px-3 py-2">C190 - Resumo por CFOP / CST</span>
      <span class="badge bg-dark rounded-pill px-3 py-2">Bloco 1350 - Estoque de combustível</span>
      <span class="badge bg-dark rounded-pill px-3 py-2">C500 - Entrada de combustível</span>
      <span class="badge bg-dark rounded-pill px-3 py-2">1300 / 1310 / 1320 - Controle de estoque</span>
      <span class="badge bg-dark rounded-pill px-3 py-2">CFOP 5656 - Venda de combustível</span>
    </div>
    <p class="text-center text-muted mt-4">EFD ICMS/IPI • EFD Contribuições • ECD • ECF • EFD Reinf</p>
  </div>
</section>

<!-- CTA -->
<section class="hero text-white py-5">
  <div class="container text-center">
    <h2 class="fw-bold">Teste hoje com um posto real da sua carteira.</h2>
    <p class="text-white-50 fs-5">Suba o SPED do mês passado. Se em 5 minutos você não souber qual nota está errada, o sistema não é para você.</p>
    <a href="index.php?aba=cadastro&plano=teste" class="btn btn-laranja btn-lg px-5">Criar conta grátis - 30 dias - até 5 empresas</a>
    <p class="small text-white-50 mt-3">Sem cartão • Sem fidelidade • Feito para contadores de Garanhuns e região</p>
  </div>
</section>

<footer class="bg-white py-4 text-center text-muted small">
  SPEDFácil © 2026 - Garanhuns, PE &nbsp;|&nbsp; <a href="como-funciona.php">Como funciona</a> &nbsp;|&nbsp; <a href="index.php">Entrar</a>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>