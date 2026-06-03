<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$titulo_pagina = 'Admin — Agendamentos';
$pdo = getConnection();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';

// PROCESSAR AÇÕES (CONFIRMAR/CANCELAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $msg = '<div class="alert-prix-error">Falha na validação de segurança. Atualize a página e tente novamente.</div>';
    } else {
        $agendamento_id = (int)($_POST['agendamento_id'] ?? 0);
        
        if ($_POST['acao'] === 'confirmar' && $agendamento_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'confirmado' WHERE id = :id");
                if ($stmt->execute([':id' => $agendamento_id])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $msg = '<div class="alert-prix-success"><i class="bi bi-check-circle-fill me-2"></i>Agendamento confirmado com sucesso!</div>';
                } else {
                    $msg = '<div class="alert-prix-error">Erro ao confirmar agendamento.</div>';
                }
            } catch (Exception $e) {
                $msg = '<div class="alert-prix-error">Erro: ' . sanitizar($e->getMessage()) . '</div>';
            }
        }
        
        if ($_POST['acao'] === 'cancelar' && $agendamento_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = :id");
                if ($stmt->execute([':id' => $agendamento_id])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $msg = '<div class="alert-prix-success"><i class="bi bi-check-circle-fill me-2"></i>Agendamento cancelado.</div>';
                } else {
                    $msg = '<div class="alert-prix-error">Erro ao cancelar agendamento.</div>';
                }
            } catch (Exception $e) {
                $msg = '<div class="alert-prix-error">Erro: ' . sanitizar($e->getMessage()) . '</div>';
            }
        }
    }
}

// BUSCAR AGENDAMENTOS COM FILTRO
$filtro_status = $_GET['status'] ?? 'pendente';
$query = "SELECT ag.id, ag.aluno_id, ag.professor_id, ag.data, ag.hora, ag.status, ag.observacao, ag.criado_em, 
          a.nome AS aluno_nome, a.email AS aluno_email, p.nome AS professor_nome 
          FROM agendamentos ag 
          JOIN alunos a ON ag.aluno_id = a.id 
          JOIN professores p ON ag.professor_id = p.id";

if ($filtro_status !== 'todos') {
    $query .= " WHERE ag.status = :status";
}

$query .= " ORDER BY ag.data DESC, ag.hora DESC";

$stmt = $pdo->prepare($query);
if ($filtro_status !== 'todos') {
    $stmt->execute([':status' => $filtro_status]);
} else {
    $stmt->execute();
}
$agendamentos = $stmt->fetchAll();

// CONTAR POR STATUS
$stmt_pendentes = $pdo->query("SELECT COUNT(*) FROM agendamentos WHERE status = 'pendente'");
$count_pendentes = $stmt_pendentes->fetchColumn();

$stmt_confirmados = $pdo->query("SELECT COUNT(*) FROM agendamentos WHERE status = 'confirmado'");
$count_confirmados = $stmt_confirmados->fetchColumn();

$stmt_cancelados = $pdo->query("SELECT COUNT(*) FROM agendamentos WHERE status = 'cancelado'");
$count_cancelados = $stmt_cancelados->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <span class="section-badge">Admin</span>
            <h1 class="section-title mb-0">GERENCIAR <span>AGENDAMENTOS</span></h1>
        </div>
        <a href="<?= admin_route('index.php') ?>" class="btn btn-outline-prix" id="btnVoltarAdmin"><i class="bi bi-arrow-left me-1"></i>Voltar ao Painel</a>
    </div>
</section>

