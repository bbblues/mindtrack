# STATE OF PROJECT — MindTrack (ERP Clínico SaaS / ABA)

> **Documento de retomada.** Gerado a partir da leitura direta do código, das
> migrations e do banco de dados em execução (`mindtrack_db`).
> Última verificação: **01/11/2026**.
> Branch: `master` · último commit: `a5ac689` (`fix: corrige bug de deslocamento
> de mes (getMonth -1) no drag-and-drop da agenda semanal`).
>
> ⚠️ **Existe trabalho não commitado** no working tree (módulo de protocolos
> complexos). Ver seção 3.

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

---

## 3. O que está EM ANDAMENTO (incompleto)

### 3.1 Módulo de Protocolos Complexos (VB-MAPP / AFLS) — 🟡 ~70%

Todo o trabalho está **não commitado** (`git status`): migrations 39 e 40,
services de importação, seeder, script CLI, arquivos XLSX e
`docs/modules/complex_protocols.md` (vazio).

**O que já foi feito (validado no banco):**

| Entrega | Artefato |
| --- | --- |
| Migrations de schema | `2026-11-01-000039_AddComplexProtocolFields.php`, `2026-11-01-000040_AddSectionToProtocolLevels.php` |
| Novos campos | `protocol_items.max_score` (DECIMAL 5,2), `protocol_items.metadata` (JSON), `protocols.view_slug`, `patient_assessment_items.score_achieved` (DECIMAL 5,2), `protocol_levels.section`, `patient_assessments.score` → DECIMAL(10,2) |
| Models atualizados | `includeGlobalRows = true` em `ProtocolModel`, `ProtocolLevelModel`, `ProtocolSkillModel`, `ProtocolItemModel`, `ProtocolStatusModel` |
| Contrato de importação | `ProtocolImporterInterface` (payload normalizado) |
| Importadores | `VbMappImporter` (6 abas → 6 seções) e `AflsImporter` (6 abas → 6 seções, ignora "CONSOLIDADO") |
| Persistência | `ProtocolImportWriter` (UPSERT idempotente, nunca apaga) |
| CLI | `scripts/import_protocol.php` (`--dry-run`, `--file=`, `--protocol=`, `--tenant=`) |
| Planilhas | `database/data/protocolos/VB-MAPP preenchimento.xlsx`, `database/data/protocolos/AFLS-2.0_2a7b346d66e24d65a19361b906c12bec.xlsx` |
| Cálculo de score | `ScoreCalculatorService` com `max_score`, `score_achieved` e agregação `by_skill` / `by_section` |

**Estado real do banco após a importação (já executada):**

| Protocolo | ID | `tenant_id` | Seções | Habilidades | Itens |
| --- | --- | --- | --- | --- | --- |
| VB-MAPP | 4 | `NULL` (global) | 6 | 52 | 1.118 |
| AFLS | 5 | `NULL` (global) | 6 | 65 | 1.936 |

Total: **12 seções, 117 habilidades, 3.054 itens**.
Seções gravadas — VB-MAPP: *Marcadores, Barreiras, Transição, Rastreamento Nível
I, II e III*; AFLS: *VIDA BÁSICA, PART. COMUNITÁRIA, VIDA DOMÉSTICA, ESCOLARES,
VOCACIONAIS, VIDA INDEPENDENTE*.

**O que falta (bloqueadores):**

1. **`protocol_statuses` está vazio (0 registros)** — o `ProtocolImportWriter`
   não cria status; sem status, o select da tela de avaliação só tem
   "Nao avaliado" e a nota (`score_achieved`) fica sempre 0, pois o endpoint
   deriva o valor do status escolhido.
2. **Views customizadas não existem** — `protocols.view_slug` (`vb-mapp`,
   `afls`) não é lido em nenhum lugar do código;
   `AssessmentController::show()` sempre renderiza
   `App\Modules\Assessments\Views\show` (lista genérica em acordeões).
3. **`section` não é usada na UI** — `buildLevels()` não retorna `section`, logo
   as telas não agrupam por seção (essencial para achar a seção da planilha).
4. **Importação sem interface** — só via CLI; não há upload/tela administrativa.
5. **Legado conflitante** — `ComplexProtocolSeeder` ainda lê JSON (modelo
   abandonado) e faz *hard delete* dos níveis (`clearLevels`).
6. **`docs/modules/complex_protocols.md` está vazio** (0 bytes).
7. **Sem testes automatizados** para importadores/writer/score (existem apenas os
   testes de exemplo do CI4 em `tests/`).

---

## 4. Arquitetura de Protocolos Complexos

### 4.1 Modelo de dados unificado

Não existem tabelas dedicadas a VB-MAPP/AFLS. Os dois protocolos vivem nas
mesmas tabelas dos protocolos simples — a diferença é o volume, o
`view_slug` e os campos novos.

