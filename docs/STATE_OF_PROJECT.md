# STATE OF PROJECT — MindTrack (ERP Clínico SaaS / ABA)

> **Documento de retomada.** Gerado a partir da leitura direta do código, das
> migrations e do banco de dados em execução (`mindtrack_db`).
> Última verificação: **06/11/2026**.
> Branch: `master` · último commit: `b04dccf` (`feat: profissional ve todos os pacientes mas edita apenas os vinculados; testes automatizados de RBAC e documentacao de seguranca`).

---

## 1. Stack e Ambiente

| Componente | Versão / Configuração |
| --- | --- |
| Framework | CodeIgniter **4.7** (`codeigniter4/framework: ^4.7`; runtime reporta `4.7.5`) |
| Linguagem | PHP **8.2+** (`php:8.2-apache`) |
| Servidor Web | Apache com `mod_rewrite` e `AllowOverride All` (DocumentRoot em `public/`) |
| Banco | MySQL **8.0** (`mysql:8.0`), charset `utf8mb4` |
| Frontend | Views nativas do CI4 + **Tailwind CSS** (CDN) + **Alpine.js** (CDN) + Fetch API |
| Libs de front | **DayPilot Lite** (Agenda), **Chart.js 4.4.1** (Dashboard), **ViaCEP** (endereço) |
| Libs de back | **Dompdf ^3.1** (PDF), **PhpSpreadsheet ^5.10** (importação XLSX) |
| Ambiente local | Docker Compose (`docker-compose.yml`) |
| Ambiente produção | VPS Ubuntu **nativa** (sem Docker) — ver `docs/DEPLOY_VPS.md` |

### 1.1 Containers

| Serviço | Container | Porta | Observação |
| --- | --- | --- | --- |
| `app` | `mindtrack_app` | `8080:80` | `CI_ENVIRONMENT=development`, volume `./:/var/www/html` |
| `db` | `mindtrack_db` | `3306:3306` | banco `mindtrack`, usuário `mindtrack`, senha `mindtrack` |

- URL local: **http://localhost:8080**
- Super Admin seed: `admin@mindtrack.com` / `admin123`
- No `.env`, o host do banco **deve** ser `db` (nunca `localhost`).

### 1.2 Comandos essenciais

```bash
# Subir / parar o ambiente
docker compose up -d --build
docker compose down

# Logs
docker compose logs -f app

# Migrations e seeders
docker compose exec app php spark migrate
docker compose exec app php spark migrate:status
docker compose exec app php spark db:seed CoreSeeder
docker compose exec app php spark db:seed DemoDataSeeder --force

# Inspeção
docker compose exec app php spark routes
docker compose exec db mysql -umindtrack -pmindtrack mindtrack

# Importação de protocolos complexos (XLSX) — ver seção 4
docker compose exec app php scripts/import_protocol.php --dry-run
docker compose exec app php scripts/import_protocol.php
```

> **Deploy (VPS):** `bash deploy.sh` executa `git pull origin master` →
> `composer install --no-dev` → `php spark migrate` → limpeza de caches →
> ajuste de permissão de `writable/`. O script **não** roda seeders.

---

## 2. O que está IMPLEMENTADO (funcionando)

Legenda: ✅ concluído e em uso · 🟡 concluído com pendências conhecidas

### 2.1 Core (Núcleo / SaaS) ✅
Autenticação por sessão (`AuthController`) com filtros `auth`, `role` e
`superadmin`. Isolamento multi-tenant centralizado no `App\Models\BaseModel`.
O hub de configurações centraliza equipe, cargos e cadastros auxiliares.

### 2.2 Pacientes ✅
CRUD completo com abas (Pessoal, Clínico, Endereço, Responsáveis), soft delete
via inativação (`patients/toggle-status`), cor automática por paciente
(`PatientColor`, paleta de 30 cores priorizando cores ainda não usadas no
tenant), busca global por nome/CPF e paginação de 50 registros com filtro
"Mostrar Inativos" preservado na query string. A listagem separa "ver" de
"editar": o profissional enxerga toda a clínica ("Ver Todos os Pacientes"),
mas os botões "Editar"/"Ativo/Inativo" só aparecem para os pacientes vinculados
a ele (ver 2.12).

