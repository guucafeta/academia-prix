<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/includes/functions.php';
 
$titulo_pagina  = 'Home';
$meta_descricao = 'Academia Prix Matriz em Campo Mourão, PR. Musculação, CrossFit, Pilates, Spinning e Personal Trainer.';
$professores  = getProfessores();
$modalidades  = getModalidades();
$planos       = getPlanos();
$video_hero      = BASE_URL . '/videos/depoimentos/depoimento-estrutura.mp4';
$img_modalidades = BASE_URL . '/imagensprix/modalidades-abertura.jpg';
 
$mensagem_erro_sessao = '';
if (!empty($_SESSION['mensagem_erro'])) {
    $mensagem_erro_sessao = $_SESSION['mensagem_erro'];
    unset($_SESSION['mensagem_erro']);
}
 
require_once __DIR__ . '/includes/header.php';
?>
 
<section class="hero-section" id="hero">
    <video class="hero-video-bg" autoplay muted loop playsinline preload="metadata" poster="<?= BASE_URL ?>/imagensprix/modalidades-abertura.jpg" aria-hidden="true">
        <source src="<?= $video_hero ?>" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-7">
                
                <h1 class="hero-tagline" data-animate>
                    TREINE COM<br><span class="highlight">FOCO.</span> AGENDE<br>
                    COM SEU <span class="highlight">PERSONAL</span><br>NA PRIX.
                </h1>
                <p class="hero-sub" data-animate>Estrutura completa, professores qualificados e o ambiente certo para você conquistar seus objetivos em Campo Mourão.</p>
                <div class="d-flex gap-3 flex-wrap" data-animate>
                    <a href="#agendamento" class="btn btn-prix btn-lg" id="btnHeroCTA"><i class="bi bi-calendar-check me-2"></i>Agendar Treino</a>
                    <a href="planos.php" class="btn btn-outline-prix btn-lg" id="btnHeroPlanos">Ver Planos</a>
                </div>
                <div class="hero-stats" data-animate>
                    <div><div class="hero-stat-num">500+</div><div class="hero-stat-label">Alunos Ativos</div></div>
                    <div><div class="hero-stat-num">6+</div><div class="hero-stat-label">Modalidades</div></div>
                    <div><div class="hero-stat-num">4</div><div class="hero-stat-label">Professores</div></div>
                </div>
            </div>
        </div>
    </div>
</section>
 
