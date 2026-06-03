# 🌐 DOCUMENTAÇÃO DE ARQUITETURA DE REDE E SISTEMAS OPERACIONAIS
# Academia Prix Matriz

---

## 📐 Arquitetura Geral

```
┌─────────────────────────────────────────────────────────┐
│          MÁQUINA LOCAL (Desenvolvimento)                │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │     CAMADA APRESENTAÇÃO (Browser)               │  │
│  │  http://localhost:8080/projetoacademia/         │  │
│  │  - Google Chrome, Firefox, Safari                │  │
│  │  - Requisições HTTP/HTTPS                        │  │
│  └──────────────────────────────────────────────────┘  │
│           ▲                                              │
│           │ HTTP/HTTPS (Porta 8080)                    │
│           ▼                                              │
│  ┌──────────────────────────────────────────────────┐  │
│  │  CAMADA APLICAÇÃO (Web Server)                  │  │
│  │  - Apache 2.4 com PHP 7.4+                       │  │
│  │  - Processamento de requisições                  │  │
│  │  - Rewrite URLs (.htaccess)                      │  │
│  │  - Sessions e Cookies                            │  │
│  └──────────────────────────────────────────────────┘  │
│           ▲                                              │
│           │ TCP/IP Socket                               │
│           │ localhost:3306                              │
│           ▼                                              │
│  ┌──────────────────────────────────────────────────┐  │
│  │  CAMADA DADOS (Database Server)                 │  │
│  │  - MySQL 5.7+ / MariaDB 10.3+                   │  │
│  │  - Banco: academia_prix                          │  │
│  │  - 6 Tabelas estruturadas                        │  │
│  │  - Storage Engine: InnoDB                        │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🖥️ Sistema Operacional

### Requerimentos
- **OS**: Windows 10+ / macOS / Linux (Ubuntu 18.04+)
- **PHP**: 7.4 ou superior
- **MySQL/MariaDB**: 5.7+ ou 10.3+
- **Apache**: 2.4+
- **Navegador**: Chrome, Firefox, Edge, Safari (versões recentes)

### Ambiente Testado
- **OS**: Windows 10 / Ubuntu 20.04
- **Apache**: 2.4.41
- **PHP**: 7.4.3
- **MySQL**: 5.7.30
- **Navegador**: Google Chrome 90+

---

## 🌍 Camadas de Rede

### Camada 1: Aplicação (OSI Layer 7)
```
PROTOCOLO: HTTP/HTTPS
PORTA: 8080 (desenvolvimento) / 443 (produção)
DADOS: Requisições e respostas em HTML, JSON, CSS, JS
```

**Tipos de Requisições:**
- `GET /projetoacademia/` → Página inicial
- `POST /projetoacademia/admin/login.php` → Login admin
- `GET /projetoacademia/api/professores` → Dados JSON (futuro)

---

### Camada 2: Transporte (OSI Layer 4)
```
PROTOCOLO: TCP
PORTA: 8080
VELOCIDADE: Até 1 Gbps (local)
RELIABILITY: Confiável, ordenado, com controle de fluxo
```

---

### Camada 3: Internet (OSI Layer 3)
```
PROTOCOLO: IPv4
ENDEREÇO LOCAL: 127.0.0.1 (loopback)
MÁSCARA: 255.255.255.255
GATEWAY: Nenhum (local)
DNS: localhost
```

---

### Camada 4: Enlace (OSI Layer 2)
```
PROTOCOLO: Ethernet (se em rede local)
MAC ADDRESS: Auto-atribuído pelo SO
```

---

## 🔐 Configuração de Segurança

### Servidor Web (Apache)
```apache
# .htaccess configurações de segurança:

# 1. Bloquear acesso ao .env
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

# 2. Desabilitar listagem de diretório
Options -Indexes

# 3. Rewrite URLs (remover .php)
RewriteEngine On
RewriteRule ^(.+)\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

### Banco de Dados (MySQL)
```sql
-- Usuário com privilégios limitados
CREATE USER 'academia_user'@'localhost' IDENTIFIED BY 'senha_forte';
GRANT SELECT, INSERT, UPDATE ON academia_prix.* TO 'academia_user'@'localhost';
FLUSH PRIVILEGES;

-- Senha criptografada com hash
-- Usar: password_hash($senha, PASSWORD_BCRYPT)
```

### Aplicação (PHP)
```php
// Validação de entrada
$_POST['email'] = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

// Proteção contra XSS
echo htmlspecialchars($usuario_nome, ENT_QUOTES, 'UTF-8');

// Proteção contra SQL Injection
$stmt = $pdo->prepare("SELECT * FROM alunos WHERE email = ?");
$stmt->execute([$email]);
```

---

## 📊 Fluxo de Comunicação

### 1. Requisição de Página
```
Cliente (Browser)
    ↓ HTTP GET
Servidor Apache (Porta 8080)
    ↓ Processa PHP
Servidor MySQL (Porta 3306)
    ↓ SQL Query
Banco de Dados
    ↓ Resultado
Servidor MySQL
    ↓ Retorna dados
Servidor Apache
    ↓ Renderiza HTML
Cliente (Browser) - Exibe página
```

