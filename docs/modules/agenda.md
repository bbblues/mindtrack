# Módulo de Agenda (Appointments)

## Regras de Negócio CRÍTICAS
1. **Soft Validation (Conflito de Horário):** Se o profissional já tiver um agendamento no mesmo horário, o sistema NÃO deve bloquear o salvamento. Ele deve retornar um aviso (JSON 409) e o frontend deve exibir um modal de confirmação (force_override).
2. **Recorrência:** Agendamentos recorrentes devem ser salvos como registros individuais no banco, compartilhando o mesmo `recurrence_group_id`.
3. **Duração Padrão:** A hora de fim é calculada com base em uma configuração global (ex: 40 min), mas pode ser alterada.
4. **Timezone:** As datas e horas salvas no banco e retornadas pela API devem ser no formato local (sem o sufixo `Z` de UTC) para garantir que o DayPilot exiba o horário correto sem deslocamento.
5. **Agenda Livre:** A agenda não possui restrições de horário de funcionamento ou dias fechados. Ela exibirá sempre 24 horas por dia, 7 dias por semana.

## Interface e Visualização
1. **Biblioteca:** Usar DayPilot Lite via CDN. O FullCalendar não é mais utilizado.
2. **Controle de Renderização:** Apenas uma visão (Dia OU Semana) deve ser renderizada por vez. Usar Alpine.js para destruir (`dispose()`) o calendário inativo e inicializar o ativo.
3. **Barra de Rolagem:** O calendário NÃO deve ter barra de rolagem interna. A rolagem deve ser apenas a da página principal do navegador.
4. **Ordenação de Profissionais:** A ordem das colunas (visão Dia) e linhas (visão Semana) é definida pelo campo `agenda_order` (peso) na tela de Configurações. A barra de chips arrastáveis foi removida da Agenda.

## Estilo dos Cards de Agendamento
1. **Fundo:** O fundo do card deve ser a cor do paciente (`patient.color`).
2. **Texto:** A fonte deve ser **negrito**. A cor do texto deve ser preta ou branca, calculada via `PatientColor::getContrastText()`.
3. **Padding:** O padding interno do card deve ser ligeiramente aumentado (ex: 4px ou 6px) para dar mais respiro ao texto.
4. **Bordas e Elementos Laterais:** O card deve ser exibido **sem borda** (border: none). Se possível, remover a barra lateral colorida padrão do DayPilot para que o card seja um bloco único de cor.
5. **Conteúdo do Card:** Na visão Semana, o card deve exibir o nome do paciente e o horário (ex: "08:00 - 08:40").

## Lógica de Navegação
### Visão Dia
- **Layout:** Colunas representam os Profissionais. Linhas representam os Horários (24h).
- **Navegação:** Botão `<` (Dia Anterior), uma barra com 10 botões de datas (do dia atual até +9 dias à frente), e botão `>` (Próximo Dia). 

### Visão Semana
- **Layout:** Linhas representam os Profissionais. Colunas representam os dias da semana, **sempre de Domingo a Sábado** (garantir `weekStarts: 0`).
- **Navegação:** Botão `< Semana Anterior` (subtrai 7 dias), botão `Semana Atual` (retorna ao Domingo da semana corrente), e botão `Próxima Semana >` (adiciona 7 dias).

## Interações
1. **Drag-and-Drop de Agendamentos:** Os cards podem ser arrastados para outros horários/dias/profissionais. A troca de profissional e horário deve ser persistida no banco. A validação de conflito (Soft Validation) deve ser acionada.
2. **Clique no Card:** Abre o modal de detalhes com a opção "Registrar Evolução".