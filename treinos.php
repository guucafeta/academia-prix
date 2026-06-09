<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
// Sessão já iniciada pelo config.php
$titulo_pagina  = 'Meus Treinos';
$meta_descricao = 'Treinos, vídeos e dicas da Academia Prix.';

$categoria_filtro = $_GET['cat'] ?? '';
$videos = [
    ['arquivo'=>BASE_URL . '/videos/depoimentos/depoimento-estrutura.mp4', 'titulo'=>'Estrutura da Academia', 'descricao'=>'Conheça nossas instalações e equipamentos.', 'categoria'=>'Estrutura', 'icone'=>'bi-buildings'],
    ['arquivo'=>BASE_URL . '/videos/depoimentos/depoimento-alunos.mp4', 'titulo'=>'Depoimentos de Alunos', 'descricao'=>'O que nossos alunos acham da academia.', 'categoria'=>'Depoimentos', 'icone'=>'bi-chat-hearts'],
    ['arquivo'=>BASE_URL . '/videos/depoimentos/depoimento-decicao.mp4', 'titulo'=>'Motivação para Começar', 'descricao'=>'Tome a decisão certa para sua saúde.', 'categoria'=>'Motivação', 'icone'=>'bi-heart-fill'],
    ['arquivo'=>BASE_URL . '/videos/treinos/treino-gluteo-upgrade.mp4', 'titulo'=>'Treino: Glúteo Upgrade', 'descricao'=>'Série de exercícios para definição e força.', 'categoria'=>'Treino', 'icone'=>'bi-play-circle'],
];

if (!empty($categoria_filtro)) {
    $videos = array_filter($videos, function(array $v) use ($categoria_filtro): bool {
        return mb_strtolower($v['categoria']) === mb_strtolower($categoria_filtro);
    });
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero text-center">
    <div class="container">
        <span class="section-badge">Conteúdo Exclusivo</span>
        <h1 class="section-title">TREINOS E <span>VÍDEOS</span></h1>
        <p class="section-sub mx-auto">Aprenda com nossos professores, confira depoimentos e veja a estrutura da academia.</p>
    </div>
</section>

<section class="section-prix section-dark" id="videos-treino">
    <div class="container">
        <div class="mb-5">
            <a href="<?= BASE_URL ?>/treinos.php" class="filter-btn me-2 mb-2 <?= empty($categoria_filtro) ? 'active' : '' ?>" id="filtroTodosCat">Todos</a>
            <a href="<?= BASE_URL ?>/treinos.php?cat=Estrutura" class="filter-btn me-2 mb-2 <?= $categoria_filtro === 'Estrutura' ? 'active' : '' ?>" id="filtroEstrutura">Estrutura</a>
            <a href="<?= BASE_URL ?>/treinos.php?cat=Depoimentos" class="filter-btn me-2 mb-2 <?= $categoria_filtro === 'Depoimentos' ? 'active' : '' ?>" id="filtroDepoimentos">Depoimentos</a>
            <a href="<?= BASE_URL ?>/treinos.php?cat=Treino" class="filter-btn me-2 mb-2 <?= $categoria_filtro === 'Treino' ? 'active' : '' ?>" id="filtroTreino">Treinos</a>
        </div>

        <?php if (empty($videos)): ?>
            <div class="text-center py-5" id="emptyVideos">
                <i class="bi bi-camera-video-off" style="font-size:2.5rem;"></i>
                <p style="color:var(--prix-muted);margin-top:16px;">Nenhum vídeo disponível nesta categoria.</p>
            </div>
        <?php else: ?>
            <div class="videos-grid">
                <?php foreach ($videos as $idx => $video): ?>
                <div class="video-card" data-animate>
                    <!-- CORRIGIDO: removido poster fixo — cada vídeo carrega naturalmente -->
                    <video controls preload="metadata" aria-label="<?= sanitizar($video['titulo']) ?>" id="video<?= $idx ?>">
                        <source src="<?= htmlspecialchars($video['arquivo']) ?>" type="video/mp4">
                        Seu navegador não suporta vídeo HTML5.
                    </video>
                    <div class="video-card-body">
                        <div class="video-card-meta">
                            <span class="badge">
                                <i class="bi <?= sanitizar($video['icone']) ?> me-1"></i><?= sanitizar($video['categoria']) ?>
                            </span>
                        </div>
                        <div class="video-card-title"><?= sanitizar($video['titulo']) ?></div>
                        <div class="video-card-desc mt-1"><?= sanitizar($video['descricao']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>