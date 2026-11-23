<?php
// C:\xampp\htdocs\presped\pages\usuarios.php
require_once '../config.php';
require_once '../includes/log.php';

exigePerfil(['admin']);

$escritorio_id = $_SESSION['escritorio_id'];
$erro = '';
$sucesso = '';

$perfis_validos = ['admin','contador','auxiliar','visualizador'];
$perfis_nomes = [
  'admin' => 'Administrador',
  'contador' => 'Contador',
  'auxiliar' => 'Auxiliar',
  'visualizador' => 'Visualizador',
];

// --- DESATIVAR ---
if (isset($_GET['excluir'])) {
  $id = (int)$_GET['excluir'];
  $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ? AND escritorio_id = ?");
  $stmt->execute([$id, $escritorio_id]);
  $u_excluir = $stmt->fetch();

  $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ? AND escritorio_id = ?");
  $stmt->execute([$id, $escritorio_id]);

  if ($u_excluir) {
    registrar_log($pdo, 'exclusao_usuario', "Desativou o usuário: {$u_excluir['nome']} ({$u_excluir['email']}) - ID: $id");
  }
  header("Location: usuarios.php?ok=excluido");
  exit;
}

// --- CARREGAR PARA EDITAR ---
$editando = null;
if (isset($_GET['editar'])) {
  $id = (int)$_GET['editar'];
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND escritorio_id = ? AND ativo = 1");
  $stmt->execute([$id, $escritorio_id]);
  $editando = $stmt->fetch();
}

// --- SALVAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $perfil = trim($_POST['perfil'] ?? '');
  $senha = $_POST['senha'] ?? '';

  if ($nome === '' || $email === '' || !in_array($perfil, $perfis_validos)) {
    $erro = 'Preencha nome, e-mail e perfil.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erro = 'E-mail inválido.';
  } elseif (!$id && strlen($senha) < 6) {
    $erro = 'A senha precisa ter no mínimo 6 caracteres.';
  } elseif ($id && $senha !== '' && strlen($senha) < 6) {
    $erro = 'A nova senha precisa ter no mínimo 6 caracteres.';
  } else {
    // Não deixa usar o e-mail do próprio escritório como usuário
    $stmt = $pdo->prepare("SELECT id FROM escritorios WHERE id = ? AND email = ?");
    $stmt->execute([$escritorio_id, $email]);
    if ($stmt->fetch()) {
      $erro = 'Este e-mail já é o e-mail do escritório. Use outro e-mail para este usuário.';
    } else {
      // Verifica e-mail duplicado na equipe
      $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ? AND escritorio_id = ? AND ativo = 1";
      $params = [$email, $escritorio_id];
      if ($id) { $sql .= " AND id != ?"; $params[] = $id; }
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      if ($stmt->fetchColumn() > 0) {
        $erro = 'Já existe um usuário com este e-mail.';
      } else {
        if ($id) {
          // --- LOG DE EDIÇÃO: busca os dados antigos antes de salvar ---
          $stmt = $pdo->prepare("SELECT nome, email, perfil FROM usuarios WHERE id = ? AND escritorio_id = ?");
          $stmt->execute([$id, $escritorio_id]);
          $antigo = $stmt->fetch();

          if ($senha !== '') {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, perfil=?, senha=? WHERE id=? AND escritorio_id=?");
            $stmt->execute([$nome, $email, $perfil, $hash, $id, $escritorio_id]);
          } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, perfil=? WHERE id=? AND escritorio_id=?");
            $stmt->execute([$nome, $email, $perfil, $id, $escritorio_id]);
          }

          if ($antigo) {
            $mudancas = [];
            if ($antigo['nome'] !== $nome) $mudancas[] = "Nome - De: {$antigo['nome']} Para: $nome";
            if ($antigo['email'] !== $email) $mudancas[] = "E-mail - De: {$antigo['email']} Para: $email";
            if ($antigo['perfil'] !== $perfil) $mudancas[] = "Perfil - De: {$perfis_nomes[$antigo['perfil']]} Para: {$perfis_nomes[$perfil]}";
            if ($senha !== '') $mudancas[] = "Senha - De: ******** Para: ******** (alterada)";
            $detalhe = !empty($mudancas) ? "\nAlterações:\n" . implode("\n", $mudancas) : "\nNenhuma alteração detectada";
            registrar_log($pdo, 'edicao_usuario', "Editou o usuário: $nome ($email) - ID: $id$detalhe");
          }

          header("Location: usuarios.php?ok=editado");
          exit;
        } else {
          $hash = password_hash($senha, PASSWORD_DEFAULT);
          $stmt = $pdo->prepare("INSERT INTO usuarios (escritorio_id, nome, email, senha, perfil, ativo) VALUES (?, ?, ?, ?, ?, 1)");
          $stmt->execute([$escritorio_id, $nome, $email, $hash, $perfil]);
          $novo_id = $pdo->lastInsertId();
          registrar_log($pdo, 'criacao_usuario', "Criou o usuário: $nome ($email) - Perfil: {$perfis_nomes[$perfil]} - ID: $novo_id");
          header("Location: usuarios.php?ok=cadastrado");
          exit;
        }
      }
    }
  }
  $editando = ['id' => $id, 'nome' => $nome, 'email' => $email, 'perfil' => $perfil];
}

