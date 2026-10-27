# Padrões de Código e Estrutura (CI4)

## 1. Estrutura de Pastas
- `app/Modules/NomeDoModulo/Controllers/` (ex: PatientController.php)
- `app/Modules/NomeDoModulo/Models/` (ex: PatientModel.php)
- `app/Modules/NomeDoModulo/Views/` (ex: index.php, form.php)
- `app/Modules/NomeDoModulo/Config/Routes.php` (rotas específicas do módulo)

## 2. Boas Práticas
- **Controllers:** Mantenha os Controllers enxutos. A validação de dados deve ser feita usando as regras nativas do CI4 (`$this->validate([...])`).
- **Models:** Use o Query Builder do CI4. Defina as propriedades `$table`, `$primaryKey`, `$allowedFields`, `$useTimestamps` e `$useSoftDeletes`.
- **Views:** Use a sintaxe nativa do PHP (`<?= $variavel ?>`). **Sempre** use a função `esc()` para escapar dados vindos do banco (prevenção de XSS).
- **Segurança:** Use as funções nativas do CI4 para CSRF (`csrf_token()`, `csrf_field()`).
- **Nomenclatura:** Classes em PascalCase (ex: `PatientController`), métodos em camelCase (ex: `getPatients`), tabelas no plural (ex: `patients`).