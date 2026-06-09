<?php
// ============================================================
// aluno/login.php — Autenticação do Aluno
//
// RESPONSABILIDADE DESTE ARQUIVO:
//   Exibe o formulário de login e autentica o aluno.
//   Suporta dois formatos de senha: bcrypt (atual) e MD5 (legado),
//   garantindo compatibilidade com cadastros antigos.
//
// SEGURANÇA APLICADA:
//   - session_regenerate_id() após login bem-sucedido (anti Session Fixation)
//   - Validação de redirect para evitar Open Redirect Attack
//   - Compatibilidade MD5→bcrypt (contas antigas continuam funcionando)
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
// Sessão já iniciada pelo config.php

// Se já estiver logado como aluno (não admin), redireciona direto para a área
if (!empty($_SESSION['aluno_id']) && empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

$titulo_pagina  = 'Login — Área do Aluno';
$meta_descricao = 'Acesse sua área exclusiva na Academia Prix.';
$msg_err = '';
$msg_ok  = '';

// Exibe mensagem de sucesso quando vem de um cadastro recém-realizado
if (!empty($_GET['cadastro'])) {
    $msg_ok = 'Cadastro realizado com sucesso! Faça login para continuar.';
}

// ── Processar o formulário de login ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_aluno'])) {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!$email || !$senha) {
        $msg_err = 'Preencha e-mail e senha.';
    } else {
        try {
            $pdo  = getConnection();

            // Busca o aluno pelo e-mail, garantindo que a conta está ativa (ativo = 1)
            $stmt = $pdo->prepare("SELECT id, nome, email, senha FROM alunos WHERE email = :email AND ativo = 1 LIMIT 1");
            $stmt->execute([':email' => $email]);
            $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

            // ── Verificação de senha com suporte a legado ─────────────
            // Sistema antigo usava MD5 (hash de 32 caracteres).
            // Sistema atual usa bcrypt via password_hash() (hash de 60+ caracteres).
            // A verificação detecta qual formato usar automaticamente.
            $senha_ok = false;
            if ($aluno) {
                if (strlen($aluno['senha']) === 32) {
                    // Conta legada com senha em MD5 — ainda funciona
                    $senha_ok = (md5($senha) === $aluno['senha']);
                } else {
                    // Conta moderna com bcrypt — mais seguro
                    $senha_ok = password_verify($senha, $aluno['senha']);
                }
            }

            if ($senha_ok) {
                // ── Login bem-sucedido ────────────────────────────────
                // session_regenerate_id(true) gera novo ID de sessão e invalida o antigo,
                // prevenindo Session Fixation Attack (invasor não pode reutilizar ID antigo)
                session_regenerate_id(true);
                $_SESSION['aluno_id']    = $aluno['id'];
                $_SESSION['aluno_nome']  = $aluno['nome'];
                $_SESSION['aluno_email'] = $aluno['email'];
                $_SESSION['is_admin']    = false; // Garante que não herda permissão de admin

                // ── Validação do redirect ─────────────────────────────
                // Se veio um parâmetro ?redirect= (ex: após tentar acessar uma página protegida),
                // só redireciona se for um caminho relativo (sem scheme/host) para evitar
                // Open Redirect Attack (invasor colocando https://site-malicioso.com no redirect).
                $redirect_raw = $_GET['redirect'] ?? '';
                $redirect = BASE_URL . '/aluno.php'; // Destino padrão
                if ($redirect_raw !== '') {
                    $parsed = parse_url($redirect_raw);
                    // Aceita apenas caminhos relativos: sem protocolo e sem domínio externo
                    if (empty($parsed['scheme']) && empty($parsed['host']) && !empty($parsed['path'])) {
                        $redirect = $redirect_raw;
                    }
                }
                header('Location: ' . $redirect);
                exit;
            } else {
                // Mensagem genérica: não revela se o e-mail existe ou não (segurança)
                $msg_err = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $e) {
            error_log("Erro PDO login aluno: " . $e->getMessage());
            $msg_err = 'Erro ao conectar ao banco de dados.';
        } catch (Exception $e) {
            error_log("Erro login aluno: " . $e->getMessage());
            $msg_err = 'Erro ao processar login.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-hero text-center">
    <div class="container">
        <span class="section-badge">Área do Aluno</span>
        <h1 class="section-title">ACESSE SUA <span>CONTA</span></h1>
        <p style="color:var(--prix-muted);max-width:480px;margin:0 auto;">Entre com seu e-mail e senha ou crie uma conta gratuita.</p>
    </div>
</section>

<section class="section-prix section-dark">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="agendamento-box">

                    <!-- Mensagem de sucesso (ex: após cadastro) -->
                    <?php if ($msg_ok): ?>
                        <div class="alert-prix-success mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= sanitizar($msg_ok) ?></div>
                    <?php endif; ?>

                    <!-- Mensagem de erro (credenciais inválidas ou campos vazios) -->
                    <?php if ($msg_err): ?>
                        <div class="alert-prix-error mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?= sanitizar($msg_err) ?></div>
                    <?php endif; ?>

                    <h3 class="section-title mb-4">ENTRAR</h3>

                    <form method="POST" class="form-prix">
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <!-- autofocus: foco automático no campo de e-mail ao carregar a página -->
                            <input type="email" name="email" id="email" class="form-control"
                                placeholder="seu@email.com"
                                value="<?= sanitizar($_POST['email'] ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="senha" class="form-label">Senha</label>
                            <div class="input-group">
                                <input type="password" name="senha" id="senha" class="form-control"
                                    placeholder="Sua senha" required>
                                <!-- Botão de visibilidade da senha (mesmo padrão do cadastro) -->
                                <button type="button" class="btn btn-outline-secondary" id="btnVerSenha"
                                    style="border-color:rgba(255,107,33,.3);color:var(--prix-muted);"
                                    title="Mostrar/ocultar senha">
                                    <i class="bi bi-eye" id="iconeSenha"></i>
                                </button>
                            </div>
                        </div>
                        <!-- name="login_aluno" identifica este formulário no PHP -->
                        <button type="submit" name="login_aluno" class="btn btn-prix w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                        </button>
                    </form>

                    <hr style="border-color:rgba(255,107,33,.2);margin:24px 0;">

                    <p class="text-center" style="color:var(--prix-muted);font-size:.9rem;margin:0;">
                        Não tem conta ainda?
                        <a href="<?= BASE_URL ?>/aluno/cadastro.php" style="color:var(--prix-orange);font-weight:600;">
                            Cadastre-se grátis
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Toggle mostrar/ocultar senha (igual ao cadastro.php)
document.getElementById('btnVerSenha').addEventListener('click', function () {
    const input  = document.getElementById('senha');
    const icone  = document.getElementById('iconeSenha');
    const visivel = input.type === 'text';
    input.type   = visivel ? 'password' : 'text';
    icone.className = visivel ? 'bi bi-eye' : 'bi bi-eye-slash';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
