<?php
if (!defined('ABSPATH')) {
    exit;
}

function meu_banner_apply_auto_insert_rules($content) {
    if (is_feed() || !is_main_query() || !is_singular()) {
        return $content;
    }

    $rules = get_option('meu_banner_auto_insert_rules', []);
    if (empty($rules)) {
        return $content;
    }

    $current_post_type = get_post_type();
    if (!$current_post_type) {
        if (is_front_page() || is_home()) {
            $current_post_type = 'home';
        } else if (is_singular()) {
            $current_post_type = get_queried_object()->post_type;
        }
    }

    foreach ($rules as $rule) {
        if (empty($rule['enabled']) || empty($rule['bloco_id']) || $rule['insertion_type'] !== 'content') {
            continue;
        }

        if (!in_array($current_post_type, $rule['post_types'])) {
            continue;
        }

        $banner_shortcode = '[meu_banner id="' . intval($rule['bloco_id']) . '"]';
        $align_class = 'align' . sanitize_key($rule['align']);
        $banner_html = '<div class="meu-banner-auto-inserted ' . $align_class . '">' . do_shortcode($banner_shortcode) . '</div>';

        switch ($rule['position']) {
            case 'before_content':
                $content = $banner_html . $content;
                break;
            case 'after_content':
                $content = $content . $banner_html;
                break;
            case 'after_paragraph':
                $paragraphs = explode('</p>', $content);
                $paragraph_num = max(0, intval($rule['paragraph_num']) - 1);
                if (count($paragraphs) > $paragraph_num) {
                    array_splice($paragraphs, $paragraph_num + 1, 0, $banner_html);
                    $content = implode('</p>', $paragraphs);
                }
                break;
        }
    }

    return $content;
}
add_filter('the_content', 'meu_banner_apply_auto_insert_rules');

function meu_banner_enqueue_site_banners() {
    if (is_admin()) {
        return;
    }

    $rules = get_option('meu_banner_auto_insert_rules', []);
    if (empty($rules)) {
        return;
    }

    $current_post_type = 'other';
    if (is_front_page() || is_home()) {
        $current_post_type = 'home';
    } elseif (is_singular()) {
        $current_post_type = get_queried_object()->post_type;
    } elseif (is_archive()) {
        $current_post_type = get_queried_object()->name;
    } elseif (is_search()) {
        $current_post_type = 'search';
    }

    $banners_to_show = [];

    foreach ($rules as $rule) {
        if (empty($rule['enabled']) || empty($rule['bloco_id']) || $rule['insertion_type'] !== 'page') {
            continue;
        }

        $applies = false;
        if (in_array('all_site', $rule['post_types'])) {
            $applies = true;
        } elseif ($current_post_type === 'home' && in_array('home', $rule['post_types'])) {
            $applies = true;
        } elseif (in_array($current_post_type, $rule['post_types'])) {
            $applies = true;
        }

        if (!$applies) {
            continue;
        }

        $cookie_name = 'meu_banner_freq_' . $rule['bloco_id'];
        if (isset($_COOKIE[$cookie_name])) {
            continue;
        }

        $banners_to_show[] = [
            'bloco_id' => $rule['bloco_id'],
            'format'   => $rule['page_format'],
            'style'    => $rule['page_style'],
            'frequency' => [
                'type' => $rule['frequency_type'],
                'time_value' => $rule['frequency_time_value'] ?? 1,
                'time_unit' => $rule['frequency_time_unit'] ?? 'hours',
                'access_value' => $rule['frequency_access_value'] ?? 5,
            ]
        ];
    }

    if (!empty($banners_to_show)) {
        wp_enqueue_style('meu-banner-auto-insert', MEU_BANNER_PLUGIN_URL . 'css/auto-insert.css', [], '1.1.0');
        wp_enqueue_script('meu-banner-frontend-banner', MEU_BANNER_PLUGIN_URL . 'js/frontend-banner.js', [], '1.1.0', true);
        wp_localize_script('meu-banner-frontend-banner', 'meuBannerData', [
            'banners' => $banners_to_show,
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'meu_banner_enqueue_site_banners');

function meu_banner_handle_page_access() {
    $access_count = isset($_COOKIE['meu_banner_page_access']) ? intval($_COOKIE['meu_banner_page_access']) + 1 : 1;
    setcookie('meu_banner_page_access', $access_count, time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
    wp_send_json_success(['count' => $access_count]);
}
add_action('wp_ajax_meu_banner_page_access', 'meu_banner_handle_page_access');
add_action('wp_ajax_nopriv_meu_banner_page_access', 'meu_banner_handle_page_access');

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
