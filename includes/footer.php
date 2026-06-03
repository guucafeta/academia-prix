<?php if (!defined('BASE_URL')) require_once __DIR__ . '/config.php'; ?>

<footer class="footer-prix" id="rodape">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="footer-brand">🏋️ ACADEMIA PRIX</h5>
                <p class="footer-desc">A academia mais completa de Campo Mourão. Estrutura moderna, professores qualificados e o ambiente certo para você conquistar seus objetivos.</p>
                <div class="social-icons">
                    <a href="https://www.instagram.com/prixacademia" target="_blank" rel="noopener" class="social-btn" aria-label="Instagram" id="linkInstagram"><i class="bi bi-instagram"></i></a>
                    
                    <a href="https://wa.me/554498062895" target="_blank" rel="noopener" class="social-btn" aria-label="WhatsApp" id="linkWhatsapp"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-title">Navegação</h6>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/planos.php">Planos</a></li>
                    <li><a href="<?= BASE_URL ?>/treinos.php">Meus Treinos</a></li>
                    <li><a href="<?= BASE_URL ?>/professores.php">Professores</a></li>
                    <li><a href="<?= BASE_URL ?>/contato.php">Contato</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-title">Modalidades</h6>
                <ul class="footer-links">
                    <li><a href="#">Musculação</a></li>
                    <li><a href="#">CrossFit</a></li>
                    <li><a href="#">Pilates</a></li>
                    <li><a href="#">Spinning</a></li>
                    <li><a href="#">Funcional</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6">
                <h6 class="footer-title">Academia Prix — Unidade Matriz</h6>
                <ul class="footer-contact">
                    <li><i class="bi bi-geo-alt-fill"></i>Avenida Irmãos Pereira, 251 Centro Campo Mourão - PR 87301-010 Brasil</li>
                    <li><i class="bi bi-telephone-fill"></i>+55 44 9806-2895</li>
                    <li><i class="bi bi-clock-fill"></i>Seg–Qui: 05:30 às 00:00 | Sex: 05:30 às 23:00 | Sáb: 09:00–12:00, 14:00–18:00 | Dom: 09:00–11:00</li>
                    
                </ul>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> Academia Prix Matriz. Todos os direitos reservados.</p>
            <p>Campo Mourão — Paraná, Brasil</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 50);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
})();
</script>
</body>
</html>
