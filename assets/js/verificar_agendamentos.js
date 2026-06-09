/**
 * assets/js/verificar_agendamentos.js
 * Sistema de Atualização em Tempo Real da Tabela de Agendamentos
 *
 * COMO FUNCIONA (Polling):
 *   A cada 8 segundos, este script consulta a API PHP (/api/verificar_agendamentos.php).
 *   A API retorna apenas agendamentos NÃO cancelados.
 *   O script então:
 *     1. Remove linhas da tabela que não estão mais na resposta (cancelados)
 *     2. Atualiza o badge de status se ele mudou (ex: pendente → confirmado)
 *     3. Adiciona novas linhas se um agendamento foi criado em outro dispositivo
 *
 * POR QUE POLLING E NÃO WEBSOCKET?
 *   Polling (consulta periódica) é mais simples de implementar e suficiente
 *   para esta aplicação. WebSockets seriam necessários apenas para atualização
 *   verdadeiramente instantânea (chats, jogos em tempo real, etc.).
 *
 * IIFE (Immediately Invoked Function Expression):
 *   O código inteiro fica dentro de (function(){ ... })() para não
 *   poluir o escopo global — nenhuma variável interna vaza para window.
 */

(function () {
    'use strict'; // Modo estrito: previne erros silenciosos e más práticas JS

    // ── Configurações centralizadas ───────────────────────────
    // Alterar o intervalo ou a URL aqui reflete em todo o script.
    const CONFIG = {
        intervalo  : 8000, // Intervalo de polling: 8000ms = 8 segundos
        // BASE_URL_JS é injetado pelo PHP na página (evita hardcode de URL)
        url        : (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + '/api/verificar_agendamentos.php',
        urlCancelar: (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + '/api/cancelar_agendamento.php',
    };

    // Se a tabela de agendamentos não existir na página, encerra imediatamente
    // (script incluído globalmente mas só ativo na página certa)
    const tabela = document.getElementById('tabelaAgendamentos');
    if (!tabela) return;

    // ── Funções auxiliares ────────────────────────────────────

    /**
     * Retorna a classe CSS Bootstrap para o badge de status.
     * @param {string} status - 'confirmado', 'pendente', ou outro
     * @returns {string} Classe CSS
     */
    function badgeClass(status) {
        return { confirmado: 'bg-success', pendente: 'bg-warning text-dark' }[status] || 'bg-secondary';
    }

    /**
     * Sanitiza texto para inserção segura no DOM via textContent.
     * Cria um nó de texto temporário que converte < > & em entidades HTML.
     * Previne XSS ao inserir dados vindos da API no HTML.
     * @param {*} str - Valor a ser escapado
     * @returns {string} HTML seguro
     */
    function esc(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str != null ? String(str) : ''));
        return d.innerHTML;
    }

    /**
     * Lê o CSRF token do campo hidden na página.
     * O token é necessário para validar o formulário de cancelamento.
     * @returns {string} Valor do token CSRF
     */
    function getCsrf() {
        const el = document.getElementById('csrf_token_agendamento');
        return el ? el.value : '';
    }

    /**
     * Aplica animação de fade-out e slide em uma linha da tabela.
     * @param {HTMLElement} linha - Elemento <tr> a ser animado
     * @param {number}      ms    - Duração da animação em ms
     * @param {Function}    cb    - Callback executado após a animação
     */
    function fade(linha, ms, cb) {
        linha.style.transition = `opacity ${ms}ms ease, transform ${ms}ms ease`;
        linha.style.opacity    = '0';
        linha.style.transform  = 'translateX(-8px)'; // Desloca levemente para a esquerda
        setTimeout(cb, ms); // Chama o callback após a duração da animação
    }

    /**
     * Aplica um flash laranja na linha para indicar atualização.
     * Útil para chamar atenção quando o status muda (ex: pendente → confirmado).
     * @param {HTMLElement} linha - Elemento <tr> a ser destacado
     */
    function pulsar(linha) {
        linha.style.transition      = 'background-color 0s'; // Troca imediata para laranja
        linha.style.backgroundColor = 'rgba(255,107,0,0.18)';
        setTimeout(() => {
            linha.style.transition      = 'background-color 0.6s ease'; // Volta suavemente
            linha.style.backgroundColor = 'transparent';
        }, 80);
    }

    /**
     * Gera o HTML do formulário de cancelamento de um agendamento.
     * O formulário usa POST (mais seguro que GET para ações destrutivas)
     * e pede confirmação antes de enviar via onsubmit confirm().
     * @param {number|string} agId - ID do agendamento
     * @returns {string} HTML do formulário
     */
    function botaoCancelar(agId) {
        return `
            <form method="POST" action="${esc(CONFIG.urlCancelar)}" style="margin:0;"
                  onsubmit="return confirm('Deseja cancelar este agendamento?');">
                <input type="hidden" name="agendamento_id" value="${esc(agId)}">
                <input type="hidden" name="csrf_token" value="${esc(getCsrf())}">
                <button type="submit" class="btn btn-sm btn-danger"
                        style="padding:2px 8px;font-size:.75rem;">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
            </form>`;
    }

    /**
     * Cria um elemento <tr> completo para um agendamento.
     * Apenas agendamentos pendentes exibem o botão de cancelar.
     * @param {Object} ag - Dados do agendamento vindos da API
     * @returns {HTMLElement} Elemento <tr> pronto para inserir no DOM
     */
    function novaLinha(ag) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-ag-id', ag.id);       // Identifica a linha pelo ID do agendamento
        tr.setAttribute('data-status', ag.status);  // Armazena o status atual para comparação futura
        const label = ag.status.charAt(0).toUpperCase() + ag.status.slice(1); // Capitaliza: "pendente" → "Pendente"
        const acoes = ag.status === 'pendente'
            ? botaoCancelar(ag.id)
            : '<span style="color:var(--prix-muted);font-size:.85rem;">-</span>'; // Confirmados não podem ser cancelados
        tr.innerHTML = `
            <td>${esc(ag.professor_nome)}</td>
            <td>${esc(ag.especialidade)}</td>
            <td>${esc(ag.data_formatada || ag.data)}</td>
            <td>${esc(ag.hora_formatada || ag.hora)}</td>
            <td><span class="badge ${badgeClass(ag.status)}" style="font-size:.78rem;">${label}</span></td>
            <td>${acoes}</td>`;
        return tr;
    }

    // ── Lógica principal de sincronização ─────────────────────

    /**
     * Sincroniza a tabela HTML com os dados retornados pela API.
     *
     * ESTRATÉGIA:
     *   A API é a "fonte da verdade". A tabela deve refletir exatamente
     *   o que a API retornou. Fazemos isso em três passos:
     *   1. Remover linhas que não estão mais na API (canceladas)
     *   2. Atualizar status de linhas que mudaram
     *   3. Adicionar novas linhas que ainda não estão na tabela
     *
     * @param {Array} agendamentos - Lista de agendamentos retornada pela API
     */
    function atualizarTabela(agendamentos) {
        const tbody    = tabela.querySelector('tbody');
        const emptyBox = document.getElementById('tabelaAgendamentosVazia'); // Placeholder "sem agendamentos"
        if (!tbody) return;

        // Cria um Set com os IDs retornados pela API para busca O(1)
        const idsApi = new Set(agendamentos.map(ag => String(ag.id)));

        // PASSO 1: Remover linhas ausentes (agendamentos cancelados ou removidos)
        Array.from(tbody.querySelectorAll('tr[data-ag-id]')).forEach(linha => {
            if (!idsApi.has(linha.getAttribute('data-ag-id'))) {
                fade(linha, 350, () => {
                    linha.remove();
                    // Se a tabela ficou vazia, mostra o estado vazio
                    if (!tbody.querySelector('tr[data-ag-id]') && emptyBox) {
                        emptyBox.style.display = 'block';
                        tabela.closest('.table-responsive').style.display = 'none';
                    }
                });
            }
        });

        // PASSO 2 e 3: Atualizar existentes e inserir novos
        const idsNaTabela = new Set(
            Array.from(tbody.querySelectorAll('tr[data-ag-id]'))
                 .map(tr => tr.getAttribute('data-ag-id'))
        );

        agendamentos.forEach(ag => {
            const linha = tbody.querySelector(`tr[data-ag-id="${ag.id}"]`);

            if (!linha) {
                // PASSO 3: Agendamento novo (feito em outra aba/dispositivo) — inserir
                if (!idsNaTabela.has(String(ag.id))) {
                    const tr = novaLinha(ag);
                    tr.style.opacity = '0'; // Começa invisível para o fade-in
                    tbody.insertBefore(tr, tbody.firstChild); // Insere no topo
                    setTimeout(() => {
                        tr.style.transition = 'opacity 0.4s ease';
                        tr.style.opacity    = '1'; // Fade-in suave
                    }, 30);
                    pulsar(tr); // Flash laranja para chamar atenção
                }
                return;
            }

            // PASSO 2: Verificar se o status mudou (ex: admin confirmou o agendamento)
            const statusAtual = linha.getAttribute('data-status');
            if (statusAtual !== ag.status) {
                // Atualiza o badge de cor e texto
                const badge = linha.querySelector('.badge');
                if (badge) {
                    badge.className   = `badge ${badgeClass(ag.status)}`;
                    badge.textContent = ag.status.charAt(0).toUpperCase() + ag.status.slice(1);
                    badge.style.fontSize = '.78rem';
                }
                // Atualiza a coluna de ações (confirmado → remove botão cancelar)
                const tdAcao = linha.querySelector('td:last-child');
                if (tdAcao) {
                    tdAcao.innerHTML = ag.status === 'pendente'
                        ? botaoCancelar(ag.id)
                        : '<span style="color:var(--prix-muted);font-size:.85rem;">-</span>';
                }
                linha.setAttribute('data-status', ag.status);
                pulsar(linha); // Flash visual para indicar mudança
            }
        });
    }

    // ── Polling assíncrono ────────────────────────────────────

    /**
     * Consulta a API e atualiza a tabela.
     * async/await torna o código assíncrono legível como síncrono.
     * Erros de rede são capturados silenciosamente para não quebrar a UX.
     */
    async function verificar() {
        try {
            // cache: 'no-store' impede que o navegador use uma resposta em cache
            const res  = await fetch(CONFIG.url, { cache: 'no-store' });
            if (!res.ok) return; // Ignora erros HTTP (401, 500, etc.) silenciosamente
            const json = await res.json();
            if (json.success && Array.isArray(json.data)) {
                atualizarTabela(json.data);
            }
        } catch (_) {
            // Erro de rede (offline, CORS, etc.) — ignora silenciosamente
        }
    }

    // ── Inicialização ─────────────────────────────────────────

    verificar(); // Primeira verificação imediata ao carregar a página

    // Polling: repete a verificação a cada 8 segundos
    setInterval(verificar, CONFIG.intervalo);

    // Verifica quando o usuário volta para a aba (após ficar em outra aba)
    // document.hidden é true quando a aba está em background
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) verificar();
    });

    // Verifica quando a página é restaurada do cache do navegador (mobile back/forward)
    // e.persisted = true indica que veio do cache (bfcache), não de uma nova carga
    window.addEventListener('pageshow', e => {
        if (e.persisted) verificar();
    });

    // Expõe a função verificar() globalmente para que perfil_aluno.js
    // possa disparar uma atualização manual pelo botão "Atualizar"
    window.verificarAgendamentosGlobal = verificar;

})();