### 2.3 Mural e Jornada do Paciente ✅
Feed de publicações por tipo (`patient_posts`) com filtros em abas, e marcos
(`patient_milestones`) exibidos como timeline agrupada por mês/ano. Ao criar um
paciente, o marco "Início do Tratamento" é gerado automaticamente.

### 2.4 Agenda ✅
Agenda DayPilot Lite com visão Dia (colunas = profissionais) e Semana (linhas =
profissionais), navegação customizada, drag-and-drop com persistência AJAX
(`agenda/update-schedule`), *soft validation* de conflito de horário (HTTP 409 +
`force_override`), recorrência via `recurrence_group_id`, cards coloridos pela
cor do paciente com contraste calculado e ordenação de profissionais pelo campo
`users.agenda_order`.

### 2.5 Protocolos (simples) — builder dinâmico ✅
Criação de protocolos com estrutura aninhada **Níveis → Habilidades → Itens →
Status** em formulário com abas e Alpine.js (`protocols/create`), listagem
paginada com busca e resumo da estrutura. Status são configuráveis por protocolo
com valor numérico (`protocol_statuses.value`).

### 2.6 Avaliações de Pacientes ✅ 🟡
Início da avaliação a partir da listagem de pacientes (copia todos os
`protocol_items` para `patient_assessment_items`), tela de execução com modos
Visualizar/Editar e acordeões Nível → Habilidade → Item, gravação por item via
AJAX (`assessments/update-item`), recálculo de score em tempo real pelo
`ScoreCalculatorService`, finalização (`completed`, bloqueia edição), exportação
PDF (Dompdf) e geração de plano terapêutico.
🟡 *A pontuação de protocolos complexos recém-importados não funciona — ver 6.1.*

### 2.7 Plano Terapêutico ✅
Geração do plano a partir de uma avaliação finalizada (`plans/store`),
visualização com metas por nível/habilidade/objetivo (`plans/show`) e alternância
de status do item (`plans/toggle-item`).

### 2.8 Evoluções de Sessão ✅
Registro de evolução vinculado ao agendamento (`evolutions/create`,
`evolutions/store`), marca o agendamento como `realizado`, uso de modelos de nota
(`note_templates`) e feed de evoluções do paciente (`_feed.php`).

### 2.9 Dashboard ✅
Dois dashboards por perfil: painel do profissional/Super Admin (contadores) e
painel de gestão (`dashboard_management.php`) para cargos `admin`/`gestor`, com
KPIs vindos de `/dashboard/data` (pacientes ativos, agendamentos do mês, taxa de
faltas, assiduidade), gráfico de barras (realizados × faltas nos últimos 6 meses),
rosca por profissional e ranking Top 5 via Chart.js.

### 2.10 Configurações ✅
Hub `settings` com CRUD em modais (Alpine + Fetch) de: Unidades, Salas,
Especialidades, Convênios, Tags, Motivos de Falta, Motivos de Alta e Modelos de
Nota. Também expõe Equipe (`settings/users`) e Cargos (`settings/roles`).

### 2.11 Super Admin ✅
Gestão de clínicas (`tenants`) protegida pelo filtro `superadmin`, com criação de
tenant + `module_tenant` (feature flags) e vínculo automático dos módulos base
(`core`, `patients`, `agenda`).

### 2.12 Usuários, Cargos e RBAC ✅
Cargos isolados por tenant (Admin, Gestor, Profissional, Recepção + cargos
customizados), permissões granulares (`permissions`, `permission_role`),
`RoleFilter` com sintaxe `role:admin,gestor`, `SuperAdminFilter` para `/tenants`,
sidebar que respeita os cargos e perfil do usuário (`/profile`) com troca de
dados e senha. Regra de pacientes: **Profissional vê todos os pacientes da
clínica, mas edita apenas os vinculados; Iniciar Avaliação é restrito ao
Responsável Principal** (ver `docs/modules/security.md`).

### 2.13 Protocolos Complexos (VB-MAPP / AFLS / Comunicação / Academia) ✅
Importação XLSX (PhpSpreadsheet) via `scripts/import_protocol.php`, com 4
importadores, persistência idempotente (UPSERT, nunca apaga), views customizadas
por `view_slug` + PDF e cálculo de score por item/habilidade/seção. Detalhes em
`docs/modules/complex_protocols.md`.

