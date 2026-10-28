# Módulo de Protocolos (Protocols)

## Entidades
- `protocols`: O cabeçalho (ex: VB-MAPP, ABFW).
- `protocol_levels`: Níveis (ex: Nível 1, Nível 2). Ordenáveis.
- `protocol_skills`: Habilidades (ex: Mando, Tato). Pertencem a um Nível.
- `protocol_items`: Itens/perguntas. Pertencem a uma Habilidade. Ordenáveis.
- `protocol_statuses`: Status (ex: Não Avaliado, Errado, Certo) e seus valores (0, 0, 1).

## Regras de Negócio CRÍTICAS
1. **Formulário Dinâmico:** A tela deve permitir adicionar Níveis, Habilidades e Itens dinamicamente sem recarregar a página (usar Alpine.js).
2. **Imutabilidade Estrutural:** Se um protocolo já foi usado em uma avaliação de paciente, sua estrutura NÃO PODE ser alterada. O sistema deve exibir o aviso "Edição estrutural bloqueada".
3. **Status Customizáveis:** O usuário pode definir os status e seus valores.
4. **Interface:** Formulário com Abas (Geral, Status, Níveis, Habilidades e Itens).