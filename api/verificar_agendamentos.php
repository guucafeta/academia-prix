<?php
// ============================================================
// /api/verificar_agendamentos.php
// Retorna agendamentos ATIVOS do aluno logado
// CORREÇÃO: exclui cancelados da resposta para que o JS
//           remova a linha imediatamente ao detectar ausência.
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    if (empty($_SESSION['aluno_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autenticado']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
        exit;
    }

    $alunoId = (int)$_SESSION['aluno_id'];
    $pdo     = getConnection();

    // CORREÇÃO: WHERE status != 'cancelado'
    // Agendamentos cancelados não voltam mais na resposta;
    // o JS remove qualquer linha que não estiver nesta lista.
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

    foreach ($agendamentos as &$ag) {
        $ag['data_formatada'] = date('d/m/Y', strtotime($ag['data']));
        $ag['hora_formatada'] = substr($ag['hora'], 0, 5);
    }
    unset($ag);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'total'   => count($agendamentos),
        'data'    => $agendamentos,
    ]);

} catch (Exception $e) {
    error_log('API verificar_agendamentos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => DEBUG ? $e->getMessage() : 'Erro ao buscar agendamentos',
    ]);
}
exit;