---

## 3. O que está EM ANDAMENTO (incompleto)

Nenhum módulo em andamento no momento. O módulo de protocolos complexos
(último foco ativo) foi concluído — ver seção 2.13 e
`docs/modules/complex_protocols.md`. Pendências remanescentes (dívida técnica)
estão na seção 6.

---

## 4. Arquitetura de Protocolos Complexos

> Movida para `docs/modules/complex_protocols.md` (modelo de dados, fluxo de
> importação XLSX → banco, chaves naturais do UPSERT, regras de `max_score`,
> cálculo de score e views customizadas por `view_slug`).

---

## 5. Decisões Críticas (e o porquê)

### 5.1 Multi-tenancy por filtro de model, não por schema
**Decisão:** banco único; `App\Models\BaseModel` sobrescreve `builder()`
aplicando `tenant_id` da sessão a qualquer tabela que possua essa coluna
(com cache da checagem de coluna e do escopo aplicado). `permissions` e
`modules` são catálogos globais (sem `tenant_id`).
**Implicações:** em CLI (`is_cli()`) e para o Super Admin (`tenant_id = NULL`) o
filtro não é aplicado. Esse é o motivo pelo qual o Super Admin enxerga tudo — e
também pelo qual queries manuais em `scripts/` precisam dispensar o filtro
conscientemente.

### 5.2 Protocolos globais (`tenant_id NULL`) + `includeGlobalRows`
Protocolos de catálogo (VB-MAPP, AFLS) pertencem ao sistema, não à clínica.
Os models de protocolo declaram `includeGlobalRows = true`, e o `BaseModel`
passa a gerar `(tenant_id = X OR tenant_id IS NULL)`. Protocolos criados por uma
clínica continuam isolados.

### 5.3 Import por XLSX em PHP (PhpSpreadsheet) — substituiu o legado
**Decisão registrada:** abandonar CSV, JSON e scripts PowerShell; ler as planilhas
diretamente em PHP com `PhpSpreadsheet\IOFactory::load()`, via um único entry
point CLI (`scripts/import_protocol.php`).
**Motivos:** a planilha é a fonte oficial entregue pelas terapeutas; CSV/JSON
exigiam conversão manual duplicada e os scripts PowerShell não rodam na VPS
Linux. Ler o XLSX em PHP torna o processo reproduzível localmente e em produção.
**Consequência:** `ComplexProtocolSeeder` (JSON) ficou obsoleto e deve ser removido.

### 5.4 Import idempotente e não destrutivo
O writer usa UPSERT por chave natural, em transação única, com contadores por
tipo (`protocols_created`, `items_updated`, ...). É seguro reimportar após ajuste
de planilha. **Nunca** há delete — para proteger `patient_assessment_items`.

### 5.5 `score_achieved` em vez de sobrescrever `value_at_time`
Manteve-se o campo legado (`value_at_time`) e adicionou-se `score_achieved`
DECIMAL(5,2). Isso permite pontuação fracionada (0.5) sem quebrar avaliações
antigas e mantém o cálculo retrocompatível.

### 5.6 `max_score` no item, não no status
O teto de pontuação pertence ao item (no VB-MAPP é fixo por seção: 1, 4 ou 5;
no AFLS vem da coluna "Pontuação"), enquanto o status define o **valor
atribuído** naquela avaliação. O cálculo é
`sum(score_achieved) / sum(max_score)`.

### 5.7 Soft delete em todo o domínio
`$useSoftDeletes = true` nos models de domínio; a UI de pacientes usa "inativar"
(`is_active`) em vez de excluir. Protocolos importados não têm rotas de
edição/exclusão (existem apenas `index` e `create`/`store` em
`app/Modules/Protocols/Config/Routes.php`), o que na prática congela a estrutura
importada até que uma tela de administração seja criada.

### 5.8 Views nativas + CDN, sem build de frontend
Tailwind/Alpine/Chart.js/DayPilot vêm por CDN; não há bundler, `npm` nem assets
compilados. Toda interatividade é Fetch API + Alpine.js, com `csrf_field()` nos
formulários e `esc()` em toda saída de banco.

---

## 6. Problemas Conhecidos e Dívida Técnica

