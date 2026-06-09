<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Limpar dados da sessão do admin
logoutAdmin();

// Invalidar o cookie de sessão no navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destruir a sessão no servidor
session_destroy();

// Redirecionar para login
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
