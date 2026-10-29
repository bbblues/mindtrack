# Deploy do MindTrack em VPS Ubuntu (instalação nativa, sem Docker)

Este guia descreve, passo a passo, como preparar uma VPS com **Ubuntu** para
rodar o MindTrack (CodeIgniter 4) de forma nativa, usando **Apache**, **PHP 8.2**
e **MySQL 8.0**.

> **Atenção:** o MindTrack é multi-tenant e contém dados clínicos sensíveis.
> Siga também a seção de **Hardening de segurança** antes de liberar o acesso.

---

## 0. Visão geral

| Componente | Versão / caminho |
| --- | --- |
| Sistema | Ubuntu 22.04 LTS ou 24.04 LTS |
| Servidor web | Apache 2.4 |
| PHP | 8.2 (mod_php) |
| Banco | MySQL 8.0 |
| Raiz do projeto | `/var/www/mindtrack` |
| DocumentRoot (público) | `/var/www/mindtrack/public` |
| Domínio de exemplo | `app.mindtrack.example.com` |

> Substitua `app.mindtrack.example.com` pelo domínio real da clínica em todo o
> guia.

---

## 1. Atualizar o sistema e instalar utilitários básicos

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl wget unzip software-properties-common ca-certificates apt-transport-https
```

---

## 2. Instalar o Apache

```bash
sudo apt install -y apache2
```

Habilite os módulos necessários:

```bash
sudo a2enmod rewrite headers ssl
sudo systemctl enable apache2
sudo systemctl restart apache2
```

---

## 3. Instalar o PHP 8.2 e as extensões

O Ubuntu 22.04 traz PHP 8.1 e o 24.04 traz PHP 8.3. Para usar exatamente o
**PHP 8.2** (exigido pelo projeto), use o repositório `ondrej/php`:

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

Instale o PHP com o módulo do Apache e as extensões usadas pelo CodeIgniter 4
e pelo Dompdf:

```bash
sudo apt install -y \
  php8.2 \
  libapache2-mod-php8.2 \
  php8.2-cli \
  php8.2-common \
  php8.2-mysql \
  php8.2-mbstring \
  php8.2-xml \
  php8.2-intl \
  php8.2-curl \
  php8.2-gd \
  php8.2-zip
