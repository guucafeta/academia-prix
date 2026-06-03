# 🏋️ Academia Prix Matriz
## Sistema de Gerenciamento de Academia Online

![Status](https://img.shields.io/badge/Status-Ativo-green)
![Versão](https://img.shields.io/badge/Versão-1.0.0-blue)
![Licença](https://img.shields.io/badge/Licença-MIT-orange)

---

## 📋 Índice
1. [Sobre o Projeto](#sobre)
2. [Funcionalidades](#funcionalidades)
3. [Tecnologias](#tecnologias)
4. [Requisitos](#requisitos)
5. [Instalação](#instalação)
6. [Configuração](#configuração)
7. [Uso](#uso)
8. [Arquitetura](#arquitetura)
9. [Documentação](#documentação)
10. [Contribuindo](#contribuindo)

---

## <a name="sobre"></a>📖 Sobre o Projeto

Academia Prix é um **sistema web de gerenciamento de academia** com foco em:
- 📅 Agendamento de aulas com professores
- 💰 Gerenciamento de planos de assinatura
- 👥 Cadastro e controle de alunos
- 🏃 Registro de modalidades e horários
- 🔐 Painel administrativo seguro

**Localização:** Av. Irmãos Pereira, 260 — Campo Mourão, Paraná  
**Contato:** (44) 9 9979-8559 | contato@prixacademia.com.br

---

## <a name="funcionalidades"></a>✨ Funcionalidades

### Para Usuários (Alunos)
- ✅ Consultar professores e especialidades
- ✅ Agendar aulas com professores específicos
- ✅ Visualizar planos de assinatura
- ✅ Ver modalidades e horários
- ✅ Contato direto via WhatsApp

### Para Administradores
- ✅ Login seguro com senha
- ✅ Gerenciar professores (CRUD)
- ✅ Gerenciar planos (CRUD)
- ✅ Visualizar agendamentos
- ✅ Editar horários de funcionamento

### Técnicas
- ✅ Banco de dados relacional (MySQL)
- ✅ Sistema de sessões seguro
- ✅ Validação de dados (frontend + backend)
- ✅ URLs amigáveis (sem .php)
- ✅ Design responsivo (mobile-first)
- ✅ SEO básico (meta tags)

---

## <a name="tecnologias"></a>🛠️ Tecnologias

### Frontend
- **HTML5** - Estrutura semântica
- **CSS3** - Estilos modernos (Flexbox, Grid)
- **Bootstrap 5** - Framework responsivo
- **Bootstrap Icons** - Ícones vetoriais

### Backend
- **PHP 7.4+** - Linguagem de servidor
- **MySQL 5.7+** - Banco de dados relacional
- **Apache 2.4+** - Servidor web

### Ferramentas
- **Git** - Controle de versão
- **Composer** (opcional) - Gerenciador de dependências
- **PHPMyAdmin** - Interface para banco de dados

---

## <a name="requisitos"></a>📋 Requisitos

### Mínimos
- PHP 7.4 ou superior
- MySQL 5.7 ou MariaDB 10.3+
- Apache 2.4 com mod_rewrite ativo
- 50 MB de espaço em disco

### Recomendados
- PHP 8.0+
- MySQL 8.0+
- Apache 2.4.40+
- 200 MB de espaço em disco
- SSD para melhor performance

### Navegadores Suportados
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## <a name="instalação"></a>🚀 Instalação

### Opção 1: XAMPP (Windows/macOS)

1. **Baixar XAMPP**
   ```
   https://www.apachefriends.org/
   ```

2. **Instalar** com componentes:
   - Apache
   - MySQL
   - PHP
   - PhpMyAdmin

3. **Copiar projeto**
   ```
   C:\xampp\htdocs\projetoacademia\
   ```

4. **Iniciar serviços**
   - Abrir XAMPP Control Panel
   - Start Apache
   - Start MySQL

### Opção 2: Linux (Ubuntu/Debian)

```bash
# Instalar dependências
sudo apt-get update
sudo apt-get install -y apache2 mysql-server php7.4 php7.4-mysql

# Copiar projeto
sudo cp -r projetoacademia /var/www/html/

# Habilitar mod_rewrite
sudo a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2
```

### Opção 3: Docker (Recomendado)

```bash
# Usar imagem Docker pré-configurada
docker pull php:7.4-apache
docker run -d -p 8080:80 -v $(pwd):/var/www/html php:7.4-apache
```

---

## <a name="configuração"></a>⚙️ Configuração

### 1. Configurar Banco de Dados

**Via PHPMyAdmin:**
```
1. Abra http://localhost/phpmyadmin
2. Crie banco: academia_prix
3. Importe: database/academia_prix.sql
```

**Via CLI:**
```bash
mysql -u root -p < database/academia_prix.sql
```

### 2. Configurar .env

```bash
# Copiar arquivo exemplo
cp .env.example .env

# Editar credenciais
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=academia_prix
DB_USER=root
DB_PASS=sua_senha
DB_CHARSET=utf8mb4

APP_ENV=development
APP_DEBUG=true

ADMIN_PASSWORD=admin123
```

### 3. Configurar Permissões

```bash
# Linux
sudo chown -R www-data:www-data /var/www/html/projetoacademia/
sudo chmod -R 755 /var/www/html/projetoacademia/
sudo chmod 644 /var/www/html/projetoacademia/.env
```

### 4. Verificar Instalação

```bash
# Testar banco
php -r "require 'config/db.php'; echo 'Conectado!';"

# Ou abrir no navegador
http://localhost:8080/projetoacademia/
```

---

## <a name="uso"></a>💻 Uso

### Acessar o Sistema

**Site Principal:**
```
http://localhost:8080/projetoacademia/
```

**Painel Admin:**
```
http://localhost:8080/projetoacademia/admin/login.php
Senha: admin123 (ou a do .env)
```

### Navegação

| Página | URL | Descrição |
|--------|-----|-----------|
| Home | `/` | Página inicial com seções |
| Professores | `/professores.php` | Listagem de instrutores |
| Planos | `/planos.php` | Pacotes de assinatura |
| Contato | `/contato.php` | Informações e horários |
| Admin | `/admin/login.php` | Painel de administração |

### Exemplo: Agendar Aula

1. Acesse a home: `http://localhost/projetoacademia/`
2. Role até "AGENDE SEU TREINO"
3. Escolha um professor
4. Selecione data e horário
5. Clique em "CONFIRMAR AGENDAMENTO"

---

## <a name="arquitetura"></a>🏗️ Arquitetura

### Estrutura de Diretórios

```
projetoacademia/
├── admin/                    # Painel administrativo
│   ├── index.php            # Dashboard
│   ├── login.php            # Autenticação
│   ├── professores.php      # CRUD professores
│   └── planos.php           # CRUD planos
│
├── assets/
│   └── css/
│       └── style.css        # Estilos principais (597 linhas)
│
├── config/
│   └── db.php              # Conexão com banco
│
├── database/
│   └── academia_prix.sql   # Schema e dados iniciais
│
├── includes/
│   ├── header.php          # Cabeçalho padrão
│   ├── footer.php          # Rodapé padrão
│   ├── functions.php       # Funções reutilizáveis (288 linhas)
│   └── constants.php       # Constantes da app
│
├── fotodospersonais/        # Fotos dos professores
│   ├── personalval.jpg
│   └── personalvinicius.jpg
│
├── videos/
│   ├── depoimentos/        # Vídeos de depoimentos
│   └── treinos/            # Vídeos de treinos
│
├── imagensprix/            # Imagens do site
│   ├── modalidades-abertura.jpg
│   └── 2025-03-08.png
│
├── index.php               # Página inicial (273 linhas)
├── aluno.php              # Área do aluno
├── professores.php        # Listagem de professores
├── planos.php             # Planos de assinatura
├── contato.php            # Página de contato
├── treinos.php            # Página de treinos
│
├── .env                    # Variáveis de ambiente
├── .env.example            # Exemplo de .env
├── .htaccess              # Rewrite URLs
├── README.md              # Este arquivo
│
└── DOCUMENTACAO/          # Documentação detalhada
    ├── DER.md             # Diagrama banco de dados
    └── ARQUITETURA.md     # Arquitetura de rede
```

### Fluxo de Dados

```
Usuário (Browser)
    ↓
    ↓ HTTP GET/POST
    ↓
Apache Server (Porta 8080)
    ↓
    ↓ Processa PHP
    ↓
index.php / admin/*.php
    ↓
    ↓ Requer includes
    ↓
includes/header.php
includes/functions.php
includes/constants.php
    ↓
    ↓ Query ao banco
    ↓
config/db.php (PDO)
    ↓
    ↓ SQL
    ↓
MySQL (Porta 3306)
    ↓ academia_prix
    ↓
Tabelas (professores, alunos, planos, etc)
    ↓
    ↓ Resultado
    ↓
PHP renderiza HTML
    ↓
assets/css/style.css
    ↓
Navegador exibe página
```

---

## <a name="documentação"></a>📚 Documentação

### Documentos Inclusos

1. **DER.md** - Diagrama de Entidade-Relacionamento
   - Estrutura do banco
   - Relacionamentos
   - Normalizações

2. **ARQUITETURA_REDE.md** - Configuração de Rede
   - Camadas OSI
   - Segurança
   - Performance

3. **README.md** - Este arquivo

### Funções Principais (functions.php)

```php
// Banco de Dados
getConnection()              // Conexão PDO
getProfessores($filtro)      // Lista professores
getPlanos()                  // Lista planos
getModalidades()             // Lista modalidades

// Agendamentos
salvarAgendamento($dados)    // Salva agendamento
validarAgendamento($dados)   // Valida dados

// Admin
isAdmin()                    // Verifica se é admin
loginAdmin()                 // Faz login admin
adminPasswordValid($senha)   // Valida senha

// Utilitários
sanitizar($dados)            // Remove XSS
formatarPreco($valor)        // Formata monetário
precoPorMes($plano)         // Calcula valor/mês
filtrarProfessoresPorEspecialidade()
```

---

## <a name="contribuindo"></a>👥 Contribuindo

### Como Contribuir

1. **Fork** o repositório
2. **Crie** uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. **Commit** suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. **Push** para a branch (`git push origin feature/AmazingFeature`)
5. **Abra** um Pull Request

### Padrões de Código

- **PHP**: PSR-12
- **HTML**: HTML5 semântico
- **CSS**: BEM naming convention
- **Commit**: Mensagens em português

### Reporte de Bugs

Abra uma issue descrevendo:
- Comportamento esperado
- Comportamento atual
- Passos para reproduzir
- Screenshots (se aplicável)

---

## 🔒 Segurança

### Medidas Implementadas

- ✅ Validação de entrada (backend)
- ✅ Proteção contra XSS (htmlspecialchars)
- ✅ Senhas armazenadas com hash (MD5 básico)
- ✅ Sessões PHP seguras
- ✅ .env não versionado
- ✅ Bloqueio de diretórios

### Melhorias Futuras

- 🔲 CSRF tokens em todos os formulários
- 🔲 Password_hash() para senhas
- 🔲 HTTPS/SSL obrigatório
- 🔲 Rate limiting
- 🔲 Two-factor authentication

---

## 📊 Estatísticas

| Métrica | Valor |
|---------|-------|
| **Linhas PHP** | ~1.500+ |
| **Linhas CSS** | 597 |
| **Tabelas BD** | 6 |
| **Funções** | 26+ |
| **Páginas** | 8 |
| **Tempo de Carregamento** | <1s |

---

## 📝 Licença

Este projeto é licenciado sob a Licença MIT - veja [LICENSE](LICENSE) para detalhes.

---

## 👨‍💼 Autores

- **Desenvolvedor**: [Seu Nome]
- **Academia**: Prix Matriz - Campo Mourão, PR
- **Data de Criação**: 2024
- **Última Atualização**: 2025

---

## 📞 Suporte

- **Email**: contato@prixacademia.com.br
- **WhatsApp**: (44) 9 9979-8559
- **Endereço**: Av. Irmãos Pereira, 260 — Campo Mourão, Paraná
- **Horários**: Segunda-quinta 05:30-00:00 | Sexta 05:30-23:00 | Sábado 09:00-12:00, 14:00-18:00 | Domingo 09:00-11:00

---

## 🙏 Agradecimentos

- Bootstrap team pelo excelente framework
- PHP community por ferramentas e documentação
- Academia Prix por confiar neste projeto

---

**Status:** ✅ Ativo e em Manutenção  
**Última Verificação:** Janeiro 2025  
**Próxima Revisão:** Abril 2025

