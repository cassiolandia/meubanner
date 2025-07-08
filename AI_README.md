# Documentação para Agentes de IA - Plugin Meu Banner

Este documento detalha a arquitetura e a lógica do plugin "Meu Banner" para guiar futuras modificações por agentes de IA.

## 1. Visão Geral do Plugin

O objetivo deste plugin é permitir o gerenciamento e a inserção automática de blocos de anúncios (banners) em um site WordPress.

### Conceitos Fundamentais

-   **Bloco de Anúncio (`meu_banner_bloco`):** É um Custom Post Type (CPT) que representa um contêiner de banners. Cada "Bloco" pode ter um ou mais banners associados a ele.
-   **Inserção Automática:** Regras definidas pelo usuário que determinam onde e como os "Blocos de Anúncio" são exibidos no site.
-   **Tipos de Inserção:**
    1.  **Conteúdo:** Insere o banner dentro do conteúdo de posts, páginas, etc. (ex: antes do texto, após o 3º parágrafo).
    2.  **Site:** Insere o banner em nível de página, como um Popup ou uma barra Sticky no rodapé.
    3.  **Lista:** Insere o banner entre os itens de um loop de posts (ex: na página inicial, arquivos de categoria, resultados de pesquisa).

## 2. Arquitetura de Arquivos

A seguir, a descrição da responsabilidade de cada arquivo principal.

-   `meu-banner.php`: **Arquivo Principal.**
    -   Registra o CPT `meu_banner_bloco`.
    -   Define constantes globais (`MEU_BANNER_PLUGIN_DIR`, `MEU_BANNER_PLUGIN_URL`).
    -   **IMPORTANTE:** Carrega os arquivos da pasta `admin/` apenas em páginas de administração, usando uma verificação `if (is_admin())`. Isso é crucial para o desempenho do site.
    -   Contém a lógica de renderização dos banners (shortcode e funções auxiliares).

-   `admin/admin-functions.php`: **Funções do Painel Admin.**
    -   Cria a Meta Box "Configurações do Bloco de Anúncio" para o CPT `meu_banner_bloco`.
    -   Renderiza os campos da Meta Box (seleção de tipo de banner, conteúdo, imagem, etc.).
    -   Salva os metadados do post (`_meu_banner_data`) usando o hook `save_post`.
    -   Adiciona colunas personalizadas (Shortcode, Visualizações) à lista de blocos no admin.

-   `admin/auto-insert-page.php`: **Página de Inserção Automática e Lógica de Inserção.**
    -   **Interface do Usuário (UI):**
        -   Renderiza a página "Inserção Automática" com as abas "Conteúdo", "Site" e "Listas".
        -   A função `meu_banner_render_rule_fields()` gera o HTML para os campos de uma regra individual.
        -   O JavaScript na função `meu_banner_auto_insert_page_scripts()` controla a interatividade da página (troca de abas, adição/remoção de regras, campos condicionais).
        -   **Para manter a aba ativa após salvar,** um campo oculto (`#meu-banner-active-tab`) armazena a aba atual, que é lida no recarregamento da página.
    -   **Lógica de Inserção (Frontend):**
        -   Contém as funções que aplicam as regras no frontend do site. Cada tipo de inserção tem sua própria função e hook.
        -   `meu_banner_apply_auto_insert_rules()`: Usa o filtro `the_content` para inserir banners em posts/páginas individuais.
        -   `meu_banner_enqueue_site_banners()`: Usa o hook `wp_enqueue_scripts` para carregar os dados dos banners de "Site" (Popup/Sticky) para o JavaScript do frontend.
        -   `meu_banner_apply_list_insert_rules()`: **A função mais complexa.** Usa o filtro `the_posts` para inserir banners em listas.
        -   `meu_banner_ad_block_render()`: **A solução para temas de bloco.** Usa o filtro `render_block` para controlar a saída de cada bloco do WordPress para os banners inseridos em listas.

-   `js/admin-rules.js`: **JavaScript da Página de Inserção Automática.**
    -   Este arquivo foi substituído pela lógica contida diretamente em `auto-insert-page.php` dentro da função `meu_banner_auto_insert_page_scripts()`. As modificações na UI devem ser feitas lá.

