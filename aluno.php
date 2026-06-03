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
$todos_planos = getPlanos();

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
                        <p id="planoAtualNome" style="color:var(--prix-muted);font-size:.9rem;margin:0;"><?= sanitizar($plano_atual['nome']) ?></p>
                        <p id="planoAtualPreco" style="color:var(--prix-orange);font-size:1.2rem;font-weight:600;"><?= formatarPreco($plano_atual['preco']) ?></p>
                    <?php else: ?>
                        <p id="planoAtualNome" style="color:var(--prix-muted);font-size:.9rem;">Nenhum plano ativo</p>
                        <p id="planoAtualPreco" style="display:none;"></p>
                    <?php endif; ?>
                    <button class="btn btn-outline-prix btn-sm mt-2" id="btnAbrirModalPlanos" data-bs-toggle="modal" data-bs-target="#modalTrocarPlano">
                        <i class="bi bi-arrow-left-right me-1"></i>Trocar Plano
                    </button>
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

<!-- Modal Trocar Plano -->
<div class="modal fade" id="modalTrocarPlano" tabindex="-1" aria-labelledby="modalTrocarPlanoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:var(--prix-card);border:1px solid var(--prix-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--prix-border);">
                <h5 class="modal-title" id="modalTrocarPlanoLabel" style="color:var(--prix-white);font-family:'Bebas Neue',sans-serif;font-size:1.5rem;letter-spacing:.05em;">
                    <i class="bi bi-credit-card me-2" style="color:var(--prix-orange);"></i>ESCOLHER PLANO
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">

                <div id="msgTrocarPlano" class="mb-3"></div>

                <?php if (!empty($plano_atual)): ?>
                <div class="alert" style="background:rgba(255,107,0,.1);border:1px solid var(--prix-orange);border-radius:10px;color:var(--prix-muted);font-size:.9rem;margin-bottom:1.5rem;">
                    <i class="bi bi-info-circle me-2" style="color:var(--prix-orange);"></i>
                    Plano atual: <strong style="color:var(--prix-white);"><?= sanitizar($plano_atual['nome']) ?></strong>
                    — <?= formatarPreco($plano_atual['preco']) ?>
                </div>
                <?php endif; ?>

                <div class="row g-3 justify-content-center" id="gridPlanosModal">
                    <?php foreach ($todos_planos as $p):
                        $meses    = (int)$p['duracao_meses'];
                        $atual_id = $plano_atual ? (int)$plano_atual['id'] : 0;
                        $is_atual = ($atual_id === (int)$p['id']);
                        $destaque = (bool)$p['destaque'];
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="plan-card h-100 <?= $destaque ? 'destaque' : '' ?>" style="position:relative;">
                            <?php if ($destaque): ?>
                                <div class="plan-badge-top"><i class="bi bi-star-fill me-1"></i>Mais Popular</div>
                            <?php endif; ?>
                            <?php if ($is_atual): ?>
                                <div style="position:absolute;top:<?= $destaque ? '44px' : '12px' ?>;right:12px;background:#198754;color:#fff;font-size:.7rem;padding:2px 8px;border-radius:20px;font-weight:600;">
                                    <i class="bi bi-check-circle me-1"></i>Atual
                                </div>
                            <?php endif; ?>
                            <div class="plan-name"><?= sanitizar($p['nome']) ?></div>
                            <div class="mt-3 mb-1">
                                <span class="plan-price"><?= formatarPreco((float)$p['preco']) ?></span>
                            </div>
                            <div class="plan-price-label">
                                <?php if ($meses === 0): ?>por sessão avulsa
                                <?php else: ?>por <?= $meses ?> <?= $meses == 1 ? 'mês' : 'meses' ?><?php endif; ?>
                            </div>
                            <?php if ($meses > 1): ?>
                            <div class="plan-per-month">
                                <i class="bi bi-tag me-1"></i>Equivale a <?= formatarPreco(precoPorMes($p)) ?>/mês
                            </div>
                            <?php endif; ?>
                            <hr style="border-color:var(--prix-border);margin:16px 0;">
                            <p style="color:var(--prix-muted);font-size:.88rem;margin-bottom:12px;"><?= sanitizar($p['descricao']) ?></p>
                            <ul style="color:var(--prix-muted);font-size:.83rem;list-style:none;padding:0;margin:0 0 16px;">
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Acesso a todas as modalidades</li>
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Vestiários completos</li>
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Avaliação física gratuita</li>
                                <?php if ($meses >= 3): ?>
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Orientação nutricional</li>
                                <?php endif; ?>
                                <?php if ($meses >= 6): ?>
                                <li><i class="bi bi-check-circle-fill text-success me-2"></i>1 sessão personal grátis/mês</li>
                                <?php endif; ?>
                            </ul>
                            <?php if ($is_atual): ?>
                                <button class="btn btn-outline-secondary w-100 mt-auto" disabled>
                                    <i class="bi bi-check2 me-1"></i>Plano Ativo
                                </button>
                            <?php else: ?>
                                <button class="btn <?= $destaque ? 'btn-prix' : 'btn-outline-prix' ?> w-100 mt-auto btn-selecionar-plano"
                                        data-plano-id="<?= (int)$p['id'] ?>"
                                        data-plano-nome="<?= sanitizar($p['nome']) ?>"
                                        data-plano-preco="<?= sanitizar(formatarPreco((float)$p['preco'])) ?>">
                                    <i class="bi bi-arrow-right-circle me-1"></i>Quero Este Plano
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
            <div class="modal-footer" style="border-top:1px solid var(--prix-border);">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Dados para o JS -->
<script>
const CSRF_TOKEN_PLANO = <?= json_encode($_SESSION['csrf_token']) ?>;
const PLANO_ATUAL_ID   = <?= json_encode($plano_atual ? (int)$plano_atual['id'] : null) ?>;
</script>
<script src="<?= URL_ASSETS ?>/js/trocar_plano.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>