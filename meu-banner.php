<?php
/**
 * Plugin Name:       Meu Banner
 * Description:       Um plugin para gerenciar blocos de anúncios com suporte a banners em subgrupos, exibição via shortcode e inserção automática.
 * Version:           1.6.5
 * Author:            Cássio
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Text Domain:       meu-banner
 */

if (!defined('ABSPATH')) {
    exit; // Acesso direto bloqueado
}

// Define constantes úteis para o plugin
define('MEU_BANNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MEU_BANNER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Inclui os arquivos de administração
if (is_admin()) {
    require_once MEU_BANNER_PLUGIN_DIR . 'admin/admin-functions.php';
    require_once MEU_BANNER_PLUGIN_DIR . 'admin/auto-insert-page.php';
    require_once MEU_BANNER_PLUGIN_DIR . 'admin/reports.php';
}

// Inclui a lógica de inserção do frontend
require_once MEU_BANNER_PLUGIN_DIR . 'includes/frontend-insertion.php';



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
    } elseif ($banner['type'] === 'image' && !empty($banner['image_id'])) {
        $image_id = absint($banner['image_id']);
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        $link_url = esc_url($banner['url']);
        if ($image_url) {
            $img_tag = sprintf('<img src="%s" alt="%s">', esc_url($image_url), esc_attr($image_alt));
            if (!empty($link_url)) {
                $inner_html = sprintf('<a href="%s" target="_blank" rel="noreferrer nofollow">%s</a>', $link_url, $img_tag);
            } else {
                $inner_html = $img_tag;
            }
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
function meu_banner_render_block($bloco_id, $align = 'center') {
    global $meu_banner_needs_tracking_script, $meu_banner_is_on_page;

    if (!$bloco_id) {
        return '<!-- Meu Banner: Bloco não encontrado -->';
    }

    $data = get_post_meta($bloco_id, '_meu_banner_data', true);
    if (empty($data) || !is_array($data)) {
        return '<!-- Meu Banner: Bloco sem configuração -->';
    }

    // Lógica interna para obter o conteúdo do banner (desktop/mobile/geral)
    $subgrupos = isset($data['subgrupos']) ? $data['subgrupos'] : [];
    $banner_content_html = '';
    $display_mode = isset($data['display_mode']) ? $data['display_mode'] : 'geral';
    $close_button_html = "<button type='button' class='meu-banner-close-btn' aria-label='Fechar Anúncio'>×</button>";

    $render_banner_with_close_button = function($banner, $subgroup_key) use ($close_button_html) {
        if (!$banner) return '';
        $html = meu_banner_render_html($banner, $subgroup_key);
        if ($html) {
            // Insere o botão de fechar ANTES do </div> de fechamento do .meu-banner-item
            return preg_replace('/(<\/div>)$/i', $close_button_html . '$1', $html, 1);
        }
        return '';
    };

    if ($display_mode === 'geral' && !empty($subgrupos['geral'])) {
        $banner = meu_banner_get_weighted_random_banner($subgrupos['geral']);
        $banner_content_html .= $render_banner_with_close_button($banner, 'geral');
    } elseif ($display_mode === 'responsivo') {
        if (!empty($subgrupos['desktop'])) {
            $banner_desktop = meu_banner_get_weighted_random_banner($subgrupos['desktop']);
            $banner_content_html .= $render_banner_with_close_button($banner_desktop, 'desktop');
        }
        if (!empty($subgrupos['mobile'])) {
            $banner_mobile = meu_banner_get_weighted_random_banner($subgrupos['mobile']);
            $banner_content_html .= $render_banner_with_close_button($banner_mobile, 'mobile');
        }
    }

    if (empty($banner_content_html)) {
        return '<!-- Meu Banner: Nenhum banner ativo ou completo para este bloco -->';
    }

    $meu_banner_is_on_page = true;
    $tracking_enabled = !empty($data['tracking_enabled']);

    // Construção do Wrapper
    $wrapper_classes = ['meu-banner-wrapper'];
    if (!empty($align)) {
        $wrapper_classes[] = 'meu-banner-align-' . sanitize_html_class($align);
    }

    if ($display_mode === 'responsivo') {
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

    return "<div {$wrapper_attrs_str}>{$banner_content_html}</div>";
}

/**
 * Manipulador do shortcode, agora usando a função unificada.
 */
function meu_banner_shortcode_handler($atts) {
    $atts = shortcode_atts([
        'id'   => 0,
        'name' => '',
        'align' => 'center',
    ], $atts, 'meu_banner');

    $bloco_id = 0;
    if (!empty($atts['id'])) {
        $bloco_id = absint($atts['id']);
    } elseif (!empty($atts['name'])) {
        $post = get_page_by_path($atts['name'], OBJECT, 'meu_banner_bloco');
        if ($post) {
            $bloco_id = $post->ID;
        }
    }

    return meu_banner_render_block($bloco_id, $atts['align']);
}
add_shortcode('meu_banner', 'meu_banner_shortcode_handler');

/**
 * Insere banners INLINE no conteúdo.
 */
function meu_banner_auto_insert_content($content) {
    if (!is_singular() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $all_rules = get_option('meu_banner_auto_insert_rules', []);
    if (empty($all_rules)) {
        return $content;
    }

    $current_post_type = get_post_type();
    $placements = [];
    $before_content_html = '';
    $after_content_html = '';

    foreach ($all_rules as $rule) {
        if (empty($rule['enabled']) ||
            empty($rule['bloco_id']) ||
            ($rule['insertion_type'] !== 'content') ||
            empty($rule['post_types']) ||
            !in_array($current_post_type, $rule['post_types'])) {
            continue;
        }

        $banner_html = meu_banner_render_block(intval($rule['bloco_id']), esc_attr($rule['align']));

        if (empty($banner_html) || strpos($banner_html, '<!-- Meu Banner:') === 0) {
            continue;
        }

        switch ($rule['position']) {
            case 'before_content':
                $before_content_html .= $banner_html;
                break;
            case 'after_content':
                $after_content_html .= $banner_html;
                break;
            case 'after_paragraph':
                if (!empty($rule['paragraph_num'])) {
                    $p_num = intval($rule['paragraph_num']);
                    if (!isset($placements[$p_num])) {
                        $placements[$p_num] = '';
                    }
                    $placements[$p_num] .= $banner_html;
                }
                break;
        }
    }

    if (!empty($placements)) {
        $paragraphs = preg_split('/(<\/p>)/i', $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $new_content = '';
        $p_counter = 0;

        for ($i = 0; $i < count($paragraphs); $i += 2) {
            $paragraph_content = $paragraphs[$i];
            $paragraph_delimiter = isset($paragraphs[$i + 1]) ? $paragraphs[$i + 1] : '';
            $new_content .= $paragraph_content . $paragraph_delimiter;
            $p_counter++;

            if (isset($placements[$p_counter])) {
                $new_content .= $placements[$p_counter];
            }
        }
        $content = $new_content;
    }

    return $before_content_html . $content . $after_content_html;
}
add_filter('the_content', 'meu_banner_auto_insert_content', 99);

/**
 * Renderiza os banners de PÁGINA (Popup/Sticky) no rodapé.
 * Versão corrigida para permitir múltiplos banners do mesmo tipo (ex: um para desktop, um para mobile).
 */
function meu_banner_render_page_banners() {
    global $meu_banner_is_on_page, $meu_banner_needs_tracking_script;

    $all_rules = get_option('meu_banner_auto_insert_rules', []);
    if (empty($all_rules)) {
        return;
    }

    $is_home = is_front_page() || is_home();
    $popups_to_render = [];
    $stickies_to_render = [];
    $close_button_html = "<button type='button' class='meu-banner-close-btn' aria-label='Fechar Anúncio'>×</button>";

    foreach ($all_rules as $rule) {
        if (empty($rule['enabled']) || empty($rule['bloco_id']) || empty($rule['post_types']) || $rule['insertion_type'] !== 'page' || !in_array($rule['page_format'], ['popup', 'sticky'])) {
            continue;
        }

        $display = false;
        $rule_post_types = $rule['post_types'];

        if (in_array('all_site', $rule_post_types)) {
            $display = true;
        } elseif ($is_home && in_array('home', $rule_post_types)) {
            $display = true;
        } elseif (!$is_home) {
            if (is_singular() && in_array(get_post_type(), $rule_post_types)) {
                $display = true;
            } elseif ((is_archive() || is_category() || is_tag()) && in_array('archive', $rule_post_types)) {
                $display = true;
            }
        }

        if (!$display) {
            continue;
        }

        $data = get_post_meta($rule['bloco_id'], '_meu_banner_data', true);
        if (empty($data)) {
            continue;
        }

        $desktop_html = '';
        $mobile_html = '';
        $banner_content_html = '';
        $visibility_classes = '';
        $display_mode = $data['display_mode'] ?? 'geral';
        $subgrupos = $data['subgrupos'] ?? [];

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

            if (!empty($desktop_html) && empty($mobile_html)) {
                $visibility_classes = ' hide-on-mobile';
            } elseif (empty($desktop_html) && !empty($mobile_html)) {
                $visibility_classes = ' hide-on-desktop hide-on-tablet';
            }
        }

        if (empty($banner_content_html)) {
            continue;
        }

        $meu_banner_is_on_page = true;
        if (!empty($data['tracking_enabled'])) {
            $meu_banner_needs_tracking_script = true;
        }

        $format = $rule['page_format'] ?? 'popup';
        $style = $rule['page_style'] ?? 'dark';

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

        $banner_html = '';
        if ($format === 'popup') {
            $banner_html .= '<div class="meu-banner-page-container meu-banner-popup-overlay' . esc_attr($visibility_classes) . '"></div>';
            $banner_html .= '<div class="meu-banner-page-container meu-banner-popup-wrapper' . esc_attr($visibility_classes) . '" role="dialog" aria-modal="true" ' . $container_attrs . '>';
            $banner_html .= $close_button_html;
            $banner_html .= $banner_content_html;
            $banner_html .= '</div>';
            $popups_to_render[] = $banner_html;
        }

        if ($format === 'sticky') {
            $sticky_classes = 'meu-banner-page-container meu-banner-sticky-wrapper meu-banner-sticky-style-' . esc_attr($style) . esc_attr($visibility_classes);
            $banner_html .= '<div class="' . $sticky_classes . '" role="complementary" ' . $container_attrs . '>';
            $banner_html .= $banner_content_html;
            $banner_html .= $close_button_html;
            $banner_html .= '</div>';
            $stickies_to_render[] = $banner_html;
        }
    }

    // Renderiza todos os banners coletados
    if (!empty($popups_to_render)) {
        echo implode('', $popups_to_render);
    }
    if (!empty($stickies_to_render)) {
        echo implode('', $stickies_to_render);
    }
}
add_action('wp_footer', 'meu_banner_render_page_banners');

/**
 * Enfileira todos os assets do frontend (CSS/JS) no rodapé.
 * Isso garante que a flag de renderização seja acionada antes do enfileiramento.
 */
function meu_banner_enqueue_frontend_assets() {
    global $meu_banner_is_on_page, $meu_banner_needs_tracking_script;

    if ($meu_banner_is_on_page) {
        wp_enqueue_style('meu-banner-frontend-styles', MEU_BANNER_PLUGIN_URL . 'css/auto-insert.css', [], '1.4.1');
        wp_enqueue_script('meu-banner-frontend-script', MEU_BANNER_PLUGIN_URL . 'js/frontend-banner.js', [], '1.4.1', true);

        if ($meu_banner_needs_tracking_script) {
            wp_localize_script('meu-banner-frontend-script', 'meuBannerAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('meu_banner_track_view_nonce'),
            ]);
        }
    }
}
add_action('wp_footer', 'meu_banner_enqueue_frontend_assets');

/**
 * Limpa o cache de plugins de otimização conhecidos ao salvar as regras.
 */
function meu_banner_clear_optimization_caches() {
    // WP Rocket
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
    }
    // W3 Total Cache
    if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();
    }
    // WP Super Cache
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
    // LiteSpeed Cache
    if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
        LiteSpeed_Cache_API::purge_all();
    }
}
add_action('update_option_meu_banner_auto_insert_rules', 'meu_banner_clear_optimization_caches', 10, 0);
add_action('save_post_meu_banner_bloco', 'meu_banner_clear_optimization_caches', 10, 0);

