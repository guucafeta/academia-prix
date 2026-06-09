<?php
// ============================================================
// includes/functions.php — Biblioteca de Funções da Academia Prix
//
// RESPONSABILIDADE DESTE ARQUIVO:
//   Centraliza TODAS as funções reutilizáveis da aplicação.
//   Nenhuma página deve conter lógica de negócio diretamente —
//   toda operação com o banco deve passar por funções daqui.
//
// ORGANIZAÇÃO:
//   1. Autenticação (admin e aluno)
//   2. Professores
//   3. Planos
//   4. Modalidades
//   5. Agendamentos
//   6. Utilitários (formatação, sanitização)
//   7. Helpers de roteamento (URLs e redirecionamentos)
// ============================================================

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../config/db.php';

// ============================================================
// 1. AUTENTICAÇÃO
// ============================================================

/**
 * Verifica se o usuário logado na sessão é um administrador.
 *
 * A flag is_admin é definida em loginAdmin() e removida em logoutAdmin().
 * Usada para proteger todas as páginas da área /admin/.
 *
 * @return bool true se for admin, false caso contrário
 */
function isAdmin(): bool {
    return (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);
}

/**
 * Redireciona para a página de login admin se o usuário não for admin.
 * Use no topo de qualquer página da área /admin/.
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Valida a senha do administrador com comparação segura (timing-safe).
 *
 * hash_equals() protege contra ataques de timing (timing attack):
 * mesmo que a senha esteja errada, o tempo de comparação é constante,
 * impossibilitando adivinhar a senha por diferença de tempo de resposta.
 *
 * @param string $senha Senha digitada no formulário
 * @return bool true se a senha for correta
 */
function adminPasswordValid(string $senha): bool {
    return hash_equals(ADMIN_PASSWORD, trim($senha));
}

/**
 * Registra o login do administrador na sessão.
 *
 * session_regenerate_id(true) gera um novo ID de sessão e destrói o antigo,
 * prevenindo Session Fixation Attack (ataque onde um invasor usa um ID de
 * sessão conhecido para sequestrar a conta após o login).
 */
function loginAdmin(): void {
    session_regenerate_id(true); // Proteção contra Session Fixation
    $_SESSION['aluno_id']   = ADMIN_ID;
    $_SESSION['aluno_nome'] = 'Administrador';
    $_SESSION['is_admin']   = true;
}

/**
 * Encerra a sessão do administrador removendo as variáveis relevantes.
 * Não destrói a sessão inteira para preservar outros dados como o csrf_token.
 */
function logoutAdmin(): void {
    unset($_SESSION['aluno_id'], $_SESSION['aluno_nome'], $_SESSION['is_admin']);
}

/**
 * Retorna a lista de IDs considerados administradores.
 *
 * Suporta múltiplos admins via .env: ADMIN_IDS=1,2,3
 * Usa cache estático para não repetir a leitura em múltiplas chamadas.
 *
 * @return array Lista de IDs (inteiros) dos admins
 */
function getAdminIds(): array {
    static $ids = null; // Cache: calculado apenas uma vez por requisição
    if ($ids !== null) return $ids;

    // Lê ADMIN_IDS do .env, com fallback para ADMIN_ID e depois para "1"
    $raw = getenv('ADMIN_IDS');
    if ($raw === false || trim($raw) === '') $raw = getenv('ADMIN_ID');
    if ($raw === false || trim($raw) === '') $raw = '1';

    // Converte "1,2,3" → [1, 2, 3] removendo valores inválidos
    $ids = array_filter(array_map('intval', array_map('trim', explode(',', $raw))));
    if (empty($ids)) $ids = [1];
    return $ids;
}

/**
 * Retorna o nome do aluno logado.
 * Primeiro tenta a sessão (mais rápido); se não tiver, busca no banco.
 *
 * @return string|null Nome do aluno ou null se não estiver logado
 */
