# Módulo de Pacientes (Patients)

## Entidades
- `patients`: O paciente.
- `guardians`: Os responsáveis (Pai, Mãe, etc).
- `patient_guardians`: Pivô (is_primary, is_financial).

## Campos do Paciente
- **Dados Pessoais:** Nome, Data de Nascimento, CPF, Sexo, Status (Ativo/Inativo).
- **Dados Clínicos:** Diagnóstico, Nível de Suporte, Alergias.
- **Vínculos:** Unidade, Convênio, Número da Carteirinha, Tags.
- **Endereço:** CEP (com autocomplete via ViaCEP), Rua, Número, etc.

## Regras de Negócio Críticas
1. **SoftDeletes:** Pacientes nunca devem ser deletados do banco, apenas inativados.
2. **Responsáveis:** Um paciente pode ter múltiplos responsáveis (marcar um como Principal e um como Financeiro).
3. **Obrigatoriedade:** Nome, Data de Nascimento e Unidade são obrigatórios.
4. **Interface:** Tabela com busca (nome/cpf), filtros e paginação. O formulário deve usar Abas (Tabs) para organizar Pessoal, Clínico, Endereço e Responsáveis.

## Identificação Visual (Cores)
- Cada paciente deve ter uma cor (`color` - string HEX) para identificação visual na Agenda.
- **Regra de Atribuição:** Ao criar um paciente, o sistema sorteia uma cor de uma paleta fixa de 30 cores contrastantes. O sistema deve priorizar cores que ainda não estão em uso naquela clínica (tenant).
- **Edição:** A cor pode ser alterada manualmente por qualquer usuário com permissão de edição de paciente, usando um `<input type="color">` no formulário.

## Paginação e Filtros
1. **Paginação:** A listagem deve exibir **50 pacientes por página**.
2. **Estilo da Paginação:** Os links de paginação devem ser estilizados com Tailwind CSS (horizontal, botões arredondados, sem quebra de linha vertical).
3. **Filtro de Inativos:** Por padrão, a listagem deve ocultar pacientes com `is_active = 0`. Deve haver um checkbox/toggle no topo da tabela chamado "Mostrar Inativos" que, ao ser marcado, recarrega a lista trazendo todos.
4. **Busca Global:** A barra de busca deve procurar por nome ou CPF em **todos** os pacientes (ativos e inativos), ignorando o filtro de inativos enquanto houver um termo de busca ativo.
5. **Preservação de Estado:** O termo de busca, o estado do checkbox "Mostrar Inativos" e a paginação devem ser preservados na URL (query string) para não se perderem ao navegar entre as páginas.