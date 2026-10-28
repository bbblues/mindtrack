# Módulo de Relatórios e Perfil

## 1. Relatórios em PDF (Relatórios Clínicos)
- **Objetivo:** Permitir que o terapeuta gere um PDF com o resultado da Avaliação de um paciente ou o resumo do Plano Terapêutico.
- **Regras de Negócio:**
  - O PDF deve conter o cabeçalho da clínica (logo, nome, CNPJ).
  - Deve conter os dados do paciente, data, profissional responsável e a tabela com Níveis, Habilidades e Itens (com o status final e o score total).
  - **Biblioteca:** Usar a biblioteca Dompdf (instalada via Composer) para gerar o PDF a partir de uma View HTML.
- **Interface:** Adicionar botões "Exportar PDF" na tela de Avaliação Finalizada e no Plano Terapêutico.

## 2. Perfil do Usuário
- **Objetivo:** Permitir que o usuário logado altere seus dados básicos e senha.
- **Regras de Negócio:**
  - Tela acessível pelo menu do usuário no topo (Topbar).
  - Permitir alterar Nome, E-mail e Senha (com confirmação de senha atual).