# Módulo de Plano Terapêutico (Therapeutic Plans)

## Entidades
- `therapeutic_plans`: O plano de um paciente. Guarda o status (Ativo, Arquivado), data de início e fim.
- `plan_items`: Os objetivos do plano. Podem ser vinculados a um `protocol_item_id` (vindo de uma avaliação) ou ser um texto livre (meta customizada).

## Regras de Negócio CRÍTICAS
1. **Finalização da Avaliação:** Na tela de execução da avaliação, deve haver um botão "Finalizar Avaliação" que muda o status para 'completed' e trava a edição dos itens.
2. **Criação do Plano:** O terapeuta deve poder selecionar os itens "Não Avaliados/Errados" da avaliação e enviá-los para o Plano Terapêutico com um clique (ou adicionar manualmente).
3. **Interface:** A tela do Plano Terapêutico deve ter uma tabela listando as metas, com colunas de Nível, Habilidade, Objetivo, Status (Ex: Em Andamento, Adquirido) e botões de ação.