if (isset($_GET['ok'])) {
  if ($_GET['ok'] === 'cadastrado') $sucesso = 'Usuário criado com sucesso! Ele já pode fazer login com o e-mail e senha cadastrados.';
  if ($_GET['ok'] === 'editado') $sucesso = 'Usuário atualizado com sucesso!';
  if ($_GET['ok'] === 'excluido') $sucesso = 'Usuário desativado.';
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE escritorio_id = ? AND ativo = 1 ORDER BY nome");
$stmt->execute([$escritorio_id]);
$usuarios = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Usuários da equipe</h2>
    <p class="text-muted mb-0"><?= count($usuarios) ?> usuário(s) ativo(s)</p>
  </div>
  <a href="dashboard.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Voltar para Dashboard</a>
</div>

<?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><?= $editando ? 'Editar usuário' : 'Novo usuário' ?></div>
  <div class="card-body">
    <form method="POST">
      <?php if (!empty($editando['id'])): ?><input type="hidden" name="id" value="<?= (int)$editando['id'] ?>"><?php endif; ?>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nome *</label>
          <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($editando['nome'] ?? '') ?>" placeholder="Ex: Maria Silva">
        </div>
        <div class="col-md-4">
          <label class="form-label">E-mail *</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($editando['email'] ?? '') ?>" placeholder="maria@seuescritorio.com.br">
        </div>
        <div class="col-md-2">
          <label class="form-label">Perfil *</label>
          <select name="perfil" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($perfis_nomes as $valor => $rotulo): ?>
              <option value="<?= $valor ?>" <?= ($editando['perfil'] ?? '') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Senha <?= $editando ? '(deixe em branco pra manter)' : '*' ?></label>
          <input type="password" name="senha" class="form-control" <?= $editando ? '' : 'required' ?> placeholder="Mínimo 6 caracteres">
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $editando ? 'Salvar alterações' : 'Criar usuário' ?></button>
        <?php if ($editando): ?><a href="usuarios.php" class="btn btn-secondary">Cancelar edição</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <strong>O que cada perfil pode fazer:</strong>
    <ul class="mb-0 mt-2 small text-muted">
      <li><strong>Administrador:</strong> Tudo, inclusive criar usuários e trocar plano.</li>
      <li><strong>Contador:</strong> Cadastrar empresa, validar arquivo, ver Dashboard e Simulador. Não cria usuário nem troca plano.</li>
      <li><strong>Auxiliar:</strong> Validar arquivo e ver Dashboard.</li>
      <li><strong>Visualizador:</strong> Só visualiza Dashboard, Histórico e Simulador.</li>
    </ul>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-bold">Equipe cadastrada</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th style="width:180px">Ações</th></tr></thead>
      <tbody>
        <?php if (empty($usuarios)): ?>
        <tr><td colspan="4" class="text-center py-4 text-muted">Nenhum usuário ainda. O dono do escritório já tem acesso total como Administrador.</td></tr>
        <?php else: foreach ($usuarios as $u): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($u['nome']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge bg-primary"><?= htmlspecialchars($perfis_nomes[$u['perfil']] ?? $u['perfil']) ?></span></td>
          <td>
            <a href="usuarios.php?editar=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluir" data-id="<?= $u['id'] ?>" data-nome="<?= htmlspecialchars($u['nome']) ?>">Desativar</button>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle text-danger"></i> Desativar usuário?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Tem certeza que deseja desativar <strong id="nomeUsuarioExcluir"></strong>?</p>
        <small class="text-muted">Ele não vai conseguir mais fazer login, mas o histórico será mantido.</small>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluir" class="btn btn-danger">Sim, desativar</a>
      </div>
    </div>
  </div>
</div>

<script>
const modalExcluir = document.getElementById('modalExcluir');
if (modalExcluir) {
  modalExcluir.addEventListener('show.bs.modal', function(event) {
    const botao = event.relatedTarget;
    document.getElementById('nomeUsuarioExcluir').textContent = botao.getAttribute('data-nome');
    document.getElementById('btnConfirmarExcluir').href = 'usuarios.php?excluir=' + botao.getAttribute('data-id');
  });
}
</script>

<?php require_once '../includes/footer.php'; ?>