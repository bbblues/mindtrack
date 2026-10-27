# MindTrack

ERP Clinico SaaS para clinicas de ABA, construido com CodeIgniter 4, Apache e MySQL 8 em ambiente 100% Docker.

## Stack

- PHP 8.2 + Apache (extensoes: `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `zip`)
- CodeIgniter 4.7
- MySQL 8.0
- Tailwind CSS + Alpine.js (via CDN, nas views)

## Subir o ambiente

```bash
docker compose up -d --build
```

A aplicacao fica disponivel em http://localhost:8080.

### Primeira execucao

```bash
# 1. Configurar o ambiente (o .env nao e versionado)
cp .env.example .env

# 2. Subir os containers
docker compose up -d --build

# 3. Rodar as migrations
docker compose exec app php spark migrate

# 4. Semear o Super Admin e os modulos base
docker compose exec app php spark db:seed CoreSeeder
```

> No `.env`, o host do banco deve ser `db` (servico do `docker-compose.yml`), nunca `localhost`.

## Acesso

- URL: http://localhost:8080
- Super Admin: `admin@mindtrack.com` / `admin123`
- MySQL: `localhost:3306` | banco `mindtrack` | usuario `mindtrack` | senha `mindtrack`

> Troque a senha do Super Admin e as credenciais do banco em producao.

## Banco de dados (Core)

| Tabela | Descricao |
|---|---|
| `tenants` | Clinicas (clientes do SaaS) |
| `users` | Usuarios (o Super Admin tem `tenant_id = NULL`) |
| `roles` | Perfis de acesso por tenant |
| `permissions` | Catalogo global de permissoes |
| `role_user` | Vinculo usuario x perfil |
| `permission_role` | Vinculo perfil x permissao |
| `modules` | Catalogo de modulos do SaaS |
| `module_tenant` | Modulos habilitados por tenant |
| `settings` | Configuracoes chave-valor por tenant |

Todas as tabelas de dominio possuem `tenant_id` e soft delete (`deleted_at`), exceto os catalogos globais `permissions` e `modules` e as tabelas de vinculo `role_user` / `permission_role`.

## Multi-tenancy

- `App\Models\BaseModel` aplica automaticamente `WHERE tenant_id = <tenant da sessao>` em qualquer model cuja tabela possua a coluna `tenant_id`.
- O login deve gravar `tenant_id` na sessao. O Super Admin nao possui tenant (`tenant_id = NULL`) e, por isso, enxerga todos os registros.
- Em execucoes CLI (migrations/seeders) o filtro e ignorado.

## Estrutura

```
app/
  Models/BaseModel.php          # isolamento multi-tenant + soft delete
  Modules/Core/Models/          # models do dominio Core
  Modules/<Modulo>/             # Controllers, Models, Views, Config/Routes.php
  Database/Migrations/          # schema
  Database/Seeds/               # seeds
```

> Controllers de modulo ficam em `App\Modules\<Modulo>\Controllers`. Ao registrar rotas, use o namespace absoluto (com `\` inicial) no handler:
> `$routes->get('pacientes', '\App\Modules\Patients\Controllers\PatientController::index');`

## Comandos uteis

```bash
docker compose up -d                 # subir
docker compose down                  # parar
docker compose logs -f app           # logs do Apache/PHP
docker compose exec app php spark migrate
docker compose exec app php spark db:seed CoreSeeder
docker compose exec app php spark routes
docker compose exec db mysql -umindtrack -pmindtrack mindtrack
```

## Referencias

- [CodeIgniter 4 User Guide](https://codeigniter.com/user_guide/)
- Diretrizes internas em `.clinerules/` e `docs/modules/`.

