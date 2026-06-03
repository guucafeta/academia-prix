<?php
/**
 * API: atualizar_plano_aluno.php
 * Permite que o aluno logado troque seu plano direto da área do aluno.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
    exit;
}

// Aluno deve estar logado
if (empty($_SESSION['aluno_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado.']);
    exit;
}

// Ler body JSON
$body = json_decode(file_get_contents('php://input'), true);

// Validar CSRF
$csrf_enviado = $body['csrf_token'] ?? '';
if (empty($csrf_enviado) || $csrf_enviado !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'Token de segurança inválido. Recarregue a página.']);
    exit;
}

$plano_id = isset($body['plano_id']) ? (int)$body['plano_id'] : 0;
if ($plano_id <= 0) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Plano inválido.']);
    exit;
}

$aluno_id = (int)$_SESSION['aluno_id'];

// Buscar dados do plano para confirmar que existe e está ativo
try {
    $pdo  = getConnection();
    $stmt = $pdo->prepare("SELECT id, nome, preco, duracao_meses FROM planos WHERE id = :id AND ativo = 1 AND duracao_meses > 0");
    $stmt->execute([':id' => $plano_id]);
    $plano = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao verificar plano.']);
    exit;
}

if (!$plano) {
    http_response_code(404);
    echo json_encode(['sucesso' => false, 'erro' => 'Plano não encontrado ou inativo.']);
    exit;
}

// Executar troca de plano (função já existente em functions.php)
$ok = mudarPlanoAluno($aluno_id, $plano_id);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível atualizar o plano. Tente novamente.']);
    exit;
}

// Renovar CSRF token após operação bem-sucedida
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo json_encode([
    'sucesso'         => true,
    'mensagem'        => 'Plano atualizado com sucesso!',
    'plano_id'        => (int)$plano['id'],
    'plano_nome'      => $plano['nome'],
    'plano_preco'     => (float)$plano['preco'],
    'novo_csrf_token' => $_SESSION['csrf_token'],
]);