```
protocols                   (tenant_id NULL = protocolo global do sistema)
 ├─ id, name, description, view_slug, is_active
 └─ protocol_levels          (section = cabeçalho da planilha / área)
     ├─ id, protocol_id, name, section, sort_order
     └─ protocol_skills      (habilidade/objetivo do nível)
         ├─ id, level_id, name, description, sort_order
         └─ protocol_items   (item avaliável)
             ├─ id, skill_id, name, description, sort_order
             ├─ max_score    DECIMAL(5,2)  -- pontuação máxima do item
             └─ metadata     JSON          -- origem na planilha (sheet/area/qtde/cell)

protocol_statuses           (legenda de pontuação por protocolo: 0, 0.5, 1...)
 └─ id, protocol_id, name, value, color, sort_order

patient_assessments         (instância da avaliação de um paciente)
 ├─ id, tenant_id, patient_id, protocol_id, status, score DECIMAL(10,2)
 └─ patient_assessment_items (cópia congelada dos itens no início da avaliação)
     ├─ id, assessment_id, protocol_item_id
     ├─ value_at_time  -- legado (int)
     ├─ status_id      -- FK lógica para protocol_statuses
     └─ score_achieved DECIMAL(5,2)  -- nota efetiva (0..max_score)
```

**Consequências de projeto:**

- `patient_assessment_items` guarda `protocol_item_id` com `ON DELETE CASCADE`.
  Por isso o writer **nunca apaga** itens (só faz upsert): apagar a estrutura
  apagaria avaliações já feitas.
- `value_at_time` foi mantido como fallback; `score_achieved` é a fonte de
  verdade. `ScoreCalculatorService::resolveAchieved()` usa `score_achieved` e cai
  para `value_at_time` quando nulo.
- O mesmo service já calcula `by_section` (agrupando por
  `protocol_levels.section`) — o dado está pronto para as views customizadas.

### 4.2 Fluxo de importação (XLSX → banco)

```
database/data/protocolos/*.xlsx
        │  PhpSpreadsheet\IOFactory::load()
        ▼
VbMappImporter / AflsImporter   (implementam ProtocolImporterInterface)
        │  retornam payload normalizado:
        │  ['name' => 'VB-MAPP', 'view_slug' => 'vb-mapp',
        │   'levels' => [ ['name' => ..., 'section' => ...,
        │                  'skills' => [ ['name' => ...,
        │                    'items' => [ ['name' => ..., 'max_score' => 1.0,
        │                                  'metadata' => [...]]]]]]]
        ▼
ProtocolImportWriter::write($payload, $dryRun, $tenantId)
        │  transação única + UPSERT por chave natural
        ▼
protocols → protocol_levels → protocol_skills → protocol_items
```

**Chaves naturais do upsert (idempotência):**

| Nível | Chave |
| --- | --- |
| Protocolo | `name` + `view_slug` |
| Nível/Seção | `protocol_id` + `name` |
| Habilidade | `level_id` + `name` |
| Item | `skill_id` + `name` (truncado em 255 chars) |

**Regras do contrato (`ProtocolImporterInterface`):**

- `levels[].name` e `levels[].section` recebem o nome da aba/seção de origem
  (ex.: "Marcadores", "VIDA BÁSICA").
- Itens sem nome são contados como `items_skipped` (não geram erro).
- `metadata` preserva a rastreabilidade (`source_file`, `sheet`, `area`/`skill`,
  `qtde`, `milestone`, `cell`).
- Abas auxiliares são ignoradas (`CONSOLIDADO` no AFLS).
- Status (`protocol_statuses`) **não** são importados — devem ser criados
  separadamente (pendência 6.1).

**Como cada importador define `max_score` (comportamento atual):**

| Protocolo | Origem | Regra |
| --- | --- | --- |
| VB-MAPP | Fixo por seção no código | Marcadores = `1.0`; Barreiras = `4.0`; Transição = `5.0`; Rastreamento Níveis I/II/III = `1.0` |
| AFLS | Coluna **"Pontuação"** da planilha (3ª coluna do bloco da área) | Usa o valor da célula; `1.0` quando vazio/zero. Item `name` = código `"<ÁREA> <Qtde>"`, `description` = a tarefa |

> ⚠️ O VB-MAPP **não** lê a coluna "Qtde" para o teto de pontuação — o valor é
> fixo por seção. Se a planilha tiver pontuação variável por item, o importador
> precisa ser ajustado (item da Fase 1 do roadmap).

### 4.3 Pontuação

`ScoreCalculatorService::calculate(int $assessmentId)` retorna:

```php
[
  'total_score'    => float,
  'total_possible' => float,
  'percentage'     => float,
  'by_skill'       => [['level','skill','score','max','percentage'], ...],
  'by_section'     => [['section','score','max','percentage'], ...],
]
```

`achieved` é clampado em `[0, max_score]`; `by_section` agrupa por
`protocol_levels.section` com fallback `"Geral"` quando a seção é vazia.

