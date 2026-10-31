# Módulo de Agenda (Appointments)

## Regras de Negócio CRÍTICAS
1. **Soft Validation (Conflito de Horário):** Se o profissional já tiver um agendamento no mesmo horário, o sistema NÃO deve bloquear o salvamento. Ele deve retornar um aviso (JSON 409) e o frontend deve exibir um modal de confirmação (force_override).
2. **Recorrência:** Agendamentos recorrentes devem ser salvos como registros individuais no banco, compartilhando o mesmo `recurrence_group_id`.
3. **Duração Padrão:** A hora de fim é calculada com base em uma configuração global (ex: 40 min), mas pode ser alterada.

## Interface e Visualização
1. **Biblioteca:** Usar DayPilot Lite via CDN. O FullCalendar não é mais utilizado.
2. **Controle de Renderização:** Apenas uma visão (Dia OU Semana) deve ser renderizada por vez. Usar Alpine.js para destruir (`dispose()`) o calendário inativo e inicializar o ativo, evitando sobreposição de grids.
3. **Barra de Rolagem:** O calendário NÃO deve ter barra de rolagem interna. Usar `heightSpec: "Full"` para que a rolagem seja apenas a da página do navegador.

## Lógica de Navegação
A navegação é customizada e se adapta à visão selecionada:

### Visão Dia
- **Layout:** Colunas representam os Profissionais. Linhas representam os Horários.
- **Navegação:** Botão `<` (Dia Anterior), uma barra com 10 botões de datas (do dia atual até +9 dias à frente, ex: [31/10] [01/11] ...), e botão `>` (Próximo Dia). Ao clicar em um botão de data, a agenda pula para aquele dia.

### Visão Semana
- **Layout:** Linhas representam os Profissionais. Colunas representam os dias da semana, **sempre de Domingo a Sábado** (garantir `firstDayOfWeek: 0`).
- **Navegação:** Botão `< Semana Anterior` (subtrai 7 dias), botão `Semana Atual` (retorna ao Domingo da semana corrente), e botão `Próxima Semana >` (adiciona 7 dias).

## Cores e Cards
1. **Cores do Paciente:** O fundo do card de agendamento deve ser a cor do paciente (`patient.color`).
2. **Contraste do Texto:** A cor do texto do card deve ser preta ou branca, calculada via `PatientColor::getContrastText()` no backend.
3. **Conteúdo do Card:** Na visão Semana, o card deve exibir o nome do paciente e o horário (ex: "08:00 - 08:40").

## Interações
1. **Ordenação de Profissionais:** As colunas (visão dia) ou linhas (visão semana) devem permitir Drag-and-Drop para reordenar os profissionais. Essa ordem será salva globalmente no banco de dados (implementação no próximo sprint).
2. **Drag-and-Drop de Agendamentos:** Os cards podem ser arrastados para outros horários/dias/profissionais, passando pela Soft Validation. A troca de profissional também deve ser persistida no banco.