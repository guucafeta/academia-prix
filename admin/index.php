<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$titulo_pagina = 'Admin — Painel';
$base = URL_PUBLIC;
$adminNome = getCurrentAlunoNome() ?: 'Administrador';
$professores = getProfessores();
$planos      = getPlanos();
$pdo  = getConnection();
$stmt = $pdo->query("SELECT ag.*, a.nome AS aluno_nome, p.nome AS professor_nome FROM agendamentos ag JOIN alunos a ON ag.aluno_id = a.id JOIN professores p ON ag.professor_id = p.id ORDER BY ag.criado_em DESC LIMIT 10");
$agendamentos_recentes = $stmt->fetchAll();
$total_alunos = $pdo->query("SELECT COUNT(*) FROM alunos")->fetchColumn();
$total_agen   = $pdo->query("SELECT COUNT(*) FROM agendamentos")->fetchColumn();
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="section-badge">Administração</span>
        <h1 class="section-title">PAINEL <span>ADMIN</span></h1>
        <p style="color:var(--prix-muted);">Olá, <?= sanitizar($adminNome) ?>. Gerencie professores, planos e agendamentos.</p>
    </div>
</section>

<section class="section-prix section-dark" id="admin-painel">
    <div class="container">
        <div class="row g-3 mb-5" data-animate>
            <?php
            $resumo = [
                ['valor'=>count($professores),'label'=>'Professores','icone'=>'bi-person-video3','cor'=>'var(--prix-orange)'],
                ['valor'=>count($planos),'label'=>'Planos','icone'=>'bi-credit-card','cor'=>'#0dcaf0'],
                ['valor'=>(int)$total_alunos,'label'=>'Alunos','icone'=>'bi-people-fill','cor'=>'#198754'],
                ['valor'=>(int)$total_agen,'label'=>'Agendamentos','icone'=>'bi-calendar-check','cor'=>'#ffc107'],
            ];
            foreach ($resumo as $r):
            ?>
            <div class="col-6 col-md-3">
                <div class="card-prix p-3 text-center">
                    <i class="bi <?= sanitizar($r['icone']) ?>" style="font-size:2rem;color:<?= $r['cor'] ?>;"></i>
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:2.2rem;color:var(--prix-white);line-height:1.2;margin-top:8px;"><?= (int)$r['valor'] ?></div>
                    <div style="color:var(--prix-muted);font-size:.8rem;"><?= sanitizar($r['label']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3 mb-5" data-animate>
            <div class="col-md-3">
                <a href="<?= admin_route('agendamentos.php') ?>" class="card-prix p-4 d-block text-center text-decoration-none" id="adminLinkAgendamentos">
                    <i class="bi bi-calendar-check" style="font-size:2rem;color:#ffc107;"></i>
                    <h5 class="mt-2" style="color:var(--prix-white);">Gerenciar Agendamentos</h5>
                    <p style="color:var(--prix-muted);font-size:.85rem;">Confirmar, cancelar e acompanhar agendamentos.</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= admin_route('professores.php') ?>" class="card-prix p-4 d-block text-center text-decoration-none" id="adminLinkProfessores">
                    <i class="bi bi-person-video3" style="font-size:2rem;color:var(--prix-orange);"></i>
                    <h5 class="mt-2" style="color:var(--prix-white);">Gerenciar Professores</h5>
                    <p style="color:var(--prix-muted);font-size:.85rem;">Adicionar, editar e remover professores.</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= admin_route('planos.php') ?>" class="card-prix p-4 d-block text-center text-decoration-none" id="adminLinkPlanos">
                    <i class="bi bi-credit-card" style="font-size:2rem;color:#0dcaf0;"></i>
                    <h5 class="mt-2" style="color:var(--prix-white);">Gerenciar Planos</h5>
                    <p style="color:var(--prix-muted);font-size:.85rem;">Criar e editar planos disponíveis.</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= $base ?>/index.php" class="card-prix p-4 d-block text-center text-decoration-none" id="adminLinkSite">
                    <i class="bi bi-house-fill" style="font-size:2rem;color:#198754;"></i>
                    <h5 class="mt-2" style="color:var(--prix-white);">Ver Site</h5>
                    <p style="color:var(--prix-muted);font-size:.85rem;">Visualizar o site como o público vê.</p>
                </a>
            </div>
        </div>

        <div data-animate>
            <h3 class="section-title mb-3">AGENDAMENTOS <span>RECENTES</span></h3>
            <?php if (empty($agendamentos_recentes)): ?>
                <p style="color:var(--prix-muted);">Nenhum agendamento cadastrado ainda.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-prix" id="tabelaAgendamentosAdmin">
                    <thead><tr><th>#</th><th>Aluno</th><th>Professor</th><th>Data</th><th>Hora</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($agendamentos_recentes as $ag): ?>
                        <tr>
                            <td><?= (int)$ag['id'] ?></td>
                            <td><?= sanitizar($ag['aluno_nome']) ?></td>
                            <td><?= sanitizar($ag['professor_nome']) ?></td>
                            <td><?= formatarData($ag['data']) ?></td>
                            <td><?= sanitizar(substr($ag['hora'], 0, 5)) ?></td>
                            <td><span class="badge <?= badgeStatus($ag['status']) ?>"><?= ucfirst(sanitizar($ag['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>