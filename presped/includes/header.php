<?php
// C:\xampp\htdocs\presped\includes\header.php
if (session_status() === PHP_SESSION_NONE) session_start();

// --- Logo do escritório (mesmo lugar do relatório) ---
$escritorio_logo_header = null;
$plano_header = $_SESSION['escritorio_plano'] ?? 'pro';
$nome_escritorio_header = $_SESSION['escritorio_nome'] ?? 'Escritório';
$usuario_nome_header = $_SESSION['usuario_nome'] ?? $nome_escritorio_header;
$perfil_header = $_SESSION['usuario_perfil'] ?? 'visualizador';
$perfis_nomes_header = [
  'admin' => 'Administrador',
  'contador' => 'Contador',
  'auxiliar' => 'Auxiliar',
  'visualizador' => 'Visualizador',
];
$perfil_nome_header = $perfis_nomes_header[$perfil_header] ?? 'Visualizador';

// --- COMPETÊNCIA DO TOPO - vale para todas as páginas, Dashboard lê daqui ---
$competencia_topo = $_GET['competencia'] ?? date('Y-m');
$competencia_topo = str_replace('/', '-', $competencia_topo);
if (!preg_match('/^\d{4}-\d{2}$/', $competencia_topo)) {
  $competencia_topo = date('Y-m');
}
// Página atual para o form do topo voltar para ela mesma
$pagina_atual_topo = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');

// --- COMPETÊNCIA SÓ APARECE NO DASHBOARD E NO SIMULADOR ---
$pagina_atual_topo = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$paginas_com_competencia = [
  'dashboard.php',
  'simuladordctfweb.php',
  'simulador-dctfweb.php',
  'simulador_dctfweb.php',
  'simulador.php',
];
$mostrar_competencia_topo = in_array(strtolower($pagina_atual_topo), $paginas_com_competencia);