### 6.1 🔴 CRÍTICO — `protocol_statuses` vazio nos protocolos importados
Tabela com **0 registros**. O `ProtocolImportWriter` não cria status e o
`ProtocolController` só cria status no fluxo manual de UI. Efeito prático:
avaliações de VB-MAPP/AFLS não conseguem pontuar itens (o select só oferece o
label "Nao avaliado"), `score_achieved` permanece 0 e o PDF/plano saem zerados.
**Ação:** criar status por protocolo (`Não avaliado`=0, `Emergente`=0.5,
`Adquirido`=1 ou a escala específica do protocolo) no importador, no writer ou
via migration/seeder idempotente.
**Atalho possível:** `assessments/update-item` já aceita `score_achieved`
explícito (validado entre `0` e `max_score`), então uma view customizada poderia
pontuar sem depender de status — decisão a tomar na Fase 2.
**Feito:** as views customizadas (6.2) pontuam exclusivamente via
`score_achieved` e **não dependem** de `protocol_statuses`; a pendência de status
segue valendo para a view genérica e para o fluxo manual.

### 6.2 ✅ RESOLVIDO — Views customizadas por `view_slug`
`AssessmentController::show()` escolhe a view por `protocols.view_slug`:
`protocols/vb_mapp.php`, `protocols/afls.php`, `protocols/comunicacao.php` e
`protocols/academico.php` (cada uma com seu `_panel` e PDF próprio). Todas usam
abas Alpine por `protocol_levels.section`, `x-show` para troca sem reload,
gravação célula a célula em `assessments/update-item` e atualização imediata do
score total e por seção (resposta traz `score`, `total_possible`, `percentage`,
`by_skill` e `by_section`). Protocolos sem `view_slug` seguem na view genérica.

### 6.3 ✅ RESOLVIDO — `section` exposto na UI
`buildLevels()` passou a retornar `section` (e o `sort_order` de cada item, usado
para indexar as matrizes de Rastreamento). As views VB-MAPP/AFLS usam a seção
como aba e como chave do resumo por seção devolvido por `update-item`.
**Pendência menor:** `name` e `section` do nível ainda recebem a mesma string no
importador (duplicidade de informação).

### 6.4 🟠 Importação só por CLI
Não há tela de upload, nem `spark` command registrado, nem log/auditoria da
importação. Rodar em produção exige SSH (ver `docs/DEPLOY_VPS.md`).

### 6.5 🟠 `ComplexProtocolSeeder` obsoleto e destrutivo
Ainda lê `database/data/protocols/*.json` (arquivos que não existem mais) e faz
`clearLevels()` (DELETE físico). Rodar `db:seed ComplexProtocolSeeder` hoje
resulta em erro — ou, se houver JSON, em perda de estrutura e das avaliações
ligadas por cascade. Deve ser removido ou reescrito sobre o `ProtocolImportWriter`.

### 6.6 🟠 Drift de migration: `CreateClinicSettings`
A migration `2026-10-31-000039_CreateClinicSettings` está **registrada na tabela
`migrations`** mas o **arquivo não existe** no repositório. Isso quebra
`migrate:rollback` / `migrate:refresh` em ambientes novos.

### 6.7 ✅ RESOLVIDO — Documentação do módulo
`docs/modules/complex_protocols.md` foi preenchido com o contrato do importador,
o schema novo, as regras de `max_score` e o fluxo de reimportação.

### 6.8 🟡 Zero testes de domínio
`tests/` contém apenas os exemplos do framework (`ExampleDatabaseTest`,
`ExampleSessionTest`, `HealthTest`). Importadores, writer e
`ScoreCalculatorService` — que processam 3.054 itens — não têm cobertura alguma.
O `--dry-run` é a única validação disponível hoje.

### 6.9 🟡 Backup em produção é manual
`docs/DEPLOY_VPS.md` documenta `mysqldump` manual; não há cron/systemd timer nem
script versionado de backup/restore.

### 6.10 ✅ RESOLVIDO — Trabalho commitado
Todo o trabalho do módulo de protocolos complexos (importadores, views, PDF,
script CLI, planilhas XLSX e documentação) está commitado no branch `master`.

### 6.11 🟡 Cadastros citados na documentação sem implementação
A documentação menciona feriados (`holidays`) e lista de espera (`waiting_list`)
que **não** existem em migrations nem no banco — são planejamento, não código.

