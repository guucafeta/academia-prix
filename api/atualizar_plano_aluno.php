<?php
/**
 * api/atualizar_plano_aluno.php — API REST: Trocar Plano do Aluno
 *
 * RESPONSABILIDADE DESTE ARQUIVO:
 *   Recebe o ID do novo plano escolhido pelo aluno e executa a troca,
 *   expirando o plano atual e ativando o novo.
 *   Chamado pelo JavaScript em assets/js/trocar_plano.js.
 *
 * MÉTODO ACEITO: POST
 * CORPO DA REQUISIÇÃO: JSON { plano_id, csrf_token }
 * AUTENTICAÇÃO: Sessão ativa com aluno_id
 *
 * SEGURANÇA APLICADA:
 *   - Verificação de método HTTP
 *   - Verificação de sessão
 *   - Validação CSRF (token no corpo JSON)
 *   - Validação do plano_id antes de executar
 *   - Renovação do CSRF token após operação bem-sucedida
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
// Sessão já iniciada pelo config.php

header('Content-Type: application/json; charset=utf-8');

// ── Verificar método HTTP ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
    exit;
}

// ── Verificar autenticação ────────────────────────────────────
if (empty($_SESSION['aluno_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado.']);
    exit;
}

// ── Ler o corpo JSON da requisição ────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);

// ── Validação CSRF ────────────────────────────────────────────
// O token deve ser enviado pelo JS no corpo JSON e deve bater com o da sessão.
// Isso impede que formulários externos executem a troca de plano sem consentimento.
$csrf_enviado = $body['csrf_token'] ?? '';
if (empty($csrf_enviado) || $csrf_enviado !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403); // Forbidden
    echo json_encode(['sucesso' => false, 'erro' => 'Token de segurança inválido. Recarregue a página.']);
    exit;
}

// ── Validar plano_id ──────────────────────────────────────────
// (int) converte o valor recebido para inteiro, eliminando qualquer injeção de texto
$plano_id = isset($body['plano_id']) ? (int)$body['plano_id'] : 0;
if ($plano_id <= 0) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Plano inválido.']);
    exit;
}

$aluno_id = (int)$_SESSION['aluno_id'];

// ── Verificar se o plano existe e está ativo no banco ─────────
// Feito aqui antes de chamar mudarPlanoAluno() para retornar dados
// do plano (nome, preço) na resposta JSON sem segunda consulta.
try {
    $pdo  = getConnection();
    $stmt = $pdo->prepare("SELECT id, nome, preco, duracao_meses FROM planos WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $plano_id]);
    $plano = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao verificar plano.']);
    exit;
}

if (!$plano) {
    http_response_code(404); // Not Found
    echo json_encode(['sucesso' => false, 'erro' => 'Plano não encontrado ou inativo.']);
    exit;
}

// ── Executar a troca de plano ─────────────────────────────────
// mudarPlanoAluno() está em functions.php e faz:
//   1. Expira o plano atual → 2. Cria novo vínculo ativo
$ok = mudarPlanoAluno($aluno_id, $plano_id);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível atualizar o plano. Tente novamente.']);
    exit;
}

// ── Renovar CSRF token ────────────────────────────────────────
// Após uma operação sensível bem-sucedida, gera um novo token.
// O JS atualiza seu token local com o novo valor retornado,
// garantindo que a próxima troca também seja protegida.
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── Resposta de sucesso ───────────────────────────────────────
// Retorna os dados do novo plano para o JS atualizar a tela sem reload
echo json_encode([
    'sucesso'         => true,
    'mensagem'        => 'Plano atualizado com sucesso!',
    'plano_id'        => (int)$plano['id'],
    'plano_nome'      => $plano['nome'],
    'plano_preco'     => (float)$plano['preco'],
    'novo_csrf_token' => $_SESSION['csrf_token'], // Novo token para o JS usar na próxima requisição
]);
