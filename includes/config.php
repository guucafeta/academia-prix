<?php
// ============================================================
// includes/config.php — Configurações Centralizadas da Aplicação
//
// RESPONSABILIDADE DESTE ARQUIVO:
//   Este é o primeiro arquivo carregado em TODAS as páginas do sistema.
//   Ele define a URL base do site, caminhos de diretórios, inicia a
//   sessão do usuário e controla o modo de depuração.
//
//   Ordem de inclusão recomendada em cada página:
//     1. config.php  ← define BASE_URL e inicia a sessão
//     2. constants.php ← horários, valores fixos
//     3. functions.php  ← funções que usam as constantes acima
// ============================================================

// ── Detectar a URL base do site ───────────────────────────────
// Lê APP_URL do .env (definido pelo admin ao fazer o deploy).
// Se não estiver configurado, detecta automaticamente pelo protocolo e host.
// Exemplo de resultado: "https://academia-prix.com.br" ou "http://localhost"
$_app_url = getenv('APP_URL') ?: '';
if (!empty($_app_url)) {
    // Usa a URL definida manualmente no .env (recomendado em produção)
    define('BASE_URL', rtrim($_app_url, '/'));
} else {
    // Detecta automaticamente: "https" se o servidor usa SSL, "http" caso contrário
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST']; // ex: "localhost" ou "meusite.com"
    define('BASE_URL', "{$protocol}://{$host}");
}

// ── Constantes de caminhos de diretório (no servidor) ─────────
// Usadas para require_once e include — caminhos absolutos no sistema de arquivos.
define('ROOT_DIR',     __DIR__ . '/..');        // Raiz do projeto
define('ADMIN_DIR',    ROOT_DIR . '/admin');    // Pasta do painel admin
define('INCLUDES_DIR', __DIR__);               // Pasta includes/

// ── Constantes de URL (para links e redirecionamentos) ────────
// Usadas para gerar links corretos nas páginas HTML.
define('URL_PUBLIC',  BASE_URL);               // Site público: https://seusite.com
define('URL_ADMIN',   BASE_URL . '/admin');    // Painel admin: https://seusite.com/admin
define('URL_ASSETS',  BASE_URL . '/assets');   // CSS/JS/imagens: https://seusite.com/assets
define('URL_API',     BASE_URL . '/api');      // Endpoints da API: https://seusite.com/api

// ── Configuração de sessão ────────────────────────────────────
// A sessão armazena: aluno_id, aluno_nome, is_admin, csrf_token, etc.
define('SESSION_TIMEOUT', 3600); // Tempo máximo de inatividade: 1 hora (3600 segundos)

// ── Modo de depuração ─────────────────────────────────────────
// Em desenvolvimento: DEBUG = true → exibe erros PHP na tela.
// Em produção:        DEBUG = false → oculta erros (segurança).
define('DEBUG', false); // ← mude para TRUE apenas em ambiente local!

if (DEBUG) {
    ini_set('display_errors', 1);  // Mostra erros na tela
    error_reporting(E_ALL);        // Reporta todos os tipos de erro
} else {
    ini_set('display_errors', 0);  // Oculta erros (não exibe para o usuário)
    error_reporting(0);            // Suprime todos os erros
}

// ── Iniciar sessão PHP ────────────────────────────────────────
// session_start() deve ser chamada uma única vez por requisição.
// A verificação session_status() evita o erro "session already started".
// Após esta linha, $_SESSION está disponível em toda a aplicação.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