if (!empty($_SESSION['escritorio_id']) && isset($pdo)) {
  $stmt_h = $pdo->prepare("SELECT logo FROM escritorios WHERE id = ?");
  $stmt_h->execute([$_SESSION['escritorio_id']]);
  $escritorio_logo_header = $stmt_h->fetchColumn();
  // Garante que o caminho sempre fique /presped/uploads/logos/...
  if (!empty($escritorio_logo_header)) {
    $escritorio_logo_header = ltrim($escritorio_logo_header, '/');
    $escritorio_logo_header = preg_replace('#^(\.\./)+#', '', $escritorio_logo_header);
    $escritorio_logo_header = '/presped/' . $escritorio_logo_header;
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SPEDFácil - Validador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
  
	html {
	  scrollbar-gutter: stable;
	}  
  
    /* Trava a logo no tamanho do menu */
    .logo-escritorio-box {
      height: 32px;
      max-width: 170px;
      background: #fff;
      border-radius: 6px;
      padding: 2px 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
      border: 1px solid #e2e8f0;
    }
    .logo-escritorio-box img {
      max-height: 100%;
      max-width: 100%;
      width: auto;
      height: auto;
      object-fit: contain;
      display: block;
    }

    /* ===== SIDEBAR - estilo da imagem ===== */
    body { background: #f1f5f9; }
    .sidebar {
      width: 250px;
      min-height: 100vh;
      background: #12283d;
      color: #cbd5e1;
      padding: 20px 16px;
      position: fixed;
      left: 0; top: 0; bottom: 0;
      display: flex;
      flex-direction: column;
      z-index: 1030;
    }
    .sidebar-logo {
      text-align: center;
      font-size: 26px;
      line-height: 1;
      margin-bottom: 30px;
    }
    .sidebar-titulo {
      font-size: 11px;
      letter-spacing: 1.5px;
      color: #64748b;
      font-weight: 700;
      padding-left: 12px;
    }
    .sidebar-menu { display: flex; flex-direction: column; gap: 4px; }
    .sidebar-menu a {
      color: #cbd5e1;
      text-decoration: none;
      padding: 10px 12px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 15px;
    }
    .sidebar-menu a:hover { background: rgba(255,255,255,0.06); color: #fff; }
    .sidebar-menu a.active {
      background: #1a3a5a;
      color: #fff;
      border-left: 3px solid #3b82f6;
    }
    .sidebar-menu .dot {
      width: 8px; height: 8px; background: #22c55e;
      border-radius: 50%; margin-left: auto;
      flex-shrink: 0;
    }
    .sidebar-footer {
      margin-top: auto;
      border-top: 1px solid rgba(255,255,255,0.1);
      padding-top: 15px;
    }
    .sidebar-footer a { color: #cbd5e1; text-decoration: none; display: flex; gap: 10px; padding: 10px 12px; border-radius: 10px; }
    .sidebar-footer a:hover { background: rgba(255,255,255,0.06); color: #fff; }

    /* Conteúdo desloca pra direita da sidebar */
    .main-content { margin-left: 250px; min-height: 100vh; }

    /* Barra superior - 3 colunas com mesmo peso: esquerda | CENTRO | direita */
    .topbar {
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      padding: 10px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 1020;
    }
    .topbar-esquerda {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1;
      min-width: 0;
    }
    /* CENTRO - logo + nome + plano, centralizado de verdade */
    .topbar-centro {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      flex: 1;
      white-space: nowrap;
    }
    .topbar-direita {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 10px;
      flex: 1;
      flex-shrink: 0;
    }
    .topbar-competencia input[type="month"] {
      width: 175px;
    }

    @media (max-width: 768px) {
      .sidebar { display: none; }
      .main-content { margin-left: 0; }
      .topbar { flex-wrap: wrap; }
      .topbar-centro { order: 3; flex-basis: 100%; }
      .topbar-competencia label { display: none; }
      .topbar-competencia input[type="month"] { width: 150px; }
    }
  </style>
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
  <!-- TOPBAR FIXA - esquerda | CENTRO | direita -->
  <div class="topbar">
    <!-- 1: USUÁRIO NO CANTO ESQUERDO -->
    <div class="topbar-esquerda">
      
	  <span class="d-md-none fw-bold">SPED<span class="text-success">Fácil</span></span>
	  
      <span class="text-dark small d-flex align-items-center gap-2">
        <span class="text-muted text-truncate"><?= htmlspecialchars($usuario_nome_header) ?> - <span class="badge bg-primary"><?= htmlspecialchars($perfil_nome_header) ?></span></span>
      </span>
    </div>

    <!-- 2: CENTRO - LOGO + ESCRITÓRIO + PLANO, NO MEIO DA LINHA -->
    <div class="topbar-centro">
      <?php if (!empty($escritorio_logo_header)): ?>
        <span class="logo-escritorio-box">
          <img src="<?= htmlspecialchars($escritorio_logo_header) ?>" alt="Logo do escritório">
        </span>
      <?php endif; ?>
      <span class="text-nowrap small"><?= htmlspecialchars($nome_escritorio_header) ?> | <?= htmlspecialchars($plano_header) ?></span>
    </div>

<!-- 3: COMPETÊNCIA NO CANTO DIREITO - SÓ DASHBOARD E SIMULADOR -->
<?php if ($mostrar_competencia_topo): ?>
<div class="topbar-direita">
  <form method="GET" action="<?= htmlspecialchars($pagina_atual_topo) ?>" class="topbar-competencia d-flex align-items-center gap-2 mb-0">
    <?php
      foreach ($_GET as $chave_topo => $valor_topo) {
        if ($chave_topo === 'competencia') continue;
        if (is_array($valor_topo)) continue;
        echo '<input type="hidden" name="' . htmlspecialchars($chave_topo) . '" value="' . htmlspecialchars($valor_topo) . '">';
      }
    ?>
    <label class="text-muted small mb-0">Competência</label>
    <input type="month" name="competencia" class="form-control form-control-sm" value="<?= htmlspecialchars($competencia_topo) ?>" onchange="this.form.submit()">
  </form>
</div>
<?php endif; ?>
	
	
  </div>

  <div class="container-fluid p-4 pb-5">