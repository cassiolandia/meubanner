<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra o conteúdo a ser inserido no loop de posts do WordPress.
 *
 * @param array $args {
 *     Argumentos para a inserção.
 *     @type string $codigo  O bloco de código HTML a ser inserido. Obrigatório.
 *     @type int    $numero  O número do post (baseado em 1) para a inserção. Ex: 3 para o terceiro post. Obrigatório.
 *     @type string $posicao A posição relativa ao post. Aceita 'before' ou 'after'. Padrão 'after'.
 * }
 * NÃO CHAME ESTA FUNÇÃO DIRETAMENTE.
 */
function inserir_html_no_loop( $args = [] ) {
    global $wp_query;

    $defaults = [
        'codigo'  => '',
        'numero'  => 0,
        'posicao' => 'after',
    ];
    $args = wp_parse_args( $args, $defaults );

    if ( empty( $args['codigo'] ) || empty( $args['numero'] ) ) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('Tentativa de inserir HTML no loop sem "numero" ou "codigo".');
        }
        return; // Interrompe a execução para este caso especial.
    }

    // Garante que a variável global exista.
    if ( ! isset( $GLOBALS['html_para_inserir_no_loop'] ) ) {
        $GLOBALS['html_para_inserir_no_loop'] = [];
    }

    // Adiciona os detalhes da inserção à variável global.
    $GLOBALS['html_para_inserir_no_loop'][] = [
        'codigo'  => $args['codigo'],
        'numero'  => (int) $args['numero'],
        'posicao' => $args['posicao'],
    ];

    // Adiciona o hook para processar a inserção.
    if ( ! has_action( 'the_post', '_callback_processar_insercao_html' ) ) {
        add_action( 'the_post', '_callback_processar_insercao_html' );
        add_action( 'loop_end', '_callback_processar_insercao_html' ); // Para garantir que o último item seja processado.
    }
}

/**
 * Função de callback que processa as inserções agendadas a cada post no loop.
 * @internal
 */
function _callback_processar_insercao_html() {
    global $wp_query;

    // Interrompe a execução se não for o loop principal ou se não estivermos no loop.
    if ( ! $wp_query->is_main_query() || ! in_the_loop() ) {
        return;
    }

    // Se a variável global não estiver definida, não há nada para fazer.
    if ( ! isset( $GLOBALS['html_para_inserir_no_loop'] ) ) {
        return;
    }

    $posicao_atual = $wp_query->current_post + 1;

    foreach ( $GLOBALS['html_para_inserir_no_loop'] as $key => $insercao ) {
        if ( $posicao_atual === $insercao['numero'] ) {
            if ( 'before' === $insercao['posicao'] ) {
                echo $insercao['codigo'];
                unset( $GLOBALS['html_para_inserir_no_loop'][ $key ] );
            }
        }
    }

    // Processa as inserções 'after' no final do post.
    if ( $posicao_atual === $wp_query->post_count ) {
        foreach ( $GLOBALS['html_para_inserir_no_loop'] as $key => $insercao ) {
            if ( 'after' === $insercao['posicao'] ) {
                echo $insercao['codigo'];
                unset( $GLOBALS['html_para_inserir_no_loop'][ $key ] );
            }
        }
    }

    // Limpa a variável global no final do loop.
    if ( ! $wp_query->in_the_loop && empty( $GLOBALS['html_para_inserir_no_loop'] ) ) {
        unset( $GLOBALS['html_para_inserir_no_loop'] );
    }
}

// Hook para limpar a variável global no início de cada loop principal, se necessário.
add_action( 'loop_start', function() {
    global $wp_query;
    if ( $wp_query->is_main_query() ) {
        $GLOBALS['html_para_inserir_no_loop'] = [];
    }
});