<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$titulo_pagina = 'Admin — Planos';
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
            $erros   = [];
            $nome    = trim($_POST['nome']    ?? '');
            $preco   = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
            $duracao = (int)($_POST['duracao_meses'] ?? 0);
            $desc    = trim($_POST['descricao'] ?? '');
            $destaque = isset($_POST['destaque']) ? 1 : 0;
            if (empty($nome)) $erros[] = 'Nome do plano obrigatório.';
            if ($preco <= 0)  $erros[] = 'Preço inválido.';
            if (empty($erros)) {
                $stmt = $pdo->prepare("INSERT INTO planos (nome, descricao, preco, duracao_meses, destaque) VALUES (:nome, :desc, :preco, :dur, :dest)");
                $stmt->execute([':nome'=>$nome, ':desc'=>$desc, ':preco'=>$preco, ':dur'=>$duracao, ':dest'=>$destaque]);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $msg = '<div class="alert-prix-success">Plano cadastrado com sucesso!</div>';
            } else {
                $msg = '<div class="alert-prix-error">' . implode('<br>', array_map('sanitizar', $erros)) . '</div>';
            }
        }

        if ($_POST['acao'] === 'editar' && !empty($_POST['id'])) {
            $erros   = [];
            $nome    = trim($_POST['nome']    ?? '');
            $preco   = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
            $duracao = (int)($_POST['duracao_meses'] ?? 0);
            $desc    = trim($_POST['descricao'] ?? '');
            $destaque = isset($_POST['destaque']) ? 1 : 0;
            if (empty($nome)) $erros[] = 'Nome do plano obrigatório.';
            if ($preco <= 0)  $erros[] = 'Preço inválido.';
            if (empty($erros)) {
                $stmt = $pdo->prepare("UPDATE planos SET nome=:nome, descricao=:desc, preco=:preco, duracao_meses=:dur, destaque=:dest WHERE id=:id");
                $stmt->execute([':nome'=>$nome, ':desc'=>$desc, ':preco'=>$preco, ':dur'=>$duracao, ':dest'=>$destaque, ':id'=>(int)$_POST['id']]);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $msg = '<div class="alert-prix-success">Plano atualizado com sucesso!</div>';
            } else {
                $msg = '<div class="alert-prix-error">' . implode('<br>', array_map('sanitizar', $erros)) . '</div>';
            }
        }

        if ($_POST['acao'] === 'deletar' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare("UPDATE planos SET ativo = 0 WHERE id = :id");
            $stmt->execute([':id' => (int)$_POST['id']]);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $msg = '<div class="alert-prix-success">Plano removido.</div>';
        }
    }
}

$planos = getPlanos();

// Plano sendo editado
$editando = null;
if (!empty($_GET['editar']) && is_numeric($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM planos WHERE id = :id AND ativo = 1 LIMIT 1");
    $stmt->execute([':id' => (int)$_GET['editar']]);
    $editando = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <span class="section-badge">Admin</span>
            <h1 class="section-title mb-0">GERENCIAR <span>PLANOS</span></h1>
        </div>
        <a href="<?= admin_route('index.php') ?>" class="btn btn-outline-prix" id="btnVoltarAdminPlanos"><i class="bi bi-arrow-left me-1"></i>Voltar ao Painel</a>
    </div>
</section>

<section class="section-prix section-dark" id="crud-planos">
    <div class="container">
        <?= $msg ?>
        <div class="row g-4">
            <div class="col-lg-4" data-animate>
                <div class="agendamento-box">
                    <h4 class="section-title mb-4">
                        <?= $editando ? 'EDITAR <span>PLANO</span>' : 'NOVO <span>PLANO</span>' ?>
                    </h4>
                    <form method="POST" class="form-prix" id="formPlano">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'inserir' ?>">
                        <?php if ($editando): ?>
                            <input type="hidden" name="id" value="<?= (int)$editando['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="pl_nome" class="form-label">Nome do Plano *</label>
                            <input type="text" name="nome" id="pl_nome" class="form-control"
                                   value="<?= sanitizar($editando['nome'] ?? '') ?>" placeholder="Ex: Mensal, Trimestral...">
                        </div>
                        <div class="mb-3">
                            <label for="pl_preco" class="form-label">Preço (R$) *</label>
                            <input type="number" name="preco" id="pl_preco" class="form-control" step="0.01" min="0"
                                   value="<?= isset($editando['preco']) ? number_format((float)$editando['preco'], 2, '.', '') : '' ?>"
                                   placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label for="pl_dur" class="form-label">Duração (meses)</label>
                            <input type="number" name="duracao_meses" id="pl_dur" class="form-control" min="0"
                                   value="<?= isset($editando['duracao_meses']) ? (int)$editando['duracao_meses'] : '' ?>"
                                   placeholder="0 = avulso">
                        </div>
                        <div class="mb-3">
                            <label for="pl_desc" class="form-label">Descrição</label>
                            <textarea name="descricao" id="pl_desc" class="form-control" rows="3"
                                      placeholder="Descrição do plano..."><?= sanitizar($editando['descricao'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3" style="display:flex;align-items:center;gap:10px;">
                            <input type="checkbox" name="destaque" id="pl_destaque" class="form-check-input"
                                   style="width:20px;height:20px;"
                                   <?= (!empty($editando['destaque'])) ? 'checked' : '' ?>>
                            <label for="pl_destaque" class="form-label mb-0">Marcar como destaque</label>
                        </div>

                        <button type="submit" class="btn btn-prix w-100" id="btnSalvarPlano">
                            <i class="bi bi-<?= $editando ? 'floppy' : 'plus-circle' ?> me-1"></i>
                            <?= $editando ? 'Salvar Alterações' : 'Cadastrar Plano' ?>
                        </button>
                        <?php if ($editando): ?>
                            <a href="<?= admin_route('planos.php') ?>" class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-lg-8" data-animate>
                <div class="agendamento-box">
                    <h4 class="section-title mb-4">PLANOS <span>CADASTRADOS</span></h4>
                    <?php if (empty($planos)): ?>
                        <p style="color:var(--prix-muted);">Nenhum plano cadastrado.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-prix" id="tabelaPlanosAdmin">
                            <thead>
                                <tr><th>Nome</th><th>Preço</th><th>Duração</th><th>Destaque</th><th>Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($planos as $p): ?>
                                <tr>
                                    <td><?= sanitizar($p['nome']) ?></td>
                                    <td><?= formatarPreco((float)$p['preco']) ?></td>
                                    <td><?= $p['duracao_meses'] == 0 ? 'Avulso' : (int)$p['duracao_meses'] . ' mês(es)' ?></td>
                                    <td>
                                        <?php if ($p['destaque']): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= admin_route('planos.php?editar=' . (int)$p['id']) ?>"
                                           class="btn btn-sm btn-outline-secondary me-1"
                                           id="btnEditarPlano<?= (int)$p['id'] ?>"
                                           title="Editar plano">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remover este plano?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="acao" value="deletar">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" id="btnDeletarPlano<?= (int)$p['id'] ?>"><i class="bi bi-trash"></i></button>
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
