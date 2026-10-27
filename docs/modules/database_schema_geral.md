# Estrutura do Banco de Dados (Visão Macro)

## Core
- `tenants`: Clínicas/Clientes.
- `users`: Usuários (tenant_id, is_super_admin, is_active).
- `roles`, `permissions`, `role_user`, `permission_role`: Controle de Acesso (RBAC).
- `modules`, `module_tenant`: Feature Flags (quais módulos a clínica comprou).
- `settings`: Configurações chave-valor.

## Configurações Básicas
- `units`: Unidades da clínica.
- `rooms`: Salas.
- `specialties`: Especialidades.
- `tags`: Tags coloridas.
- `health_insurances`: Convênios.
- `holidays`: Feriados.
- `absence_reasons`, `discharge_reasons`: Motivos de falta/alta.
- `note_templates`: Modelos de evolução.

## Pacientes e Clínico
- `patients`: Dados do paciente (tenant_id, nome, cpf, diagnóstico, etc).
- `guardians`: Responsáveis.
- `patient_guardians`: Tabela pivô (is_primary, is_financial).
- `waiting_list`: Lista de espera.

## Regras de Ouro
1. Toda chave estrangeira deve ter `ON DELETE CASCADE` (exceto onde o histórico for crítico).
2. Sempre crie índices (`KEY`) para colunas de busca frequente (nome, cpf, tenant_id).