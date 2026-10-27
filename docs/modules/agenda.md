# Módulo de Agenda (Appointments)

## Entidades
- `appointments`: O agendamento em si.
- `professional_schedules`: A disponibilidade de trabalho do profissional.

## Campos do Agendamento
- **Vínculos:** `patient_id` (FK), `professional_id` (FK), `room_id` (FK, nullable), `unit_id` (FK).
- **Tempo:** `start_time` (datetime), `end_time` (datetime).
- **Status:** Enum ('agendado', 'confirmado', 'realizado', 'falta_paciente', 'falta_profissional', 'cancelado'). Padrão: 'agendado'.
- **Recorrência:** `is_recurring` (boolean), `recurrence_group_id` (string/uuid, nullable) para agrupar os agendamentos gerados juntos.
- **Observações:** `notes` (text).

## Regras de Negócio CRÍTICAS
1. **Soft Validation (Conflito de Horário):** Se o profissional já tiver um agendamento no mesmo horário, o sistema NÃO deve bloquear o salvamento. Ele deve retornar um aviso (JSON 409) com os detalhes do conflito, e o frontend deve exibir um modal perguntando: "Deseja continuar mesmo assim?". Se o usuário confirmar, o frontend envia um parâmetro `force_override = true` e o backend salva.
2. **Recorrência:** Ao criar um agendamento recorrente (ex: semanal), o sistema deve gerar registros individuais no banco de dados para cada dia (não usar eventos virtuais). Todos devem compartilhar o mesmo `recurrence_group_id`.
3. **Duração Padrão:** O sistema deve ler uma configuração global (ou da unidade) para pré-preencher a hora de fim (ex: 40 minutos). O usuário pode alterar.
4. **Interface (Frontend):** 
   - Visualização em Semana e Dia (Grid). 
   - Cards coloridos de acordo com o status (Ex: Azul=Agendado, Verde=Confirmado/Realizado, Vermelho=Falta).
   - As cores devem ser aplicadas via classes CSS do Tailwind.
   - Deve ser implementado Drag-and-Drop para mover os cards de horário/profissional usando a biblioteca SortableJS (via CDN).