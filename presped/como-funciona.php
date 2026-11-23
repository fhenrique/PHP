<?php
// C:\xampp\htdocs\presped\como-funciona.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SPEDFácil - SPED sem correria no fim do mês</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root{ --azul:#0A1628; --laranja:#F39C12; --verde:#1E9E4A; }
  body{ background:#F1F3F5; }
  .hero{ background:linear-gradient(160deg,#0A1628 0%, #122A4D 55%, #0A1628 100%); }
  .btn-laranja{ background:var(--laranja); color:#0A1628; font-weight:700; border:none; }
  .btn-laranja:hover{ background:#E67E22; color:#fff; }
  .btn-azul{ background:var(--azul); color:#fff; font-weight:700; border:none; }
  .icon-box{ width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:26px; }
  .card-passo{ border-radius:18px; }
  .logo-img{ max-width:260px; width:100%; }
  .badge-sped{ background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.25); color:#fff; }
</style>
</head>
<body>

<!-- MENU -->
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand" href="como-funciona.php"><img src="assets/img/logo.png" alt="SPEDFácil" style="height:42px;"></a>
    <div class="d-flex gap-2">
      <a href="index.php?aba=login" class="btn btn-outline-dark">Entrar</a>
      <a href="index.php?aba=cadastro" class="btn btn-laranja">Testar 30 dias grátis</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero text-white py-5">
  <div class="container py-4">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <span class="badge bg-warning text-dark mb-3">TESTE 30 DIAS GRÁTIS - SEM CARTÃO</span>
        <h1 class="fw-bold display-5">SPED sem correria<br>no fim do mês.</h1>
        <p class="fs-5 text-white-50">Valide EFD ICMS/IPI, EFD Contribuições, ECD e ECF antes de enviar. Saiba exatamente onde corrigir, sem abrir o PVA.</p>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <span class="badge badge-sped rounded-pill px-3 py-2">EFD ICMS/IPI</span>
          <span class="badge badge-sped rounded-pill px-3 py-2">EFD Contribuições</span>
          <span class="badge badge-sped rounded-pill px-3 py-2">ECD</span>
          <span class="badge badge-sped rounded-pill px-3 py-2">ECF</span>
          <span class="badge badge-sped rounded-pill px-3 py-2">EFD Reinf</span>
        </div>
        <a href="index.php?aba=cadastro" class="btn btn-laranja btn-lg px-4">Começar teste grátis <i class="bi bi-arrow-right"></i></a>
        <a href="#como-funciona" class="btn btn-outline-light btn-lg px-4 ms-2">Ver como funciona</a>
        <p class="small text-white-50 mt-3">✓ Até 5 empresas no teste &nbsp; ✓ 5 min para validar &nbsp; ✓ Cancele quando quiser</p>
      </div>
      <div class="col-lg-6">
        <div class="card border-0 shadow-lg rounded-4">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <strong>Dashboard - Competência 11/2026</strong>
              <span class="badge bg-primary">Plano Teste</span>
            </div>
            <div class="row g-2 mb-3 text-center">
              <div class="col-4"><div class="bg-success-subtle rounded p-2"><div class="fw-bold text-success fs-4">12</div><small>OK</small></div></div>
              <div class="col-4"><div class="bg-danger-subtle rounded p-2"><div class="fw-bold text-danger fs-4">3</div><small>Com erro</small></div></div>
              <div class="col-4"><div class="bg-warning-subtle rounded p-2"><div class="fw-bold text-warning fs-4">5</div><small>Pendentes</small></div></div>
            </div>
            <div class="alert alert-danger mb-2 py-2"><i class="bi bi-x-circle"></i> <strong>Empresa X - C100</strong><br><small>CFOP 5102 não compatível com CST 00 - Linha 1.245<br><strong>Como corrigir:</strong> trocar CST para 20</small></div>
            <div class="alert alert-success mb-0 py-2"><i class="bi bi-check-circle"></i> <strong>Empresa Y</strong> - Validado sem erros</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ECONOMIA DE TEMPO -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold">Quanto tempo seu escritório perde hoje?</h2>
      <p class="text-muted">Para um escritório com 30 empresas, todo mês:</p>
    </div>
    <div class="row g-3">
      <div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body text-center p-4"><div class="text-muted">Sem SPEDFácil</div><div class="fw-bold display-6">~20 horas</div><p class="text-muted mb-0">Validando no PVA, caçando erro em Excel e explicando erro para cliente</p></div></div></div>
      <div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-4" style="border:2px solid var(--laranja)!important;"><div class="card-body text-center p-4"><div class="text-success fw-bold">Com SPEDFácil</div><div class="fw-bold display-6">~4 horas</div><p class="text-muted mb-0">Upload, validação automática e erro já traduzido</p></div></div></div>
      <div class="col-md-4"><div class="card h-100 border-0 bg-dark text-white rounded-4"><div class="card-body text-center p-4"><div class="text-warning">Você economiza</div><div class="fw-bold display-6 text-warning">16 horas / mês</div><p class="mb-0">Para captar cliente novo ou acabar com a hora extra</p></div></div></div>
    </div>
  </div>
</section>

<!-- COMO FUNCIONA -->
<section id="como-funciona" class="py-5">
  <div class="container">
    <h2 class="fw-bold text-center mb-4">Como funciona em 3 passos</h2>
    <div class="row g-3">
      <div class="col-md-4"><div class="card card-passo h-100 border-0 shadow-sm"><div class="card-body p-4"><div class="icon-box bg-primary-subtle text-primary mb-3"><i class="bi bi-buildings"></i></div><div class="badge bg-primary mb-2">Passo 1</div><h5 class="fw-bold">Cadastre suas empresas</h5><p class="text-muted">CNPJ, regime, UF e competência. Em 2 minutos sua empresa já está pronta para validar.</p></div></div></div>
      <div class="col-md-4"><div class="card card-passo h-100 border-0 shadow-sm"><div class="card-body p-4"><div class="icon-box bg-warning-subtle text-warning mb-3"><i class="bi bi-cloud-upload"></i></div><div class="badge bg-warning text-dark mb-2">Passo 2</div><h5 class="fw-bold">Faça upload do SPED</h5><p class="text-muted">Suba o arquivo .txt do EFD, ECD ou ECF. O sistema valida na hora, sem abrir o PVA.</p></div></div></div>
      <div class="col-md-4"><div class="card card-passo h-100 border-0 shadow-sm"><div class="card-body p-4"><div class="icon-box bg-success-subtle text-success mb-3"><i class="bi bi-shield-check"></i></div><div class="badge bg-success mb-2">Passo 3</div><h5 class="fw-bold">Corrija e envie tranquilo</h5><p class="text-muted">Veja o erro traduzido: onde está, por que deu e como corrigir. Corrija e valide de novo em segundos.</p></div></div></div>
    </div>
  </div>
</section>

<!-- BENEFÍCIOS -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <h2 class="fw-bold">Feito para o contador,<br>não para a Receita.</h2>
        <p class="text-muted">O PVA mostra código. O SPEDFácil mostra solução.</p>
        <ul class="list-unstyled">
          <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Erro traduzido:</strong> nada de "Campo 15 inválido". Você vê o que está errado e como corrigir.</li>
          <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Dashboard do mês:</strong> quem está OK, com erro e pendente, na hora que você entra.</li>
          <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Checklist por empresa:</strong> nunca mais esquece de entregar um SPED.</li>
          <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Descubra o erro no dia 5, não no dia 20:</strong> menos correria, menos multa, menos hora extra.</li>
        </ul>
      </div>
      <div class="col-lg-6">
        <div class="card border-0 shadow rounded-4">
          <div class="card-header bg-dark text-white rounded-top-4 py-3"><strong>Antes vs Depois</strong></div>
          <div class="card-body p-0">
            <table class="table mb-0">
              <tr><td>Validar 30 SPEDs</td><td class="text-danger fw-bold">15 horas</td><td class="text-success fw-bold">2,5 horas</td></tr>
              <tr><td>Achar quem está pendente</td><td class="text-danger fw-bold">1 hora no Excel</td><td class="text-success fw-bold">0 min</td></tr>
              <tr><td>Explicar erro para cliente</td><td class="text-danger fw-bold">30 min / empresa</td><td class="text-success fw-bold">5 min</td></tr>
              <tr><td><strong>Total no mês</strong></td><td class="text-danger fw-bold">~20 horas</td><td class="text-success fw-bold">~4 horas</td></tr>
            </table>
          </div>
        </div>
        <div class="alert alert-warning mt-3"><i class="bi bi-gift"></i> <strong>Teste 30 dias grátis</strong> com até 5 empresas. Sem cartão, sem fidelidade.</div>
      </div>
    </div>
  </div>
</section>

<!-- PLANOS -->
<section class="py-5">
  <div class="container">
    <h2 class="fw-bold text-center">Comece grátis, cresça quando precisar</h2>
    <p class="text-center text-muted mb-4">Sem surpresa. Você só paga quando o sistema já estiver te economizando tempo.</p>
    <div class="row g-3 justify-content-center">
      <div class="col-md-4"><div class="card h-100 border-0 shadow rounded-4" style="border:2px solid var(--laranja)!important;"><div class="card-body p-4 text-center"><span class="badge bg-warning text-dark">MAIS ESCOLHIDO PARA TESTAR</span><h4 class="fw-bold mt-2">Teste</h4><div class="display-6 fw-bold">Grátis</div><p class="text-muted">1 mês - até 5 empresas</p><ul class="list-unstyled text-start"><li>✓ Validação EFD, ECD, ECF</li><li>✓ Dashboard e checklist</li><li>✓ Erro traduzido</li></ul><a href="index.php?aba=cadastro" class="btn btn-laranja w-100 btn-lg">Começar teste grátis</a></div></div></div>
      <div class="col-md-4"><div class="card h-100 border-0 shadow rounded-4"><div class="card-body p-4 text-center"><h4 class="fw-bold">Start</h4><div class="display-6 fw-bold">R$ 149<small class="fs-6 text-muted">/mês</small></div><p class="text-muted">Até 10 empresas</p><ul class="list-unstyled text-start"><li>✓ Tudo do Teste</li><li>✓ Histórico completo</li><li>✓ Suporte prioritário</li></ul><a href="index.php?aba=cadastro" class="btn btn-azul w-100 btn-lg">Assinar Start</a></div></div></div>
      <div class="col-md-4"><div class="card h-100 border-0 shadow rounded-4"><div class="card-body p-4 text-center"><h4 class="fw-bold">Pro</h4><div class="display-6 fw-bold">R$ 299<small class="fs-6 text-muted">/mês</small></div><p class="text-muted">Até 30 empresas</p><ul class="list-unstyled text-start"><li>✓ Tudo do Start</li><li>✓ Múltiplos usuários</li><li>✓ Relatórios para cliente</li></ul><a href="index.php?aba=cadastro" class="btn btn-azul w-100 btn-lg">Assinar Pro</a></div></div></div>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<section class="hero text-white py-5">
  <div class="container text-center">
    <h2 class="fw-bold">Teste hoje com uma empresa real.</h2>
    <p class="text-white-50 fs-5">Se em 5 minutos você não entender o erro do seu SPED, o sistema não é para você.</p>
    <a href="index.php?aba=cadastro" class="btn btn-laranja btn-lg px-5">Criar conta grátis - 30 dias</a>
    <p class="small text-white-50 mt-3">Sem cartão • Sem fidelidade • Cancele quando quiser</p>
  </div>
</section>

<footer class="bg-white py-4 text-center text-muted small">
  SPEDFácil © 2026 - Feito para contadores em Garanhuns, PE &nbsp;|&nbsp; <a href="index.php">Entrar</a> &nbsp;|&nbsp; <a href="index.php?aba=cadastro">Criar conta</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>