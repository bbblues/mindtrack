# Diretrizes de UI/UX

## 1. Layout
- Sidebar à esquerda (fixa), Topbar superior, Área de Conteúdo central.
- Cores: Azul (primária), Branco (fundo), Cinza (secundário). Status: Verde (Sucesso), Vermelho (Falta/Erro), Amarelo (Alerta), Azul (Info).

## 2. Padrões de Interação (CRÍTICO)
- **SEM MODO EDIÇÃO GLOBAL:** Não use o conceito de "travar a tela inteira". A edição deve ser contextual (botões de editar nas próprias tabelas ou modais).
- **AJAX / Fetch API:** Atualizações de status (ex: marcar "Certo" em um protocolo) devem ser feitas via AJAX (Fetch API) sem recarregar a página.
- **Empty States:** Nunca mostre apenas "Nenhum registro encontrado". Adicione um ícone, uma frase amigável e um Call to Action (Ex: "+ Adicionar Paciente").
- **Modais:** Use modais (HTML + Alpine.js) para formulários rápidos e visualização de detalhes.
- **Progressive Disclosure:** Formulários longos devem ser divididos em Abas (Tabs).