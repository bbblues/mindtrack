# Módulo de Agenda (Appointments)

## Regras de Negócio CRÍTICAS
1. **Soft Validation (Conflito de Horário):** Se o profissional já tiver um agendamento no mesmo horário, o sistema NÃO deve bloquear o salvamento. Ele deve retornar um aviso (JSON 409) e o frontend deve exibir um modal de confirmação (force_override).
2. **Recorrência:** Agendamentos recorrentes devem ser salvos como registros individuais no banco, compartilhando o mesmo `recurrence_group_id`.
3. **Duração Padrão:** A hora de fim é calculada com base em uma configuração global (ex: 40 min), mas pode ser alterada.

## Interface e Visualização (NOVO)
1. **Biblioteca:** Usar DayPilot Lite (ou DHTMLX Scheduler) via CDN. O FullCalendar não será mais usado.
2. **Visões:**
   - **Visão Dia:** Colunas representam os Profissionais. Linhas representam os Horários (ex: 08:00, 08:30).
   - **Visão Semana:** Linhas representam os Profissionais. Colunas representam os dias da semana (Seg a Dom).
   - A visão "Mês" deve ser removida.
3. **Cores do Paciente:** O fundo do card de agendamento deve ser a cor do paciente (cadastrada no módulo de Pacientes).
4. **Contraste do Texto:** A cor do texto do card deve ser preta ou branca, calculada dinamicamente com base na luminosidade da cor do paciente, para garantir leitura.
5. **Ordenação de Profissionais:** As colunas (visão dia) ou linhas (visão semana) devem permitir Drag-and-Drop para reordenar os profissionais. Essa ordem deve ser salva globalmente no banco de dados para a clínica.
6. **Drag-and-Drop de Agendamentos:** Os cards de agendamento podem ser arrastados para outros horários/dias/profissionais, passando pela Soft Validation.