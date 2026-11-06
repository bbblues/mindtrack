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

## Regra de Visualização vs. Edição de Pacientes

- **Profissional (terapeuta):** vê **todos os pacientes da clínica**
  (`patients.view.any`), incluindo perfil e histórico, mas só **edita/desativa
  os pacientes vinculados** a ele (`patients.edit.own`, `patients.deactivate.own`).
- **Recepção:** cria e visualiza pacientes (`patients.view.any`), sem permissão
  de edição.
- **Admin / Gestor:** visualizam e editam todos (`patients.view.any`,
  `patients.edit.any`, `patients.deactivate.any`).
- **Iniciar Avaliação:** restrito ao **Responsável Principal** do paciente
  (`patient_professionals.is_primary = 1`) ou a quem possui `assessments.edit.any`
  (checagem `can_own_primary`).
- **Implementação:** o `PatientController` anexa as flags `can_edit`,
  `can_deactivate` e `can_assess` na listagem (`withPatientPermissions`),
  `edit()` é protegido por `patients.edit`, e a view `Patients/Views/index.php`
  oculta os botões "Editar", "Ativo/Inativo" e "Iniciar Avaliação" quando o
  usuário não tem a permissão correspondente. A tela de perfil (`show`) já
  respeita `patients.edit` no botão "Editar".