# Módulo de Usuários e RBAC (Users)

## Entidades
- `users`: Funcionários da clínica.
- `roles`: Cargos (Admin, Gestor, Profissional, Recepção). **Agora são isolados por tenant.**
- `permissions`: Permissões granulares.
- `role_user`, `permission_role`: Tabelas pivô.

## Regras de Negócio CRÍTICAS
1. **Isolamento de Cargos:** Cada clínica tem seus próprios cargos. A Clínica A não vê os cargos da Clínica B.
2. **Criação Automática:** Ao cadastrar uma nova Clínica (Tenant), o sistema deve criar automaticamente os 4 cargos padrão para ela: `Admin`, `Gestor`, `Profissional` e `Recepção`.
3. **Cargos Customizados:** O gestor da clínica pode criar cargos adicionais (ex: "Estagiário", "Supervisor") que ficarão vinculados apenas ao `tenant_id` dele.
4. **Interface:** 
   - Tela de Gestão de Cargos com listagem e botão "Novo Cargo".
   - Formulário simples (Apenas Nome do Cargo).