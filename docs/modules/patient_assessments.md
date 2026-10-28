# Módulo de Avaliações de Pacientes (Patient Assessments)

## Entidades
- `patient_assessments`: A avaliação de um paciente X com um protocolo Y. Guarda o score total, status (Em Andamento, Finalizada), data de início/fim.
- `patient_assessment_items`: O status atual de cada item do protocolo para aquele paciente (guarda o `status_id` escolhido, o valor numérico do status no momento e a data da alteração).

## Regras de Negócio CRÍTICAS
1. **Vínculo:** Ao iniciar uma avaliação, o sistema copia a estrutura do protocolo ou apenas referencia? A melhor prática é referenciar o `protocol_id` na `patient_assessments` e criar os `patient_assessment_items` vazios (status_id = null) para cada `protocol_item` do protocolo.
2. **Cálculo de Score:** O score total é a soma dos valores (`protocol_statuses.value`) dos itens respondidos. O sistema deve atualizar esse score a cada item marcado.
3. **Interface de Execução (O "Santo Graal"):** 
   - Deve ter um Modo Visualização (apenas leitura, como nos prints).
   - Deve ter um Modo Edição (onde o terapeuta marca os status).
   - A tela deve listar os Níveis, Habilidades e Itens (Acordeões).
   - No Modo Edição, cada item deve ter um select (ou botões) com os status do protocolo (ex: Certo, Errado, Não Avaliado).
   - Ao mudar o status, deve salvar via AJAX e atualizar o Score na tela em tempo real.