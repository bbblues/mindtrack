# Módulo Core (Gestão de Clientes e Acesso)

## Regras de Negócio
1. O Super Admin (dono do SaaS) tem `is_super_admin = true` e `tenant_id = null`.
2. Ao criar um novo Tenant (Clínica), o sistema deve vincular automaticamente os módulos base (`core`, `patients`, `agenda`) na tabela `module_tenant`.
3. A matriz de permissões deve ser flexível, permitindo que o Gestor crie novos cargos (roles) e marque as permissões (permissions) via interface.
4. **Isolamento:** Um usuário da Clínica A jamais pode ver dados da Clínica B. O `BaseModel` deve forçar isso.