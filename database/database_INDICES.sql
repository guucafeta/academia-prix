-- ============================================================
-- ÍNDICES RECOMENDADOS — Academia Prix
-- ============================================================
-- Execute este script no PHPMyAdmin para melhorar performance
-- ============================================================

USE academia_prix;

-- ============================================================
-- ÍNDICES PARA ALUNOS
-- ============================================================

-- Índice na coluna email (usado em login)
ALTER TABLE alunos ADD INDEX idx_alunos_email (email);

-- Índice no nome (para buscas)
ALTER TABLE alunos ADD INDEX idx_alunos_nome (nome);

-- Índice na coluna ativo (filtros)
ALTER TABLE alunos ADD INDEX idx_alunos_ativo (ativo);

-- Índice combinado (email + ativo)
ALTER TABLE alunos ADD INDEX idx_alunos_email_ativo (email, ativo);

-- ============================================================
-- ÍNDICES PARA PROFESSORES
-- ============================================================

-- Índice no nome
ALTER TABLE professores ADD INDEX idx_professores_nome (nome);

-- Índice na especialidade (filtros)
ALTER TABLE professores ADD INDEX idx_professores_especialidade (especialidade);

-- Índice no ativo
ALTER TABLE professores ADD INDEX idx_professores_ativo (ativo);

-- Índice combinado
ALTER TABLE professores ADD INDEX idx_professores_especialidade_ativo (especialidade, ativo);

-- ============================================================
-- ÍNDICES PARA AGENDAMENTOS
-- ============================================================

-- Índice na coluna data (ordenação e filtros)
ALTER TABLE agendamentos ADD INDEX idx_agendamentos_data (data);

-- Índice na coluna status
ALTER TABLE agendamentos ADD INDEX idx_agendamentos_status (status);

-- Índice nas chaves estrangeiras (melhor performance em JOINs)
ALTER TABLE agendamentos ADD INDEX idx_agendamentos_aluno_id (aluno_id);
ALTER TABLE agendamentos ADD INDEX idx_agendamentos_professor_id (professor_id);

-- Índice combinado (data + status)
ALTER TABLE agendamentos ADD INDEX idx_agendamentos_data_status (data, status);

-- Índice combinado (aluno + professor)
ALTER TABLE agendamentos ADD INDEX idx_agendamentos_aluno_professor (aluno_id, professor_id);

-- ============================================================
-- ÍNDICES PARA ALUNO_PLANO
-- ============================================================

-- Índices nas chaves estrangeiras
ALTER TABLE aluno_plano ADD INDEX idx_aluno_plano_aluno_id (aluno_id);
ALTER TABLE aluno_plano ADD INDEX idx_aluno_plano_plano_id (plano_id);

-- Índice no status
ALTER TABLE aluno_plano ADD INDEX idx_aluno_plano_status (status);

-- Índice na data de início (relatórios)
ALTER TABLE aluno_plano ADD INDEX idx_aluno_plano_data_inicio (data_inicio);

-- Índice combinado
ALTER TABLE aluno_plano ADD INDEX idx_aluno_plano_aluno_status (aluno_id, status);

-- ============================================================
-- ÍNDICES PARA PLANOS
-- ============================================================

-- Índice no ativo (filtros)
ALTER TABLE planos ADD INDEX idx_planos_ativo (ativo);

-- Índice no destaque (mostrar populares)
ALTER TABLE planos ADD INDEX idx_planos_destaque (destaque);

-- ============================================================
-- ÍNDICES PARA MODALIDADES
-- ============================================================

-- Índice no nome (buscas)
ALTER TABLE modalidades ADD INDEX idx_modalidades_nome (nome);

-- ============================================================
-- VERIFICAR ÍNDICES CRIADOS
-- ============================================================

-- Listar todos os índices
SHOW INDEXES FROM alunos;
SHOW INDEXES FROM professores;
SHOW INDEXES FROM agendamentos;
SHOW INDEXES FROM aluno_plano;
SHOW INDEXES FROM planos;
SHOW INDEXES FROM modalidades;

-- ============================================================
-- OTIMIZAÇÕES ADICIONAIS
-- ============================================================

-- Analisar tabelas (atualiza estatísticas)
ANALYZE TABLE alunos;
ANALYZE TABLE professores;
ANALYZE TABLE agendamentos;
ANALYZE TABLE aluno_plano;
ANALYZE TABLE planos;
ANALYZE TABLE modalidades;

-- Otimizar tabelas (desfragmentar)
OPTIMIZE TABLE alunos;
OPTIMIZE TABLE professores;
OPTIMIZE TABLE agendamentos;
OPTIMIZE TABLE aluno_plano;
OPTIMIZE TABLE planos;
OPTIMIZE TABLE modalidades;

-- ============================================================
-- CONFIGURAÇÕES DE PERFORMANCE
-- ============================================================

-- Aumentar tamanho máximo de JOIN
SET SESSION max_join_size = 18446744073709551615;

-- Aumentar tempo de execução
SET SESSION wait_timeout = 28800;

-- ============================================================
-- ESTATÍSTICAS DE PERFORMANCE
-- ============================================================

-- Ver tamanho das tabelas
SELECT 
    TABLE_NAME,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)'
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'academia_prix'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- Ver número de linhas
SELECT 
    TABLE_NAME,
    TABLE_ROWS AS 'Linhas'
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'academia_prix';

-- ============================================================
-- QUERIES OTIMIZADAS PARA USAR OS ÍNDICES
-- ============================================================

-- Buscar aluno por email (usa índice)
SELECT * FROM alunos WHERE email = 'joao@academia.com';

-- Listar professores por especialidade (usa índice)
SELECT * FROM professores WHERE especialidade = 'Personal Trainer' ORDER BY nome;

-- Agendamentos do dia (usa índice)
SELECT * FROM agendamentos WHERE DATE(data) = CURDATE() AND status = 'confirmado';

-- Planos ativos e destaques (usa índice)
SELECT * FROM planos WHERE ativo = 1 AND destaque = 1;

-- Alunos com planos ativos (usa múltiplos índices)
SELECT a.* FROM alunos a
INNER JOIN aluno_plano ap ON a.id = ap.aluno_id
WHERE ap.status = 'ativo' AND ap.data_fim >= CURDATE();

-- ============================================================
-- LIMPEZA (Se precisar remover índices)
-- ============================================================

-- Remover índices específicos (use com cuidado!)
-- ALTER TABLE alunos DROP INDEX idx_alunos_email;
-- ALTER TABLE agendamentos DROP INDEX idx_agendamentos_data;

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================

-- Verificar saúde do banco
CHECK TABLE alunos, professores, agendamentos, aluno_plano, planos, modalidades;

-- Se houver erros, executar REPAIR
-- REPAIR TABLE alunos;
-- REPAIR TABLE professores;

-- ============================================================
