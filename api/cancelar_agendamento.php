<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['aluno_id'])) {
    header('Location: ' . BASE_URL . '/aluno/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

// CORRIGIDO: validar CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['mensagem'] = 'Falha na validação de segurança. Tente novamente.';
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

$aluno_id       = getCurrentAlunoId();
$agendamento_id = isset($_POST['agendamento_id']) ? (int)$_POST['agendamento_id'] : 0;

if ($agendamento_id <= 0) {
    header('Location: ' . BASE_URL . '/aluno.php');
    exit;
}

if (cancelarAgendamento($agendamento_id, $aluno_id)) {
    // Regenerar CSRF após ação bem-sucedida
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['mensagem']   = 'Agendamento cancelado com sucesso!';
} else {
    $_SESSION['mensagem'] = 'Erro ao cancelar agendamento. Tente novamente.';
}

header('Location: ' . BASE_URL . '/aluno.php');
exit;
