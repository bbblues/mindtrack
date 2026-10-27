# Stack Tecnológica e Arquitetura (MindTrack - CI4)

## 1. Visão Geral
O MindTrack é um ERP Clínico SaaS focado em clínicas de ABA. Usaremos CodeIgniter 4 com ambiente Docker.

## 2. Stack
- **Backend:** CodeIgniter 4 (PHP 8.2+).
- **Servidor Web:** Apache (já embutido na imagem Docker do PHP).
- **Frontend:** Views nativas do CI4 (PHP + HTML) + Tailwind CSS (via CDN).
- **Interatividade:** Alpine.js (via CDN) e Fetch API nativa.
- **Banco de Dados:** MySQL 8.0.
- **Ambiente:** Docker Compose.

## 3. Arquitetura Base (REGRAS CRÍTICAS)
- **Multi-Tenancy:** Banco Único. TODAS as tabelas de domínio devem ter a coluna `tenant_id`.
- **Modularidade:** Organize os arquivos dentro de `app/Modules/` (ex: `app/Modules/Patients/Controllers`).
- **Isolamento de Tenant:** Crie um `BaseModel` para filtrar automaticamente as queries pelo `tenant_id` logado.
- **Soft Deletes:** Use `$useSoftDeletes = true;` nos Models.