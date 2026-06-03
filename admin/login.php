<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já está logado como admin, vai direto pro painel
if (!empty($_SESSION['is_admin']) && !empty($_SESSION['aluno_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $senha = $_POST['senha'] ?? '';
    if (adminPasswordValid($senha)) {
        loginAdmin();
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    }
    $msg = 'Senha incorreta. Tente novamente.';
}

$titulo_pagina = 'Admin — Login';
$meta_descricao = 'Login administrativo da Academia Prix.';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-hero text-center">
    <div class="container">
        <span class="section-badge">Administração</span>
        <h1 class="section-title">LOGIN <span>ADMIN</span></h1>
        <p class="section-sub mx-auto">Acesse o painel administrativo da Academia Prix.</p>
    </div>
</section>

<section class="section-prix section-dark">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="agendamento-box">
                    <?php if ($msg): ?>
                        <div class="alert-prix-error mb-4"><?= sanitizar($msg) ?></div>
                    <?php endif; ?>
                    <form method="POST" class="form-prix">
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha Admin</label>
                            <input type="password" name="senha" id="senha" class="form-control" required placeholder="Digite a senha de administrador" autofocus>
                        </div>
                        <button type="submit" name="login" class="btn btn-prix w-100">Entrar</button>
                    </form>
                    <p class="mt-4" style="color:var(--prix-muted);font-size:.9rem;">Use a senha definida em <code>.env</code> na variável <strong>ADMIN_PASSWORD</strong>.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>