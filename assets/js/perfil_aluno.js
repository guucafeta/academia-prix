/**
 * Gerenciador de Perfil do Aluno + Botão Atualizar Agendamentos
 * Arquivo: assets/js/perfil_aluno.js
 *
 * CORREÇÕES:
 *   1. URL da API usa BASE_URL_JS (injetado pelo PHP) — sem hardcode.
 *   2. Elementos de edição existem em aluno.php (formulário adicionado).
 */

(function () {
    'use strict';

    const API_URL = (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + '/api/atualizar_perfil_aluno.php';

    const btnSalvar     = document.getElementById('btnSalvarPerfil');
    const inputNome     = document.getElementById('inputNomeAluno');
    const inputEmail    = document.getElementById('inputEmailAluno');
    const inputTelefone = document.getElementById('inputTelefoneAluno');
    const msgPerfil     = document.getElementById('msgPerfilAluno');
    const avatarGrande  = document.getElementById('avatarInicial');
    const avatarHero    = document.getElementById('avatarInicialHero');
    const nomeHero      = document.getElementById('nomeAlunoHero');

    if (!btnSalvar) return;

    btnSalvar.addEventListener('click', salvarPerfil);

    [inputNome, inputEmail, inputTelefone].forEach(function (el) {
        if (el) el.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') salvarPerfil();
        });
    });

    async function salvarPerfil() {
        const nome     = inputNome     ? inputNome.value.trim()     : '';
        const email    = inputEmail    ? inputEmail.value.trim()    : '';
        const telefone = inputTelefone ? inputTelefone.value.trim() : '';

        if (!nome || nome.length < 3) {
            mostrarMsg('Nome deve ter pelo menos 3 caracteres', 'error');
            if (inputNome) inputNome.focus();
            return;
        }

        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Salvando...';

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nome: nome, email: email, telefone: telefone }),
                cache: 'no-store',
            });

            const dados = await response.json();

            if (response.ok && dados.sucesso) {
                mostrarMsg('✓ Perfil salvo com sucesso!', 'success');

                if (inputNome)     inputNome.value     = dados.nome;
                if (inputEmail)    inputEmail.value    = dados.email;
                if (inputTelefone) inputTelefone.value = dados.telefone;

                if (avatarGrande) avatarGrande.textContent = dados.inicial;
                if (avatarHero)   avatarHero.textContent   = dados.inicial;
                if (nomeHero)     nomeHero.textContent     = dados.nome.toUpperCase();

            } else {
                mostrarMsg(dados.erro || 'Erro ao salvar. Tente novamente.', 'error');
            }

        } catch (e) {
            mostrarMsg('Erro na conexão. Tente novamente.', 'error');
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="bi bi-floppy me-1"></i>Salvar Alterações';
        }
    }

    function mostrarMsg(texto, tipo) {
        if (!msgPerfil) return;
        const cls  = tipo === 'success' ? 'alert-prix-success' : 'alert-prix-error';
        const icon = tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle';
        msgPerfil.innerHTML = `<div class="${cls}"><i class="bi ${icon} me-2"></i>${texto}</div>`;
        if (tipo === 'success') setTimeout(() => { msgPerfil.innerHTML = ''; }, 4000);
    }

    // ── Botão Atualizar Agendamentos ───────────────────────────

    const btnAtualizar = document.getElementById('btnAtualizarAgendamentos');
    if (btnAtualizar) {
        btnAtualizar.addEventListener('click', function () {
            btnAtualizar.disabled = true;
            btnAtualizar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Atualizando...';

            if (window.verificarAgendamentosGlobal) {
                window.verificarAgendamentosGlobal();
            } else {
                location.reload();
            }

            setTimeout(() => {
                btnAtualizar.disabled = false;
                btnAtualizar.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Atualizar';
            }, 1200);
        });
    }

})();
