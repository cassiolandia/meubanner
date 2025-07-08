<?php
if (!defined('ABSPATH')) {
    exit;
}

// --- LÓGICA DE INSERÇÃO EM LISTAS ---

function meu_banner_apply_list_insert_rules($posts) {
    if (is_admin() || !is_main_query() || empty($posts) || is_singular()) {
        return $posts;
    }

    $rules = get_option('meu_banner_auto_insert_rules', []);
    if (empty($rules)) return $posts;

    $applicable_rules = [];
    foreach ($rules as $rule) {
        if (empty($rule['enabled']) || empty($rule['bloco_id']) || ($rule['insertion_type'] ?? 'content') !== 'list') {
            continue;
        }

        $rule_locations = $rule['post_types'] ?? [];
        if (empty($rule_locations)) continue;

        $applies = false;
        $is_on_home = is_front_page() || is_home();
        $is_on_paged_home = $is_on_home && is_paged();

        if (in_array('all_lists', $rule_locations)) {
            $applies = true;
        } elseif ($is_on_paged_home && in_array('home_paged', $rule_locations)) {
            $applies = true;
        } elseif ($is_on_home && !$is_on_paged_home && in_array('home', $rule_locations)) {
            $applies = true;
        } elseif (is_search() && in_array('search', $rule_locations)) {
            $applies = true;
        } elseif (is_archive()) {
            $post_type_in_query = get_query_var('post_type');
            if (empty($post_type_in_query)) $post_type_in_query = 'post';
            $post_type_in_query = is_array($post_type_in_query) ? $post_type_in_query : [$post_type_in_query];
            if (count(array_intersect($post_type_in_query, $rule_locations)) > 0) {
                $applies = true;
            }
        }

        if ($applies) $applicable_rules[] = $rule;
    }

    if (empty($applicable_rules)) return $posts;

    usort($applicable_rules, fn($a, $b) => ($a['list_item_num'] ?? 1) <=> ($b['list_item_num'] ?? 1));

    $offset = 0;
    foreach ($applicable_rules as $rule) {
        $banner_content = do_shortcode('[meu_banner id="' . intval($rule['bloco_id']) . '"]');
        if (empty($banner_content)) continue;

        $banner_post = new WP_Post((object)[
            'ID'             => -1 * intval($rule['bloco_id']),
            'post_title'     => 'Banner Ad',
            'post_content'   => $banner_content,
            'post_status'    => 'publish',
            'post_type'      => 'meu_banner_ad',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'filter'         => 'raw',
        ]);

        $position = intval($rule['list_item_num']);
        if ($rule['list_position'] === 'before_item') {
            $position = max(0, $position - 1);
        }

        $final_position = $position + $offset;

        if ($final_position >= 0 && $final_position <= count($posts)) {
            array_splice($posts, $final_position, 0, [$banner_post]);
            $offset++;
        }
    }

    return $posts;
}
add_filter('the_posts', 'meu_banner_apply_list_insert_rules', 20, 1);

function meu_banner_ad_block_render($block_content, $block) {
    global $post;

    if (!is_object($post) || !isset($post->post_type) || $post->post_type !== 'meu_banner_ad') {
        return $block_content;
    }

    $blocks_to_hide = [
        'core/post-title',
        'core/post-date',
        'core/post-author',
        'core/post-terms',
        'core/post-featured-image',
        'core/spacer',
    ];

    if (in_array($block['blockName'], $blocks_to_hide)) {
        return ''; // Retorna uma string vazia para ocultar o bloco
    }

    if ($block['blockName'] === 'core/post-excerpt' || $block['blockName'] === 'core/post-content') {
        return $post->post_content;
    }

    return $block_content; // Retorna o conteúdo original para outros blocos
}
add_filter('render_block', 'meu_banner_ad_block_render', 10, 2);

function meu_banner_ad_post_class_filter($classes) {
    global $post;
    if (is_object($post) && isset($post->post_type) && $post->post_type === 'meu_banner_ad') {
        return ['meu-banner-list-item', 'wp-block-post']; // Adiciona a classe base para manter o estilo
    }
    return $classes;
}
add_filter('post_class', 'meu_banner_ad_post_class_filter', 999);