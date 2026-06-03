<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$titulo_pagina = 'Admin — Professores';
$pdo = getConnection();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $msg = '<div class="alert-prix-error">Falha na validação de segurança. Atualize a página e tente novamente.</div>';
    } else {

        if ($_POST['acao'] === 'inserir') {
            $dados = [
                ':nome'          => trim($_POST['nome'] ?? ''),
                ':especialidade' => trim($_POST['especialidade'] ?? ''),
                ':bio'           => trim($_POST['bio'] ?? ''),
                ':instagram'     => trim($_POST['instagram'] ?? ''),
            ];
            $erros = [];
            if (empty($dados[':nome']))          $erros[] = 'Nome obrigatório.';
            if (empty($dados[':especialidade'])) $erros[] = 'Especialidade obrigatória.';
            if (empty($erros)) {
                $stmt = $pdo->prepare("INSERT INTO professores (nome, especialidade, bio, instagram) VALUES (:nome, :especialidade, :bio, :instagram)");
                $stmt->execute($dados);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $msg = '<div class="alert-prix-success">Professor cadastrado com sucesso!</div>';
            } else {
                $msg = '<div class="alert-prix-error">' . implode('<br>', array_map('sanitizar', $erros)) . '</div>';
            }
        }

        if ($_POST['acao'] === 'editar' && !empty($_POST['id'])) {
            $erros = [];
            $nome          = trim($_POST['nome'] ?? '');
            $especialidade = trim($_POST['especialidade'] ?? '');
            $bio           = trim($_POST['bio'] ?? '');
            $instagram     = trim($_POST['instagram'] ?? '');
            if (empty($nome))          $erros[] = 'Nome obrigatório.';
            if (empty($especialidade)) $erros[] = 'Especialidade obrigatória.';
            if (empty($erros)) {
                $stmt = $pdo->prepare("UPDATE professores SET nome=:nome, especialidade=:esp, bio=:bio, instagram=:ig WHERE id=:id");
                $stmt->execute([':nome'=>$nome, ':esp'=>$especialidade, ':bio'=>$bio, ':ig'=>$instagram, ':id'=>(int)$_POST['id']]);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $msg = '<div class="alert-prix-success">Professor atualizado com sucesso!</div>';
            } else {
                $msg = '<div class="alert-prix-error">' . implode('<br>', array_map('sanitizar', $erros)) . '</div>';
            }
        }

        if ($_POST['acao'] === 'deletar' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare("UPDATE professores SET ativo = 0 WHERE id = :id");
            $stmt->execute([':id' => (int)$_POST['id']]);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $msg = '<div class="alert-prix-success">Professor removido.</div>';
        }
    }
}

$professores = getProfessores();

// Professor sendo editado (se clicou em "Editar")
$editando = null;
if (!empty($_GET['editar']) && is_numeric($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM professores WHERE id = :id AND ativo = 1 LIMIT 1");
    $stmt->execute([':id' => (int)$_GET['editar']]);
    $editando = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <span class="section-badge">Admin</span>
            <h1 class="section-title mb-0">GERENCIAR <span>PROFESSORES</span></h1>
        </div>
        <a href="<?= admin_route('index.php') ?>" class="btn btn-outline-prix" id="btnVoltarAdmin"><i class="bi bi-arrow-left me-1"></i>Voltar ao Painel</a>
    </div>
</section>

<section class="section-prix section-dark" id="crud-professores">
    <div class="container">
        <?= $msg ?>
        <div class="row g-4">
            <!-- Formulário: inserir ou editar -->
            <div class="col-lg-4" data-animate>
                <div class="agendamento-box">
                    <h4 class="section-title mb-4">
                        <?= $editando ? 'EDITAR <span>PROFESSOR</span>' : 'NOVO <span>PROFESSOR</span>' ?>
                    </h4>
                    <form method="POST" class="form-prix" id="formProfessor">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'inserir' ?>">
                        <?php if ($editando): ?>
                            <input type="hidden" name="id" value="<?= (int)$editando['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="adm_nome" class="form-label">Nome completo *</label>
                            <input type="text" name="nome" id="adm_nome" class="form-control"
                                   value="<?= sanitizar($editando['nome'] ?? '') ?>" placeholder="Nome do professor">
                        </div>
                        <div class="mb-3">
                            <label for="adm_esp" class="form-label">Especialidade *</label>
                            <input type="text" name="especialidade" id="adm_esp" class="form-control"
                                   value="<?= sanitizar($editando['especialidade'] ?? '') ?>" placeholder="Ex: Musculação, CrossFit...">
                        </div>
                        <div class="mb-3">
                            <label for="adm_bio" class="form-label">Bio</label>
                            <textarea name="bio" id="adm_bio" class="form-control" rows="3" placeholder="Breve descrição..."><?= sanitizar($editando['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="adm_ig" class="form-label">Instagram</label>
                            <input type="text" name="instagram" id="adm_ig" class="form-control"
                                   value="<?= sanitizar($editando['instagram'] ?? '') ?>" placeholder="@usuario">
                        </div>

                        <button type="submit" class="btn btn-prix w-100" id="btnSalvarProfessor">
                            <i class="bi bi-<?= $editando ? 'floppy' : 'plus-circle' ?> me-1"></i>
                            <?= $editando ? 'Salvar Alterações' : 'Cadastrar Professor' ?>
                        </button>
                        <?php if ($editando): ?>
                            <a href="<?= admin_route('professores.php') ?>" class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Tabela de professores -->
            <div class="col-lg-8" data-animate>
                <div class="agendamento-box">
                    <h4 class="section-title mb-4">PROFESSORES <span>CADASTRADOS</span></h4>
                    <?php if (empty($professores)): ?>
                        <p style="color:var(--prix-muted);">Nenhum professor cadastrado.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-prix" id="tabelaProfessoresAdmin">
                            <thead>
                                <tr><th>Nome</th><th>Especialidade</th><th>Instagram</th><th>Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($professores as $p): ?>
                                <tr>
                                    <td><?= sanitizar($p['nome']) ?></td>
                                    <td><?= sanitizar($p['especialidade']) ?></td>
                                    <td><?= sanitizar($p['instagram'] ?? '-') ?></td>
                                    <td>
                                        <a href="<?= admin_route('professores.php?editar=' . (int)$p['id']) ?>"
                                           class="btn btn-sm btn-outline-secondary me-1"
                                           id="btnEditarProf<?= (int)$p['id'] ?>"
                                           title="Editar professor">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remover este professor?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="acao" value="deletar">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" id="btnDeletarProf<?= (int)$p['id'] ?>"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