### 6.12 🟡 Builder manual não cobre os campos novos
O formulário `protocols/create` não preenche `max_score`, `metadata` nem
`section`; protocolos criados pela UI ficam com o default de `max_score` e
`section = NULL` (caem em "Geral" no cálculo por seção).

---

## 7. Roadmap Imediato

> **Requisito transversal (antes ou junto da Fase 1):** popular
> `protocol_statuses` para VB-MAPP e AFLS (a gravação de `status_id` /
> `score_achieved` por item **já está implementada** em
> `assessments/update-item`). Sem status, nenhuma fase abaixo produz resultado
> visível ao terapeuta.

### Fase 1 — Validar e refinar a importação VB-MAPP (XLSX)
- Reexecutar `php scripts/import_protocol.php --dry-run --protocol=vb-mapp` e
  comparar os contadores com o banco (esperado: 6 seções / 52 habilidades /
  1.118 itens).
- Conferir `max_score` por seção contra a planilha (hoje é fixo no código:
  1/4/5/1) e avaliar se cada item precisa de teto próprio.
- Tratar linhas de cabeçalho/rodapé remanescentes e itens truncados em 255 chars.
- **DoD:** reimportação idempotente (segunda execução só contabiliza
  `*_updated`), zero itens vazios, status criados, doc do contrato escrita.

### Fase 2 — View customizada VB-MAPP ✅ CONCLUÍDA
- [x] `protocols/vb_mapp.php` + `_vb_mapp_panel.php`: 6 abas (Marcadores,
  Barreiras, Transição, Rastreamento I/II/III), matriz marco × habilidade,
  matriz passo × habilidade no Rastreamento, checkbox 0/1 nas Barreiras e nota
  0..5 (passo 1) na Transição.
- [x] *Branch* por `view_slug` em `AssessmentController::show()` (ver 4.4) e
  `section` + `sort_order` em `buildLevels()`.
- [x] Gravação AJAX por célula em `assessments/update-item`, com 6 cards de score
  por seção atualizados na própria resposta (`by_section`), score total no topo,
  finalização com bloqueio de edição e geração de plano terapêutico.
- **DoD atendido:** 1.118 itens editáveis sem recarregar a página.

### Fase 3 — Validar importação AFLS
- `--dry-run --protocol=afls`, comparar 6 seções / 65 habilidades / 1.936 itens.
- Garantir que a aba `CONSOLIDADO` continue ignorada e que as áreas de cada
  seção estejam em `metadata`.
- **DoD:** mesma régua da Fase 1, com relatório de divergências por seção.

### Fase 4 — View customizada AFLS ✅ CONCLUÍDA
- [x] `protocols/afls.php` + `_afls_panel.php`: 6 abas por seção (título exibido
  com acentuação, chave crua preservada para casar com `by_section`) e tabela
  Qtde/Tarefa/Pontuação por área, com `step="0.5"`.
- [x] Mesmo endpoint AJAX e `ScoreCalculatorService`; `saveNumber` arredonda para
  múltiplos de 0,5 e reverte o input quando a gravação falha.
- **DoD atendido:** 1.936 itens editáveis, totais por seção, PDF e plano.

### Depois das fases (backlog técnico)
1. Excluir/reescrever `ComplexProtocolSeeder` (6.5).
2. Recriar o arquivo da migration `CreateClinicSettings` (6.6).
3. ✅ Preencher `docs/modules/complex_protocols.md` (6.7) — concluído.
4. Testes de `VbMappImporter`, `AflsImporter`, `ProtocolImportWriter` e
   `ScoreCalculatorService` (6.8).
5. Tela administrativa de protocolos (upload XLSX + status) e rotas de
   edição/exclusão.
6. Script de backup agendado na VPS (6.9).
7. ✅ Commit do trabalho pendente (6.10) — concluído.

---

## 8. Arquivos-Chave

