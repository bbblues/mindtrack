# Módulo de Protocolos Complexos (VB-MAPP, AFLS, Comunicação, Academia)

> Documenta a importação e a avaliação de protocolos estruturados que chegam em
> planilhas oficiais (XLSX). Reusa as mesmas tabelas dos protocolos simples
> (`protocols` → `protocol_levels` → `protocol_skills` → `protocol_items` →
> `protocol_statuses`) — a diferença é o volume, o `view_slug` e os campos
> `max_score`, `metadata` e `section`.

## Status

- ✅ Importação XLSX via PhpSpreadsheet para 4 protocolos
- ✅ Modelo de dados unificado + `view_slug` por protocolo
- ✅ Views customizadas (VB-MAPP, AFLS, Comunicação, Academia) + PDF por protocolo
- ✅ Cálculo de score (`ScoreCalculatorService`) com `max_score` / `score_achieved`
- 🟡 `protocol_statuses` não é criado automaticamente pela importação
- 🟡 Importação apenas via CLI (sem tela de upload)
- 📌 Tela administrativa de protocolos (upload + status)

## Protocolos importados

| Protocolo | `view_slug` | Níveis/Seções | Fonte (XLSX) | Regra de `max_score` |
| --- | --- | --- | --- | --- |
| VB-MAPP | `vb-mapp` | 6 | `VB-MAPP preenchimento.xlsx` | Fixo por seção: Marcadores/Rastreamento = `1.0`, Barreiras = `4.0`, Transição = `5.0` |
| AFLS | `afls` | 6 | `AFLS-2.0_*.xlsx` | Coluna **"Pontuação"** da planilha (default `1.0`) |
| Matriz de Comunicação | `comunicacao` | 7 | `MATRIZ DE COMUNICAÇÃO EXCEL.xlsx` | Fixo `2.0` (NÃO OBSERVADO / EMERGENTE / DOMINADO) |
| Avaliação Global Academia do Autismo | `academico` | 5 | `MODELO AVALIAÇÃO GLOBAL ACADEMIA DO AUTISMO.xlsx` | Fixo `2.0` (0 = NÃO REALIZA, 1 = EMERGENTE, 2 = REALIZA) |

> Protocolos importados são **globais** (`tenant_id = NULL`): pertencem ao
> sistema, não à clínica. Os models de protocolo declaram
> `includeGlobalRows = true`, e o `BaseModel` gera
> `(tenant_id = X OR tenant_id IS NULL)`.

## Modelo de dados unificado

Não existem tabelas dedicadas a cada protocolo. Todos vivem nas tabelas dos
protocolos simples:

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

- `patient_assessment_items.protocol_item_id` tem `ON DELETE CASCADE`. Por isso
  o writer **nunca apaga** itens (só faz upsert): apagar a estrutura apagaria
  avaliações já feitas.
- `value_at_time` foi mantido como fallback; `score_achieved` é a fonte de
  verdade. `ScoreCalculatorService::resolveAchieved()` usa `score_achieved` e
  cai para `value_at_time` quando nulo.
- O mesmo service calcula `by_section` (agrupando por `protocol_levels.section`).

## Fluxo de importação (XLSX → banco)

```
database/data/protocolos/*.xlsx
        │  PhpSpreadsheet\IOFactory::load()
        ▼
VbMappImporter / AflsImporter / ComunicacaoImporter / AcademicoImporter
        │  (implementam ProtocolImporterInterface)
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
- Abas auxiliares são ignoradas (ex.: `CONSOLIDADO` no AFLS).
- Status (`protocol_statuses`) **não** são importados — devem ser criados
  separadamente (ver Pendências).

## Regras de `max_score` por importador

| Protocolo | Origem do teto | Regra |
| --- | --- | --- |
| VB-MAPP | Fixo por seção no código | Marcadores = `1.0`; Barreiras = `4.0`; Transição = `5.0`; Rastreamento Níveis I/II/III = `1.0` |
| AFLS | Coluna **"Pontuação"** (3ª coluna do bloco da área) | Usa o valor da célula; `1.0` quando vazio/zero. Item `name` = `"<ÁREA> <Qtde>"`, `description` = a tarefa |
| Comunicação | Fixo no código | `2.0` para todo item (NÃO OBSERVADO / EMERGENTE / DOMINADO) |
| Academia | Fixo no código | `2.0` para toda tarefa (0/1/2) |

> ⚠️ O VB-MAPP **não** lê a coluna "Qtde" para o teto de pontuação — o valor é
> fixo por seção. Se a planilha tiver pontuação variável por item, o importador
> precisa ser ajustado.

## Pontuação (`ScoreCalculatorService`)

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

- `achieved` é clampado em `[0, max_score]`.
- `by_section` agrupa por `protocol_levels.section` com fallback `"Geral"`.
- Cálculo geral: `sum(score_achieved) / sum(max_score)`.

**Como a nota é gravada (`assessments/update-item`):** o endpoint recebe
`status_id` e/ou `score_achieved`. O valor efetivo é `score_achieved` (se
enviado e numérico) ou, na ausência, `status.value`. Validação:
`0 <= score_achieved <= protocol_items.max_score`; após gravar, o `score` da
avaliação é recalculado e persistido.

## Views customizadas (por `view_slug`)

`AssessmentController::show()` escolhe a view a partir de `protocols.view_slug`:

```php
$view = match ($protocol['view_slug'] ?? null) {
    'vb-mapp'     => 'App\Modules\Assessments\Views\protocols\vb_mapp',
    'afls'        => 'App\Modules\Assessments\Views\protocols\afls',
    'comunicacao' => 'App\Modules\Assessments\Views\protocols\comunicacao',
    'academico'   => 'App\Modules\Assessments\Views\protocols\academico',
    default       => 'App\Modules\Assessments\Views\show',
};
```

Cada view dedicada usa abas Alpine por `protocol_levels.section`, `x-show` para
troca sem reload, gravação célula a célula em `assessments/update-item` e
atualização imediata do score total e por seção (a resposta traz `score`,
`total_possible`, `percentage`, `by_skill` e `by_section`). Há também um PDF
por protocolo em `App\Modules\Assessments\Views\pdf\`.

## CLI de importação

```bash
docker compose exec app php scripts/import_protocol.php --dry-run
docker compose exec app php scripts/import_protocol.php --protocol=vb-mapp
docker compose exec app php scripts/import_protocol.php --file="VB-MAPP preenchimento.xlsx"
```

Opções: `--dry-run` (simula, nada grava), `--file=` (um XLSX específico),
`--protocol=vb-mapp|afls|comunicacao|academico`, `--tenant=ID`.

Sem `--file`, processa todos os `.xlsx` de `database/data/protocolos/` e
detecta o tipo pelo nome do arquivo. O import é idempotente (upsert) e **nunca**
apaga registros.

## Pendências (dívida técnica)

- 🟡 `protocol_statuses` não é criado pelo writer; as views complexas pontuam
  exclusivamente via `score_achieved` (não dependem de status), mas a view
  genérica e o fluxo manual ainda dependem.
- 🟡 Importação só por CLI; sem tela de upload/administração.
- 🟡 `ComplexProtocolSeeder` (legado JSON) está obsoleto e faz *hard delete*.
- 🟡 O builder manual (`protocols/create`) não preenche `max_score`, `metadata`
  nem `section`.
- 📌 Testes automatizados para importadores/writer/score.