-   `js/frontend-banner.js`: **JavaScript para Banners de Site (Popup/Sticky).**
    -   Lida com a exibição, fechamento e controle de frequência (via cookies) dos banners de "Site".

## 3. Fluxo de Dados e Lógica de Inserção em Listas

Esta é a funcionalidade mais complexa e o foco das edições recentes.

**Objetivo:** Inserir um banner entre os posts de um loop (ex: página inicial) sem quebrar o layout do tema.

**Como Funciona:**

1.  **Hook `the_posts`:** A função `meu_banner_apply_list_insert_rules()` é acionada. Ela recebe a lista de posts que o WordPress está prestes a exibir.
2.  **Verificação de Regras:** A função verifica se alguma regra do tipo "Lista" se aplica à página atual (home, arquivo, pesquisa, etc.).
3.  **Criação de um "Post Falso":** Se uma regra se aplica, um objeto `WP_Post` "falso" é criado.
    -   `post_type` é definido como `meu_banner_ad` para fácil identificação.
    -   `post_content` contém o HTML real do banner a ser exibido.
4.  **Injeção na Lista:** O "post falso" é inserido na lista de posts (`$posts`) na posição configurada pelo usuário.
5.  **Problema:** O tema do WordPress agora tentará renderizar este "post falso" como se fosse um post real, usando seus próprios blocos (título, data, resumo, etc.), o que resulta em HTML indesejado.
6.  **Solução - Hook `render_block`:** A função `meu_banner_ad_block_render()` intercepta a renderização de **cada bloco** do Gutenberg.
    -   **Passo 1:** A função primeiro verifica se o post atual é o nosso "post falso" (`$post->post_type === 'meu_banner_ad'`). Se não for, ela não faz nada e retorna o conteúdo original do bloco.
    -   **Passo 2:** Se for um post de banner, ela verifica o nome do bloco que está sendo renderizado (ex: `core/post-title`, `core/post-excerpt`).
    -   **Passo 3:** Se o bloco for um que não queremos exibir (título, data, autor, etc.), a função retorna uma **string vazia**, efetivamente o removendo da saída.
    -   **Passo 4:** Se o bloco for `core/post-excerpt` ou `core/post-content`, a função retorna o conteúdo do nosso banner (`$post->post_content`), garantindo que ele seja exibido no lugar correto.

## 4. Como Modificar o Código (Guia para a IA)

### Cenário: Adicionar uma nova condição de exibição para regras de "Lista".

**Exemplo:** Adicionar uma opção para exibir um banner apenas em arquivos de uma `custom_taxonomy` específica.

1.  **Modificar a UI:**
    -   **Arquivo:** `admin/auto-insert-page.php`
    -   **Função:** `meu_banner_render_rule_fields()`
    -   **Ação:** Dentro do `elseif ($insertion_type === 'list')`, adicione um novo `<label>` com um `<input type="checkbox">` para a nova condição. Certifique-se de que o `name` do input siga o padrão `rules[<?php echo esc_attr($index); ?>][post_types][]`.

2.  **Salvar o Novo Dado:**
    -   **Arquivo:** `admin/auto-insert-page.php`
    -   **Função:** `meu_banner_render_auto_insert_page()`
    -   **Ação:** A lógica de salvamento no topo da função já salva todos os valores do array `post_types`. Nenhuma alteração é necessária aqui, desde que o `name` do input esteja correto.

3.  **Implementar a Lógica da Condição:**
    -   **Arquivo:** `admin/auto-insert-page.php`
    -   **Função:** `meu_banner_apply_list_insert_rules()`
    -   **Ação:** Dentro do loop `foreach ($rules as $rule)`, adicione uma nova verificação `elseif` para a sua nova condição.
        -   Use as funções condicionais do WordPress (ex: `is_tax('sua_taxonomy')`) para verificar se a condição é verdadeira.
        -   Se for, verifique se o valor correspondente está no array `$rule_locations` (ex: `in_array('sua_taxonomy', $rule_locations)`).
        -   Se ambas as condições forem verdadeiras, defina `$applies = true;`.

Seguindo este guia, as modificações serão consistentes com a arquitetura existente, garantindo estabilidade e desempenho.
