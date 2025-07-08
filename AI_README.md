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

## 2. Arquitetura de Arquivos (Pós-Refatoração)

A seguir, a descrição da responsabilidade de cada arquivo principal após as correções de duplicação e a implementação da rotação de banners.

-   `meu-banner.php`: **Arquivo Principal e Lógica Central de Renderização.**
    -   Registra o CPT `meu_banner_bloco`.
    -   Define constantes globais.
    -   Carrega os arquivos de `admin/` e `includes/`.
    -   **Lógica de Renderização Principal:**
        -   `meu_banner_shortcode_handler()`: Processa o shortcode `[meu_banner]`.
        -   `meu_banner_render_block()`: **Função central que busca os dados de um bloco**, seleciona o banner apropriado (considerando o modo geral/responsivo) e prepara o HTML.
        -   `meu_banner_get_weighted_random_banner()`: **Nova função crucial.** Recebe uma lista de banners e **seleciona um aleatoriamente, respeitando o peso (weight) de cada um**. É isso que permite a rotação.
        -   `meu_banner_render_html()`: Gera o HTML final para um único banner (tag `<a>`, `<img>` ou código HTML).
    -   **Lógica de Inserção Automática (Conteúdo):**
        -   `meu_banner_auto_insert_content()`: **ÚNICA função responsável por inserir banners no conteúdo de posts/páginas** (`the_content`). Ela é robusta e lida com múltiplas posições (antes, depois, após parágrafos).

-   `admin/admin-functions.php`: **Funções do Painel Admin.**
    -   Cria e renderiza a Meta Box de "Configurações do Bloco", onde os banners, seus tipos (HTML/Imagem), **pesos (weight)** e modo de exibição (Geral/Responsivo) são definidos.
    -   Salva os metadados do bloco (`_meu_banner_data`).
    -   Adiciona colunas personalizadas (Shortcode, Visualizações, Relatório) à lista de blocos.

-   `admin/auto-insert-page.php`: **Página de "Inserção Automática".**
    -   **Interface do Usuário (UI):** Renderiza a página de configurações onde os usuários criam e gerenciam as regras de inserção para "Conteúdo", "Site" e "Listas".
    -   **NÃO CONTÉM MAIS LÓGICA DE INSERÇÃO NO FRONTEND.** Sua responsabilidade é apenas salvar as regras no banco de dados (`update_option`).

-   `includes/frontend-insertion.php`: **Lógica de Inserção (Site e Listas).**
    -   `meu_banner_apply_auto_insert_rules()`: **Esta função foi desativada (comentada)** para evitar a duplicação de banners no conteúdo.
    -   `meu_banner_enqueue_site_banners()`: Lida com a inserção de banners de "Site" (Popup/Sticky), enfileirando os scripts e dados necessários.
    -   `meu_banner_apply_list_insert_rules()`: Lida com a inserção de banners em "Listas" (home, arquivos, etc.), usando o hook `the_posts`.
    -   `meu_banner_ad_block_render()`: Função auxiliar para a inserção em listas, garantindo que o banner seja exibido corretamente em temas de bloco.

-   `js/frontend-banner.js`: **JavaScript para Banners de Site e Rastreamento.**
    -   Lida com a exibição, fechamento e controle de frequência (cookies) dos banners de "Site" (Popup/Sticky).
    -   Contém a lógica AJAX para rastrear as visualizações dos banners quando a opção está ativada.


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