function getCurrentAlunoNome(): ?string {
    if (!empty($_SESSION['aluno_nome'])) {
        return trim($_SESSION['aluno_nome']);
    }
    // Fallback: buscar no banco se o nome não estiver na sessão
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

/**
 * Retorna o ID do aluno logado como inteiro, ou null.
 * Sempre valida que é numérico antes de retornar.
 */
function getCurrentAlunoId(): ?int {
    if (!empty($_SESSION['aluno_id']) && is_numeric($_SESSION['aluno_id'])) {
        return (int)$_SESSION['aluno_id'];
    }
    return null;
}

// ============================================================
// 2. PROFESSORES
// ============================================================

/**
 * Retorna todos os professores ativos do banco de dados.
 *
 * O filtro "WHERE ativo = 1" garante que professores desativados
 * pelo admin não apareçam no site público.
 *
 * uniqueByFields() remove duplicatas que possam surgir de inserts acidentais.
 *
 * @return array Lista de professores (arrays associativos)
 */
function getProfessores(): array {
    try {
        $pdo  = getConnection();
        $stmt = $pdo->query("SELECT * FROM professores WHERE ativo = 1 ORDER BY nome ASC");
        $professores = $stmt->fetchAll();
        // Remove possíveis duplicatas pelo conjunto nome+especialidade+bio+instagram
        return uniqueByFields($professores, ['nome', 'especialidade', 'bio', 'instagram']);
    } catch (Exception $e) {
        error_log("Erro ao buscar professores: " . $e->getMessage());
        return []; // Retorna array vazio em caso de falha (evita erro na página)
    }
}

/**
 * Remove itens duplicados de um array com base em campos específicos.
 *
 * Útil para garantir unicidade quando o banco pode ter registros repetidos.
 * A chave de comparação é gerada concatenando os valores dos campos escolhidos,
 * tudo em minúsculas para comparação case-insensitive.
 *
 * Exemplo: uniqueByFields($professores, ['nome', 'especialidade'])
 * Remove professores com mesmo nome E mesma especialidade.
 *
 * @param array $items  Lista de registros
 * @param array $fields Nomes dos campos que formam a chave única
 * @return array Lista sem duplicatas
 */
function uniqueByFields(array $items, array $fields): array {
    $result = [];
    $seen   = []; // Dicionário para rastrear chaves já vistas
    foreach ($items as $item) {
        $keyParts = [];
        foreach ($fields as $field) {
            $keyParts[] = mb_strtolower(trim((string)($item[$field] ?? '')));
        }
        $key = implode('||', $keyParts); // Ex: "joão||musculação||..."
        if ($key === '' || isset($seen[$key])) continue; // Pula duplicata
        $seen[$key] = true;
        $result[] = $item;
    }
    return $result;
}

/**
 * Filtra lista de professores por especialidade (busca parcial, case-insensitive).
 * Usado para futuros filtros no front-end.
 *
 * @param array  $professores Lista completa de professores
 * @param string $especialidade Texto a procurar na especialidade (ex: "Pilates")
 * @return array Professores filtrados
 */
function filtrarProfessoresPorEspecialidade(array $professores, string $especialidade): array {
    if (empty($especialidade)) return $professores;
    return array_filter($professores, function(array $p) use ($especialidade): bool {
        return stripos($p['especialidade'] ?? '', $especialidade) !== false;
    });
}

// ============================================================
// 3. PLANOS
// ============================================================

/**
 * Retorna todos os planos ativos ordenados do mais barato ao mais caro.
 * Planos desativados pelo admin não aparecem para os alunos.
 *
 * @return array Lista de planos
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
 * Calcula o valor mensal equivalente de um plano para exibição.
 *
 * Exemplo: plano de R$ 300 por 3 meses → R$ 100/mês.
 * Para planos mensais (duracao_meses = 1), retorna o próprio preço.
 *
 * @param array $plano Registro de plano do banco
 * @return float Preço por mês
 */
function precoPorMes(array $plano): float {
    if ((int)$plano['duracao_meses'] <= 0) return (float)$plano['preco'];
    return round((float)$plano['preco'] / (int)$plano['duracao_meses'], 2);
}

/**
 * Filtra planos dentro de uma faixa de preço.
 * Usado em filtros de busca de planos.
 *
 * @param array $planos Lista de planos
 * @param float $min    Preço mínimo
 * @param float $max    Preço máximo
 * @return array Planos dentro da faixa
 */
function filtrarPlanosPorPreco(array $planos, float $min, float $max): array {
    return array_filter($planos, function(array $p) use ($min, $max): bool {
        $preco = (float)$p['preco'];
        return $preco >= $min && $preco <= $max;
    });
}

// ============================================================
// 4. MODALIDADES
// ============================================================

/**
 * Retorna todas as modalidades disponíveis na academia.
 * Exibidas na seção de modalidades do site público.
 *
 * @return array Lista de modalidades
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

// ============================================================
// 5. AGENDAMENTOS
// ============================================================

/**
 * Retorna os horários disponíveis por dia da semana.
 *
 * Os horários ficam cadastrados na tabela "horarios_funcionamento" do banco
 * (gerenciados pelo admin em /admin/horarios.php).
 *
 * O resultado é um array indexado por número do dia (0=Dom, 1=Seg...6=Sáb),
 * onde cada valor é uma lista de strings "HH:MM".
 * Exemplo: [1 => ['05:30', '06:00', ...], 2 => [...], ...]
 *
 * CACHE ESTÁTICO: buscado apenas uma vez por requisição para não
 * consultar o banco toda vez que o formulário precisar dos horários.
 *
 * @return array Horários indexados por dia da semana
 */
function getHorariosPorDia(): array {
    static $cache = null;
    if ($cache !== null) return $cache; // Retorna o cache se já foi buscado
    try {
        $pdo  = getConnection();
        $stmt = $pdo->query("SELECT dia_semana, hora FROM horarios_funcionamento WHERE ativo = 1 ORDER BY dia_semana, hora");
        $rows = $stmt->fetchAll();
        $cache = [];
        foreach ($rows as $row) {
            // substr(..., 0, 5) pega apenas "HH:MM" do formato "HH:MM:SS" do MySQL
            $cache[(int)$row['dia_semana']][] = substr($row['hora'], 0, 5);
        }
    } catch (Exception $e) {
        // Fallback para a constante em constants.php se o banco falhar
        $cache = defined('HORARIOS_POR_DIA') ? HORARIOS_POR_DIA : [];
    }
    return $cache;
}

/**
 * Valida os dados de um agendamento antes de salvar no banco.
 *
 * REGRAS VALIDADAS:
 *   - aluno_id e professor_id devem ser numéricos e preenchidos
 *   - data é obrigatória e não pode ser no passado
 *   - hora deve ter o formato HH:MM
 *   - o horário deve existir na tabela de horários daquele dia da semana
 *   - a academia deve funcionar naquele dia (sem horários = academia fechada)
 *
 * @param array $dados Dados do formulário de agendamento
 * @return array Lista de erros (vazia se tudo ok)
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
        $erros[] = 'A data não pode ser no passado.'; // Impede agendamentos retroativos
    }
    if (empty($dados['hora'])) {
        $erros[] = 'O horário é obrigatório.';
    } else {
        // Valida o formato HH:MM com regex
        if (!preg_match('/^\d{2}:\d{2}$/', $dados['hora'])) {
            $erros[] = 'Formato de horário inválido.';
        } elseif (!empty($dados['data'])) {
            // Verifica se o horário existe para aquele dia da semana
            $dow         = (int) date('w', strtotime($dados['data'])); // 0=Dom...6=Sáb
            $horariosDia = getHorariosPorDia()[$dow] ?? [];
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
 * Salva um novo agendamento no banco de dados.
 *
 * Usa Prepared Statement para evitar SQL Injection:
 * os valores do usuário (:aluno_id, :professor_id, etc.) são passados
 * separadamente da query SQL, nunca concatenados diretamente.
 *
 * @param array $dados Dados validados do agendamento
 * @return bool true se salvou com sucesso, false em caso de erro
 */
function salvarAgendamento(array $dados): bool {
    $erros = validarAgendamento($dados);
    if (!empty($erros)) return false; // Revalida por segurança antes de inserir

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
 * Retorna os agendamentos ativos de um aluno específico.
 *
 * JOIN com professores para trazer nome e especialidade do professor
 * diretamente na mesma consulta (evita N+1 queries).
 *
 * Agendamentos cancelados são excluídos (status != 'cancelado').
 *
 * @param int $alunoId ID do aluno
 * @return array Lista de agendamentos com dados do professor
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

/**
 * Cancela um agendamento de um aluno.
 *
 * SEGURANÇA: a cláusula "AND aluno_id = :aluno_id" garante que um aluno
 * só pode cancelar seus próprios agendamentos (nunca os de outro aluno).
 * Apenas agendamentos com status 'pendente' podem ser cancelados.
 *
 * rowCount() > 0 confirma que realmente atualizou alguma linha
 * (retorna false se o agendamento não existir ou já foi confirmado).
 *
 * @param int $agendamentoId ID do agendamento
 * @param int $alunoId       ID do aluno dono do agendamento
 * @return bool true se cancelou com sucesso
 */
function cancelarAgendamento(int $agendamentoId, int $alunoId): bool {
    try {
        $pdo  = getConnection();
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = :id AND aluno_id = :aluno_id AND status = 'pendente'");
        $stmt->execute([':id' => $agendamentoId, ':aluno_id' => $alunoId]);
        return $stmt->rowCount() > 0; // true somente se cancelou de fato
    } catch (Exception $e) {
        error_log("Erro ao cancelar agendamento: " . $e->getMessage());
        return false;
    }
}

// ============================================================
// 6. PLANOS DO ALUNO
// ============================================================

/**
 * Retorna o plano atualmente ativo de um aluno.
 *
 * JOIN entre a tabela aluno_plano (vínculo) e planos (dados do plano).
 * Retorna o plano mais recente (ORDER BY ap.id DESC LIMIT 1).
 * Retorna null se o aluno não tiver nenhum plano ativo.
 *
 * @param int $aluno_id
 * @return array|null Dados do plano ou null
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
 * Troca o plano de um aluno para um novo plano.
 *
 * FLUXO DA OPERAÇÃO:
 *   1. Valida se o novo plano existe e está ativo
 *   2. Marca o plano anterior como 'expirado'
 *   3. Calcula as datas de início e fim do novo plano
 *   4. Insere o novo vínculo com status 'ativo'
 *
 * PLANOS AVULSOS (duracao_meses = 0): tratados como 30 dias corridos.
 * PLANOS MENSAIS (duracao_meses > 0): calculados com strtotime("+N months").
 *
 * @param int $aluno_id ID do aluno
 * @param int $plano_id ID do novo plano escolhido
 * @return bool true se a troca foi realizada com sucesso
 */
function mudarPlanoAluno($aluno_id, $plano_id) {
    try {
        $pdo = getConnection();

        // Passo 1: Verificar se o plano existe e está ativo
        $stmt_validar = $pdo->prepare("SELECT id, duracao_meses FROM planos WHERE id = :plano_id AND ativo = 1");
        $stmt_validar->execute([':plano_id' => $plano_id]);
        $plano = $stmt_validar->fetch(PDO::FETCH_ASSOC);
        if (!$plano) throw new Exception("Plano inválido");

        // Passo 2: Expirar todos os planos ativos anteriores do aluno
        $stmt_exp = $pdo->prepare("UPDATE aluno_plano SET status = 'expirado' WHERE aluno_id = :aluno_id AND status = 'ativo'");
        $stmt_exp->execute([':aluno_id' => $aluno_id]);

        // Passo 3: Calcular data de início e fim do novo plano
        $data_inicio = date('Y-m-d'); // Começa hoje
        $meses = (int)$plano['duracao_meses'];
        $data_fim = $meses > 0
            ? date('Y-m-d', strtotime("+{$meses} months")) // Ex: +3 meses
            : date('Y-m-d', strtotime('+30 days'));          // Avulso: 30 dias fixos

        // Passo 4: Inserir novo vínculo ativo
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

// ============================================================
// 7. UTILITÁRIOS
// ============================================================

/**
 * Formata um valor float para o padrão monetário brasileiro.
 * Exemplo: 150.5 → "R$ 150,50"
 *
 * @param float $valor
 * @return string
 */
function formatarPreco(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Converte data no formato MySQL (YYYY-MM-DD) para o formato brasileiro (DD/MM/YYYY).
 * Retorna "-" para datas vazias.
 *
 * @param string $data Data no formato MySQL
 * @return string Data no formato BR
 */
function formatarData(string $data): string {
    if (empty($data)) return '-';
    return date('d/m/Y', strtotime($data));
}

/**
 * Retorna a classe CSS Bootstrap para colorir o badge de status do agendamento.
 *
 * Usa match() do PHP 8 — equivalente a um switch, mas mais conciso.
 * Retorna cores semânticas: verde=confirmado, vermelho=cancelado, amarelo=pendente.
 *
 * @param string $status Status do agendamento
 * @return string Classe CSS do badge Bootstrap
 */
function badgeStatus(string $status): string {
    return match($status) {
        'confirmado' => 'bg-success', // Verde
        'cancelado'  => 'bg-danger',  // Vermelho
        default      => 'bg-warning text-dark', // Amarelo (pendente)
    };
}

/**
 * Sanitiza uma string para exibição segura em HTML.
 *
 * htmlspecialchars() converte caracteres especiais em entidades HTML:
 *   < → &lt;   > → &gt;   " → &quot;   ' → &#039;
 *
 * Isso impede XSS (Cross-Site Scripting): se alguém cadastrar
 * <script>alert('hacked')</script> como nome, será exibido como
 * texto literal e não executado como código JavaScript.
 *
 * ENT_QUOTES converte tanto aspas duplas quanto simples.
 * Sempre use esta função ao exibir dados do banco ou de formulários no HTML.
 *
 * @param string $valor String a ser sanitizada
 * @return string String segura para HTML
 */
function sanitizar(string $valor): string {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna o email do aluno logado.
 * Busca na sessão primeiro (cache); se não tiver, consulta o banco
 * e armazena na sessão para as próximas chamadas.
 */
function getCurrentAlunoEmail(): ?string {
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

/** Retorna o telefone do aluno logado diretamente do banco. */
function getCurrentAlunoTelefone(): ?string {
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
 * Retorna a data de criação da conta do aluno no formato DD/MM/YYYY.
 * DATE_FORMAT() é executado diretamente no MySQL para evitar conversão no PHP.
 */
function getCurrentAlunoDataCriacao(): ?string {
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
// 8. HELPERS DE ROTEAMENTO
// ============================================================
// Funções auxiliares para gerar URLs absolutas e redirecionar.
// Usam BASE_URL para garantir que os links funcionem tanto em
// localhost quanto em produção, sem hardcode de domínio.

/** Gera URL absoluta de qualquer caminho do site. Ex: route('planos.php') → 'https://seusite.com/planos.php' */
if (!function_exists('route')) {
    function route($path) { return BASE_URL . '/' . ltrim($path, '/'); }
}

/** Gera URL de uma página do painel admin. Ex: admin_route('planos.php') */
if (!function_exists('admin_route')) {
    function admin_route($page) { return URL_ADMIN . '/' . ltrim($page, '/'); }
}

/** Gera URL de uma página pública. Ex: public_route('planos.php') */
if (!function_exists('public_route')) {
    function public_route($page) { return URL_PUBLIC . '/' . ltrim($page, '/'); }
}

/** Gera URL de um asset (CSS, JS, imagem). Ex: asset('css/style.css') */
if (!function_exists('asset')) {
    function asset($path) { return URL_ASSETS . '/' . ltrim($path, '/'); }
}

/** Redireciona para uma página do admin e encerra o script. */
if (!function_exists('redirect_admin')) {
    function redirect_admin($page) { header('Location: ' . admin_route($page)); exit; }
}

/** Redireciona para uma página pública e encerra o script. */
if (!function_exists('redirect_public')) {
    function redirect_public($page) { header('Location: ' . public_route($page)); exit; }
}

/** Protege páginas admin: redireciona se não for admin. Versão alternativa de requireAdmin(). */
if (!function_exists('requireAdmin_secure')) {
    function requireAdmin_secure() {
        if (!isAdmin()) { header('Location: ' . admin_route('login.php')); exit; }
    }
}

/** Protege páginas de aluno: redireciona para login se não estiver logado. */
if (!function_exists('requireAluno_secure')) {
    function requireAluno_secure() {
        if (empty($_SESSION['aluno_id'])) { header('Location: ' . public_route('aluno/login.php')); exit; }
    }
}
