<?php
// ============================================================
// config/db.php — Conexão PDO com o banco MySQL
// Academia Prix Matriz
//
// RESPONSABILIDADE DESTE ARQUIVO:
//   Este arquivo é o ponto central de conexão com o banco de dados.
//   Ele faz duas coisas essenciais:
//   1. Lê as configurações do arquivo .env (senhas, host do banco, etc.)
//   2. Fornece a função getConnection() que retorna uma conexão PDO
//      reutilizável para toda a aplicação.
// ============================================================

/**
 * carregarEnv()
 *
 * Lê o arquivo .env da raiz do projeto e carrega as variáveis
 * de ambiente no PHP (ex: DB_HOST, DB_PASS, APP_ENV, etc.).
 *
 * Por que .env? Para não deixar senhas e dados sensíveis
 * diretamente no código-fonte. O .env é ignorado pelo Git.
 *
 * @param string $caminhoEnv Caminho para o arquivo .env
 */
function carregarEnv($caminhoEnv = __DIR__ . '/../.env') {
    // Se o arquivo .env não existir, exibe erro e encerra
    if (!file_exists($caminhoEnv)) {
        die('<div style="background:#1a1a1a;color:#ff6b00;font-family:monospace;padding:20px;">
             <h3>❌ Erro: Arquivo .env não encontrado</h3>
             <p>Crie um arquivo .env na raiz do projeto com as variáveis de ambiente.</p>
             </div>');
    }

    // Lê o arquivo linha por linha, ignorando linhas vazias
    $linhas = file($caminhoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);

        // Ignora linhas em branco e comentários (linhas que começam com #)
        if ($linha === '' || strpos($linha, '#') === 0) {
            continue;
        }

        // Ignora linhas sem o sinal de igual (formato inválido)
        if (strpos($linha, '=') === false) {
            continue;
        }

        // Separa CHAVE=VALOR em dois fragmentos no primeiro "="
        list($chave, $valor) = explode('=', $linha, 2);
        $chave = trim($chave);
        $valor = trim($valor);

        // Registra a variável no ambiente do processo PHP e no array $_ENV
        putenv("$chave=$valor");
        $_ENV[$chave] = $valor;
    }
}

// ── Carrega o .env antes de tudo ─────────────────────────────
carregarEnv();

// ── Constantes de configuração do banco ──────────────────────
// Cada constante lê do .env; se não encontrar, usa um valor padrão seguro.
define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');  // Endereço do servidor MySQL
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');        // Porta padrão MySQL
define('DB_NAME',    getenv('DB_NAME')    ?: 'academia_prix'); // Nome do banco de dados
define('DB_USER',    getenv('DB_USER')    ?: 'root');        // Usuário do banco
define('DB_PASS',    getenv('DB_PASS')    ?: '');            // Senha do banco
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');     // Charset (suporta emojis e acentos)

// ── Constantes da aplicação ───────────────────────────────────
define('APP_ENV',       getenv('APP_ENV')   ?: 'production');  // Ambiente: 'production' ou 'development'
define('APP_DEBUG',     filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN)); // Modo debug on/off
define('ADMIN_ID',      (int)(getenv('ADMIN_ID') ?: 1));        // ID do administrador no banco
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'admin123'); // Senha do admin (vem do .env!)

// Suporte a múltiplos administradores (IDs separados por vírgula no .env)
define('ADMIN_IDS', getenv('ADMIN_IDS') ?: getenv('ADMIN_ID'));

// ============================================================
// getConnection()
//
// Retorna uma instância PDO conectada ao MySQL.
//
// PADRÃO SINGLETON: A conexão é criada apenas uma vez e reutilizada
// em toda a requisição (variável estática $pdo). Isso evita abrir
// múltiplas conexões desnecessárias ao banco de dados.
//
// CONFIGURAÇÕES PDO ESCOLHIDAS:
//   - ERRMODE_EXCEPTION: qualquer erro SQL lança uma exceção PHP
//     (mais seguro e fácil de depurar do que erros silenciosos)
//   - FETCH_ASSOC: fetchAll() retorna arrays associativos por padrão
//     (ex: $row['nome'] em vez de $row[0])
//   - EMULATE_PREPARES = false: usa prepared statements reais do MySQL,
//     prevenindo SQL Injection de forma mais robusta
// ============================================================
function getConnection(): PDO {
    static $pdo = null; // Armazena a conexão entre chamadas da função

    if ($pdo === null) {
        // Monta a DSN (Data Source Name): string de configuração do PDO
        $dsn = 'mysql:host=' . DB_HOST
             . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME
             . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lança exceção em erros SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Retorna arrays associativos
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Prepared statements reais
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Exibe mensagem amigável de erro de conexão (sem expor detalhes sensíveis em produção)
            die('<div style="background:#1a1a1a;color:#ff6b00;font-family:monospace;padding:20px;">
                 <h3>⚠️ Erro de Conexão ao Banco</h3>
                 <p>' . htmlspecialchars($e->getMessage()) . '</p>
                 </div>');
        }
    }

    return $pdo;
}
