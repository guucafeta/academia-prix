<?php
// ============================================================
// aluno/cadastro.php — Página de Cadastro de Novo Aluno
//
// RESPONSABILIDADE DESTE ARQUIVO:
//   Exibe o formulário de criação de conta e processa o POST.
//   Fluxo: exibe formulário → aluno preenche → POST → valida →
//          salva no banco → redireciona para o login.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
// Sessão já iniciada pelo config.php

// Se o aluno já está logado (e não é admin), não precisa se cadastrar de novo
if (!empty($_SESSION['aluno_id']) && empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

$titulo_pagina  = 'Cadastro — Área do Aluno';
$meta_descricao = 'Crie sua conta na Academia Prix e acesse sua área exclusiva.';
$msg_err = []; // Array de erros de validação

// ── Processar o formulário de cadastro ────────────────────────
// Só executa quando o formulário for enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {

    // Lê os campos do formulário, removendo espaços extras com trim()
    $nome      = trim($_POST['nome']      ?? '');
    $email     = trim($_POST['email']     ?? '');
    $telefone  = trim($_POST['telefone']  ?? '');
    $senha     = $_POST['senha']          ?? '';
    $confirma  = $_POST['confirma_senha'] ?? '';

    // ── Validações de entrada ─────────────────────────────────
    if (strlen($nome) < 3)
        $msg_err[] = 'Nome deve ter pelo menos 3 caracteres.';

    // filter_var com FILTER_VALIDATE_EMAIL verifica formato válido de e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $msg_err[] = 'E-mail inválido.';

    if (strlen($senha) < 6)
        $msg_err[] = 'Senha deve ter pelo menos 6 caracteres.';

    // Compara a senha digitada com a confirmação
    if ($senha !== $confirma)
        $msg_err[] = 'As senhas não conferem.';

    // ── Verificar e-mail duplicado ────────────────────────────
    // Só consulta o banco se não houver erros anteriores (evita queries desnecessárias)
    if (empty($msg_err)) {
        $pdo = getConnection();

        // Prepared statement: o :email é substituído de forma segura pelo PDO
        $stmt = $pdo->prepare("SELECT id FROM alunos WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn()) {
            $msg_err[] = 'email_duplicado'; // Código especial para exibir link de login
        }
    }

    // ── Inserir no banco de dados ─────────────────────────────
    if (empty($msg_err)) {
        // password_hash() com PASSWORD_DEFAULT (bcrypt) cria um hash seguro da senha.
        // NUNCA armazenamos a senha em texto puro. O hash é irreversível —
        // para verificar, usa-se password_verify() no login.
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
            // Cadastro bem-sucedido: redireciona para o login com parâmetro ?cadastro=1
            // para exibir mensagem de confirmação
            header('Location: ' . BASE_URL . '/aluno/login.php?cadastro=1');
            exit;
        } else {
            $msg_err[] = 'Erro ao cadastrar. Tente novamente.';
        }
    }
}

// Inclui o cabeçalho HTML (menu, meta tags, CSS)
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
                        <!-- Exibe cada erro de validação como um item da lista -->
                        <div class="alert-prix-error mb-4">
                            <?php foreach ($msg_err as $e): ?>
                                <?php if ($e === 'email_duplicado'): ?>
                                    <!-- Tratamento especial para e-mail já cadastrado: exibe link para login -->
                                    <div><i class="bi bi-exclamation-triangle me-1"></i>Este e-mail já está cadastrado.
                                        <a href="<?= BASE_URL ?>/aluno/login.php" style="color:var(--prix-orange);">Faça login.</a>
                                    </div>
                                <?php else: ?>
                                    <!-- sanitizar() evita XSS ao exibir a mensagem de erro -->
                                    <div><i class="bi bi-exclamation-triangle me-1"></i><?= sanitizar($e) ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="section-title mb-4">CADASTRO</h3>

                    <!-- Formulário de cadastro: action vazia = envia para a própria página -->
                    <form method="POST" class="form-prix">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <!-- value mantém o campo preenchido após erro (UX: não perde o que digitou) -->
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
                            <!-- input-group combina o campo de senha com o botão de mostrar/ocultar -->
                            <div class="input-group">
                                <input type="password" name="senha" id="senha" class="form-control"
                                    placeholder="Mínimo 6 caracteres" required>
                                <!-- Botão que alterna entre mostrar e ocultar a senha via JavaScript -->
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
                        <!-- name="cadastrar" é verificado no PHP: isset($_POST['cadastrar']) -->
                        <button type="submit" name="cadastrar" class="btn btn-prix w-100">
                            <i class="bi bi-person-plus me-2"></i>Criar Conta
                        </button>
                    </form>

                    <hr style="border-color:rgba(255,107,33,.2);margin:24px 0;">

                    <p class="text-center" style="color:var(--prix-muted);font-size:.9rem;margin:0;">
                        Já tem conta?
                        <a href="<?= BASE_URL ?>/aluno/login.php" style="color:var(--prix-orange);font-weight:600;">
                            Fazer login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// ── Toggle mostrar/ocultar senha ──────────────────────────────
// Alterna o type do input entre "password" (oculto) e "text" (visível),
// e troca o ícone do botão para dar feedback visual ao usuário.
document.getElementById('btnVerSenha').addEventListener('click', function () {
    const input  = document.getElementById('senha');
    const icone  = document.getElementById('iconeSenha');
    const visivel = input.type === 'text';
    input.type   = visivel ? 'password' : 'text';
    icone.className = visivel ? 'bi bi-eye' : 'bi bi-eye-slash';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
