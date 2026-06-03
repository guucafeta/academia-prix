# 📊 DIAGRAMA DE ENTIDADE-RELACIONAMENTO (DER)
# Academia Prix Matriz

## Estrutura do Banco de Dados

```
┌─────────────────────────┐
│     PROFESSORES         │
├─────────────────────────┤
│ id (PK)                 │
│ nome                    │
│ especialidade           │
│ foto                    │
│ bio                     │
│ instagram               │
│ ativo                   │
│ criado_em               │
└────────┬────────────────┘
         │
         │ 1:N
         │
    ┌────▼────────────────────────┐
    │    AGENDAMENTOS             │
    ├─────────────────────────────┤
    │ id (PK)                     │
    │ aluno_id (FK)               │
    │ professor_id (FK) ◄─────────┤
    │ data                        │
    │ hora                        │
    │ status                      │
    │ observacao                  │
    │ criado_em                   │
    └────▲────────────────────────┘
         │
         │ 1:N
         │
┌────────┴───────────────┐
│      ALUNOS            │
├────────────────────────┤
│ id (PK)                │
│ nome                   │
│ email (UNIQUE)         │
│ senha                  │
│ telefone               │
│ data_nascimento        │
│ ativo                  │
│ criado_em              │
└────────┬───────────────┘
         │
         │ 1:N
         │
    ┌────▼────────────────┐
    │  ALUNO_PLANO        │
    ├─────────────────────┤
    │ id (PK)             │
    │ aluno_id (FK) ◄─────┤
    │ plano_id (FK)       │
    │ data_inicio         │
    │ data_fim            │
    │ status              │
    └────┬────────────────┘
         │
         │ 1:N
         │
    ┌────▼───────────────┐
    │     PLANOS         │
    ├────────────────────┤
    │ id (PK)            │
    │ nome               │
    │ descricao          │
    │ preco              │
    │ duracao_meses      │
    │ destaque           │
    │ ativo              │
    └────────────────────┘

┌──────────────────────────┐
│   MODALIDADES            │
├──────────────────────────┤
│ id (PK)                  │
│ nome                     │
│ descricao                │
│ horario                  │
│ icone                    │
└──────────────────────────┘
(Sem relação direta - pode ser expandida futuramente)
```

---

## 📋 Descrição das Tabelas

### PROFESSORES
Armazena informações dos instrutores da academia.
- **id**: Identificador único
- **nome**: Nome completo do professor
- **especialidade**: Tipo de treino que ministra (Musculação, Personal, CrossFit, Pilates)
- **foto**: Caminho da foto do professor
- **bio**: Descrição profissional do professor
- **instagram**: Handle do Instagram
- **ativo**: Flag para desativar sem deletar
- **criado_em**: Data de cadastro

### ALUNOS
Armazena dados dos clientes da academia.
- **id**: Identificador único
- **nome**: Nome completo do aluno
- **email**: Email único para login
- **senha**: Senha criptografada
- **telefone**: Contato do aluno
- **data_nascimento**: Para validações e relatórios
- **ativo**: Flag para desativar sem deletar
- **criado_em**: Data de cadastro

### PLANOS
Pacotes de assinatura disponíveis.
- **id**: Identificador único
- **nome**: Nome do plano (Mensal, Trimestral, etc)
- **descricao**: Detalhes do plano
- **preco**: Valor em reais
- **duracao_meses**: Duração da assinatura
- **destaque**: Flag para plano mais popular
- **ativo**: Flag para desativar plano

### MODALIDADES
Tipos de treino/aulas oferecidas.
- **id**: Identificador único
- **nome**: Nome da modalidade (Musculação, CrossFit, etc)
- **descricao**: O que é essa modalidade
- **horario**: Quando funciona
- **icone**: Ícone Bootstrap para exibição

### AGENDAMENTOS (Tabela de Relacionamento N:N)
Vincula alunos aos professores para aulas.
- **id**: Identificador único
- **aluno_id**: FK → ALUNOS (ON DELETE CASCADE)
- **professor_id**: FK → PROFESSORES (ON DELETE CASCADE)
- **data**: Data da aula
- **hora**: Horário da aula
- **status**: pendente, confirmado, cancelado
- **observacao**: Notas adicionais
- **criado_em**: Data de criação

