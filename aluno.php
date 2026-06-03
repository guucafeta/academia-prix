<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['aluno_id'])) {
    header('Location: ' . BASE_URL . '/aluno/login.php');
    exit;
}

if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$titulo_pagina  = 'Minha Área';
$meta_descricao = 'Área exclusiva do aluno na Academia Prix.';

$aluno_id          = getCurrentAlunoId();
$aluno_nome        = getCurrentAlunoNome();
$aluno_email       = getCurrentAlunoEmail();
$aluno_telefone    = getCurrentAlunoTelefone();
$aluno_data_criacao = getCurrentAlunoDataCriacao();

$agendamentos = getAgendamentosAluno($aluno_id);

$mensagem = '';
if (!empty($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

$plano_atual = getPlanoAluno($aluno_id);

// CSRF token para cancelamento de agendamento
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/header.php';
?>

<?php
// Injetar BASE_URL para os scripts JS não usarem URL hardcoded
?>
<script>
const BASE_URL_JS = <?= json_encode(rtrim(BASE_URL, '/')) ?>;
</script>

<section class="page-hero">
    <div class="container">
        <span class="section-badge">Área do Aluno</span>
        <h1 class="section-title">BEM-VINDO, <span id="nomeAlunoHero"><?= sanitizar(explode(' ', $aluno_nome)[0]) ?></span>!</h1>
        <p style="color:var(--prix-muted);">Gerencie seus agendamentos, plano e perfil.</p>
    </div>
</section>

<section class="section-prix section-dark" id="painel-aluno">
    <div class="container">

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <?= sanitizar($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Resumo -->
        <div class="row g-3 mb-5" data-animate>
            <div class="col-md-4">
                <div class="card-prix p-4 text-center">
                    <div id="avatarInicialHero" style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--prix-orange),#FF8C00);display:flex;align-items:center;justify-content:center;font-family:'Bebas Neue',sans-serif;font-size:2rem;color:#fff;margin:0 auto 12px;">
                        <?= mb_strtoupper(mb_substr($aluno_nome, 0, 1, 'UTF-8')) ?>
                    </div>
                    <h5 class="mt-1" style="color:var(--prix-white);">Meu Perfil</h5>
                    <p style="color:var(--prix-muted);font-size:.9rem;margin:0;"><?= sanitizar($aluno_nome) ?></p>
                    <p style="color:var(--prix-muted);font-size:.85rem;"><?= sanitizar($aluno_email) ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-prix p-4 text-center">
                    <i class="bi bi-credit-card" style="font-size:2.5rem;color:#0dcaf0;"></i>
                    <h5 class="mt-3" style="color:var(--prix-white);">Meu Plano</h5>
                    <?php if ($plano_atual): ?>
                        <p style="color:var(--prix-muted);font-size:.9rem;margin:0;"><?= sanitizar($plano_atual['nome']) ?></p>
                        <p style="color:var(--prix-orange);font-size:1.2rem;font-weight:600;"><?= formatarPreco($plano_atual['preco']) ?></p>
                    <?php else: ?>
                        <p style="color:var(--prix-muted);font-size:.9rem;">Nenhum plano ativo</p>
                        <a href="<?= BASE_URL ?>/planos.php" class="btn btn-prix btn-sm mt-2">Ver Planos</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-prix p-4 text-center">
                    <i class="bi bi-calendar-check" style="font-size:2.5rem;color:#198754;"></i>
                    <h5 class="mt-3" style="color:var(--prix-white);">Agendamentos</h5>
                    <p style="color:var(--prix-orange);font-size:2rem;font-weight:600;margin:0;"><?= count($agendamentos) ?></p>
                    <p style="color:var(--prix-muted);font-size:.85rem;">agendamento(s)</p>
                </div>
            </div>
        </div>

        <!-- Meus Agendamentos -->
        <div class="mb-5" data-animate>
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h3 class="section-title mb-0">MEUS <span>AGENDAMENTOS</span></h3>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-prix btn-sm" id="btnAtualizarAgendamentos">
                        <i class="bi bi-arrow-clockwise me-1"></i>Atualizar
                    </button>
                    <a href="<?= BASE_URL ?>/index.php#agendamento" class="btn btn-prix btn-sm" id="btnNovoAgendamento">
                        <i class="bi bi-plus-circle me-2"></i>Novo Agendamento
                    </a>
                </div>
            </div>

            <!-- Placeholder vazio: PHP o exibe quando não há agendamentos;
                 JS o mostra/esconde dinamicamente via id="tabelaAgendamentosVazia" -->
            <div id="tabelaAgendamentosVazia"
                 class="agendamento-box text-center py-5"
                 style="<?= !empty($agendamentos) ? 'display:none;' : '' ?>">
                <i class="bi bi-calendar-x" style="font-size:3rem;color:var(--prix-muted);opacity:.3;"></i>
                <p style="color:var(--prix-muted);margin-top:16px;">Você ainda não tem agendamentos.</p>
                <a href="<?= BASE_URL ?>/index.php#agendamento" class="btn btn-prix mt-3">
                    Agendar com Professor
                </a>
            </div>

            <?php if (!empty($agendamentos)): ?>
                <div class="table-responsive">
                    <table class="table table-prix" id="tabelaAgendamentos">
                        <!-- Campo CSRF para o JS de cancelamento dinâmico -->
                        <input type="hidden" id="csrf_token_agendamento" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <thead>
                            <tr>
                                <th>Professor</th>
                                <th>Especialidade</th>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as $ag): ?>
                            <tr data-ag-id="<?= (int)$ag['id'] ?>" data-status="<?= sanitizar($ag['status']) ?>">
                                <td><?= sanitizar($ag['professor_nome']) ?></td>
                                <td><?= sanitizar($ag['especialidade']) ?></td>
                                <td><?= formatarData($ag['data']) ?></td>
                                <td><?= sanitizar(substr($ag['hora'], 0, 5)) ?></td>
                                <td>
                                    <span class="badge <?= badgeStatus($ag['status']) ?>">
                                        <?= ucfirst(sanitizar($ag['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ag['status'] === 'pendente'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/api/cancelar_agendamento.php" style="display:inline;"
                                          onsubmit="return confirm('Deseja cancelar este agendamento?')">
                                        <input type="hidden" name="agendamento_id" value="<?= (int)$ag['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" id="btnCancelar<?= (int)$ag['id'] ?>">
                                            <i class="bi bi-x-circle"></i> Cancelar
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span style="color:var(--prix-muted);font-size:.85rem;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dados Pessoais + Edição de Perfil -->
        <div data-animate>
            <h3 class="section-title mb-4">INFORMAÇÕES <span>DA CONTA</span></h3>
            <div class="row g-4">

                <!-- CORRIGIDO: formulário de edição de perfil com os IDs que perfil_aluno.js espera -->
                <div class="col-md-6">
                    <div class="agendamento-box">
                        <h5 style="color:var(--prix-white);margin-bottom:20px;">
                            <i class="bi bi-person-fill me-2"></i>Dados Pessoais
                        </h5>

                        <div id="msgPerfilAluno" class="mb-3"></div>

                        <div class="mb-3">
                            <label for="inputNomeAluno" class="form-label" style="color:var(--prix-muted);font-size:.85rem;">Nome</label>
                            <input type="text" id="inputNomeAluno" class="form-control"
                                   value="<?= sanitizar($aluno_nome) ?>" placeholder="Seu nome completo">
                        </div>
                        <div class="mb-3">
                            <label for="inputEmailAluno" class="form-label" style="color:var(--prix-muted);font-size:.85rem;">E-mail</label>
                            <input type="email" id="inputEmailAluno" class="form-control"
                                   value="<?= sanitizar($aluno_email) ?>" placeholder="seu@email.com">
                        </div>
                        <div class="mb-3">
                            <label for="inputTelefoneAluno" class="form-label" style="color:var(--prix-muted);font-size:.85rem;">Telefone</label>
                            <input type="tel" id="inputTelefoneAluno" class="form-control"
                                   value="<?= sanitizar($aluno_telefone ?: '') ?>" placeholder="(44) 99999-9999">
                        </div>
                        <div class="mb-4">
                            <label style="color:var(--prix-muted);font-size:.85rem;display:block;margin-bottom:4px;">Membro desde</label>
                            <p style="color:var(--prix-white);margin:0;"><?= sanitizar($aluno_data_criacao ?: '-') ?></p>
                        </div>

                        <button class="btn btn-prix w-100" id="btnSalvarPerfil">
                            <i class="bi bi-floppy me-1"></i>Salvar Alterações
                        </button>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="col-md-6">
                    <div class="agendamento-box">
                        <h5 style="color:var(--prix-white);margin-bottom:20px;">
                            <i class="bi bi-gear-fill me-2"></i>Ações Rápidas
                        </h5>
                        <a href="<?= BASE_URL ?>/index.php#agendamento" class="btn btn-prix w-100 mb-3" id="btnAgendarProfessor">
                            <i class="bi bi-calendar-plus me-2"></i>Agendar com Professor
                        </a>
                        <a href="<?= BASE_URL ?>/planos.php" class="btn btn-outline-prix w-100 mb-3" id="btnVerPlanos">
                            <i class="bi bi-credit-card me-2"></i>Ver Planos Disponíveis
                        </a>
                        <a href="<?= BASE_URL ?>/treinos.php" class="btn btn-outline-prix w-100 mb-3" id="btnMeusTreinos">
                            <i class="bi bi-play-circle me-2"></i>Meus Treinos
                        </a>
                        <a href="<?= BASE_URL ?>/aluno/logout.php" class="btn btn-outline-secondary w-100" id="btnSairAluno">
                            <i class="bi bi-box-arrow-right me-2"></i>Sair
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<script src="<?= URL_ASSETS ?>/js/verificar_agendamentos.js"></script>
<script src="<?= URL_ASSETS ?>/js/perfil_aluno.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>