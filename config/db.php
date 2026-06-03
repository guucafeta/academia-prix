<?php
// ============================================================
// config/db.php — Conexão PDO com o banco MySQL
// Academia Prix Matriz
// ============================================================

function carregarEnv($caminhoEnv = __DIR__ . '/../.env') {
    if (!file_exists($caminhoEnv)) {
        die('<div style="background:#1a1a1a;color:#ff6b00;font-family:monospace;padding:20px;">
             <h3>❌ Erro: Arquivo .env não encontrado</h3>
             <p>Crie um arquivo .env na raiz do projeto com as variáveis de ambiente.</p>
             </div>');
    }

    $linhas = file($caminhoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || strpos($linha, '#') === 0) {
            continue;
        }
        if (strpos($linha, '=') === false) {
            continue;
        }
        list($chave, $valor) = explode('=', $linha, 2);
        $chave = trim($chave);
        $valor = trim($valor);
        putenv("$chave=$valor");
        $_ENV[$chave] = $valor;
    }
}

carregarEnv();

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'academia_prix');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('ADMIN_ID', (int)(getenv('ADMIN_ID') ?: 1));
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'admin123');

define('ADMIN_IDS', getenv('ADMIN_IDS') ?: getenv('ADMIN_ID'));

function getConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME
             . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="background:#1a1a1a;color:#ff6b00;font-family:monospace;padding:20px;">
                 <h3>⚠️ Erro de Conexão ao Banco</h3>
                 <p>' . htmlspecialchars($e->getMessage()) . '</p>
                 </div>');
        }
    }
    return $pdo;
}
