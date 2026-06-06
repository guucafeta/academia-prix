<?php
// ============================================================
// includes/functions.php — Funções Tech Forge
// Academia Prix Matriz
// ============================================================

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Verifica se o usuário está logado como admin
 */
function isAdmin(): bool {
    // Se tem a flag is_admin true na sessão, é admin
    return (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function adminPasswordValid(string $senha): bool {
    return hash_equals(ADMIN_PASSWORD, trim($senha));
}

function loginAdmin(): void {
    // Session JÁ iniciada em config.php
    session_regenerate_id(true);
    $_SESSION['aluno_id']   = ADMIN_ID;
    $_SESSION['aluno_nome'] = 'Administrador';
    $_SESSION['is_admin']   = true;
}

function logoutAdmin(): void {
    // Session JÁ iniciada em config.php
    unset($_SESSION['aluno_id'], $_SESSION['aluno_nome'], $_SESSION['is_admin']);
}

/**
 * Retorna IDs de administradores configurados no .env.
 */
function getAdminIds(): array {
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }

    $raw = getenv('ADMIN_IDS');
    if ($raw === false || trim($raw) === '') {
        $raw = getenv('ADMIN_ID');
    }
    if ($raw === false || trim($raw) === '') {
        $raw = '1';
    }

    $ids = array_filter(array_map('intval', array_map('trim', explode(',', $raw))));
    if (empty($ids)) {
        $ids = [1];
    }
    return $ids;
}

function getCurrentAlunoNome(): ?string {
    // Session JÁ iniciada em config.php
    if (!empty($_SESSION['aluno_nome'])) {
        return trim($_SESSION['aluno_nome']);
    }
    if (!empty($_SESSION['aluno_id']) && is_numeric($_SESSION['aluno_id'])) {
        try {
            $pdo  = getConnection();
            $stmt = $pdo->prepare('SELECT nome FROM alunos WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => (int)$_SESSION['aluno_id']]);
            $row = $stmt->fetch();
            return $row['nome'] ?? null;
        } catch (Exception $e) {
            error_log("Erro ao buscar nome aluno: " . $e->getMessage());
            return null;
        }
    }
    return null;
}

function getCurrentAlunoId(): ?int {
    // Session JÁ iniciada em config.php
    if (!empty($_SESSION['aluno_id']) && is_numeric($_SESSION['aluno_id'])) {
        return (int)$_SESSION['aluno_id'];
    }
    return null;
}

// ── Professores ─────────────────────────────────────────────

/**
 * Retorna todos os professores ativos do banco como array.
 */
function getProfessores(): array {
    try {
        $pdo  = getConnection();
        $stmt = $pdo->query("SELECT * FROM professores WHERE ativo = 1 ORDER BY nome ASC");
        $professores = $stmt->fetchAll();
        return uniqueByFields($professores, ['nome', 'especialidade', 'bio', 'instagram']);
    } catch (Exception $e) {
        error_log("Erro ao buscar professores: " . $e->getMessage());
        return [];
    }
}

/**
 * Remove itens duplicados com base em chaves.
 */
function uniqueByFields(array $items, array $fields): array {
    $result = [];
    $seen = [];
    foreach ($items as $item) {
        $keyParts = [];
        foreach ($fields as $field) {
            $keyParts[] = mb_strtolower(trim((string)($item[$field] ?? '')));
        }
        $key = implode('||', $keyParts);
        if ($key === '' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $result[] = $item;
    }
    return $result;
}

/**
 * Filtra professores por especialidade.
 */
function filtrarProfessoresPorEspecialidade(array $professores, string $especialidade): array {
    if (empty($especialidade)) {
        return $professores;
    }
    return array_filter($professores, function(array $p) use ($especialidade): bool {
        return stripos($p['especialidade'] ?? '', $especialidade) !== false;
    });
}

// ── Planos ──────────────────────────────────────────────────

/**
 * Retorna todos os planos ativos do banco.
 */
function getPlanos(): array {
    try {
        $pdo  = getConnection();
        $stmt = $pdo->query("SELECT * FROM planos WHERE ativo = 1 ORDER BY preco ASC");
        $planos = $stmt->fetchAll();
        return uniqueByFields($planos, ['nome', 'preco', 'duracao_meses']);
    } catch (Exception $e) {
        error_log("Erro ao buscar planos: " . $e->getMessage());
        return [];
    }
}

/**
 * Calcula o preço mensal equivalente de um plano.
 */
function precoPorMes(array $plano): float {
    if ((int)$plano['duracao_meses'] <= 0) {
        return (float)$plano['preco'];
    }
    return round((float)$plano['preco'] / (int)$plano['duracao_meses'], 2);
}

/**
 * Filtra planos por faixa de preço.
 */
function filtrarPlanosPorPreco(array $planos, float $min, float $max): array {
    return array_filter($planos, function(array $p) use ($min, $max): bool {
        $preco = (float)$p['preco'];
        return $preco >= $min && $preco <= $max;
    });
}

// ── Modalidades ─────────────────────────────────────────────

/**
 * Retorna todas as modalidades do banco.
 */
function getModalidades(): array {
    try {
        $pdo  = getConnection();
        $stmt = $pdo->query("SELECT * FROM modalidades ORDER BY nome ASC");
        $modalidades = $stmt->fetchAll();
        return uniqueByFields($modalidades, ['nome', 'horario']);
    } catch (Exception $e) {
        error_log("Erro ao buscar modalidades: " . $e->getMessage());
        return [];
    }
}

// ── Agendamentos ────────────────────────────────────────────

/**
 * Valida os dados de um agendamento antes de inserir.
 */
function validarAgendamento(array $dados): array {
    $erros = [];

    if (empty($dados['aluno_id']) || !is_numeric($dados['aluno_id'])) {
        $erros[] = 'Aluno inválido.';
    }
    if (empty($dados['professor_id']) || !is_numeric($dados['professor_id'])) {
        $erros[] = 'Selecione um professor.';
    }
    if (empty($dados['data'])) {
        $erros[] = 'A data é obrigatória.';
    } elseif (strtotime($dados['data']) < strtotime('today')) {
        $erros[] = 'A data não pode ser no passado.';
    }
    if (empty($dados['hora'])) {
        $erros[] = 'O horário é obrigatório.';
    } else {
        // Valida se o horário pertence aos disponíveis para o dia da semana
        if (!preg_match('/^\d{2}:\d{2}$/', $dados['hora'])) {
            $erros[] = 'Formato de horário inválido.';
        } elseif (!empty($dados['data'])) {
            $dow = (int) date('w', strtotime($dados['data'])); // 0=Dom, 6=Sáb
            $horariosDia = HORARIOS_POR_DIA[$dow] ?? [];
            if (empty($horariosDia)) {
                $erros[] = 'A academia não funciona neste dia.';
            } elseif (!in_array($dados['hora'], $horariosDia, true)) {
                $erros[] = 'Horário indisponível para o dia selecionado.';
            }
        }
    }

    return $erros;
}

/**
 * Salva um agendamento no banco após validação.
 */
function salvarAgendamento(array $dados): bool {
    $erros = validarAgendamento($dados);
    if (!empty($erros)) {
        return false;
    }
    try {
        $pdo  = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO agendamentos (aluno_id, professor_id, data, hora, observacao)
            VALUES (:aluno_id, :professor_id, :data, :hora, :observacao)
        ");
        return $stmt->execute([
            ':aluno_id'     => (int)$dados['aluno_id'],
            ':professor_id' => (int)$dados['professor_id'],
            ':data'         => $dados['data'],
            ':hora'         => $dados['hora'],
            ':observacao'   => $dados['observacao'] ?? '',
        ]);
    } catch (Exception $e) {
        error_log("Erro ao salvar agendamento: " . $e->getMessage());
        return false;
    }
}

/**
 * Retorna agendamentos de um aluno.
 */
function getAgendamentosAluno(int $alunoId): array {
    try {
        $pdo  = getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, p.nome AS professor_nome, p.especialidade
            FROM agendamentos a
            JOIN professores p ON a.professor_id = p.id
            WHERE a.aluno_id = :id
              AND a.status != 'cancelado'
            ORDER BY a.data ASC, a.hora ASC
        ");
        $stmt->execute([':id' => $alunoId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erro ao buscar agendamentos: " . $e->getMessage());
        return [];
    }
}

// ── Utilitários ─────────────────────────────────────────────

/**
 * Formata preço em Real brasileiro.
 */
function formatarPreco(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata data do MySQL para formato brasileiro.
 */
function formatarData(string $data): string {
    if (empty($data)) return '-';
    return date('d/m/Y', strtotime($data));
}

/**
 * Retorna a classe de badge Bootstrap para status do agendamento.
 */
function badgeStatus(string $status): string {
    return match($status) {
        'confirmado' => 'bg-success',
        'cancelado'  => 'bg-danger',
        default      => 'bg-warning text-dark',
    };
}

/**
 * Cancela/remove um agendamento de um aluno específico.
 */
function cancelarAgendamento(int $agendamentoId, int $alunoId): bool {
    try {
        $pdo  = getConnection();
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = :id AND aluno_id = :aluno_id AND status = 'pendente'");
        $stmt->execute([':id' => $agendamentoId, ':aluno_id' => $alunoId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Erro ao cancelar agendamento: " . $e->getMessage());
        return false;
    }
}

/**
 * Sanitiza string para exibição segura em HTML.
 */
function sanitizar(string $valor): string {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Busca o plano atual de um aluno.
 */
function getPlanoAluno($aluno_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT p.id, p.nome, p.preco, p.duracao_meses, p.descricao 
            FROM planos p
            INNER JOIN aluno_plano ap ON ap.plano_id = p.id
            WHERE ap.aluno_id = :aluno_id
              AND ap.status = 'ativo'
              AND p.duracao_meses > 0
            ORDER BY ap.id DESC
            LIMIT 1
        ");
        $stmt->execute([':aluno_id' => $aluno_id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    } catch (Exception $e) {
        error_log("Erro ao buscar plano do aluno: " . $e->getMessage());
        return null;
    }
}

/**
 * Muda o plano de um aluno.
 */
function mudarPlanoAluno($aluno_id, $plano_id) {
    try {
        $pdo = getConnection();

        // Validar se o plano existe e é válido
        $stmt_validar = $pdo->prepare("
            SELECT id, duracao_meses FROM planos WHERE id = :plano_id AND ativo = 1 AND duracao_meses > 0
        ");
        $stmt_validar->execute([':plano_id' => $plano_id]);
        $plano = $stmt_validar->fetch(PDO::FETCH_ASSOC);

        if (!$plano) {
            throw new Exception("Plano inválido");
        }

        // Expirar planos ativos anteriores do aluno
        $stmt_exp = $pdo->prepare("
            UPDATE aluno_plano SET status = 'expirado'
            WHERE aluno_id = :aluno_id AND status = 'ativo'
        ");
        $stmt_exp->execute([':aluno_id' => $aluno_id]);

        // Calcular datas com base na duração do plano
        $data_inicio = date('Y-m-d');
        $data_fim = date('Y-m-d', strtotime("+{$plano['duracao_meses']} months"));

        // Inserir novo plano ativo
        $stmt_insert = $pdo->prepare("
            INSERT INTO aluno_plano (aluno_id, plano_id, data_inicio, data_fim, status)
            VALUES (:aluno_id, :plano_id, :data_inicio, :data_fim, 'ativo')
        ");
        return $stmt_insert->execute([
            ':aluno_id'    => $aluno_id,
            ':plano_id'    => $plano_id,
            ':data_inicio' => $data_inicio,
            ':data_fim'    => $data_fim,
        ]);
    } catch (Exception $e) {
        error_log("Erro ao mudar plano do aluno: " . $e->getMessage());
        return false;
    }
}

/**
 * Retorna email do aluno logado.
 */
function getCurrentAlunoEmail(): ?string {
    // Session JÁ iniciada em config.php
    if (!empty($_SESSION['aluno_email'])) return trim($_SESSION['aluno_email']);
    if (!empty($_SESSION['aluno_id']) && is_numeric($_SESSION['aluno_id'])) {
        try {
            $pdo  = getConnection();
            $stmt = $pdo->prepare('SELECT email FROM alunos WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => (int)$_SESSION['aluno_id']]);
            $result = $stmt->fetchColumn();
            if ($result) { $_SESSION['aluno_email'] = $result; return $result; }
        } catch (Exception $e) {
            error_log("Erro ao buscar email aluno: " . $e->getMessage());
        }
    }
    return null;
}

/**
 * Retorna telefone do aluno logado.
 */
function getCurrentAlunoTelefone(): ?string {
    // Session JÁ iniciada em config.php
    if (!empty($_SESSION['aluno_id']) && is_numeric($_SESSION['aluno_id'])) {
        try {
            $pdo  = getConnection();
            $stmt = $pdo->prepare('SELECT telefone FROM alunos WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => (int)$_SESSION['aluno_id']]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            error_log("Erro ao buscar telefone aluno: " . $e->getMessage());
            return null;
        }
    }
    return null;
}

/**
 * Retorna data de criação da conta do aluno.
 */
function getCurrentAlunoDataCriacao(): ?string {
    // Session JÁ iniciada em config.php
    if (!empty($_SESSION['aluno_id']) && is_numeric($_SESSION['aluno_id'])) {
        try {
            $pdo  = getConnection();
            $stmt = $pdo->prepare('SELECT DATE_FORMAT(criado_em, "%d/%m/%Y") FROM alunos WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => (int)$_SESSION['aluno_id']]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            error_log("Erro ao buscar data criação aluno: " . $e->getMessage());
            return null;
        }
    }
    return null;
}

// ============================================================
// FUNÇÕES HELPER DE ROTEAMENTO
// ============================================================

/**
 * Gera URL absoluta de forma segura
 */
if (!function_exists('route')) {
    function route($path) {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

/**
 * Gera URL da área admin
 */
if (!function_exists('admin_route')) {
    function admin_route($page) {
        return URL_ADMIN . '/' . ltrim($page, '/');
    }
}

/**
 * Gera URL da área pública
 */
if (!function_exists('public_route')) {
    function public_route($page) {
        return URL_PUBLIC . '/' . ltrim($page, '/');
    }
}

/**
 * Gera URL de asset (CSS, JS, imagens)
 */
if (!function_exists('asset')) {
    function asset($path) {
        return URL_ASSETS . '/' . ltrim($path, '/');
    }
}

/**
 * Redireciona para uma rota admin
 */
if (!function_exists('redirect_admin')) {
    function redirect_admin($page) {
        header('Location: ' . admin_route($page));
        exit;
    }
}

/**
 * Redireciona para uma rota pública
 */
if (!function_exists('redirect_public')) {
    function redirect_public($page) {
        header('Location: ' . public_route($page));
        exit;
    }
}

/**
 * Verifica e protege páginas admin
 */
if (!function_exists('requireAdmin_secure')) {
    function requireAdmin_secure() {
        if (!isAdmin()) {
            header('Location: ' . admin_route('login.php'));
            exit;
        }
    }
}

/**
 * Verifica e protege páginas de aluno
 */
if (!function_exists('requireAluno_secure')) {
    function requireAluno_secure() {
        if (empty($_SESSION['aluno_id'])) {
            header('Location: ' . public_route('aluno/login.php'));
            exit;
        }
    }
}