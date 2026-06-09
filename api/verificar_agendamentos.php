<?php
// ============================================================
// api/verificar_agendamentos.php — API REST: Listar Agendamentos Ativos
//
// RESPONSABILIDADE DESTE ARQUIVO:
//   Endpoint consultado periodicamente pelo JavaScript da área do aluno
//   (assets/js/verificar_agendamentos.js) para manter a tabela de
//   agendamentos atualizada em tempo real sem recarregar a página.
//
// MÉTODO ACEITO: GET
// AUTENTICAÇÃO:  Sessão ativa com aluno_id
// RETORNO:       JSON com lista de agendamentos ativos
//
// POR QUE EXCLUIR CANCELADOS?
//   O JS usa a lista retornada como "verdade atual": qualquer linha
//   presente na tabela HTML mas ausente nesta resposta é removida
//   com efeito de fade-out. Isso garante sincronização imediata.
// ============================================================

// ── Headers da resposta ───────────────────────────────────────
header('Content-Type: application/json; charset=utf-8'); // Informa que retornamos JSON
header('X-Content-Type-Options: nosniff');                // Previne MIME sniffing no navegador
header('Cache-Control: no-store');                        // Não armazenar em cache (dados sempre frescos)

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // ── Verificar autenticação ────────────────────────────────
    // Se não há sessão ativa, retorna 401 Unauthorized (não autorizado)
    if (empty($_SESSION['aluno_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autenticado']);
        exit;
    }

    // ── Verificar método HTTP ─────────────────────────────────
    // Esta rota só aceita GET. Qualquer outro método retorna 405 Method Not Allowed.
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
        exit;
    }

    $alunoId = (int)$_SESSION['aluno_id'];
    $pdo     = getConnection();

    // ── Consulta principal ────────────────────────────────────
    // Retorna apenas agendamentos que NÃO são cancelados.
    // JOIN com professores traz nome, especialidade e telefone em uma única query.
    // Ordenado por data e hora para exibição cronológica.
    $stmt = $pdo->prepare('
        SELECT
            a.id,
            a.data,
            a.hora,
            a.status,
            a.observacao,
            p.id           AS professor_id,
            p.nome         AS professor_nome,
            p.especialidade,
            p.telefone     AS professor_telefone
        FROM agendamentos a
        JOIN professores p ON a.professor_id = p.id
        WHERE a.aluno_id = :aluno_id
          AND a.status   != \'cancelado\'
        ORDER BY a.data ASC, a.hora ASC
    ');

    $stmt->execute([':aluno_id' => $alunoId]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Pós-processamento: formatar datas para exibição ───────
    // Adiciona campos formatados para facilitar o trabalho do JavaScript,
    // evitando que o JS precise manipular strings de data.
    foreach ($agendamentos as &$ag) {
        $ag['data_formatada'] = date('d/m/Y', strtotime($ag['data'])); // Ex: "15/06/2025"
        $ag['hora_formatada'] = substr($ag['hora'], 0, 5);              // Ex: "14:30" (remove segundos)
    }
    unset($ag); // Limpa a referência para evitar efeitos colaterais

    // ── Resposta de sucesso ───────────────────────────────────
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'total'   => count($agendamentos), // Útil para debug e para o JS verificar se está vazio
        'data'    => $agendamentos,
    ]);

} catch (Exception $e) {
    // ── Tratamento de erro interno ────────────────────────────
    // Em produção (DEBUG = false), não expõe detalhes do erro ao usuário
    error_log('API verificar_agendamentos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => DEBUG ? $e->getMessage() : 'Erro ao buscar agendamentos',
    ]);
}
exit;
