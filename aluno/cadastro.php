<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Se já está logado, manda para a área
if (!empty($_SESSION['aluno_id']) && empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

$titulo_pagina  = 'Cadastro — Área do Aluno';
$meta_descricao = 'Crie sua conta na Academia Prix e acesse sua área exclusiva.';
$msg_err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    $nome      = trim($_POST['nome']      ?? '');
    $email     = trim($_POST['email']     ?? '');
    $telefone  = trim($_POST['telefone']  ?? '');
    $senha     = $_POST['senha']          ?? '';
    $confirma  = $_POST['confirma_senha'] ?? '';

    // Validações
    if (strlen($nome) < 3)               $msg_err[] = 'Nome deve ter pelo menos 3 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg_err[] = 'E-mail inválido.';
    if (strlen($senha) < 6)              $msg_err[] = 'Senha deve ter pelo menos 6 caracteres.';
    if ($senha !== $confirma)            $msg_err[] = 'As senhas não conferem.';

    if (empty($msg_err)) {
        $pdo = getConnection();

        // Checar e-mail duplicado
        $stmt = $pdo->prepare("SELECT id FROM alunos WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn()) {
            $msg_err[] = 'email_duplicado';
        }
    }

    if (empty($msg_err)) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO alunos (nome, email, senha, telefone, ativo)
            VALUES (:nome, :email, :senha, :telefone, 1)
        ");
        if ($stmt->execute([
            ':nome'     => $nome,
            ':email'    => $email,
            ':senha'    => $hash,
            ':telefone' => $telefone,
        ])) {
            header('Location: ' . BASE_URL . '/aluno/login.php?cadastro=1');
            exit;
        } else {
            $msg_err[] = 'Erro ao cadastrar. Tente novamente.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-hero text-center">
    <div class="container">
        <span class="section-badge">Área do Aluno</span>
        <h1 class="section-title">CRIAR <span>CONTA</span></h1>
        <p style="color:var(--prix-muted);max-width:480px;margin:0 auto;">Cadastre-se gratuitamente e acesse a área exclusiva do aluno.</p>
    </div>
</section>

<section class="section-prix section-dark">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="agendamento-box">

                    <?php if (!empty($msg_err)): ?>
                        <div class="alert-prix-error mb-4">
                            <?php foreach ($msg_err as $e): ?>
                                <?php if ($e === 'email_duplicado'): ?>
                                    <div><i class="bi bi-exclamation-triangle me-1"></i>Este e-mail já está cadastrado.
                                        <a href = "<?= BASE_URL ?>/aluno/login.php" style="color:var(--prix-orange);">Faça login.</a>
                                    </div>
                                <?php else: ?>
                                    <div><i class="bi bi-exclamation-triangle me-1"></i><?= sanitizar($e) ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="section-title mb-4">CADASTRO</h3>

                    <form method="POST" class="form-prix">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" name="nome" id="nome" class="form-control"
                                placeholder="Seu nome completo"
                                value="<?= sanitizar($_POST['nome'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control"
                                placeholder="seu@email.com"
                                value="<?= sanitizar($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefone" class="form-label">Telefone <span style="color:var(--prix-muted);font-size:.82rem;">(opcional)</span></label>
                            <input type="tel" name="telefone" id="telefone" class="form-control"
                                placeholder="(44) 99999-9999"
                                value="<?= sanitizar($_POST['telefone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <div class="input-group">
                                <input type="password" name="senha" id="senha" class="form-control"
                                    placeholder="Mínimo 6 caracteres" required>
                                <button type="button" class="btn btn-outline-secondary" id="btnVerSenha"
                                    style="border-color:rgba(255,107,33,.3);color:var(--prix-muted);">
                                    <i class="bi bi-eye" id="iconeSenha"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="confirma_senha" class="form-label">Confirmar Senha</label>
                            <input type="password" name="confirma_senha" id="confirma_senha" class="form-control"
                                placeholder="Repita a senha" required>
                        </div>
                        <button type="submit" name="cadastrar" class="btn btn-prix w-100">
                            <i class="bi bi-person-plus me-2"></i>Criar Conta
                        </button>
                    </form>

                    <hr style="border-color:rgba(255,107,33,.2);margin:24px 0;">

                    <p class="text-center" style="color:var(--prix-muted);font-size:.9rem;margin:0;">
                        Já tem conta?
                        <a href = "<?= BASE_URL ?>/aluno/login.php" style="color:var(--prix-orange);font-weight:600;">
                            Fazer login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('btnVerSenha').addEventListener('click', function () {
    const input  = document.getElementById('senha');
    const icone  = document.getElementById('iconeSenha');
    const visivel = input.type === 'text';
    input.type   = visivel ? 'password' : 'text';
    icone.className = visivel ? 'bi bi-eye' : 'bi bi-eye-slash';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>