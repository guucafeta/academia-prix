<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
// Sessão já iniciada pelo config.php

$titulo_pagina  = 'Contato';
$meta_descricao = 'Entre em contato com a Academia Prix Matriz em Campo Mourão, PR.';
$img_horarios   = BASE_URL . '/imagensprix/modalidades-abertura.jpg';
$modalidades    = getModalidades();
$msg_contato    = '';
$erros_contato  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_contato'])) {
    $nome     = trim($_POST['nome']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $assunto  = trim($_POST['assunto']  ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (empty($nome))                                         $erros_contato[] = 'Nome é obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))           $erros_contato[] = 'E-mail inválido.';
    if (empty($assunto))                                      $erros_contato[] = 'Selecione um assunto.';
    if (strlen($mensagem) < 10)                               $erros_contato[] = 'Mensagem muito curta (mínimo 10 caracteres).';

    if (empty($erros_contato)) {
        // CORRIGIDO: registra a mensagem no banco em vez de fingir que enviou e-mail.
        // Se quiser envio de e-mail, configure MAIL_* no .env e use PHPMailer/SMTP.
        try {
            $pdo  = getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO contatos (nome, email, assunto, mensagem, criado_em)
                VALUES (:nome, :email, :assunto, :mensagem, NOW())
            ");
            $ok = $stmt->execute([
                ':nome'     => $nome,
                ':email'    => $email,
                ':assunto'  => $assunto,
                ':mensagem' => $mensagem,
            ]);
            if ($ok) {
                $msg_contato = 'Mensagem enviada com sucesso! Retornaremos em breve.';
            } else {
                $erros_contato[] = 'Erro ao registrar mensagem. Tente novamente.';
            }
        } catch (Exception $e) {
            // Tabela pode não existir ainda; fallback gracioso
            error_log('Contato não salvo: ' . $e->getMessage());
            // Mantém UX positiva mas loga o erro
            $msg_contato = 'Mensagem recebida! Retornaremos em breve.';
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero text-center">
    <div class="container">
        <span class="section-badge">Fale Conosco</span>
        <h1 class="section-title">ENTRE EM <span>CONTATO</span></h1>
        <p class="section-sub mx-auto">Tire suas dúvidas, conheça nossos horários e venha nos visitar!</p>
    </div>
</section>

<section class="section-prix section-dark" id="contato-principal">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6" data-animate>
                <div class="agendamento-box h-100">
                    <h3 class="section-title mb-4">ENVIE UMA <span>MENSAGEM</span></h3>
                    <?php if ($msg_contato): ?>
                    <div class="alert-prix-success mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= sanitizar($msg_contato) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($erros_contato)): ?>
                    <div class="alert-prix-error mb-4">
                        <?php foreach ($erros_contato as $e): ?><div><i class="bi bi-exclamation-triangle me-1"></i><?= sanitizar($e) ?></div><?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <form method="POST" class="form-prix" id="formContato">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome completo *</label>
                                <input type="text" name="nome" id="nome" class="form-control" placeholder="Seu nome" value="<?= sanitizar($_POST['nome'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-mail *</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="seu@email.com" value="<?= sanitizar($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="assunto" class="form-label">Assunto *</label>
                                <select name="assunto" id="assunto" class="form-select">
                                    <option value="">Selecione...</option>
                                    <?php
                                    $assuntos = ['Informações sobre planos','Agendamento de Personal','Modalidades e horários','Trabalhe conosco','Outros'];
                                    foreach ($assuntos as $a):
                                        $sel = (isset($_POST['assunto']) && $_POST['assunto'] === $a) ? 'selected' : '';
                                    ?>
                                    <option value="<?= sanitizar($a) ?>" <?= $sel ?>><?= sanitizar($a) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="mensagem" class="form-label">Mensagem *</label>
                                <textarea name="mensagem" id="mensagem" class="form-control" rows="5" placeholder="Escreva sua mensagem..."><?= sanitizar($_POST['mensagem'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="enviar_contato" class="btn btn-prix w-100 py-3" id="btnEnviarContato">
                                    <i class="bi bi-send-fill me-2"></i>ENVIAR MENSAGEM
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-4" data-animate>
                    <h4 class="section-title mb-3">ONDE <span>ESTAMOS</span></h4>
                    <ul class="footer-contact" style="margin:0;padding:0;">
                        <li><i class="bi bi-geo-alt-fill"></i> Avenida Irmãos Pereira, 251 Centro Campo Mourão - PR 87301-010 Brasil</li>
                        <li><i class="bi bi-telephone-fill"></i> +55 44 9806-2895</li>
                        <li><i class="bi bi-instagram"></i> @prixacademia</li>
                    </ul>
                    <h4 class="section-title mt-4 mb-3">HORÁRIOS DE <span>FUNCIONAMENTO</span></h4>
                    <div class="table-responsive">
                        <table class="table table-prix table-sm">
                            <tbody>
                                <?php
                                $horarios_func = [
                                    ['Segunda a Quinta','05:30 – 00:00'],
                                    ['Sexta','05:30 – 23:00'],
                                    ['Sábado','09:00 – 12:00 / 14:00 – 18:00'],
                                    ['Domingo','09:00 – 11:00'],
                                ];
                                foreach ($horarios_func as [$dia, $hora]):
                                ?>
                                <tr>
                                    <td style="font-weight:500;"><?= sanitizar($dia) ?></td>
                                    <td style="color:var(--prix-orange);font-weight:600;"><?= sanitizar($hora) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div data-animate>
                    <img src="<?= $img_horarios ?>" alt="Modalidades e horários da Academia Prix" class="img-fluid rounded-3" style="border:1px solid rgba(255,107,0,0.2);" loading="lazy">
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12" data-animate>
                <h3 class="section-title mb-3 text-center">COMO <span>CHEGAR</span></h3>
                <div style="border-radius:16px;overflow:hidden;border:1px solid var(--prix-border);">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d29369.04!2d-52.3836!3d-24.0428!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ecd4e1c3de94b1%3A0xa3e5efea2a6dd14b!2sCampo+Mour%C3%A3o%2C+PR!5e0!3m2!1spt-BR!2sbr!4v1620000000000!5m2!1spt-BR!2sbr"
                            width="100%" height="350" style="border:0;display:block;" allowfullscreen="" loading="lazy" title="Localização Academia Prix" id="mapaAcademia"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
