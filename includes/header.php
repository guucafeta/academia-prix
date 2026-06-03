<?php
require_once __DIR__ . '/config.php';
$pagina_atual = basename($_SERVER['PHP_SELF'], '.php');

// Content-Security-Policy otimizado para funcionar com Google Maps
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://maps.googleapis.com https://maps.gstatic.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com https://maps.googleapis.com https://maps.gstatic.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https: blob:; connect-src 'self' https://maps.googleapis.com https://maps.gstatic.com https://*.googleapis.com https://*.gstatic.com; frame-src https://www.google.com; child-src https://www.google.com; worker-src 'self' blob:; frame-ancestors 'none';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $meta_descricao ?? 'Academia Prix Matriz em Campo Mourão, PR.' ?>">
    <title><?= isset($titulo_pagina) ? sanitizar($titulo_pagina) . ' — Academia Prix' : 'Academia Prix Matriz | Campo Mourão - PR' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= URL_ASSETS ?>/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-prix fixed-top" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <span class="logo-prix">🏋️ ACADEMIA PRIX</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPrix"
                aria-controls="navbarPrix" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarPrix">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link <?= $pagina_atual === 'index' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $pagina_atual === 'planos' ? 'active' : '' ?>" href="<?= BASE_URL ?><?= (!empty($_SESSION['is_admin']) ? '/admin' : '') ?>/planos.php">Planos</a></li>
                <li class="nav-item"><a class="nav-link <?= $pagina_atual === 'treinos' ? 'active' : '' ?>" href="<?= BASE_URL ?>/treinos.php">Meus Treinos</a></li>
                <li class="nav-item"><a class="nav-link <?= $pagina_atual === 'professores' ? 'active' : '' ?>" href="<?= BASE_URL ?><?= (!empty($_SESSION['is_admin']) ? '/admin' : '') ?>/professores.php">Professores</a></li>
                <li class="nav-item"><a class="nav-link <?= $pagina_atual === 'contato' ? 'active' : '' ?>" href="<?= BASE_URL ?>/contato.php">Contato</a></li>
            </ul>
            <?php if (!empty($_SESSION['aluno_id'])): ?>
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-prix btn-sm px-4" id="btnAreaAdmin">
                            <i class="bi bi-shield-lock me-1"></i>Painel Admin
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/aluno.php" class="btn btn-prix btn-sm px-4" id="btnAreaAluno">
                            <i class="bi bi-person-fill me-1"></i><?= sanitizar($_SESSION['aluno_nome'] ?? 'Minha Área') ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?= !empty($_SESSION['is_admin']) ? BASE_URL . '/admin/logout.php' : BASE_URL . '/aluno/logout.php' ?>" class="btn btn-sm btn-outline-secondary" title="Sair"
                        style="border-color:rgba(255,107,33,.4);color:var(--prix-muted);">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= BASE_URL ?>/aluno/login.php" class="btn btn-prix btn-sm px-4" id="btnAreaAluno">
                        <i class="bi bi-person-fill me-1"></i> Área do Aluno
                    </a>
                    <a href="<?= BASE_URL ?>/admin/login.php"
                       class="btn btn-sm"
                       title="Entrar como Administrador"
                       style="border:1px solid rgba(255,107,33,.35);color:var(--prix-muted);font-size:.75rem;padding:4px 10px;border-radius:6px;opacity:.7;transition:opacity .2s;"
                       onmouseover="this.style.opacity='1'"
                       onmouseout="this.style.opacity='.7'">
                        <i class="bi bi-shield-lock me-1"></i>Admin
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div style="height:72px;"></div>