**Como a nota é gravada (`assessments/update-item`):** o endpoint recebe
`status_id` e/ou `score_achieved`. O valor efetivo é
`score_achieved` (se enviado e numérico) ou, na ausência, `status.value`.
Validação: `0 <= score_achieved <= protocol_items.max_score`; após gravar, o
`score` da avaliação é recalculado e persistido.

### 4.4 Onde a view customizada vai entrar

`AssessmentController::show()` (genérico) já monta o payload completo
(avaliação, níveis, habilidades, itens). O ponto de extensão previsto é o
`protocols.view_slug`:

```php
$view = match ($protocol['view_slug'] ?? null) {
    'vb-mapp' => 'App\\Modules\\Assessments\\Views\\protocols\\vb_mapp',
    'afls'    => 'App\\Modules\\Assessments\\Views\\protocols\\afls',
    default   => 'App\\Modules\\Assessments\\Views\\show',
};
```

Esse *branch* **existe** desde a Fase 2: protocolos com `view_slug = 'vb-mapp'`
ou `'afls'` renderizam views dedicadas e recebem também os `scores` de
`ScoreCalculatorService::calculate()`, os `levels` (agora com `section` e com o
`sort_order` de cada item) e os `statuses`. Protocolos sem `view_slug` continuam
na view genérica `show.php`.

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
`protocols/vb_mapp.php` (matriz de marcos na seção Marcadores, matriz por passo
nas seções Rastreamento, tabela nas Barreiras/Transição) e
`protocols/afls.php` (tabela Qtde/Tarefa/Pontuação por área). Ambas usam abas
Alpine por `protocol_levels.section`, `x-show` para troca sem reload, gravação
célula a célula em `assessments/update-item` e atualização imediata do score
total e por seção (resposta traz `score`, `total_possible`, `percentage`,
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

### 6.7 🟠 Documentação do módulo vazia
`docs/modules/complex_protocols.md` existe com **0 bytes**. Deve documentar o
contrato do importador, o schema novo e o fluxo de reimportação.

### 6.8 🟡 Zero testes de domínio
`tests/` contém apenas os exemplos do framework (`ExampleDatabaseTest`,
`ExampleSessionTest`, `HealthTest`). Importadores, writer e
`ScoreCalculatorService` — que processam 3.054 itens — não têm cobertura alguma.
O `--dry-run` é a única validação disponível hoje.

### 6.9 🟡 Backup em produção é manual
`docs/DEPLOY_VPS.md` documenta `mysqldump` manual; não há cron/systemd timer nem
script versionado de backup/restore.

### 6.10 🟡 Trabalho não commitado
Migrations 39/40, `BaseModel`, `AssessmentController`, models de protocolos,
`composer.json`/`composer.lock` (PhpSpreadsheet), `Dockerfile`,
`scripts/import_protocol.php`, `ComplexProtocolSeeder`, planilhas XLSX e
`docs/modules/complex_protocols.md`. **Risco:** perda de trabalho e
impossibilidade de reproduzir o estado por outro ambiente.

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
3. Preencher `docs/modules/complex_protocols.md` (6.7).
4. Testes de `VbMappImporter`, `AflsImporter`, `ProtocolImportWriter` e
   `ScoreCalculatorService` (6.8).
5. Tela administrativa de protocolos (upload XLSX + status) e rotas de
   edição/exclusão.
6. Script de backup agendado na VPS (6.9).
7. Commit do trabalho pendente (6.10).

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
| `app/Modules/Protocols/Services/ProtocolImportWriter.php` | UPSERT transacional (nunca apaga) |
| `scripts/import_protocol.php` | Entry point CLI (`--dry-run`, `--file`, `--protocol`, `--tenant`) |
| `database/data/protocolos/*.xlsx` | Planilhas oficiais |
| `app/Database/Seeds/ComplexProtocolSeeder.php` | **Legado JSON — obsoleto** |
| `app/Modules/Protocols/Config/Routes.php` | `protocols.index`, `protocols.create`, `protocols.store` |
| `app/Modules/Protocols/Controllers/ProtocolController.php` | Builder dinâmico (Alpine) |
| `app/Modules/Assessments/Controllers/AssessmentController.php` | Execução da avaliação, `buildLevels()`, AJAX, PDF |
| `app/Modules/Assessments/Services/ScoreCalculatorService.php` | Score por item/habilidade/seção |
| `app/Modules/Assessments/Views/show.php` | **View genérica atual** (sem branch por `view_slug`) |
| `app/Modules/Plans/Controllers/PlanController.php` | Plano terapêutico a partir da avaliação |
| `app/Database/Migrations/2026-11-01-000039_AddComplexProtocolFields.php` | `max_score`, `metadata`, `view_slug`, `score_achieved` |
| `app/Database/Migrations/2026-11-01-000040_AddSectionToProtocolLevels.php` | `protocol_levels.section` |
| `docs/modules/complex_protocols.md` | **Vazio — a ser escrito** |

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


