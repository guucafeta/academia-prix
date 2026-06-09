<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
// Sessão já iniciada pelo config.php
$titulo_pagina  = 'Professores';
$meta_descricao = 'Conheça os professores da Academia Prix Matriz.';
$todos_professores = getProfessores();
$professores = $todos_professores;
require_once __DIR__ . '/includes/header.php';
?>
 
<section class="page-hero text-center">
    <div class="container">
        <span class="section-badge">Nossa Equipe</span>
        <h1 class="section-title">NOSSOS <span>PROFESSORES</span></h1>
        <p class="section-sub mx-auto">Profissionais qualificados prontos para te guiar até seus objetivos.</p>
    </div>
</section>
 
<section class="section-prix section-dark" id="lista-professores">
    <div class="container">
        <!-- Filtros removidos -->
 
        <?php if (empty($professores)): ?>
        <div class="text-center py-5" style="color:var(--prix-muted);">
            <i class="bi bi-person-x" style="font-size:2.5rem;"></i>
            <p class="mt-3">Nenhum professor encontrado.</p>
        </div>
        <?php else: ?>
        <div class="row g-4 justify-content-center">
            <?php foreach ($professores as $prof): ?>
            <div class="col-lg-3 col-md-6" data-animate>
                <div class="prof-card h-100">
                    <?php $foto = trim($prof['foto'] ?? ''); ?>
                    <?php if ($foto): ?>
                        <!-- CORRIGIDO: Remover $base duplicado e usar caminho direto -->
                        <img src="<?= BASE_URL ?>/<?= sanitizar($foto) ?>" alt="Foto de <?= sanitizar($prof['nome']) ?>" class="prof-avatar">
                    <?php else: ?>
                        <div class="prof-avatar-placeholder"><?= mb_substr(sanitizar($prof['nome']), 0, 1) ?></div>
                    <?php endif; ?>
                    <div class="prof-name"><?= sanitizar($prof['nome']) ?></div>
                    <div class="prof-esp"><?= sanitizar($prof['especialidade']) ?></div>
                    <div class="prof-bio"><?= sanitizar($prof['bio']) ?></div>
                    <?php if (!empty($prof['instagram'])): ?>
                    <div class="prof-instagram mt-2"><i class="bi bi-instagram me-1"></i><?= sanitizar($prof['instagram']) ?></div>
                    <?php endif; ?>
                    <a href="index.php#agendamento" class="btn btn-outline-prix w-100 mt-3" id="btnAgendarProf<?= (int)$prof['id'] ?>" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <i class="bi bi-calendar-plus me-1"></i>Agendar com <?= sanitizar(explode(' ', $prof['nome'])[0]) ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p class="text-center mt-4" style="color:var(--prix-muted);font-size:.85rem;">
            Exibindo <strong style="color:var(--prix-orange);"><?= count($todos_professores) ?></strong> professor(es).
        </p>
    </div>
</section>
 
<section class="section-prix section-darker">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center" data-animate>
                <h2 class="section-title">QUER TREINAR COM UM <span>PERSONAL?</span></h2>
                <p class="section-sub mx-auto mb-4">Treinos individualizados para resultados mais rápidos.</p>
               <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-2">
    <a href="index.php#agendamento" class="btn btn-prix btn-lg" id="btnProfCTAAgendar"><i class="bi bi-calendar-check me-2"></i>Agendar Sessão</a>
    <a href="planos.php" class="btn btn-outline-prix btn-lg" id="btnProfCTAPlanos">Ver Planos</a>
</div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
