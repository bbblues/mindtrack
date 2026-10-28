# Módulo de Configurações Avançadas (Settings)

## Entidades Gerenciáveis
- `units`: Unidades da clínica.
- `specialties`: Especialidades (Fono, Psico, etc).
- `health_insurances`: Convênios.
- `rooms`: Salas (vinculadas a uma unidade).
- `tags`: Tags coloridas.
- `absence_reasons` e `discharge_reasons`: Motivos de falta/alta.
- `note_templates`: Modelos de evolução.

## Regras de Negócio CRÍTICAS
1. **Isolamento Multi-Tenant:** Todos os cadastros DEVEM ser vinculados ao `tenant_id` do usuário logado. Um usuário da Clínica A não pode ver os cadastros da Clínica B.
2. **Interface Central:** Uma tela de "Configurações" com um grid de cards visuais (como no sistema de referência). Ao clicar em um card, abre-se a tela de gestão daquele item.
3. **CRUD Rápido:** Os cadastros são simples (geralmente só Nome/Cor). Devem usar modais com Alpine.js e Fetch API para criar/editar sem recarregar a página.

## Prioridade de Implementação (Para este Sprint)
1. Tela Central (`/settings`).
2. CRUD de Unidades.
3. CRUD de Especialidades.
4. CRUD de Convênios.

## Prioridade de Implementação (Sprint 15 - Finalizando Configurações)
5. CRUD de Salas (vinculadas a uma Unidade).
6. CRUD de Tags (Nome e Cor).
7. CRUD de Motivos de Falta e Motivos de Alta.
8. CRUD de Modelos de Nota (Nome e Texto Padrão).