<section class="section-prix section-dark" id="agendamento">
    <div class="container">
        <div class="text-center mb-5" data-animate>
            <span class="section-badge">Agende Agora</span>
            <h2 class="section-title">AGENDE SEU <span>TREINO</span></h2>
            <p class="section-sub mx-auto">Escolha o professor, a data e o horário. É rápido e fácil.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="agendamento-box" data-animate>
                    <?php
                    $msg_sucesso = '';
                    $msg_erro = [];
                    if ($mensagem_erro_sessao) {
                        $msg_erro[] = $mensagem_erro_sessao;
                    }
 
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agendar'])) {
                        if (!isset($_SESSION['aluno_id']) || empty($_SESSION['aluno_id'])) {
                            $_SESSION['mensagem_erro'] = 'Você precisa estar logado para agendar!';
                            header('Location: ' . BASE_URL . 'index.php#agendamento');
                            exit;
                        }
 
                        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                            $msg_erro[] = 'Falha na validação de segurança. Tente novamente.';
                        } else {
                            $dados = [
                                'aluno_id'     => $_SESSION['aluno_id'] ?? null,
                                'professor_id' => $_POST['professor_id'] ?? '',
                                'data'         => $_POST['data'] ?? '',
                                'hora'         => $_POST['hora'] ?? '',
                                'observacao'   => $_POST['observacao'] ?? '',
                            ];
                            $msg_erro = validarAgendamento($dados);
                            if (empty($msg_erro)) {
                                if (salvarAgendamento($dados)) {
                                    $msg_sucesso = 'Agendamento realizado com sucesso! Aguarde confirmação.';
                                } else {
                                    $msg_erro[] = 'Erro ao salvar agendamento. Tente novamente.';
                                }
                            }
                        }
                    }
                    ?>
                    <?php if ($msg_sucesso): ?>
                        <div class="alert-prix-success mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= sanitizar($msg_sucesso) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($msg_erro)): ?>
                        <div class="alert-prix-error mb-4">
                            <?php foreach ($msg_erro as $erro): ?><div><?= sanitizar($erro) ?></div><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
 
                    <form method="POST" action="#agendamento" class="form-prix" id="formAgendamento">
                        <?php
                        if (empty($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        ?>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <h5 class="form-step-title">
                            <span class="form-step-number">1</span>
                            ESCOLHA O PROFESSOR
                        </h5>
                        <div class="row g-3 mb-4">
                            <?php foreach ($professores as $prof): ?>
                            <div class="col-6 col-md-3">
                                <label class="w-100">
                                    <input type="radio" name="professor_id" value="<?= (int)$prof['id'] ?>" id="prof_<?= (int)$prof['id'] ?>" class="d-none prof-radio"
                                           <?= (isset($_POST['professor_id']) && $_POST['professor_id'] == $prof['id']) ? 'checked' : '' ?>>
                                    <div class="prof-select-card text-center p-3">
                                        <?php $foto = trim($prof['foto'] ?? ''); ?>
<?php if ($foto): ?>
    <img src="<?= BASE_URL ?>/<?= sanitizar($foto) ?>" 
         alt="Foto de <?= sanitizar($prof['nome']) ?>" 
         style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--prix-orange);margin:0 auto 8px;display:block;">
<?php else: ?>
    <div class="prof-avatar-placeholder mx-auto mb-2" style="width:60px;height:60px;font-size:1.5rem;">
        <?= mb_substr($prof['nome'], 0, 1) ?>
    </div>
<?php endif; ?>
                                        <div style="font-size:.85rem;color:var(--prix-text);font-weight:600;"><?= sanitizar($prof['nome']) ?></div>
                                        <div style="font-size:.75rem;color:var(--prix-muted);"><?= sanitizar($prof['especialidade']) ?></div>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
 
                        <h5 class="form-step-title">
                            <span class="form-step-number">2</span>
                            SELECIONE DATA E HORÁRIO
                        </h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="data" class="form-label">Data</label>
                                <input type="date" name="data" id="data" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= sanitizar($_POST['data'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="hora" class="form-label">Horário</label>
                                <select name="hora" id="hora" class="form-select">
                                    <option value="">Selecione...</option>
                                    <?php
                                    $horarios = HORARIOS_FUNCIONAMENTO;
                                    foreach ($horarios as $h):
                                        $sel = (isset($_POST['hora']) && $_POST['hora'] === $h) ? 'selected' : '';
                                    ?>
                                    <option value="<?= $h ?>" <?= $sel ?>><?= $h ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="observacao" class="form-label">Observação (opcional)</label>
                                <textarea name="observacao" id="observacao" class="form-control" rows="2" placeholder="Ex: Treino de membros inferiores..."><?= sanitizar($_POST['observacao'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <button type="submit" name="agendar" class="btn btn-prix w-100 py-3" id="btnSubmitAgendar">
                            <i class="bi bi-calendar-check me-2"></i>CONFIRMAR AGENDAMENTO
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
 
 
<section class="section-prix section-dark" id="professores-home">
    <div class="container">
        <div class="text-center mb-5" data-animate>
            <span class="section-badge">Nossa Equipe</span>
            <h2 class="section-title">CONHEÇA NOSSOS <span>PROFESSORES</span></h2>
            <p class="section-sub mx-auto">Deslize para conhecer nossa equipe qualificada.</p>
        </div>
        
        <!-- CAROUSEL BOOTSTRAP DE PROFESSORES -->
        <div class="carousel-profesores" data-animate>
            <div id="carouselProfessores" class="carousel slide" data-bs-ride="carousel">
                
                <!-- INDICADORES (bolinhas de navegação) -->
                <div class="carousel-indicators" id="indicadores-prof">
                    <?php
                    $professores_por_slide = 4;
                    $total_slides = ceil(count($professores) / $professores_por_slide);
                    for ($i = 0; $i < $total_slides; $i++):
                    ?>
                    <button type="button" 
                            data-bs-target="#carouselProfessores" 
                            data-bs-slide-to="<?= $i ?>" 
                            aria-label="Slide <?= $i + 1 ?>"
                            class="<?= $i === 0 ? 'active' : '' ?>"
                            style="background-color: var(--prix-orange); width: 12px; height: 12px; border-radius: 50%; border: none; margin: 0 6px;">
                    </button>
                    <?php endfor; ?>
                </div>
                
                <!-- ITEMS DO CAROUSEL -->
                <div class="carousel-inner">
                    <?php
                    $chunks = array_chunk($professores, $professores_por_slide);
                    foreach ($chunks as $slide_index => $chunk):
                    ?>
                    <div class="carousel-item <?= $slide_index === 0 ? 'active' : '' ?>">
                        <div class="row g-4 justify-content-center">
                            <?php foreach ($chunk as $prof): ?>
                            <div class="col-lg-3 col-md-6">
                                <div class="prof-card">
                                    <?php $foto = trim($prof['foto'] ?? ''); ?>
                                    <?php if ($foto): ?>
                                        <img src="<?= BASE_URL ?>/<?= sanitizar($foto) ?>" 
                                             alt="Foto de <?= sanitizar($prof['nome']) ?>" 
                                             class="prof-avatar">
                                    <?php else: ?>
                                        <div class="prof-avatar-placeholder"><?= mb_substr($prof['nome'], 0, 1) ?></div>
                                    <?php endif; ?>
                                    <div class="prof-name"><?= sanitizar($prof['nome']) ?></div>
                                    <div class="prof-esp"><?= sanitizar($prof['especialidade']) ?></div>
                                    <div class="prof-bio"><?= sanitizar($prof['bio']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- BOTÕES DE NAVEGAÇÃO -->
                <?php if ($total_slides > 1): ?>
                <button class="carousel-control-prev" 
                        type="button" 
                        data-bs-target="#carouselProfessores" 
                        data-bs-slide="prev"
                        style="background: rgba(255,107,0,0.2); border-radius: 50%; left: -40px; width: 40px; height: 40px;">
                    <span class="carousel-control-prev-icon" style="filter: invert(1);"></span>
                </button>
                <button class="carousel-control-next" 
                        type="button" 
                        data-bs-target="#carouselProfessores" 
                        data-bs-slide="next"
                        style="background: rgba(255,107,0,0.2); border-radius: 50%; right: -40px; width: 40px; height: 40px;">
                    <span class="carousel-control-next-icon" style="filter: invert(1);"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-5" data-animate>
            <a href="professores.php" class="btn btn-outline-prix" id="btnVerProfessores">Ver Todos os Professores</a>
        </div>
    </div>
</section>
 
 
<script>
document.querySelectorAll('.prof-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.prof-select-card').forEach(c => {
            c.classList.remove('active');
        });
        if (radio.checked) {
            radio.nextElementSibling.classList.add('active');
        }
    });
});
document.querySelectorAll('.prof-radio:checked').forEach(radio => {
    radio.nextElementSibling.classList.add('active');
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>