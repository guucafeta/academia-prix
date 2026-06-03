-- ============================================================
-- Academia Prix Matriz — Schema do Banco de Dados
-- ============================================================
 
CREATE DATABASE IF NOT EXISTS academia_prix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE academia_prix;
 
-- ------------------------------------------------------------
-- Tabela: professores
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS professores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    especialidade VARCHAR(100) NOT NULL,
    foto VARCHAR(255) DEFAULT 'personais/default.jpg',
    bio TEXT,
    instagram VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
 
-- ------------------------------------------------------------
-- Tabela: alunos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    data_nascimento DATE,
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
 
-- ------------------------------------------------------------
-- Tabela: planos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    duracao_meses INT NOT NULL,
    destaque TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;
 
-- ------------------------------------------------------------
-- Tabela: modalidades
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS modalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL,
    descricao TEXT,
    horario VARCHAR(100),
    icone VARCHAR(50) DEFAULT 'bi-activity'
) ENGINE=InnoDB;
 
-- ------------------------------------------------------------
-- Tabela: agendamentos  (N:N entre alunos e professores)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    professor_id INT NOT NULL,
    data DATE NOT NULL,
    hora TIME NOT NULL,
    status ENUM('pendente','confirmado','cancelado') DEFAULT 'pendente',
    observacao TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professores(id) ON DELETE CASCADE
) ENGINE=InnoDB;
 
-- ------------------------------------------------------------
-- Tabela: aluno_plano  (N:N entre alunos e planos)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aluno_plano (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,  
    plano_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    status ENUM('ativo','expirado','cancelado') DEFAULT 'ativo',
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
 
-- ============================================================
-- DADOS DE EXEMPLO (ATUALIZADO)
-- ============================================================
 
-- Limpar dados antigos e inserir novos
TRUNCATE TABLE agendamentos;
TRUNCATE TABLE aluno_plano;
TRUNCATE TABLE professores;
 
-- Professores (DADOS ATUALIZADOS)
INSERT INTO professores (nome, especialidade, foto, bio, instagram) VALUES
('Val Andrade',      'Personal Trainer',    'fotodospersonais/personalval.jpg', 
 'Personal Trainer | Treinos online', 
 '@val_andrad'),
 
('Vinicius',         'Personal Trainer',    'fotodospersonais/personalvinicius.jpg', 
 'Saúde/Qualidade de vida/Treinamento', 
 '@personal.viniciussardi'),
 
('Carlos Lima',      'CrossFit',            'fotodospersonais/carloslima.jpg', 
 'Atleta e treinador de CrossFit, apaixonado por desafios e superação.', 
 '@carloslima.cf'),
 
('Daniela Santos',   'Pilates e Funcional', 'fotodospersonais/danielasantos.jpg', 
 'Especialista em reabilitação e treino funcional para todos os níveis.', 
 '@dani.santos.fit');   
 
-- Planos
INSERT INTO planos (nome, descricao, preco, duracao_meses, destaque) VALUES
('Mensal',      'Acesso ilimitado por 1 mês. Ideal para quem quer experimentar.', 89.90, 1, 0),
('Trimestral',  'Acesso por 3 meses com desconto especial. O mais escolhido!',   229.90, 3, 1),
('Semestral',   'Comprometimento de 6 meses para resultados reais.',             399.90, 6, 0),
('Anual',       'Melhor custo-benefício. 12 meses de treino com economia máxima.', 699.90, 12, 0),
('Personal',    'Treino acompanhado 1:1 com professor. Sessão avulsa.',           80.00, 0, 0);
 
-- Modalidades
INSERT INTO modalidades (nome, descricao, horario, icone) VALUES
('Musculação',         'Sala equipada com aparelhos modernos para hipertrofia e condicionamento.', 'Seg a Sex: 6h–22h | Sáb: 8h–16h', 'bi-lightning-charge-fill'),
('CrossFit',           'Treinos funcionais de alta intensidade em grupo. Superação diária.', 'Seg/Qua/Sex: 7h, 12h, 19h', 'bi-person-arms-up'),
('Pilates',            'Fortalecimento do core e reabilitação com supervisão especializada.', 'Ter/Qui: 8h, 10h, 18h, 20h', 'bi-heart-pulse-fill'),
('Spinning',           'Aula de bike indoor com música e intensidade progressiva.', 'Seg/Qua/Sex: 6h, 18h30', 'bi-bicycle'),
('Treinamento Funcional', 'Movimentos naturais para melhorar força, equilíbrio e flexibilidade.', 'Ter/Qui/Sáb: 9h, 17h', 'bi-trophy-fill'),
('Aula em Grupo',      'Zumba, aeróbica e muito mais. Movimento com alegria e disposição.', 'Sáb: 9h, Dom: 10h', 'bi-music-note-beamed');
 
