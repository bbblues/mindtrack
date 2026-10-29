#!/usr/bin/env bash
#
# MindTrack - Script de deploy em producao (VPS Ubuntu, instalacao nativa)
# ---------------------------------------------------------------------------
# Uso: execute na raiz do projeto, via SSH:
#
#   bash deploy.sh
#
# Pre-requisitos:
#   - git, php-cli (>= 8.2) e composer instalados na VPS;
#   - o arquivo .env ja criado a partir de .env.example;
#   - o Apache apontando o DocumentRoot para a pasta "public".
# ---------------------------------------------------------------------------

set -euo pipefail

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

info() { echo -e "${BLUE}==>${NC} $*"; }
ok()   { echo -e "${GREEN}==>${NC} $*"; }
fail() { echo -e "${RED}==>${NC} $*"; exit 1; }

# Diretorio do script (raiz do projeto)
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${APP_DIR}"

info "Diretorio do projeto: ${APP_DIR}"

# ---------------------------------------------------------------------------
# 0. Verifica pre-requisitos minimos
# ---------------------------------------------------------------------------
command -v php >/dev/null 2>&1 || fail "PHP nao encontrado no PATH."
command -v composer >/dev/null 2>&1 || fail "Composer nao encontrado no PATH."
command -v git >/dev/null 2>&1 || fail "Git nao encontrado no PATH."

if [ ! -f ".env" ]; then
    fail "Arquivo .env nao encontrado. Execute: cp .env.example .env"
fi

# ---------------------------------------------------------------------------
# 1. Atualiza o codigo-fonte
# ---------------------------------------------------------------------------
info "[1/5] Atualizando o codigo (git pull origin main)"
git pull origin main

# ---------------------------------------------------------------------------
# 2. Instala/atualiza as dependencias de producao
# ---------------------------------------------------------------------------
info "[2/5] Instalando dependencias de producao (composer install --no-dev)"
composer install --no-dev --optimize-autoloader --classmap-authoritative

# ---------------------------------------------------------------------------
# 3. Aplica as migrations pendentes
# ---------------------------------------------------------------------------
info "[3/5] Executando migrations (php spark migrate)"
php spark migrate

# ---------------------------------------------------------------------------
# 4. Limpa caches e logs
# ---------------------------------------------------------------------------
info "[4/5] Limpando caches e logs"
php spark cache:clear
php spark logs:clear
php spark debugbar:clear

# ---------------------------------------------------------------------------
# 5. Ajusta as permissoes da pasta writable/
# ---------------------------------------------------------------------------
info "[5/5] Ajustando permissoes da pasta writable/"
# O Apache/PHP precisa gravar em writable/ (cache, logs, sessoes e uploads).
( chown -R www-data:www-data writable || sudo chown -R www-data:www-data writable ) 2>/dev/null || true
chmod -R 775 writable
find writable -type f -exec chmod 664 {} \; 2>/dev/null || true

ok "Deploy concluido com sucesso!"
