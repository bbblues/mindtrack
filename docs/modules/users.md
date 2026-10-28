# Módulo de Usuários e RBAC (Users)

## Entidades
- `users`: Funcionários da clínica.
- `roles`: Cargos (Admin, Gestor, Profissional, Recepção).
- `permissions`: Permissões granulares (ex: patients.view, agenda.edit).
- `role_user`, `permission_role`: Tabelas pivô.

## Regras de Negócio CRÍTICAS
1. **Isolamento:** Um gestor da Clínica A só pode ver e criar usuários para a Clínica A.
2. **Criação de Usuário:** Ao criar um usuário, o sistema deve vinculá-lo automaticamente ao `tenant_id` do usuário logado. O Super Admin pode criar usuários para qualquer clínica (opcionalmente).
3. **Senhas:** A senha deve ser hasheada (bcrypt).
4. **Interface:** 
   - Listagem de usuários (Nome, E-mail, Cargo, Status).
   - Formulário de criação/edição (Nome, E-mail, Senha, Unidade, Cargos/Roles).