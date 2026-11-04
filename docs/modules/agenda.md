# Módulo de Agenda (Appointments)

## Regras de Negócio CRÍTICAS
1. **Soft Validation (Conflito de Horário):** Se o profissional já tiver um agendamento no mesmo horário, o sistema NÃO deve bloquear o salvamento. Ele deve retornar um aviso (JSON 409) e o frontend deve exibir um modal de confirmação (force_override).
2. **Recorrência:** Agendamentos recorrentes devem ser salvos como registros individuais no banco, compartilhando o mesmo `recurrence_group_id`.
3. **Duração Padrão:** A hora de fim é calculada com base em uma configuração global (ex: 40 min), mas pode ser alterada.
4. **Timezone:** As datas e horas salvas no banco e retornadas pela API devem ser no formato local (sem o sufixo `Z` de UTC) para garantir que o DayPilot exiba o horário correto sem deslocamento.
5. **Disponibilidade por profissional:** Cada profissional (usuário com cargo "Profissional") tem uma configuração de agenda com os dias da semana e horários em que atende. Na grade da agenda, os períodos fora dessa disponibilidade (e as datas de feriado/fechamento) são destacados como inativos.
6. **Impedimentos (soft validation):** Ao tentar marcar um compromisso fora da disponibilidade do profissional ou em um feriado/data de fechamento, o sistema NÃO deve bloquear. Ele retorna `409 {impediment: true, reasons}` e o frontend abre um modal explicando o motivo e perguntando se deseja continuar (`force_override`).
7. **Recorrência:** Em agendamentos recorrentes, o checkbox "Ignorar datas com impedimentos" fica marcado por padrão. Quando marcado, ocorrências fora da agenda/feriado são descartadas silenciosamente; quando desmarcado, os impedimentos seguem o fluxo de confirmação.
8. **Feriados:** Feriados nacionais são sincronizados da BrasilAPI para o banco local (cache). Feriados municipais/estaduais e datas de fechamento são cadastrados manualmente em Configurações, com uma badge (rótulo + cor).
9. **Horário de funcionamento da clínica:** A clínica possui um horário de funcionamento (um turno por dia da semana, podendo haver dias fechados). A agenda colapsa o eixo de horários para essa janela útil e destaca como inativo tudo que estiver fora dela (ou em dias fechados). Agendamentos fora do horário da clínica também geram impedimento (soft validation), assim como feriados e indisponibilidade do profissional.

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
1. **Drag-and-Drop na Visão Dia:** O comportamento é **livre e direto**. Arrastar o card para qualquer lugar (mudando horário ou profissional) **salva imediatamente** via AJAX, sem abrir modal. A Soft Validation é aplicada.
2. **Drag-and-Drop na Visão Semana:**
   - **Movimentação Horizontal (Troca de Dia):** Ao arrastar o card para outro dia, o sistema deve **abrir um modal (popup)**.
     - O modal deve exibir a nova data e permitir que o usuário ajuste a **Hora de Início** e **Hora de Fim** (pré-preenchidas com o horário original).
     - Se o usuário confirmar, envia os dados via AJAX e o card deve aparecer na nova posição.
     - Se o usuário cancelar, o card retorna à posição original.
   - **Movimentação Vertical (Troca de Profissional):** Também deve **abrir o modal** para ajuste de horário, pois a linha do profissional mudou.
3. **Clique no Card:** Abre o modal de detalhes com a opção "Registrar Evolução".