### Configuração e ambiente
| Arquivo | Papel |
| --- | --- |
| `docker-compose.yml` | Serviços `app` (8080) e `db` (3306), volumes |
| `Dockerfile` | PHP 8.2 + Apache, extensões (`zip` incluída para PhpSpreadsheet) |
| `composer.json` | CI 4.7, `dompdf/dompdf ^3.1`, `phpoffice/phpspreadsheet ^5.10` |
| `.env` (não versionado) | Credenciais do banco, `CI_ENVIRONMENT` |
| `deploy.sh` / `docs/DEPLOY_VPS.md` | Deploy em VPS nativa |
| `app/Config/Filters.php` | Aliases `auth`, `role`, `superadmin`, `csrf` |
| `app/Config/Routes.php` | Rotas raiz + inclusão das rotas de módulo |

### Núcleo
| Arquivo | Papel |
| --- | --- |
| `app/Models/BaseModel.php` | Isolamento multi-tenant, `includeGlobalRows`, soft delete |
| `app/Filters/AuthFilter.php`, `RoleFilter.php`, `SuperAdminFilter.php` | RBAC |
| `app/Controllers/DashboardController.php` | KPIs + séries para Chart.js |
| `app/Modules/Core/Config/Routes.php` | Tenants, users, roles, settings (hub) |

### Protocolos e avaliações (foco atual)
| Arquivo | Papel |
| --- | --- |
| `app/Modules/Protocols/Services/ProtocolImporterInterface.php` | Contrato do payload normalizado |
| `app/Modules/Protocols/Services/VbMappImporter.php` | Parser do XLSX VB-MAPP (6 abas) |
| `app/Modules/Protocols/Services/AflsImporter.php` | Parser do XLSX AFLS (6 abas) |
| `app/Modules/Protocols/Services/ComunicacaoImporter.php` | Parser da Matriz de Comunicação (7 níveis) |
| `app/Modules/Protocols/Services/AcademicoImporter.php` | Parser da Avaliação Global Academia do Autismo (5 áreas) |
| `app/Modules/Protocols/Services/ProtocolImportWriter.php` | UPSERT transacional (nunca apaga) |
| `scripts/import_protocol.php` | Entry point CLI (`--dry-run`, `--file`, `--protocol`, `--tenant`) |
| `database/data/protocolos/*.xlsx` | Planilhas oficiais |
| `app/Database/Seeds/ComplexProtocolSeeder.php` | **Legado JSON — obsoleto** |
| `app/Modules/Protocols/Config/Routes.php` | `protocols.index`, `protocols.create`, `protocols.store` |
| `app/Modules/Protocols/Controllers/ProtocolController.php` | Builder dinâmico (Alpine) |
| `app/Modules/Assessments/Controllers/AssessmentController.php` | Execução da avaliação, `buildLevels()`, AJAX, PDF |
| `app/Modules/Assessments/Services/ScoreCalculatorService.php` | Score por item/habilidade/seção |
| `app/Modules/Assessments/Views/protocols/*.php` | Views customizadas por `view_slug` (vb_mapp, afls, comunicacao, academico) + PDF em `Views/pdf/` |
| `app/Modules/Plans/Controllers/PlanController.php` | Plano terapêutico a partir da avaliação |
| `app/Database/Migrations/2026-11-01-000039_AddComplexProtocolFields.php` | `max_score`, `metadata`, `view_slug`, `score_achieved` |
| `app/Database/Migrations/2026-11-01-000040_AddSectionToProtocolLevels.php` | `protocol_levels.section` |
| `docs/modules/complex_protocols.md` | Documentação do módulo (importação, schema, score) |

### Outros módulos
`app/Modules/Patients/` (CRUD, mural, jornada) · `app/Modules/Agenda/`
(DayPilot) · `app/Modules/Evolutions/` · `app/Modules/Assessments/` ·
`app/Modules/Protocols/` · `app/Modules/Plans/`.

---

## 9. Como retomar (checklist rápido)

1. `docker compose up -d` e confirmar http://localhost:8080.
2. `docker compose exec app php spark migrate:status` (checar drift 6.6).
3. `docker compose exec db mysql -umindtrack -pmindtrack mindtrack -e "SELECT id,name,view_slug FROM protocols; SELECT COUNT(*) FROM protocol_statuses;"`
   → deve mostrar VB-MAPP/AFLS e **status = 0** (pendência 6.1).
4. Abrir o módulo de protocolos/avaliações e confirmar o estado descrito na
   seção 3 antes de codar.
5. Começar pela Fase 1 do roadmap (seção 7).


