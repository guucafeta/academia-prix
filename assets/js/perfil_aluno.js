/**
 * assets/js/perfil_aluno.js
 * Gerenciador de Perfil do Aluno + Botão Atualizar Agendamentos
 *
 * RESPONSABILIDADE DESTE ARQUIVO:
 *   1. Gerencia o formulário de edição de perfil (nome, e-mail, telefone)
 *      enviando os dados via fetch (JSON) para a API PHP e atualizando
 *      a interface sem recarregar a página.
 *   2. Controla o botão "Atualizar" da tabela de agendamentos,
 *      acionando uma verificação manual no verificar_agendamentos.js.
 *
 * DEPENDÊNCIA:
 *   - BASE_URL_JS: variável injetada pelo PHP com a URL base do site
 *   - window.verificarAgendamentosGlobal: exposta por verificar_agendamentos.js
 */

(function () {
    'use strict'; // Modo estrito ativado

    // URL da API que recebe as atualizações de perfil
    const API_URL = (typeof BASE_URL_JS !== 'undefined' ? BASE_URL_JS : '') + '/api/atualizar_perfil_aluno.php';

    // ── Captura dos elementos do formulário de perfil ─────────
    const btnSalvar     = document.getElementById('btnSalvarPerfil');
    const inputNome     = document.getElementById('inputNomeAluno');
    const inputEmail    = document.getElementById('inputEmailAluno');
    const inputTelefone = document.getElementById('inputTelefoneAluno');
    const msgPerfil     = document.getElementById('msgPerfilAluno');     // Caixa de mensagem de feedback
    const avatarGrande  = document.getElementById('avatarInicial');      // Avatar no card de perfil
    const avatarHero    = document.getElementById('avatarInicialHero');  // Avatar no cabeçalho da seção
    const nomeHero      = document.getElementById('nomeAlunoHero');      // Nome exibido no cabeçalho

    // Se o botão Salvar não existir na página, encerra o script
    // (script incluído globalmente mas só ativo em páginas com o formulário)
    if (!btnSalvar) return;

    // ── Listeners de eventos ──────────────────────────────────

    // Clique no botão "Salvar Alterações"
    btnSalvar.addEventListener('click', salvarPerfil);

    // Suporte a Enter nos campos de texto: pressionar Enter salva sem precisar clicar
    [inputNome, inputEmail, inputTelefone].forEach(function (el) {
        if (el) el.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') salvarPerfil();
        });
    });

    /**
     * Valida os dados do formulário e envia para a API via fetch.
     * Atualiza os campos e o avatar na página após resposta de sucesso.
     */
    async function salvarPerfil() {
        // Lê os valores dos campos (trim() remove espaços desnecessários)
        const nome     = inputNome     ? inputNome.value.trim()     : '';
        const email    = inputEmail    ? inputEmail.value.trim()    : '';
        const telefone = inputTelefone ? inputTelefone.value.trim() : '';

        // Validação mínima no front-end (a API valida novamente no back-end)
        if (!nome || nome.length < 3) {
            mostrarMsg('Nome deve ter pelo menos 3 caracteres', 'error');
            if (inputNome) inputNome.focus(); // Foca no campo com erro
            return;
        }

        // Feedback visual: desabilita botão e mostra "Salvando..."
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Salvando...';

        try {
            // Envia os dados como JSON via POST para a API PHP
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nome: nome, email: email, telefone: telefone }),
                cache: 'no-store',
            });

            const dados = await response.json();

            if (response.ok && dados.sucesso) {
                // ── Sucesso: atualiza a interface ─────────────────────
                mostrarMsg('✓ Perfil salvo com sucesso!', 'success');

                // Sincroniza os campos do formulário com os dados confirmados pelo servidor
                if (inputNome)     inputNome.value     = dados.nome;
                if (inputEmail)    inputEmail.value    = dados.email;
                if (inputTelefone) inputTelefone.value = dados.telefone;

                // Atualiza o avatar com a inicial do novo nome
                // dados.inicial = primeira letra do nome em maiúsculo (calculado pela API)
                if (avatarGrande) avatarGrande.textContent = dados.inicial;
                if (avatarHero)   avatarHero.textContent   = dados.inicial;

                // Atualiza o nome exibido no cabeçalho da seção
                if (nomeHero) nomeHero.textContent = dados.nome.toUpperCase();

            } else {
                // ── Erro retornado pela API ────────────────────────────
                mostrarMsg(dados.erro || 'Erro ao salvar. Tente novamente.', 'error');
            }

        } catch (e) {
            // ── Erro de rede ───────────────────────────────────────────
            mostrarMsg('Erro na conexão. Tente novamente.', 'error');
        } finally {
            // finally: executado sempre (sucesso ou erro)
            // Restaura o botão para o estado original
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="bi bi-floppy me-1"></i>Salvar Alterações';
        }
    }

    /**
     * Exibe ou limpa a caixa de feedback do formulário de perfil.
     * Mensagens de sucesso desaparecem automaticamente após 4 segundos.
     *
     * @param {string} texto - Mensagem a exibir (string vazia para limpar)
     * @param {string} tipo  - 'success' (verde) ou 'error' (vermelho)
     */
    function mostrarMsg(texto, tipo) {
        if (!msgPerfil) return;
        const cls  = tipo === 'success' ? 'alert-prix-success' : 'alert-prix-error';
        const icon = tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle';
        msgPerfil.innerHTML = `<div class="${cls}"><i class="bi ${icon} me-2"></i>${texto}</div>`;
        // Auto-hide para mensagens de sucesso: some após 4 segundos
        if (tipo === 'success') setTimeout(() => { msgPerfil.innerHTML = ''; }, 4000);
    }

    // ── Botão "Atualizar" da tabela de agendamentos ───────────

    const btnAtualizar = document.getElementById('btnAtualizarAgendamentos');
    if (btnAtualizar) {
        btnAtualizar.addEventListener('click', function () {
            // Feedback visual durante a atualização
            btnAtualizar.disabled = true;
            btnAtualizar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Atualizando...';

            // Usa a função global exposta por verificar_agendamentos.js (se disponível)
            // Fallback: recarrega a página inteira se o script não estiver carregado
            if (window.verificarAgendamentosGlobal) {
                window.verificarAgendamentosGlobal(); // Dispara uma verificação manual imediata
            } else {
                location.reload(); // Fallback: reload completo da página
            }

            // Restaura o botão após 1.2 segundos (tempo suficiente para a requisição completar)
            setTimeout(() => {
                btnAtualizar.disabled = false;
                btnAtualizar.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Atualizar';
            }, 1200);
        });
    }

})();
