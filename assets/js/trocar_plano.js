/**
 * assets/js/trocar_plano.js
 * Gerenciador de Troca de Plano do Aluno
 *
 * RESPONSABILIDADE DESTE ARQUIVO:
 *   Controla a interação do modal "Trocar Plano" na área do aluno.
 *   Quando o aluno clica em "Quero Este Plano", envia uma requisição
 *   JSON para a API PHP e atualiza a interface sem recarregar a página.
 *
 * DEPENDÊNCIAS (variáveis injetadas pelo PHP na página aluno.php):
 *   - BASE_URL_JS  : URL base do site (ex: "https://academia-prix.com.br")
 *   - CSRF_TOKEN_PLANO : Token CSRF para proteger a requisição
 *   - PLANO_ATUAL_ID   : ID do plano atual do aluno (para marcação visual)
 */

(function () {
    'use strict'; // Modo estrito: previne variáveis não declaradas e outros erros silenciosos

    // URL da API PHP que processa a troca de plano
    const API_URL = (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + '/api/atualizar_plano_aluno.php';

    // Token CSRF: necessário para a API aceitar a requisição.
    // let (em vez de const) porque será atualizado após cada troca bem-sucedida.
    let csrfToken = (typeof CSRF_TOKEN_PLANO !== 'undefined') ? CSRF_TOKEN_PLANO : '';

    // Elementos do DOM atualizados após a troca bem-sucedida
    const msgBox       = document.getElementById('msgTrocarPlano');    // Caixa de mensagem dentro do modal
    const nomeDisplay  = document.getElementById('planoAtualNome');    // Texto com nome do plano no card
    const precoDisplay = document.getElementById('planoAtualPreco');   // Texto com preço do plano no card

    // ── Event Delegation para botões "Quero Este Plano" ───────
    // Em vez de adicionar um listener em cada botão individualmente,
    // usamos event delegation: um único listener no document captura
    // todos os cliques e verifica se o alvo é um botão de seleção de plano.
    // Vantagem: funciona mesmo para botões adicionados dinamicamente ao DOM.
    document.addEventListener('click', function (e) {
        // closest() sobe na árvore DOM procurando o botão com a classe correta
        const btn = e.target.closest('.btn-selecionar-plano');
        if (!btn) return; // Clique em outro elemento — ignora

        // Lê os atributos data-* do botão (injetados pelo PHP ao renderizar o modal)
        const planoId    = parseInt(btn.dataset.planoId, 10);
        const planoNome  = btn.dataset.planoNome;
        const planoPreco = btn.dataset.planoPreco;

        if (!planoId) return; // ID inválido — ignora

        // Confirmação antes de trocar (UX: evita trocas acidentais)
        if (!confirm('Deseja trocar para o plano "' + planoNome + '" (' + planoPreco + ')?')) return;

        trocarPlano(btn, planoId, planoNome, planoPreco);
    });

    /**
     * Envia a requisição de troca de plano para a API e trata a resposta.
     *
     * @param {HTMLElement} btn       - Botão clicado (para feedback visual)
     * @param {number}      planoId   - ID do plano escolhido
     * @param {string}      planoNome - Nome do plano (para mensagens)
     * @param {string}      planoPreco - Preço formatado do plano (para confirmação)
     */
    async function trocarPlano(btn, planoId, planoNome, planoPreco) {
        // Desabilita TODOS os botões de plano durante a requisição
        // para evitar cliques duplos ou troca simultânea
        const todosBtns = document.querySelectorAll('.btn-selecionar-plano');
        todosBtns.forEach(function (b) { b.disabled = true; });
        btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Aguarde...';

        mostrarMsg('', ''); // Limpa mensagens anteriores

        try {
            // Fetch API: envia JSON via POST para a API PHP
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    plano_id:   planoId,
                    csrf_token: csrfToken, // Token CSRF enviado no corpo (não em cookie ou header separado)
                }),
                cache: 'no-store',
            });

            const dados = await response.json();

            if (response.ok && dados.sucesso) {
                // ── Sucesso: atualiza a interface ─────────────────────

                // Atualiza o token CSRF com o novo valor retornado pela API
                // (a API regenera o token após cada operação bem-sucedida)
                if (dados.novo_csrf_token) {
                    csrfToken = dados.novo_csrf_token;
                }

                // Atualiza o card de "Plano Atual" na página do aluno
                if (nomeDisplay)  nomeDisplay.textContent  = dados.plano_nome;
                if (precoDisplay) {
                    precoDisplay.textContent   = formatarPreco(dados.plano_preco);
                    precoDisplay.style.display = ''; // Garante que está visível
                }

                // Exibe mensagem de confirmação dentro do modal
                mostrarMsg('✓ Plano "' + dados.plano_nome + '" ativado com sucesso!', 'success');

                // Atualiza visualmente o grid de planos: marca novo como ativo
                atualizarGridPlanos(planoId, planoNome, planoPreco);

                // Fecha o modal automaticamente após 2 segundos
                setTimeout(function () {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalTrocarPlano'));
                    if (modal) modal.hide();
                    mostrarMsg('', ''); // Limpa a mensagem ao fechar
                }, 2000);

            } else {
                // ── Erro retornado pela API ────────────────────────────
                mostrarMsg(dados.erro || 'Erro ao trocar plano. Tente novamente.', 'error');
                reabilitarBotoes();
                btn.innerHTML = '<i class="bi bi-arrow-right-circle me-1"></i>Quero Este Plano';
            }
        } catch (err) {
            // ── Erro de rede (sem internet, timeout, etc.) ────────────
            mostrarMsg('Erro na conexão. Verifique sua internet e tente novamente.', 'error');
            reabilitarBotoes();
            btn.innerHTML = '<i class="bi bi-arrow-right-circle me-1"></i>Quero Este Plano';
        }
    }

    /**
     * Atualiza o grid de planos no modal após uma troca bem-sucedida.
     * Marca o plano recém-selecionado como "Plano Ativo" e reabilita os demais.
     *
     * @param {number} novoPlanoId - ID do plano agora ativo
     * @param {string} novoNome    - Nome do plano (não usado aqui, reservado para uso futuro)
     * @param {string} novoPreco   - Preço do plano (idem)
     */
    function atualizarGridPlanos(novoPlanoId, novoNome, novoPreco) {
        const todosBtns = document.querySelectorAll('.btn-selecionar-plano');
        todosBtns.forEach(function (b) {
            const id = parseInt(b.dataset.planoId, 10);
            if (id === novoPlanoId) {
                // Transforma o botão do plano escolhido em indicador "Plano Ativo"
                b.disabled    = true;
                b.textContent = '✓ Plano Ativo';
                b.className   = 'btn btn-outline-secondary w-100 mt-auto';
                b.classList.remove('btn-selecionar-plano'); // Remove da seleção futura
            } else {
                // Reabilita os demais planos para uma eventual nova troca
                b.disabled  = false;
                b.innerHTML = '<i class="bi bi-arrow-right-circle me-1"></i>Quero Este Plano';
            }
        });
    }

    /** Reabilita todos os botões de seleção de plano após um erro. */
    function reabilitarBotoes() {
        document.querySelectorAll('.btn-selecionar-plano').forEach(function (b) {
            b.disabled = false;
        });
    }

    /**
     * Exibe ou limpa a caixa de mensagem dentro do modal.
     *
     * @param {string} texto - Mensagem a exibir (string vazia para limpar)
     * @param {string} tipo  - 'success' (verde) ou 'error' (vermelho)
     */
    function mostrarMsg(texto, tipo) {
        if (!msgBox) return;
        if (!texto) { msgBox.innerHTML = ''; return; } // Limpa se sem texto
        const cls  = tipo === 'success' ? 'alert-prix-success' : 'alert-prix-error';
        const icon = tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle';
        msgBox.innerHTML = '<div class="' + cls + '"><i class="bi ' + icon + ' me-2"></i>' + texto + '</div>';
    }

    /**
     * Formata um número float para o padrão monetário brasileiro.
     * Exemplo: 150.5 → "R$ 150,50"
     *
     * @param {number} valor
     * @returns {string} Preço formatado
     */
    function formatarPreco(valor) {
        if (isNaN(valor)) return valor;
        return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
    }

})();
