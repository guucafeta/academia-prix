<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$titulo_pagina = 'Admin — Horários de Funcionamento';
$pdo = getConnection();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';

$nomes_dias = [0=>'Domingo',1=>'Segunda-feira',2=>'Terça-feira',3=>'Quarta-feira',4=>'Quinta-feira',5=>'Sexta-feira',6=>'Sábado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $msg = '<div class="alert-prix-error">Falha na validação de segurança. Atualize a página e tente novamente.</div>';
    } else {

        if ($_POST['acao'] === 'toggle' && isset($_POST['id'])) {
            $stmt = $pdo->prepare("UPDATE horarios_funcionamento SET ativo = NOT ativo WHERE id = :id");
            $stmt->execute([':id' => (int)$_POST['id']]);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $msg = '<div class="alert-prix-success"><i class="bi bi-check-circle-fill me-2"></i>Horário atualizado com sucesso!</div>';
        }

        if ($_POST['acao'] === 'inserir') {
            $dia  = (int)($_POST['dia_semana'] ?? -1);
            $hora = trim($_POST['hora'] ?? '');
            $erros = [];
            if ($dia < 0 || $dia > 6) $erros[] = 'Dia da semana inválido.';
            if (!preg_match('/^\d{2}:\d{2}$/', $hora)) $erros[] = 'Formato de hora inválido (HH:MM).';
            if (empty($erros)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO horarios_funcionamento (dia_semana, hora, ativo) VALUES (:dia, :hora, 1)");
                    $stmt->execute([':dia' => $dia, ':hora' => $hora . ':00']);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $msg = '<div class="alert-prix-success"><i class="bi bi-check-circle-fill me-2"></i>Horário adicionado com sucesso!</div>';
                } catch (Exception $e) {
                    $msg = '<div class="alert-prix-error">Este horário já existe para este dia.</div>';
                }
            } else {
                $msg = '<div class="alert-prix-error">' . implode('<br>', array_map('sanitizar', $erros)) . '</div>';
            }
        }

        if ($_POST['acao'] === 'deletar' && isset($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM horarios_funcionamento WHERE id = :id");
            $stmt->execute([':id' => (int)$_POST['id']]);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $msg = '<div class="alert-prix-success"><i class="bi bi-check-circle-fill me-2"></i>Horário removido.</div>';
        }
    }
}

// Buscar todos os horários agrupados por dia
$stmt = $pdo->query("SELECT * FROM horarios_funcionamento ORDER BY dia_semana, hora");
$todos = $stmt->fetchAll();
$por_dia = [];
foreach ($todos as $h) {
    $por_dia[$h['dia_semana']][] = $h;
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <span class="section-badge">Admin</span>
            <h1 class="section-title">HORÁRIOS DE <span>FUNCIONAMENTO</span></h1>
        </div>
        <a href="<?= admin_route('index.php') ?>" class="btn btn-outline-prix"><i class="bi bi-arrow-left me-1"></i>Voltar ao Painel</a>
    </div>
</section>

<section class="section-prix section-dark">
    <div class="container">

        <?= $msg ?>

        <!-- Adicionar horário -->
        <div class="card-prix p-4 mb-5" data-animate>
            <h5 style="color:var(--prix-white);margin-bottom:20px;"><i class="bi bi-plus-circle me-2" style="color:var(--prix-orange);"></i>Adicionar Horário</h5>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="acao" value="inserir">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Dia da Semana</label>
                        <select name="dia_semana" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($nomes_dias as $num => $nome): ?>
                            <option value="<?= $num ?>"><?= $nome ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Horário (HH:MM)</label>
                        <input type="time" name="hora" class="form-control" step="1800" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-prix w-100"><i class="bi bi-plus-circle me-2"></i>Adicionar</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Horários por dia -->
        <?php foreach ($nomes_dias as $dow => $nome_dia): ?>
        <?php $horarios_dia = $por_dia[$dow] ?? []; ?>
        <div class="mb-5" data-animate>
            <h4 class="section-title mb-3">
                <?= strtoupper($nome_dia) ?>
                <span style="font-size:.9rem;color:var(--prix-muted);font-family:sans-serif;font-weight:400;margin-left:10px;">
                    (<?= count(array_filter($horarios_dia, fn($h) => $h['ativo'])) ?> horário(s) ativo(s))
                </span>
            </h4>

            <?php if (empty($horarios_dia)): ?>
                <p style="color:var(--prix-muted);font-size:.9rem;">Nenhum horário cadastrado para este dia.</p>
            <?php else: ?>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($horarios_dia as $h): ?>
                <div class="d-flex align-items-center gap-1" style="background:var(--prix-card);border:1px solid <?= $h['ativo'] ? 'var(--prix-orange)' : 'var(--prix-border)' ?>;border-radius:8px;padding:6px 12px;">
                    <span style="font-size:.9rem;color:<?= $h['ativo'] ? 'var(--prix-white)' : 'var(--prix-muted)' ?>;min-width:42px;">
                        <?= sanitizar(substr($h['hora'], 0, 5)) ?>
                    </span>

                    <!-- Ativar/Desativar -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="acao" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="padding:2px 6px;font-size:.7rem;background:none;border:none;" title="<?= $h['ativo'] ? 'Desativar' : 'Ativar' ?>">
                            <i class="bi <?= $h['ativo'] ? 'bi-toggle-on' : 'bi-toggle-off' ?>" style="font-size:1.1rem;color:<?= $h['ativo'] ? '#198754' : 'var(--prix-muted)' ?>;"></i>
                        </button>
                    </form>

                    <!-- Remover -->
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remover este horário?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="acao" value="deletar">
                        <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="padding:2px 6px;font-size:.7rem;background:none;border:none;" title="Remover">
                            <i class="bi bi-x-circle" style="font-size:.95rem;color:#dc3545;"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
