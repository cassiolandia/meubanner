<?php
if (!defined('ABSPATH')) {
    exit;
}

function meu_banner_apply_auto_insert_rules($content) {
    static $did_run = false;
    if ($did_run || is_feed() || !is_main_query() || !is_singular() || is_home() || is_archive() || is_search()) {
        return $content;
    }
    $did_run = true;

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
// add_filter('the_content', 'meu_banner_apply_auto_insert_rules'); // Removido para evitar duplicacao