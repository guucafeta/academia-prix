<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
// Sessão já iniciada pelo config.php
$titulo_pagina  = 'Planos';
$meta_descricao = 'Conheça os planos da Academia Prix Matriz em Campo Mourão.';
$planos = getPlanos();
$filtro_min = isset($_GET['min']) ? (float)$_GET['min'] : 0;
$filtro_max = isset($_GET['max']) ? (float)$_GET['max'] : 9999;
if ($filtro_min > 0 || $filtro_max < 9999) {
    $planos = filtrarPlanosPorPreco($planos, $filtro_min, $filtro_max);
}
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero text-center">
    <div class="container">
        <span class="section-badge">Invista em Você</span>
        <h1 class="section-title">NOSSOS <span>PLANOS</span></h1>
        <p class="section-sub mx-auto">Escolha o melhor plano para o seu objetivo e comece a transformar hoje.</p>
    </div>
</section>

<section class="section-prix section-dark" id="filtro-planos">
    <div class="container">
        <div class="text-center mb-4">
    <span class="d-block mb-2" style="color:var(--prix-muted);font-size:.9rem;">Filtrar por faixa de preço:</span>
            <?php
            $faixas = [
                ['label'=>'Todos',      'min'=>0,   'max'=>9999],
                ['label'=>'Até R$100',  'min'=>0,   'max'=>100],
                ['label'=>'R$100–400',  'min'=>100, 'max'=>400],
                ['label'=>'Acima R$400','min'=>400, 'max'=>9999],
            ];
            foreach ($faixas as $f):
                $active = ($filtro_min == $f['min'] && $filtro_max == $f['max']) ? 'active' : '';
            ?>
            <a href="planos.php?min=<?= $f['min'] ?>&max=<?= $f['max'] ?>" class="filter-btn me-2 mb-2 <?= $active ?>" id="filtro<?= preg_replace('/\W/','',$f['label']) ?>"><?= $f['label'] ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($planos)): ?>
        <div class="text-center py-5" style="color:var(--prix-muted);">
            <i class="bi bi-search" style="font-size:2rem;"></i>
            <p class="mt-2">Nenhum plano encontrado para esta faixa de preço.</p>
        </div>
        <?php else: ?>
        <div class="row g-4 justify-content-center">
            <?php foreach ($planos as $plano):
                $destaque = (bool)$plano['destaque'];
                $meses = (int)$plano['duracao_meses'];
            ?>
            <div class="col-lg-3 col-md-6" data-animate>
                <div class="plan-card h-100 <?= $destaque ? 'destaque' : '' ?>">
                    <?php if ($destaque): ?><div class="plan-badge-top"><i class="bi bi-star-fill me-1"></i>Mais Popular</div><?php endif; ?>
                    <div class="plan-name"><?= sanitizar($plano['nome']) ?></div>
                    <div class="mt-3 mb-1"><span class="plan-price"><?= formatarPreco((float)$plano['preco']) ?></span></div>
                    <div class="plan-price-label">
                        <?php if ($meses === 0): ?>por sessão avulsa
                        <?php else: ?>por <?= $meses ?> <?= $meses == 1 ? 'mês' : 'meses' ?><?php endif; ?>
                    </div>
                    <?php if ($meses > 1): ?>
                    <div class="plan-per-month"><i class="bi bi-tag me-1"></i>Equivale a <?= formatarPreco(precoPorMes($plano)) ?>/mês</div>
                    <?php endif; ?>
                    <hr style="border-color:var(--prix-border);margin:20px 0;">
                    <p style="color:var(--prix-muted);font-size:.9rem;"><?= sanitizar($plano['descricao']) ?></p>
                    <ul style="color:var(--prix-muted);font-size:.88rem;list-style:none;padding:0;margin:16px 0;">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Acesso a todas as modalidades</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Vestiários completos</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Avaliação física gratuita</li>
                        <?php if ($meses >= 3): ?><li><i class="bi bi-check-circle-fill text-success me-2"></i>Orientação nutricional</li><?php endif; ?>
                        <?php if ($meses >= 6): ?><li><i class="bi bi-check-circle-fill text-success me-2"></i>1 sessão personal grátis/mês</li><?php endif; ?>
                    </ul>
                    <a href="aluno.php" class="btn <?= $destaque ? 'btn-prix' : 'btn-outline-prix' ?> w-100 mt-auto" id="btnEscolherPlano<?= (int)$plano['id'] ?>">
                        <i class="bi bi-arrow-right-circle me-1"></i>Quero Este Plano
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="row mt-5">
            <div class="col-lg-8 mx-auto" data-animate>
                <h3 class="section-title text-center mb-4">DÚVIDAS <span>FREQUENTES</span></h3>
                <div class="accordion" id="accordionFaq">
                    <?php
                    $faqs = [
                        ['Posso cancelar meu plano?','Sim! Planos mensais podem ser cancelados a qualquer momento. Para planos com prazo, consulte nossas condições na recepção.'],
                        ['Tem taxa de matrícula?','Não cobramos taxa de matrícula. Você paga apenas o valor do plano escolhido.'],
                        ['Os planos incluem personal trainer?','Os planos de academia incluem acompanhamento em grupo. O personal trainer é um serviço adicional.'],
                        ['Posso congelar meu plano?','Sim, aceitamos congelamento por motivo de saúde mediante atestado médico.'],
                    ];
                    foreach ($faqs as $i => [$pergunta, $resposta]):
                    ?>
                    <div class="accordion-item" style="background:var(--prix-card);border:1px solid var(--prix-border);border-radius:10px;margin-bottom:10px;">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>"
                                    style="background:transparent;color:var(--prix-text);font-weight:500;" id="btnFaq<?= $i ?>">
                                <?= sanitizar($pergunta) ?>
                            </button>
                        </h2>
                        <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#accordionFaq">
                            <div class="accordion-body" style="color:var(--prix-muted);font-size:.9rem;"><?= sanitizar($resposta) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
