<?php
/**
 * api/atualizar_perfil_aluno.php — API REST: Atualizar Perfil do Aluno
 *
 * RESPONSABILIDADE DESTE ARQUIVO:
 *   Recebe um JSON via POST com nome, email e telefone atualizados,
 *   valida os dados, atualiza o banco e retorna os novos valores.
 *   Chamado pelo JavaScript em assets/js/perfil_aluno.js.
 *
 * MÉTODO ACEITO: POST
 * CORPO DA REQUISIÇÃO: JSON { nome, email, telefone }
 * AUTENTICAÇÃO: Sessão ativa com aluno_id
 *
 * DIFERENÇA DO FORMULÁRIO TRADICIONAL:
 *   Em vez de recarregar a página (POST → redirect), esta API responde
 *   com JSON e o JavaScript atualiza os campos sem refresh (UX mais fluida).
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Headers da resposta JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate'); // Sem cache

// ── Verificar método HTTP ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

try {
    // ── Verificar autenticação ────────────────────────────────
    // getCurrentAlunoId() lê o ID da sessão e retorna null se não estiver logado
    $aluno_id = getCurrentAlunoId();
    if (!$aluno_id) {
        http_response_code(401);
        echo json_encode(['erro' => 'Não autenticado']);
        exit;
    }

    // ── Ler e decodificar o JSON do corpo da requisição ───────
    // file_get_contents('php://input') lê o corpo bruto da requisição POST.
    // Usado quando os dados vêm como JSON (fetch API) e não como form-data.
    $dados = json_decode(file_get_contents('php://input'), true);

    // ── Validar nome ──────────────────────────────────────────
    if (empty($dados['nome']) || strlen(trim($dados['nome'])) < 3) {
        http_response_code(400); // Bad Request
        echo json_encode(['erro' => 'Nome deve ter pelo menos 3 caracteres']);
        exit;
    }

    // ── Validar e-mail ────────────────────────────────────────
    // O e-mail é opcional na atualização — se vier vazio, não altera.
    // Se vier preenchido, valida o formato.
    $novo_email = trim($dados['email'] ?? '');
    if ($novo_email !== '' && !filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['erro' => 'E-mail inválido']);
        exit;
    }

    $pdo = getConnection();

    // ── Verificar conflito de e-mail ──────────────────────────
    // Garante que o novo e-mail não está em uso por OUTRO aluno.
    // A cláusula "AND id != :id" exclui o próprio aluno da verificação
    // (ele pode manter seu e-mail atual sem conflito).
    if ($novo_email !== '') {
        $stmt = $pdo->prepare('SELECT id FROM alunos WHERE email = :email AND id != :id LIMIT 1');
        $stmt->execute([':email' => $novo_email, ':id' => $aluno_id]);
        if ($stmt->fetchColumn()) {
            http_response_code(409); // Conflict
            echo json_encode(['erro' => 'Este e-mail já está em uso por outro aluno']);
            exit;
        }
    }

    $novo_nome     = trim($dados['nome']);
    $novo_telefone = trim($dados['telefone'] ?? '');

    // ── Montar UPDATE dinâmico ────────────────────────────────
    // O e-mail só é incluído no UPDATE se foi enviado (não é obrigatório).
    // Isso evita sobrescrever o e-mail atual com vazio por acidente.
    $campos  = ['nome = :nome']; // Campo sempre atualizado
    $params  = [':nome' => $novo_nome, ':id' => $aluno_id];

    if ($novo_email !== '') {
        $campos[] = 'email = :email';
        $params[':email'] = $novo_email;
    }

    // Telefone é sempre atualizado (pode ser apagado se enviado vazio)
    $campos[]            = 'telefone = :telefone';
    $params[':telefone'] = $novo_telefone;

    // Monta a query dinamicamente: UPDATE alunos SET nome = :nome, telefone = :telefone WHERE id = :id
    $stmt = $pdo->prepare('UPDATE alunos SET ' . implode(', ', $campos) . ' WHERE id = :id');

    if ($stmt->execute($params)) {
        // ── Atualizar sessão com os novos dados ───────────────
        // Sincroniza a sessão para que o cabeçalho do site reflita imediatamente
        // as alterações sem precisar de um novo login.
        $_SESSION['aluno_nome'] = $novo_nome;
        $email_sessao = $novo_email !== '' ? $novo_email : (getCurrentAlunoEmail() ?? '');
        if ($novo_email !== '') {
            $_SESSION['aluno_email'] = $novo_email;
        }

        // Retorna os dados atualizados para o JavaScript atualizar a tela
        echo json_encode([
            'sucesso'   => true,
            'nome'      => $novo_nome,
            'email'     => $email_sessao,
            'telefone'  => $novo_telefone,
            'inicial'   => strtoupper(mb_substr($novo_nome, 0, 1, 'UTF-8')), // Primeira letra do nome para o avatar
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