### ALUNO_PLANO (Tabela de Relacionamento N:N)
Vincula alunos aos planos contratados.
- **id**: Identificador único
- **aluno_id**: FK → ALUNOS (ON DELETE CASCADE)
- **plano_id**: FK → PLANOS (ON DELETE CASCADE)
- **data_inicio**: Quando começou a vigência
- **data_fim**: Quando termina a vigência
- **status**: ativo, expirado, cancelado

---

## 🔗 Relacionamentos

### 1. PROFESSORES → AGENDAMENTOS (1:N)
Um professor pode ter múltiplos agendamentos.
- **Tipo**: Um para Muitos
- **Integridade**: ON DELETE CASCADE (se deletar professor, deleta agendamentos)

### 2. ALUNOS → AGENDAMENTOS (1:N)
Um aluno pode agendar com múltiplos professores.
- **Tipo**: Um para Muitos
- **Integridade**: ON DELETE CASCADE

### 3. ALUNOS → ALUNO_PLANO (1:N)
Um aluno pode contratar múltiplos planos (sequencialmente).
- **Tipo**: Um para Muitos
- **Integridade**: ON DELETE CASCADE

### 4. PLANOS → ALUNO_PLANO (1:N)
Um plano pode ser contratado por múltiplos alunos.
- **Tipo**: Um para Muitos
- **Integridade**: ON DELETE CASCADE

---

## 🎯 Forma Normal

Este banco segue a **3ª Forma Normal (3NF)**:

- ✅ **1NF**: Todos os atributos são atômicos (não repetem grupos)
- ✅ **2NF**: Não há dependências parciais de chave primária
- ✅ **3NF**: Não há dependências transitivas de chave primária

---

## 📊 Índices Recomendados

```sql
-- Email (login rápido)
CREATE INDEX idx_alunos_email ON alunos(email);

-- Buscas por nome
CREATE INDEX idx_professores_nome ON professores(nome);
CREATE INDEX idx_alunos_nome ON alunos(nome);

-- Ordenação
CREATE INDEX idx_agendamentos_data ON agendamentos(data);
CREATE INDEX idx_aluno_plano_status ON aluno_plano(status);
```

---

## 🔒 Constraints e Validações

### PROFESSORES
- `nome`: NOT NULL, VARCHAR(100)
- `email`: VARCHAR(150), UNIQUE
- `especialidade`: NOT NULL, VARCHAR(100)

### ALUNOS
- `nome`: NOT NULL, VARCHAR(100)
- `email`: NOT NULL, VARCHAR(150), UNIQUE
- `senha`: NOT NULL, VARCHAR(255) (deve ser hash)

### PLANOS
- `preco`: DECIMAL(10,2), NOT NULL
- `duracao_meses`: INT, NOT NULL, >= 0

### AGENDAMENTOS
- `status`: ENUM('pendente','confirmado','cancelado')
- `data`: DATE, >= HOJE
- `hora`: TIME, respeitando horários de funcionamento

---

## 💾 Tamanho Estimado

- **PROFESSORES**: ~100 KB (até 1000 professores)
- **ALUNOS**: ~500 KB (até 5000 alunos)
- **AGENDAMENTOS**: ~1 MB (até 50000 agendamentos)
- **ALUNO_PLANO**: ~200 KB (até 10000 contratos)
- **PLANOS**: ~5 KB (alguns planos)
- **MODALIDADES**: ~10 KB (6-10 modalidades)

**Total estimado: ~2.8 MB** (bem dentro do normal para uma academia)

---

## 🚀 Possíveis Expansões Futuras

1. **Tabela AULAS**: Aulas programadas (segunda-feira 7h, etc)
2. **Tabela PAGAMENTOS**: Histórico de pagamentos dos alunos
3. **Tabela FREQUENCIA**: Registro de presença
4. **Tabela PROFESSORES_MODALIDADES**: Relação M:N (professor pode dar várias modalidades)
5. **Tabela CERTIFICADOS**: Certificados conquistados pelo aluno

