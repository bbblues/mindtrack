# Módulo Super Admin (Gestão do SaaS)

## Entidades
- `tenants`: As clínicas clientes.

## Regras de Negócio CRÍTICAS
1. **Acesso Restrito:** Apenas usuários com `is_super_admin = 1` podem acessar esse módulo.
2. **Criação de Clínica:** Ao cadastrar uma nova clínica, o sistema deve obrigatoriamente criar o Tenant e vincular automaticamente os módulos base (`core`, `patients`, `agenda`) na tabela `module_tenant`.
3. **Gestão de Módulos:** O Super Admin deve poder ativar/desativar módulos específicos para cada clínica (Feature Flags).
4. **Interface:** 
   - Uma listagem de Clínicas (Tenants).
   - Um formulário com os dados da clínica e checkboxes para os módulos.
   - O menu lateral deve exibir o link "Gestão SaaS" APENAS para o Super Admin.