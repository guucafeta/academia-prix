<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar se era admin antes de destruir a sessão
$era_admin = !empty($_SESSION['is_admin']);

// Limpar todos os dados da sessão
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Se era admin, volta para a página inicial como visitante comum
// Se era aluno, vai para o login de aluno
if ($era_admin) {
    header('Location: ' . BASE_URL . '/index.php');
} else {
    header('Location: ' . BASE_URL . '/aluno/login.php');
}
exit;