<?php
if (!defined('ABSPATH')) {
    exit;
}

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