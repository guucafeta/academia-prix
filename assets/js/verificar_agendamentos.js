/**
 * Sistema de Atualização em Tempo Real de Agendamentos
 * assets/js/verificar_agendamentos.js
 *
 * Lógica de remoção:
 *   A API só retorna agendamentos que NÃO são cancelados.
 *   Qualquer linha na tabela que não vier na resposta é removida
 *   imediatamente com fade-out — seja cancelamento feito pelo
 *   aluno, pelo admin ou por qualquer outra origem.
 */

(function () {
    'use strict';

    const CONFIG = {
        intervalo : 8000,
        url       : (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + '/api/verificar_agendamentos.php',
        urlCancelar: (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + '/api/cancelar_agendamento.php',
    };

    const tabela = document.getElementById('tabelaAgendamentos');
    if (!tabela) return;

    // ── Helpers ────────────────────────────────────────────────

    function badgeClass(status) {
        return { confirmado: 'bg-success', pendente: 'bg-warning text-dark' }[status] || 'bg-secondary';
    }

    function esc(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str != null ? String(str) : ''));
        return d.innerHTML;
    }

    function getCsrf() {
        const el = document.getElementById('csrf_token_agendamento');
        return el ? el.value : '';
    }

    function fade(linha, ms, cb) {
        linha.style.transition = `opacity ${ms}ms ease, transform ${ms}ms ease`;
        linha.style.opacity    = '0';
        linha.style.transform  = 'translateX(-8px)';
        setTimeout(cb, ms);
    }

    function pulsar(linha) {
        linha.style.transition = 'background-color 0s';
        linha.style.backgroundColor = 'rgba(255,107,0,0.18)';
        setTimeout(() => {
            linha.style.transition      = 'background-color 0.6s ease';
            linha.style.backgroundColor = 'transparent';
        }, 80);
    }

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

    function novaLinha(ag) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-ag-id', ag.id);
        tr.setAttribute('data-status', ag.status);
        const label = ag.status.charAt(0).toUpperCase() + ag.status.slice(1);
        const acoes = ag.status === 'pendente' ? botaoCancelar(ag.id) : '<span style="color:var(--prix-muted);font-size:.85rem;">-</span>';
        tr.innerHTML = `
            <td>${esc(ag.professor_nome)}</td>
            <td>${esc(ag.especialidade)}</td>
            <td>${esc(ag.data_formatada || ag.data)}</td>
            <td>${esc(ag.hora_formatada || ag.hora)}</td>
            <td><span class="badge ${badgeClass(ag.status)}" style="font-size:.78rem;">${label}</span></td>
            <td>${acoes}</td>`;
        return tr;
    }

    // ── Lógica principal ───────────────────────────────────────

    function atualizarTabela(agendamentos) {
        const tbody    = tabela.querySelector('tbody');
        const emptyBox = document.getElementById('tabelaAgendamentosVazia');
        if (!tbody) return;

        const idsApi = new Set(agendamentos.map(ag => String(ag.id)));

        // 1. Remover linhas ausentes na API (canceladas pelo admin ou pelo aluno)
        Array.from(tbody.querySelectorAll('tr[data-ag-id]')).forEach(linha => {
            if (!idsApi.has(linha.getAttribute('data-ag-id'))) {
                fade(linha, 350, () => {
                    linha.remove();
                    // Se ficou vazia, mostrar placeholder
                    if (!tbody.querySelector('tr[data-ag-id]') && emptyBox) {
                        emptyBox.style.display = 'block';
                        tabela.closest('.table-responsive').style.display = 'none';
                    }
                });
            }
        });

        // 2. Atualizar status de linhas que existem
        const idsNaTabela = new Set(
            Array.from(tbody.querySelectorAll('tr[data-ag-id]'))
                 .map(tr => tr.getAttribute('data-ag-id'))
        );

        agendamentos.forEach(ag => {
            const linha = tbody.querySelector(`tr[data-ag-id="${ag.id}"]`);
            if (!linha) {
                // Linha nova (agendamento feito em outra aba/dispositivo)
                if (!idsNaTabela.has(String(ag.id))) {
                    const tr = novaLinha(ag);
                    tr.style.opacity = '0';
                    tbody.insertBefore(tr, tbody.firstChild);
                    setTimeout(() => {
                        tr.style.transition = 'opacity 0.4s ease';
                        tr.style.opacity    = '1';
                    }, 30);
                    pulsar(tr);
                }
                return;
            }

            // Atualizar status se mudou
            const statusAtual = linha.getAttribute('data-status');
            if (statusAtual !== ag.status) {
                const badge = linha.querySelector('.badge');
                if (badge) {
                    badge.className   = `badge ${badgeClass(ag.status)}`;
                    badge.textContent = ag.status.charAt(0).toUpperCase() + ag.status.slice(1);
                    badge.style.fontSize = '.78rem';
                }
                // Atualizar coluna de ação (ex: confirmado → sem botão cancelar)
                const tdAcao = linha.querySelector('td:last-child');
                if (tdAcao) {
                    tdAcao.innerHTML = ag.status === 'pendente' ? botaoCancelar(ag.id) : '<span style="color:var(--prix-muted);font-size:.85rem;">-</span>';
                }
                linha.setAttribute('data-status', ag.status);
                pulsar(linha);
            }
        });
    }

    // ── Polling ────────────────────────────────────────────────

    async function verificar() {
        try {
            const res  = await fetch(CONFIG.url, { cache: 'no-store' });
            if (!res.ok) return;
            const json = await res.json();
            if (json.success && Array.isArray(json.data)) {
                atualizarTabela(json.data);
            }
        } catch (_) { /* silencioso */ }
    }

    verificar();
    setInterval(verificar, CONFIG.intervalo);

    // Verificar ao voltar à aba (mobile e desktop)
    document.addEventListener('visibilitychange', () => { if (!document.hidden) verificar(); });
    window.addEventListener('pageshow', e => { if (e.persisted) verificar(); });

    // Expor para o botão "Atualizar" do perfil_aluno.js
    window.verificarAgendamentosGlobal = verificar;

})();