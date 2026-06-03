<?php
// includes/config.php
// ============================================================
// Configurações Centralizadas da Aplicação
// ============================================================

// Detectar se está rodando em XAMPP/localhost
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];

// BASE_URL será usada em TODAS as URLs absolutas
// Se o projeto está em: http://localhost/projetoacademia_final/
// BASE_URL = http://localhost/projetoacademia_final
define('BASE_URL', "{$protocol}://{$host}");

// Caminhos de diretórios (para includes)
define('ROOT_DIR',     __DIR__ . '/..');
define('ADMIN_DIR',    ROOT_DIR . '/admin');
define('INCLUDES_DIR', __DIR__);

// URLs estruturadas
define('URL_PUBLIC',  BASE_URL);
define('URL_ADMIN',   BASE_URL . '/admin');
define('URL_ASSETS',  BASE_URL . '/assets');
define('URL_API',     BASE_URL . '/api');

// Tempo de sessão (em segundos)
define('SESSION_TIMEOUT', 3600); // 1 hora

// Modo debug
define('DEBUG', false); // Mude para true apenas em desenvolvimento

// Se estiver em desenvolvimento, mostre erros
if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Inicializar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
