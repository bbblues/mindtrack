# Módulo de Configurações Avançadas (Settings)

## Entidades Gerenciáveis
- `units`: Unidades da clínica.
- `specialties`: Especialidades.
- `health_insurances`: Convênios.
- `rooms`: Salas (vinculadas a uma unidade).
- `tags`: Tags coloridas.
- `absence_reasons` e `discharge_reasons`: Motivos de falta/alta.
- `note_templates`: Modelos de evolução.
- `users`: Funcionários da clínica (equipe).
- `roles`: Cargos e permissões.
- `professional_schedules`: Dias e horários de atendimento de cada profissional.
- `clinic_schedules`: Dias e horários de funcionamento da clínica (um turno por dia; dias ausentes = fechado).
- `holidays` e `cities`: Feriados nacionais (sincronizados da BrasilAPI), feriados locais/datas de fechamento e a cidade da clínica.

## Regras de Negócio CRÍTICAS
1. **Isolamento Multi-Tenant:** Todos os cadastros DEVEM ser vinculados ao `tenant_id` do usuário logado.
2. **Interface Central:** Uma tela de "Configurações" com um grid de cards visuais. Ao clicar em um card, abre-se a tela de gestão daquele item.
3. **CRUD Rápido:** Os cadastros devem usar modais com Alpine.js e Fetch API para criar/editar sem recarregar a página.
4. **Equipe e Acessos:** O gerenciamento de usuários e cargos é feito dentro de Configurações, reaproveitando os controllers existentes.

## Ordenação de Profissionais (Peso)
- **Objetivo:** Definir a ordem em que as colunas (Visão Dia) e linhas (Visão Semana) dos profissionais aparecem na Agenda.
- **Regras:**
  - Na tela de listagem de Usuários (equipe), adicionar um campo numérico chamado **"Ordem na Agenda"** (peso).
  - O administrador pode digitar o número (ex: 1, 2, 3...) para cada profissional.
  - A Agenda buscará os profissionais ordenados por esse campo de forma ascendente (`ORDER BY agenda_order ASC`).
  - Profissionais sem o campo preenchido vão para o final da lista.
- **Interface:** Um input numérico no formulário de criação/edição de usuário.