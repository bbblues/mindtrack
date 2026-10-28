# Módulo de Evolução de Sessão (Evolutions)

## Entidades
- `session_evolutions`: O registro da sessão. Vinculado a `appointment_id`, `patient_id` e `professional_id`. Contém um campo de texto (evolution_text) ou notas estruturadas.
- `evolution_targets` (Opcional para o futuro): Para marcar as tentativas do Plano Terapêutico.

## Regras de Negócio CRÍTICAS
1. **Vínculo com a Agenda:** A evolução é criada a partir de um agendamento. O status do agendamento muda para 'Realizado' quando a evolução é salva.
2. **Interface:** Na Agenda, ao clicar em um card, deve aparecer a opção "Registrar Evolução". Na tela de pacientes, deve haver uma aba "Histórico" mostrando o feed de evoluções.
3. **Template de Nota:** Na tela de criação, o terapeuta pode escolher um "Modelo de Nota" (que já temos na tabela `note_templates`) para preencher o texto padrão e depois editar.