<?php
/**
 * API para atualizar perfil do aluno (nome, email, telefone).
 * Arquivo: api/atualizar_perfil_aluno.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Aceita apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

try {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $aluno_id = getCurrentAlunoId();
    if (!$aluno_id) {
        http_response_code(401);
        echo json_encode(['erro' => 'Não autenticado']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true);

    // ── Validar nome ──────────────────────────────────────────
    if (empty($dados['nome']) || strlen(trim($dados['nome'])) < 3) {
        http_response_code(400);
        echo json_encode(['erro' => 'Nome deve ter pelo menos 3 caracteres']);
        exit;
    }

    // ── Validar email ─────────────────────────────────────────
    $novo_email = trim($dados['email'] ?? '');
    if ($novo_email !== '' && !filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['erro' => 'E-mail inválido']);
        exit;
    }

    // Verificar se email já está em uso por outro aluno
    if ($novo_email !== '') {
        $pdo  = getConnection();
        $stmt = $pdo->prepare('SELECT id FROM alunos WHERE email = :email AND id != :id LIMIT 1');
        $stmt->execute([':email' => $novo_email, ':id' => $aluno_id]);
        if ($stmt->fetchColumn()) {
            http_response_code(409);
            echo json_encode(['erro' => 'Este e-mail já está em uso por outro aluno']);
            exit;
        }
    }

    $novo_nome     = trim($dados['nome']);
    $novo_telefone = trim($dados['telefone'] ?? '');

    // ── Montar UPDATE dinâmico ────────────────────────────────
    $campos  = ['nome = :nome'];
    $params  = [':nome' => $novo_nome, ':id' => $aluno_id];

    if ($novo_email !== '') {
        $campos[] = 'email = :email';
        $params[':email'] = $novo_email;
    }

    $campos[]            = 'telefone = :telefone';
    $params[':telefone'] = $novo_telefone;

    $pdo  = getConnection();
    $stmt = $pdo->prepare('UPDATE alunos SET ' . implode(', ', $campos) . ' WHERE id = :id');

    if ($stmt->execute($params)) {
        $_SESSION['aluno_nome'] = $novo_nome;
        // Sincronizar email na sessão: atualizar se veio novo, manter o atual se não veio
        $email_sessao = $novo_email !== '' ? $novo_email : (getCurrentAlunoEmail() ?? '');
        if ($novo_email !== '') {
            $_SESSION['aluno_email'] = $novo_email;
        }

        echo json_encode([
            'sucesso'   => true,
            'nome'      => $novo_nome,
            'email'     => $email_sessao,
            'telefone'  => $novo_telefone,
            'inicial'   => strtoupper(mb_substr($novo_nome, 0, 1, 'UTF-8')),
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar. Tente novamente.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>
