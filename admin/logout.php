<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fazer logout do admin
logoutAdmin();

// Redirecionar para login
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