<section class="section-prix section-dark" id="crud-agendamentos">
    <div class="container">
        <?= $msg ?>
        
        <!-- ESTATÍSTICAS -->
        <div class="row g-3 mb-5" data-animate>
            <div class="col-6 col-md-4">
                <div class="card-prix p-3 text-center">
                    <i class="bi bi-hourglass-split" style="font-size:1.5rem;color:var(--prix-orange);"></i>
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--prix-white);line-height:1.2;margin-top:8px;">
                        <?= (int)$count_pendentes ?>
                    </div>
                    <div style="color:var(--prix-muted);font-size:.8rem;">Pendentes</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card-prix p-3 text-center">
                    <i class="bi bi-check-circle-fill" style="font-size:1.5rem;color:#198754;"></i>
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--prix-white);line-height:1.2;margin-top:8px;">
                        <?= (int)$count_confirmados ?>
                    </div>
                    <div style="color:var(--prix-muted);font-size:.8rem;">Confirmados</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card-prix p-3 text-center">
                    <i class="bi bi-x-circle-fill" style="font-size:1.5rem;color:#dc3545;"></i>
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--prix-white);line-height:1.2;margin-top:8px;">
                        <?= (int)$count_cancelados ?>
                    </div>
                    <div style="color:var(--prix-muted);font-size:.8rem;">Cancelados</div>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="mb-4" data-animate>
            <a href="<?= admin_route('agendamentos.php?status=pendente') ?>" class="filter-btn me-2 mb-2 <?= $filtro_status === 'pendente' ? 'active' : '' ?>" id="filtroStatusPendente">
                <i class="bi bi-hourglass-split me-1"></i>Pendentes
            </a>
            <a href="<?= admin_route('agendamentos.php?status=confirmado') ?>" class="filter-btn me-2 mb-2 <?= $filtro_status === 'confirmado' ? 'active' : '' ?>" id="filtroStatusConfirmado">
                <i class="bi bi-check-circle-fill me-1"></i>Confirmados
            </a>
            <a href="<?= admin_route('agendamentos.php?status=cancelado') ?>" class="filter-btn me-2 mb-2 <?= $filtro_status === 'cancelado' ? 'active' : '' ?>" id="filtroStatusCancelado">
                <i class="bi bi-x-circle-fill me-1"></i>Cancelados
            </a>
            <a href="<?= admin_route('agendamentos.php?status=todos') ?>" class="filter-btn me-2 mb-2 <?= $filtro_status === 'todos' ? 'active' : '' ?>" id="filtroStatusTodos">
                <i class="bi bi-list me-1"></i>Todos
            </a>
        </div>

        <!-- TABELA DE AGENDAMENTOS -->
        <div data-animate>
            <h4 class="section-title mb-4">AGENDAMENTOS <span><?= strtoupper($filtro_status) ?></span></h4>
            
            <?php if (empty($agendamentos)): ?>
                <div class="text-center py-5" style="color:var(--prix-muted);">
                    <i class="bi bi-calendar-x" style="font-size:2.5rem;"></i>
                    <p class="mt-3">Nenhum agendamento encontrado.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-prix" id="tabelaAgendamentosAdmin">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Aluno</th>
                            <th>Professor</th>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Status</th>
                            <th>Observação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos as $ag): ?>
                        <tr>
                            <td><?= (int)$ag['id'] ?></td>
                            <td>
                                <strong><?= sanitizar($ag['aluno_nome']) ?></strong><br>
                                <small style="color:var(--prix-muted);"><?= sanitizar($ag['aluno_email']) ?></small>
                            </td>
                            <td><?= sanitizar($ag['professor_nome']) ?></td>
                            <td><?= formatarData($ag['data']) ?></td>
                            <td><?= sanitizar(substr($ag['hora'], 0, 5)) ?></td>
                            <td>
                                <span class="badge <?= badgeStatus($ag['status']) ?>" style="font-size:.78rem;">
                                    <?= ucfirst(sanitizar($ag['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($ag['observacao'])): ?>
                                    <small><?= sanitizar($ag['observacao']) ?></small>
                                <?php else: ?>
                                    <small style="color:var(--prix-muted);">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ag['status'] === 'pendente'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="acao" value="confirmar">
                                        <input type="hidden" name="agendamento_id" value="<?= (int)$ag['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" style="padding:4px 12px;font-size:.75rem;" title="Confirmar agendamento" id="btnConfirmarAg<?= (int)$ag['id'] ?>">
                                            <i class="bi bi-check-circle me-1"></i>Confirmar
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <input type="hidden" name="agendamento_id" value="<?= (int)$ag['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="padding:4px 12px;font-size:.75rem;" title="Cancelar agendamento" id="btnCancelarAg<?= (int)$ag['id'] ?>" onclick="return confirm('Cancelar este agendamento?');">
                                            <i class="bi bi-x-circle me-1"></i>Cancelar
                                        </button>
                                    </form>
                                <?php elseif ($ag['status'] === 'confirmado'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <input type="hidden" name="agendamento_id" value="<?= (int)$ag['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="padding:4px 12px;font-size:.75rem;" title="Cancelar agendamento" id="btnCancelarAg<?= (int)$ag['id'] ?>" onclick="return confirm('Cancelar este agendamento?');">
                                            <i class="bi bi-x-circle me-1"></i>Cancelar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <small style="color:var(--prix-muted);">—</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <p class="text-center mt-4" style="color:var(--prix-muted);font-size:.85rem;">
            Exibindo <strong style="color:var(--prix-orange);"><?= count($agendamentos) ?></strong> agendamento(s).
        </p>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