/**
 * Ação de desinstalação: remove opções e o CPT.
 */
function meu_banner_uninstall() {
    // Remove a opção de regras
    delete_option('meu_banner_auto_insert_rules');

    // Remove todos os posts do tipo 'meu_banner_bloco' e seus metadados
    $blocos = get_posts(['post_type' => 'meu_banner_bloco', 'numberposts' => -1, 'post_status' => 'any']);
    if (!empty($blocos)) {
        foreach ($blocos as $bloco) {
            wp_delete_post($bloco->ID, true); // true para forçar a exclusão
        }
    }

    // Remove o CPT do banco de dados (opcional, mas bom para limpeza completa)
    unregister_post_type('meu_banner_bloco');
    flush_rewrite_rules();
}
register_uninstall_hook(__FILE__, 'meu_banner_uninstall');

/**
 * Ação de desativação: apenas limpa as regras de reescrita.
 */
function meu_banner_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'meu_banner_deactivate');

/**
 * Ação de ativação: registra o CPT e define um transiente para limpar as regras de reescrita.
 * Isso evita timeouts em atualizações, adiando a chamada flush_rewrite_rules().
 */
function meu_banner_activate() {
    meu_banner_register_post_type();
    set_transient('meu_banner_flush_rewrite_rules', true, 30);
}
register_activation_hook(__FILE__, 'meu_banner_activate');

/**
 * Limpa as regras de reescrita na inicialização do admin se o transiente estiver definido.
 */
function meu_banner_flush_rules_on_init() {
    if (get_transient('meu_banner_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_transient('meu_banner_flush_rewrite_rules');
    }
}
add_action('admin_init', 'meu_banner_flush_rules_on_init');
