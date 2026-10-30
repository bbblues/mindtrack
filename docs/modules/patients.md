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