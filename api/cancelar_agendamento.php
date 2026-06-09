<?php
// ============================================================
// api/cancelar_agendamento.php — Cancelamento de Agendamento pelo Aluno
//
// RESPONSABILIDADE DESTE ARQUIVO:
//   Recebe o POST do botão "Cancelar" da tabela de agendamentos
//   e marca o agendamento como cancelado no banco de dados.
//
// MÉTODO ACEITO: POST
// AUTENTICAÇÃO:  Sessão ativa com aluno_id
//
// SEGURANÇA APLICADA:
//   1. Verificação de sessão (só alunos logados)
//   2. Verificação de método HTTP (só POST)
//   3. Validação CSRF (token secreto por sessão)
//   4. A função cancelarAgendamento() garante que o aluno só
//      cancela seus próprios agendamentos (WHERE aluno_id = ?)
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
// Sessão já iniciada pelo config.php

// Redireciona para login se não estiver autenticado
if (empty($_SESSION['aluno_id'])) {
    header('Location: ' . BASE_URL . '/aluno/login.php');
    exit;
}

// Garante que a operação só ocorre via POST (não via link ou GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

// ── Validação do CSRF Token ───────────────────────────────────
// CSRF (Cross-Site Request Forgery): ataque onde um site malicioso
// faz o navegador do aluno enviar uma requisição sem que ele saiba.
//
// O token CSRF é gerado aleatoriamente pelo servidor e armazenado
// na sessão. O formulário HTML o inclui num campo hidden.
// Se o token do POST não bater com o da sessão, a requisição é rejeitada —
// um site externo não tem como conhecer o token correto.
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['mensagem'] = 'Falha na validação de segurança. Tente novamente.';
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

$aluno_id       = getCurrentAlunoId();
$agendamento_id = isset($_POST['agendamento_id']) ? (int)$_POST['agendamento_id'] : 0;

// Rejeita IDs inválidos ou negativos
if ($agendamento_id <= 0) {
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

// ── Executar o cancelamento ───────────────────────────────────
// cancelarAgendamento() garante que aluno_id coincide com o dono
// do agendamento — nenhum aluno pode cancelar o agendamento de outro.
if (cancelarAgendamento($agendamento_id, $aluno_id)) {
    // Após ação bem-sucedida: gera novo token CSRF para invalidar o anterior
    // (previne que o mesmo form seja reenviado duas vezes)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['mensagem']   = 'Agendamento cancelado com sucesso!';
} else {
    $_SESSION['mensagem'] = 'Erro ao cancelar agendamento. Tente novamente.';
}

// Redireciona de volta para a área do aluno onde a mensagem será exibida
header('Location: ' . BASE_URL . '/aluno.php');
exit;