### 2. Requisição de Agendamento
```
Cliente submete formulário
    ↓ POST /projetoacademia/index.php
Apache valida CSRF token
    ↓
PHP valida dados ($_POST)
    ↓
Banco insere em agendamentos
    ↓
Retorna confirmação
    ↓
Cliente redireciona para home
```

---

## 🔧 Configuração DNS Local

### Opção 1: Via Hosts
**Windows:**
```
C:\Windows\System32\drivers\etc\hosts

Adicionar:
127.0.0.1       academia.local
```

**Linux/macOS:**
```
/etc/hosts

Adicionar:
127.0.0.1       academia.local
```

Acessar: `http://academia.local:8080/projetoacademia/`

### Opção 2: Via Virtual Host (Apache)
```apache
# /etc/apache2/sites-available/academia.conf

<VirtualHost *:8080>
    ServerName academia.local
    DocumentRoot /var/www/html/projetoacademia
    
    <Directory /var/www/html/projetoacademia>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/academia_error.log
    CustomLog ${APACHE_LOG_DIR}/academia_access.log combined
</VirtualHost>
```

---

## 📡 Portas Utilizadas

| Serviço | Porta | Protocolo | Status |
|---------|-------|-----------|--------|
| Apache | 8080 | HTTP | ✅ Aberta |
| MySQL | 3306 | TCP | ✅ Aberta (localhost) |
| SSH | 22 | TCP | ⛔ Bloqueada |
| HTTPS | 443 | HTTPS | ⛔ Não configurado |

---

## 🚀 Instalação e Configuração

### Passo 1: Instalação
```bash
# Windows (XAMPP)
1. Baixar XAMPP de https://www.apachefriends.org/
2. Instalar com Apache + MySQL + PHP
3. Colocar projeto em C:\xampp\htdocs\projetoacademia\

# Linux (Ubuntu)
sudo apt-get install apache2 mysql-server php7.4
sudo cp -r projetoacademia /var/www/html/
```

### Passo 2: Configuração Apache
```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite

# Mudar porta para 8080 (opcional)
sudo nano /etc/apache2/ports.conf
# Alterar: Listen 80 → Listen 8080

# Reiniciar
sudo systemctl restart apache2
```

### Passo 3: Banco de Dados
```bash
# Importar SQL
mysql -u root -p < database/academia_prix.sql

# Ou via PHPMyAdmin
# 1. Abra http://localhost/phpmyadmin
# 2. Crie banco academia_prix
# 3. Importe o arquivo SQL
```

### Passo 4: Configurar .env
```bash
# Copiar arquivo de exemplo
cp .env.example .env

# Editar com suas credenciais
DB_HOST=127.0.0.1
DB_USER=root
DB_PASS=sua_senha
DB_NAME=academia_prix
```

---

## 📈 Performance e Escalabilidade

### Cache
```php
// Implementar cache de professores
$cache_key = 'professores_lista';
if (apcu_exists($cache_key)) {
    $professores = apcu_fetch($cache_key);
} else {
    $professores = getProfessores();
    apcu_store($cache_key, $professores, 3600); // 1 hora
}
```

### Compressão
```apache
# .htaccess - Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript
</IfModule>
```

### Índices
```sql
-- Adicionar índices para melhor performance
CREATE INDEX idx_alunos_email ON alunos(email);
CREATE INDEX idx_agendamentos_data ON agendamentos(data);
CREATE INDEX idx_agendamentos_status ON agendamentos(status);
```

---

## 🔄 Backup e Recuperação

### Backup Automático
```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/backups/academia_prix"
DATA=$(date +%Y%m%d_%H%M%S)

# Backup do banco
mysqldump -u root -p academia_prix > $BACKUP_DIR/academia_prix_$DATA.sql

# Backup dos arquivos
tar -czf $BACKUP_DIR/projeto_$DATA.tar.gz /var/www/html/projetoacademia/
```

### Restauração
```bash
# Restaurar banco
mysql -u root -p academia_prix < backup_arquivo.sql

# Restaurar arquivos
tar -xzf projeto_backup.tar.gz -C /
```

---

## ✅ Checklist de Implantação

- [ ] Apache instalado e rodando na porta 8080
- [ ] MySQL/MariaDB rodando e acessível em localhost:3306
- [ ] Banco academia_prix criado e importado
- [ ] .env configurado com credenciais corretas
- [ ] .htaccess habilitado (mod_rewrite ativo)
- [ ] DNS local configurado (hosts ou virtual host)
- [ ] Permissões de arquivo corretas (755 para pastas, 644 para arquivos)
- [ ] Projeto acessível em http://localhost:8080/projetoacademia/
- [ ] Login admin funcionando
- [ ] Banco de dados respondendo
- [ ] Backup automático configurado

---

## 🔗 Referências

- [Apache Documentation](https://httpd.apache.org/docs/)
- [PHP Manual](https://www.php.net/manual/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [OSI Model](https://en.wikipedia.org/wiki/OSI_model)
- [TCP/IP Stack](https://en.wikipedia.org/wiki/Internet_protocol_suite)

