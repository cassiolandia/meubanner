# Sobre o Projeto

Este é um plugin para WordPress chamado "Meu Banner". O objetivo principal do plugin é gerenciar e exibir blocos de anúncios (banners) em um site. Os banners podem ser agrupados usando uma taxonomia personalizada chamada "Campanha" (`cr_campaign`). O plugin também inclui um sistema de rastreamento de visualizações e páginas de relatório para analisar o desempenho dos banners e das campanhas.

# Estrutura de Arquivos

- `meu-banner.php`: O arquivo principal do plugin. Ele é responsável por registrar o Custom Post Type (CPT) `meu_banner_bloco`, a taxonomia `cr_campaign`, e incluir todos os outros arquivos necessários para o funcionamento do plugin.
- `admin/admin-functions.php`: Contém a maior parte da lógica do painel de administração. Isso inclui a criação de colunas personalizadas (como "Shortcode" e "Campanha") na lista de blocos, o registro de páginas de menu e a renderização de meta boxes.
- `admin/campaign-reports.php`: Arquivo que renderiza a página de relatório de visualizações para uma campanha específica. Ele calcula o total de visualizações de todos os blocos associados a uma campanha e exibe um detalhamento diário.
- `admin/reports.php`: Renderiza o relatório de visualizações para um bloco de anúncio individual.
- `includes/`: Contém a lógica para a inserção e renderização dos banners no frontend do site.
- `js/`: Contém os arquivos JavaScript para o frontend (como rastreamento) e para o painel de administração (como a lógica da interface de edição).

# Funcionalidades Principais

- **CPT `meu_banner_bloco`**: Este é o Custom Post Type para os blocos de anúncios. É registrado na função `meu_banner_register_post_type()` em `meu-banner.php`.
- **Taxonomia `cr_campaign`**: Usada para agrupar blocos de anúncios em campanhas. É registrada na função `meu_banner_register_campanha_taxonomy()` em `meu-banner.php`. O slug da taxonomia é `cr_campaign`.
- **Relatórios de Visualização**:
  - **Por Bloco**: Acessado através da lista de blocos de anúncios. O código principal está em `admin/reports.php`.
  - **Por Campanha**: Acessado através de um link na lista de campanhas (`/wp-admin/edit-tags.php?taxonomy=cr_campaign`). O código que gera esta página está em `admin/campaign-reports.php`.

# Tarefas Comuns

### Como modificar o CPT ou a Taxonomia

Para alterar o CPT `meu_banner_bloco` ou a taxonomia `cr_campaign` (por exemplo, para alterar labels, adicionar suporte a novas funcionalidades, etc.), você deve editar as funções `meu_banner_register_post_type()` e `meu_banner_register_campanha_taxonomy()` no arquivo principal `meu-banner.php`.

### Como adicionar colunas na lista de Blocos de Anúncios

1.  **Adicionar a coluna**: Use o filtro `manage_meu_banner_bloco_posts_columns` na função `meu_banner_set_custom_edit_columns()`.
2.  **Renderizar o conteúdo da coluna**: Use a ação `manage_meu_banner_bloco_posts_custom_column` na função `meu_banner_custom_column()`.

Ambas as funções estão localizadas em `admin/admin-functions.php`.

### Como adicionar colunas na lista de Campanhas

1.  **Adicionar a coluna**: Use o filtro `manage_edit-cr_campaign_columns` na função `meu_banner_add_campaign_views_column()`.
2.  **Renderizar o conteúdo da coluna**: Use a ação `manage_cr_campaign_custom_column` na função `meu_banner_add_campaign_views_column_content()`.

Ambas as funções estão localizadas em `admin/admin-functions.php`.

### Como modificar o Relatório de Campanha

Para alterar a página de relatório de campanha, edite o arquivo `admin/campaign-reports.php`. A função `meu_banner_render_campaign_report_page()` é responsável por buscar os dados e renderizar o HTML da página.