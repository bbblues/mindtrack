# Módulo de Mural e Jornada do Paciente

## Entidades
- `patient_posts`: As publicações do Mural.
- `patient_milestones`: Os marcos da Jornada.

## 1. Mural do Paciente
- **Campos:** `tenant_id`, `patient_id`, `user_id` (quem postou), `content` (texto), `type` (Enum: 'evolution', 'communication', 'family', 'school'), timestamps + soft deletes.
- **Regras de Negócio:**
  - Funciona como um feed (mais recentes no topo).
  - O usuário logado pode criar uma publicação selecionando o Tipo.
  - Exibir o nome e o cargo de quem publicou.
  - Interface com Abas (Todos, Evolução, Comunicado, Família, Escola) para filtrar.

## 2. Jornada do Paciente
- **Campos:** `tenant_id`, `patient_id`, `title` (ex: "Início da Fonoaudiologia"), `description` (opcional), `event_date` (data do marco), `icon` (opcional), timestamps + soft deletes.
- **Regras de Negócio:**
  - Exibe uma linha do tempo (Timeline vertical) agrupada por Mês/Ano.
  - **Regra de Automação:** Ao criar um novo paciente no sistema, deve ser criado automaticamente um marco na Jornada com o título "Início do Tratamento" na data de admissão.
  - Permitir adicionar marcos manualmente.