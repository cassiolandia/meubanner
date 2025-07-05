<?php
/**
 * Plugin Name:       Meu Banner
 * Description:       Um plugin para gerenciar blocos de anúncios com suporte a banners em subgrupos, exibição via shortcode e inserção automática.
 * Version:           1.3.1
 * Author:            Seu Nome
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       meu-banner
 */

if (!defined('ABSPATH')) {
    exit; // Acesso direto bloqueado
}

// Define constantes úteis para o plugin
define('MEU_BANNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MEU_BANNER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Inclui os arquivos de administração
require_once MEU_BANNER_PLUGIN_DIR . 'admin/admin-functions.php';
require_once MEU_BANNER_PLUGIN_DIR . 'admin/auto-insert-page.php';



/**
 * Registra o Custom Post Type para os Blocos de Anúncios.
 */
function meu_banner_register_post_type() {
    $labels = [ 'name' => _x('Blocos de Anúncios', 'Post type general name', 'meu-banner'), 'singular_name' => _x('Bloco de Anúncio', 'Post type singular name', 'meu-banner'), 'menu_name' => _x('Meus Banners', 'Admin Menu text', 'meu-banner'), 'add_new' => __('Adicionar Novo', 'meu-banner'), 'add_new_item' => __('Adicionar Novo Bloco', 'meu-banner'), 'edit_item' => __('Editar Bloco', 'meu-banner'), 'new_item' => __('Novo Bloco', 'meu-banner'), 'view_item' => __('Ver Bloco', 'meu-banner'), 'search_items' => __('Pesquisar Blocos', 'meu-banner'), 'not_found' => __('Nenhum bloco encontrado', 'meu-banner'), 'not_found_in_trash' => __('Nenhum bloco encontrado na lixeira', 'meu-banner'), ];
    $args = [ 'labels' => $labels, 'public' => false, 'publicly_queryable' => false, 'show_ui' => true, 'show_in_menu' => true, 'query_var' => true, 'rewrite' => ['slug' => 'meu-banner-bloco'], 'capability_type' => 'post', 'has_archive' => false, 'hierarchical' => false, 'menu_position' => 20, 'menu_icon' => 'dashicons-format-gallery', 'supports' => ['title'], ];
    register_post_type('meu_banner_bloco', $args);
}
add_action('init', 'meu_banner_register_post_type');

/**
 * Seleção aleatória ponderada.
 */
function meu_banner_get_weighted_random_banner($banners) {
    if (empty($banners)) { return null; }
    if (count($banners) === 1) { return $banners[0]; }
    $weighted_list = [];
    foreach ($banners as $banner) {
        $weight = isset($banner['weight']) ? (int) $banner['weight'] : 1;
        for ($i = 0; $i < $weight; $i++) { $weighted_list[] = $banner; }
    }
    if (empty($weighted_list)) { return null; }
    return $weighted_list[array_rand($weighted_list)];
}

/**
 * Renderiza o HTML interno de um banner (sem wrappers).
 * Usado por todos os tipos de inserção.
 */
function meu_banner_render_html($banner, $subgroup_key) {
    if (!$banner) { return ''; }
    $classes = ['meu-banner-item'];
    if ($subgroup_key === 'desktop') { $classes[] = 'hide-on-mobile'; }
    elseif ($subgroup_key === 'mobile') { $classes[] = 'hide-on-desktop hide-on-tablet'; }
    $inner_html = '';
    if ($banner['type'] === 'html' && !empty($banner['content'])) {
        $inner_html = wp_kses_post($banner['content']);
    } elseif ($banner['type'] === 'image' && !empty($banner['image_id']) && !empty($banner['url'])) {
        $image_id = absint($banner['image_id']);
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        $link_url = esc_url($banner['url']);
        if ($image_url && $link_url) {
            $inner_html = sprintf('<a href="%s" target="_blank" rel="noreferrer nofollow"><img src="%s" alt="%s"></a>', $link_url, esc_url($image_url), esc_attr($image_alt));
        }
    }
    if (empty($inner_html)) { return ''; }
    return '<div class="' . esc_attr(implode(' ', $classes)) . '">' . $inner_html . '</div>';
}

/**
 * Detecção de Bots.
 */
function meu_banner_is_bot() { $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : ''; if (empty($user_agent)) { return true; } $bot_signatures = [ 'bot', 'crawl', 'slurp', 'spider', 'archiver', 'googlebot', 'bingbot', 'yandexbot', 'duckduckbot', 'baiduspider', 'facebookexternalhit', 'twitterbot', 'rogerbot', 'linkedinbot', 'embedly', 'quora link preview', 'showyoubot', 'outbrain', 'pinterest', 'developers.google.com/+/web/snippet', 'wget', 'curl', 'semrushbot', 'ahrefsbot', 'mj12bot' ]; foreach ($bot_signatures as $signature) { if (strpos($user_agent, $signature) !== false) { return true; } } return false; }

/**
 * Manipulador AJAX para rastrear visualizações de banner (versão com contagem diária otimizada).
 */
function meu_banner_track_view_ajax_handler() {
    check_ajax_referer('meu_banner_track_view_nonce', 'nonce');

    if (meu_banner_is_bot()) {
        wp_send_json_error(['message' => 'Bot detected. View not tracked.']);
        return;
    }

    if (isset($_POST['bloco_id'])) {
        $bloco_id = absint($_POST['bloco_id']);

        if (get_post_status($bloco_id) === 'publish') {
            $today = current_time('Y-m-d');
            
            // Obtém o array de contagens diárias
            $daily_counts = get_post_meta($bloco_id, 'meu_banner_daily_views', true);
            if (!is_array($daily_counts)) {
                $daily_counts = [];
            }

            // Incrementa a contagem para o dia de hoje
            if (isset($daily_counts[$today])) {
                $daily_counts[$today]++;
            } else {
                $daily_counts[$today] = 1;
            }

            // Salva o array atualizado
            update_post_meta($bloco_id, 'meu_banner_daily_views', $daily_counts);
            
            wp_send_json_success(['message' => 'Daily view tracked.']);
        } else {
            wp_send_json_error(['message' => 'Invalid block ID.']);
        }
        return;
    }

    wp_send_json_error(['message' => 'Missing data.']);
}
add_action('wp_ajax_nopriv_meu_banner_track_view', 'meu_banner_track_view_ajax_handler');
add_action('wp_ajax_meu_banner_track_view', 'meu_banner_track_view_ajax_handler');



// --- LÓGICA DE RENDERIZAÇÃO E INSERÇÃO ---

// Flags globais para carregar assets apenas quando necessário
$meu_banner_is_on_page = false;
$meu_banner_needs_tracking_script = false;

/**
 * Função auxiliar para obter o CONTEÚDO de um banner (sem wrapper).
 */
function meu_banner_get_content($data) {
    $subgrupos = isset($data['subgrupos']) ? $data['subgrupos'] : [];
    $output = '';
    $display_mode = isset($data['display_mode']) ? $data['display_mode'] : 'geral';
    
    if ($display_mode === 'geral' && !empty($subgrupos['geral'])) {
        $banner = meu_banner_get_weighted_random_banner($subgrupos['geral']);
        $output .= meu_banner_render_html($banner, 'geral');
    } elseif ($display_mode === 'responsivo') {
        if (!empty($subgrupos['desktop'])) { $output .= meu_banner_render_html(meu_banner_get_weighted_random_banner($subgrupos['desktop']), 'desktop'); }
        if (!empty($subgrupos['mobile'])) { $output .= meu_banner_render_html(meu_banner_get_weighted_random_banner($subgrupos['mobile']), 'mobile'); }
    }
    return $output;
}

/**
 * Manipulador do shortcode, usado para banners INLINE.
 */
function meu_banner_shortcode_handler($atts) {
    global $meu_banner_needs_tracking_script, $meu_banner_is_on_page;
    $atts = shortcode_atts(['id' => 0, 'name' => '', 'align' => ''], $atts, 'meu_banner');
    $bloco_id = 0;
    if (!empty($atts['id'])) { $bloco_id = absint($atts['id']); } elseif (!empty($atts['name'])) { $post = get_page_by_path($atts['name'], OBJECT, 'meu_banner_bloco'); if ($post) { $bloco_id = $post->ID; } }
    if (!$bloco_id) { return '<!-- Meu Banner: Bloco não encontrado -->'; }
    $data = get_post_meta($bloco_id, '_meu_banner_data', true);
    if (empty($data) || !is_array($data)) { return '<!-- Meu Banner: Bloco sem configuração -->'; }
    
    $banner_content_html = meu_banner_get_content($data);
    if (empty($banner_content_html)) { return '<!-- Meu Banner: Nenhum banner ativo ou completo para este bloco -->'; }

    $meu_banner_is_on_page = true;
    $tracking_enabled = !empty($data['tracking_enabled']);
    $wrapper_classes = ['meu-banner-wrapper'];
    if (!empty($atts['align'])) { $wrapper_classes[] = 'meu-banner-align-' . sanitize_html_class($atts['align']); }

    // Lógica de visibilidade para o wrapper
    $display_mode = $data['display_mode'] ?? 'geral';
    if ($display_mode === 'responsivo') {
        $subgrupos = $data['subgrupos'] ?? [];
        $has_desktop = !empty($subgrupos['desktop']);
        $has_mobile = !empty($subgrupos['mobile']);

        if ($has_desktop && !$has_mobile) {
            $wrapper_classes[] = 'hide-on-mobile';
        } elseif (!$has_desktop && $has_mobile) {
            $wrapper_classes[] = 'hide-on-desktop';
            $wrapper_classes[] = 'hide-on-tablet';
        }
    }

    $wrapper_attrs_str = 'class="' . esc_attr(implode(' ', $wrapper_classes)) . '"';
    if ($tracking_enabled) {
        $wrapper_attrs_str .= ' data-bloco-id="' . esc_attr($bloco_id) . '"';
        $meu_banner_needs_tracking_script = true;
    }
    $close_button_html = "<button type='button' class='meu-banner-close-btn' aria-label='Fechar Anúncio'>×</button>";
    return "<div {$wrapper_attrs_str}>{$close_button_html}{$banner_content_html}</div>";
}
add_shortcode('meu_banner', 'meu_banner_shortcode_handler');

/**
 * Insere banners INLINE no conteúdo.
 */
function meu_banner_auto_insert_content($content) { if (!is_singular() || !in_the_loop() || !is_main_query()) { return $content; } $all_rules = get_option('meu_banner_auto_insert_rules', []); if (empty($all_rules)) { return $content; } $current_post_type = get_post_type(); $placements = []; $before_content_html = ''; $after_content_html = ''; foreach ($all_rules as $rule) { if (empty($rule['enabled']) || empty($rule['bloco_id']) || ($rule['insertion_type'] !== 'content') || empty($rule['post_types']) || !in_array($current_post_type, $rule['post_types'])) { continue; } $banner_shortcode = sprintf('[meu_banner id="%d" align="%s"]', intval($rule['bloco_id']), esc_attr($rule['align'])); $banner_html = do_shortcode($banner_shortcode);         if (!is_string($banner_html) || strpos($banner_html, 'meu-banner-item') === false) { continue; } switch ($rule['position']) { case 'before_content': $before_content_html .= $banner_html; break; case 'after_content': $after_content_html .= $banner_html; break; case 'after_paragraph': if (!empty($rule['paragraph_num'])) { $p_num = intval($rule['paragraph_num']); if (!isset($placements[$p_num])) { $placements[$p_num] = ''; } $placements[$p_num] .= $banner_html; } break; } } if (!empty($placements)) { $paragraphs = preg_split('/(<\/p>)/i', $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE); $new_content = ''; $p_counter = 0; for ($i = 0; $i < count($paragraphs); $i += 2) { $paragraph_content = $paragraphs[$i]; $paragraph_delimiter = isset($paragraphs[$i + 1]) ? $paragraphs[$i + 1] : ''; $new_content .= $paragraph_content . $paragraph_delimiter; $p_counter++; if (isset($placements[$p_counter])) { $new_content .= $placements[$p_counter]; } } $content = $new_content; } return $before_content_html . $content . $after_content_html; }
add_filter('the_content', 'meu_banner_auto_insert_content', 99);

/**
 * Renderiza os banners de PÁGINA (Popup/Sticky) no rodapé.
 */
function meu_banner_render_page_banners() {
    global $meu_banner_is_on_page, $meu_banner_needs_tracking_script;

    $all_rules = get_option('meu_banner_auto_insert_rules', []);
    if (empty($all_rules)) { return; }

    // Lógica para determinar o contexto de exibição atual
    $display_context = [];
    if (is_front_page() || is_home()) { $display_context[] = 'home'; }
    if (is_archive() || is_category() || is_tag()) { $display_context[] = 'archive'; }
    if (is_singular()) { $display_context[] = get_post_type(); }
    
    $has_rendered_popup = false;
    $has_rendered_sticky = false;
    $close_button_html = "<button type='button' class='meu-banner-close-btn' aria-label='Fechar Anúncio'>×</button>";

    foreach ($all_rules as $rule) {
        if (empty($rule['enabled']) || empty($rule['bloco_id']) || ($rule['insertion_type'] !== 'page') || empty($rule['post_types'])) {
            continue;
        }

        // Verifica a condição de exibição
        $display = false;
        if (in_array('all_site', $rule['post_types'])) {
            $display = true;
        } else {
            foreach ($display_context as $context) {
                if (in_array($context, $rule['post_types'])) { $display = true; break; }
            }
        }
        if (!$display) { continue; }
        
        $data = get_post_meta($rule['bloco_id'], '_meu_banner_data', true);
        if (empty($data)) { continue; }
        
        $bloco_data = get_post_meta($rule['bloco_id'], '_meu_banner_data', true);
        if (empty($bloco_data)) {
            continue;
        }

        // Gera o conteúdo do banner e determina as classes de visibilidade do container
        $desktop_html = '';
        $mobile_html = '';
        $banner_content_html = '';
        $visibility_classes = '';
        $display_mode = $bloco_data['display_mode'] ?? 'geral';
        $subgrupos = $bloco_data['subgrupos'] ?? [];

        if ($display_mode === 'geral' && !empty($subgrupos['geral'])) {
            $banner_content_html = meu_banner_render_html(meu_banner_get_weighted_random_banner($subgrupos['geral']), 'geral');
        } elseif ($display_mode === 'responsivo') {
            if (!empty($subgrupos['desktop'])) {
                $desktop_html = meu_banner_render_html(meu_banner_get_weighted_random_banner($subgrupos['desktop']), 'desktop');
            }
            if (!empty($subgrupos['mobile'])) {
                $mobile_html = meu_banner_render_html(meu_banner_get_weighted_random_banner($subgrupos['mobile']), 'mobile');
            }
            $banner_content_html = $desktop_html . $mobile_html;

            // Adiciona classes ao container se apenas um tipo de banner (desktop ou mobile) existir
            if (!empty($desktop_html) && empty($mobile_html)) {
                $visibility_classes = ' hide-on-mobile';
            } elseif (empty($desktop_html) && !empty($mobile_html)) {
                $visibility_classes = ' hide-on-desktop hide-on-tablet';
            }
        } else {
            // Fallback para blocos antigos sem o modo de exibição definido
            $banner_content_html = meu_banner_get_content($bloco_data);
        }
        
        if (empty($banner_content_html)) { continue; }

        $meu_banner_is_on_page = true;
        if (!empty($data['tracking_enabled'])) { $meu_banner_needs_tracking_script = true; }

        $format = $rule['page_format'] ?? 'popup';
        $style = $rule['page_style'] ?? 'dark';
        
        // Prepara os atributos do container, incluindo os de frequência
        $container_attrs_arr = [
            'data-bloco-id' => esc_attr($rule['bloco_id']),
            'data-frequency-type' => esc_attr($rule['frequency_type'] ?? 'always'),
        ];

        if (isset($rule['frequency_type'])) {
            if ($rule['frequency_type'] === 'time') {
                $container_attrs_arr['data-frequency-time-value'] = esc_attr($rule['frequency_time_value'] ?? 1);
                $container_attrs_arr['data-frequency-time-unit'] = esc_attr($rule['frequency_time_unit'] ?? 'hours');
            } elseif ($rule['frequency_type'] === 'access') {
                $container_attrs_arr['data-frequency-access-value'] = esc_attr($rule['frequency_access_value'] ?? 5);
            }
        }

        $container_attrs = '';
        foreach ($container_attrs_arr as $key => $value) {
            $container_attrs .= $key . '="' . $value . '" ';
        }

        if ($format === 'popup' && !$has_rendered_popup) {
            echo '<div class="meu-banner-page-container meu-banner-popup-overlay' . esc_attr($visibility_classes) . '"></div>';
            echo '<div class="meu-banner-page-container meu-banner-popup-wrapper' . esc_attr($visibility_classes) . '" role="dialog" aria-modal="true" ' . $container_attrs . '>';
            echo $close_button_html;
            echo $banner_content_html;
            echo '</div>';
            $has_rendered_popup = true;
        }

        if ($format === 'sticky' && !$has_rendered_sticky) {
            $sticky_classes = 'meu-banner-page-container meu-banner-sticky-wrapper meu-banner-sticky-style-' . esc_attr($style) . esc_attr($visibility_classes);
            echo '<div class="' . $sticky_classes . '" role="complementary" ' . $container_attrs . '>';
            echo $banner_content_html;
            echo $close_button_html;
            echo '</div>';
            $has_rendered_sticky = true;
        }
    }
}
add_action('wp_footer', 'meu_banner_render_page_banners');

/**
 * Enfileira todos os assets do frontend (CSS/JS) no rodapé.
 */
function meu_banner_enqueue_frontend_assets() {
    global $meu_banner_is_on_page, $meu_banner_needs_tracking_script;

    if ($meu_banner_is_on_page) {
        wp_enqueue_style('meu-banner-frontend-styles', MEU_BANNER_PLUGIN_URL . 'css/auto-insert.css', [], '1.3.1');
        wp_enqueue_script('meu-banner-frontend-script', MEU_BANNER_PLUGIN_URL . 'js/frontend-banner.js', [], '1.3.1', true);
        if ($meu_banner_needs_tracking_script) {
            wp_enqueue_script('meu-banner-tracker', MEU_BANNER_PLUGIN_URL . 'js/tracker.js', [], '1.3.1', true);
            wp_localize_script('meu-banner-tracker', 'meuBannerAjax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('meu_banner_track_view_nonce'),]);
        }
    }
}
add_action('wp_footer', 'meu_banner_enqueue_frontend_assets');