```

Confirme a versão:

```bash
php -v
```

---

## 4. Instalar o MySQL 8.0

```bash
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql
```

Execute a configuração inicial de segurança:

```bash
sudo mysql_secure_installation
```

Crie o banco de dados e o usuário da aplicação:

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE mindtrack CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'mindtrack'@'localhost' IDENTIFIED BY 'ESCOLHA_UMA_SENHA_FORTE';
GRANT ALL PRIVILEGES ON mindtrack.* TO 'mindtrack'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> Anote a senha — ela será usada no arquivo `.env` (`database.default.password`).

---

## 5. Instalar o Composer

```bash
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
HASH=$(curl -sS https://composer.github.io/installer.sig)
php -r "if (hash_file('sha384', 'composer-setup.php') !== '$HASH') { echo 'Installer corrompido', PHP_EOL; exit(1); } echo 'Installer OK', PHP_EOL;"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

---

## 6. Publicar o código do projeto

### Opção A — Clonar via Git (recomendado)

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/SEU_USUARIO/mindtrack.git mindtrack
```

### Opção B — Enviar via SCP/rsync

Envie o conteúdo do projeto (sem a pasta `vendor/`) para `/var/www/mindtrack`.

---

## 7. Configurar o `.env`

```bash
cd /var/www/mindtrack
sudo cp .env.example .env
sudo nano .env
```

Ajuste, no mínimo:

```env
CI_ENVIRONMENT = production
app.baseURL = 'https://app.mindtrack.example.com/'
database.default.hostname = localhost
database.default.database = mindtrack
database.default.username = mindtrack
database.default.password = ESCOLHA_UMA_SENHA_FORTE
```

> Enquanto o SSL ainda não estiver instalado, use `http://` na `app.baseURL`
> e deixe `app.forceGlobalSecureRequests` comentado.

---

## 8. Instalar as dependências e rodar as migrations

```bash
cd /var/www/mindtrack
sudo composer install --no-dev --optimize-autoloader --classmap-authoritative
sudo php spark migrate
```

Gere a chave de criptografia (preenche `encryption.key` no `.env`):

```bash
sudo php spark key:generate
```

> O comando `php spark key:generate` escreve a chave diretamente no `.env`.
> Para apenas visualizar sem gravar, use `php spark key:generate --show`.

---

## 9. Configurar permissões da pasta `writable/`

O Apache (usuário `www-data`) precisa gravar em `writable/` (cache, logs,
sessões e uploads). Ajuste o dono da pasta:

```bash
cd /var/www/mindtrack
sudo chown -R www-data:www-data writable
sudo chmod -R 775 writable
sudo find writable -type f -exec chmod 664 {} \;
```

---

## 10. Configurar o VirtualHost do Apache

Crie o arquivo do site:

```bash
sudo nano /etc/apache2/sites-available/mindtrack.conf
```

Conteúdo:

```apache
<VirtualHost *:80>
    ServerName app.mindtrack.example.com
    ServerAdmin admin@example.com

    # IMPORTANTE: o DocumentRoot aponta para a pasta "public".
    DocumentRoot /var/www/mindtrack/public

    <Directory /var/www/mindtrack/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/mindtrack_error.log
    CustomLog ${APACHE_LOG_DIR}/mindtrack_access.log combined
</VirtualHost>
```

Habilite o site e desabilite o site padrão:

```bash
sudo a2dissite 000-default.conf
sudo a2ensite mindtrack.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

> `AllowOverride All` é necessário para que o `.htaccess` da pasta `public/`
> (rewrite/segurança) seja aplicado.

---

## 11. Instalar o certificado SSL (HTTPS) — recomendado

Com Let's Encrypt (Certbot):

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d app.mindtrack.example.com
```

Depois de instalar o SSL:

1. Ajuste `app.baseURL` para `https://...` no `.env`.
2. Descomente `app.forceGlobalSecureRequests = true` no `.env`.
3. Descomente o bloco de redirect HTTP→HTTPS no `public/.htaccess`.
4. Descomente o cabeçalho HSTS no `public/.htaccess`.

```bash
sudo systemctl reload apache2
```

---

## 12. Atualizar o sistema (deploy)

Na raiz do projeto, via SSH:

```bash
cd /var/www/mindtrack
bash deploy.sh
```

O `deploy.sh` executa, em ordem:

1. `git pull origin main`
2. `composer install --no-dev --optimize-autoloader --classmap-authoritative`
3. `php spark migrate`
4. Limpeza de caches/logs (`cache:clear`, `logs:clear`, `debugbar:clear`)
5. Ajuste de permissões da pasta `writable/`

> Se preferir não usar `git pull` (envio manual via SCP), comente a etapa 1 do
> script e rode as demais etapas.

---

## 13. Verificação pós-instalação

```bash
cd /var/www/mindtrack

# Versão do PHP
php -v

# Lista de rotas registradas
sudo php spark routes

# Status das migrations
sudo php spark migrate:status

# Logs de erro do Apache
sudo tail -f /var/log/apache2/mindtrack_error.log

# Logs da aplicação (CodeIgniter)
sudo tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

Acesse `https://app.mindtrack.example.com/` e confirme que o login abre
normalmente.

---

## 14. Hardening de segurança

1. **Nunca** exponha as pastas `app/`, `vendor/`, `writable/` ou o arquivo
   `.env` pela web. O `DocumentRoot` já aponta para `public/`; a pasta raiz
   ainda conta com um `.htaccess` de bloqueio como defesa em profundidade.
2. Ative o **firewall**:

   ```bash
   sudo ufw allow OpenSSH
   sudo ufw allow 'Apache Full'
   sudo ufw enable
   ```

3. Mantenha `CI_ENVIRONMENT = production` (oculta mensagens de erro detalhadas).
4. Use senhas fortes no MySQL e em todos os usuários do sistema.
5. Habilite HTTPS (HSTS) conforme a etapa 11.
6. Faça backups periódicos do banco:

   ```bash
   sudo mysqldump -u root -p mindtrack > backup_mindtrack_$(date +%F).sql
   ```

---

## 15. Solução de problemas comuns

| Sintoma | Causa provável | Correção |
| --- | --- | --- |
| Erro 404 em todas as rotas | `mod_rewrite` desativado ou `AllowOverride None` | `sudo a2enmod rewrite` e `AllowOverride All` no VirtualHost |
| Tela branca / erro 500 | Permissão de `writable/` ou `.env` errado | Refazer etapa 9 e conferir `.env` |
| Erro de conexão com o banco | Credenciais ou hostname errados | Usar `localhost` no `.env` e conferir usuário/senha |
| `composer` não encontrado | Instalação incompleta | Refazer etapa 5 |
| Página sem CSS (erro de URL) | `app.baseURL` incorreto | Conferir `app.baseURL` no `.env` |
