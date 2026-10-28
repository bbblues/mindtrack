# Segurança e Controle de Acesso (RBAC)

## Objetivo
Garantir que apenas usuários com os cargos corretos acessem determinadas rotas do sistema.

## Regras de Negócio CRÍTICAS
1. **Filtro de Cargos (RoleFilter):** Criar um filtro que recebe uma lista de cargos permitidos (ex: `admin`, `gestor`).
2. **Aplicação nas Rotas:** 
   - Rotas de Configurações (`/settings*`), Usuários (`/users*`) e Cargos (`/roles*`) devem ser restritas a `admin` e `gestor`.
   - Rotas clínicas (`/patients*`, `/agenda*`, `/protocols*`) devem ser acessíveis a `admin`, `gestor` e `profissional`.
   - A Recepção (`recepcao`) só deve acessar a Agenda e Pacientes (sem editar configurações).
3. **Bloqueio Visual:** O sistema deve retornar um erro 403 (Acesso Negado) ou redirecionar para o Dashboard com uma mensagem de erro se o usuário tentar acessar uma rota sem permissão.