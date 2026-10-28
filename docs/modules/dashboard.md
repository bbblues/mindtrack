# Módulo de Dashboard de Gestão

## Objetivo
Exibir métricas (KPIs) e gráficos para o gestor da clínica, permitindo visualizar a saúde operacional do negócio.

## Métricas e Gráficos (MVP)
1. **Cards de Topo:** Total de Pacientes Ativos, Agendamentos do Mês, Taxa de Faltas (%), Taxa de Assiduidade (%).
2. **Gráfico 1 (Barras):** Atendimentos Realizados vs Faltas por Mês (últimos 6 meses).
3. **Gráfico 2 (Pizza/Rosca):** Ocupação por Especialidade ou Profissional.
4. **Ranking (Tabela):** Top 5 Profissionais com mais atendimentos realizados no período.

## Regras de Negócio CRÍTICAS
1. **Filtro de Período:** O gestor deve poder filtrar os dados por mês/ano.
2. **Multi-Tenancy:** Todos os dados DEVEM ser restritos ao `tenant_id` do gestor logado.
3. **Biblioteca de Gráficos:** Use o Chart.js via CDN (leve e fácil de implementar). Os dados devem vir de um endpoint JSON no backend.
4. **Isolamento:** O Super Admin (que não tem tenant) deve ver uma visão consolidada global, ou o sistema deve pedir para ele selecionar uma clínica.