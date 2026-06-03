/**
 * trocar_plano.js
 * Gerencia a troca de plano do aluno direto da área do aluno.
 * Depende de: BASE_URL_JS, CSRF_TOKEN_PLANO, PLANO_ATUAL_ID (injetados pelo PHP)
 */

(function () {
    'use strict';

    const API_URL = (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + '/api/atualizar_plano_aluno.php';

    let csrfToken = (typeof CSRF_TOKEN_PLANO !== 'undefined') ? CSRF_TOKEN_PLANO : '';

    const msgBox       = document.getElementById('msgTrocarPlano');
    const nomeDisplay  = document.getElementById('planoAtualNome');
    const precoDisplay = document.getElementById('planoAtualPreco');

    // Delegar cliques nos botões "Quero Este Plano" dentro do modal
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-selecionar-plano');
        if (!btn) return;

        const planoId    = parseInt(btn.dataset.planoId, 10);
        const planoNome  = btn.dataset.planoNome;
        const planoPreco = btn.dataset.planoPreco;

        if (!planoId) return;

        // Confirmação
        if (!confirm('Deseja trocar para o plano "' + planoNome + '" (' + planoPreco + ')?')) return;

        trocarPlano(btn, planoId, planoNome, planoPreco);
    });

    async function trocarPlano(btn, planoId, planoNome, planoPreco) {
        // Desabilitar todos os botões durante a requisição
        const todosBtns = document.querySelectorAll('.btn-selecionar-plano');
        todosBtns.forEach(function (b) { b.disabled = true; });
        btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Aguarde...';

        mostrarMsg('', '');

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    plano_id:   planoId,
                    csrf_token: csrfToken,
                }),
                cache: 'no-store',
            });

            const dados = await response.json();

            if (response.ok && dados.sucesso) {
                // Atualizar CSRF token
                if (dados.novo_csrf_token) {
                    csrfToken = dados.novo_csrf_token;
                }

                // Atualizar display do plano no card
                if (nomeDisplay)  nomeDisplay.textContent  = dados.plano_nome;
                if (precoDisplay) {
                    precoDisplay.textContent = formatarPreco(dados.plano_preco);
                    precoDisplay.style.display = '';
                }

                // Mostrar mensagem de sucesso dentro do modal
                mostrarMsg('✓ Plano "' + dados.plano_nome + '" ativado com sucesso!', 'success');

                // Atualizar os botões do grid: marcar novo como ativo, desmarcar anterior
                atualizarGridPlanos(planoId, planoNome, planoPreco);

                // Fechar modal após 2 segundos
                setTimeout(function () {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalTrocarPlano'));
                    if (modal) modal.hide();
                    mostrarMsg('', '');
                }, 2000);

            } else {
                mostrarMsg(dados.erro || 'Erro ao trocar plano. Tente novamente.', 'error');
                // Reabilitar botões
                reabilitarBotoes();
                btn.innerHTML = '<i class="bi bi-arrow-right-circle me-1"></i>Quero Este Plano';
            }
        } catch (err) {
            mostrarMsg('Erro na conexão. Verifique sua internet e tente novamente.', 'error');
            reabilitarBotoes();
            btn.innerHTML = '<i class="bi bi-arrow-right-circle me-1"></i>Quero Este Plano';
        }
    }

    function atualizarGridPlanos(novoPlanoId, novoNome, novoPreco) {
        const todosBtns = document.querySelectorAll('.btn-selecionar-plano');

        // Transformar o botão clicado em "Plano Ativo"
        todosBtns.forEach(function (b) {
            const id = parseInt(b.dataset.planoId, 10);
            if (id === novoPlanoId) {
                b.disabled    = true;
                b.textContent = '✓ Plano Ativo';
                b.className   = 'btn btn-outline-secondary w-100 mt-auto';
                b.classList.remove('btn-selecionar-plano');
            } else {
                // Reabilitar os demais
                b.disabled  = false;
                b.innerHTML = '<i class="bi bi-arrow-right-circle me-1"></i>Quero Este Plano';
            }
        });
    }

    function reabilitarBotoes() {
        document.querySelectorAll('.btn-selecionar-plano').forEach(function (b) {
            b.disabled  = false;
        });
    }

    function mostrarMsg(texto, tipo) {
        if (!msgBox) return;
        if (!texto) { msgBox.innerHTML = ''; return; }
        const cls  = tipo === 'success' ? 'alert-prix-success' : 'alert-prix-error';
        const icon = tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle';
        msgBox.innerHTML = '<div class="' + cls + '"><i class="bi ' + icon + ' me-2"></i>' + texto + '</div>';
    }

    function formatarPreco(valor) {
        if (isNaN(valor)) return valor;
        return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
    }

})();
