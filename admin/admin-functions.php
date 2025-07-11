<?php
if (!defined('ABSPATH')) {
    exit;
}

// ... (funções anteriores inalteradas) ...
function meu_banner_add_meta_box() {
    add_meta_box(
        'meu_banner_settings_meta_box',
        __('Configurações do Bloco de Anúncio', 'meu-banner'),
        'meu_banner_render_meta_box_content',
        'meu_banner_bloco',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'meu_banner_add_meta_box');

/**
 * Renderiza o conteúdo da Meta Box.
 *
 * @param WP_Post $post O objeto do post atual.
 */
function meu_banner_render_meta_box_content($post) {
    wp_nonce_field('meu_banner_save_meta_box_data', 'meu_banner_meta_box_nonce');
    $data = get_post_meta($post->ID, '_meu_banner_data', true);
    
    // *** NOVO: Recupera o modo de exibição, com 'geral' como padrão ***
    $display_mode = isset($data['display_mode']) ? $data['display_mode'] : 'geral';
    $subgrupos = isset($data['subgrupos']) ? $data['subgrupos'] : ['geral' => [], 'desktop' => [], 'mobile' => []];
    $tracking_enabled = isset($data['tracking_enabled']) ? $data['tracking_enabled'] : false;
    ?>
    <style>
        .meu-banner-subgroup { border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px; }
        .meu-banner-subgroup h3 { margin-top: 0; }
        .banner-item { background: #f9f9f9; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; }
        .banner-item .field { margin-bottom: 10px; }
        .banner-item .field label { font-weight: bold; display: block; margin-bottom: 5px; }
        .banner-item textarea, .banner-item input[type=text], .banner-item input[type=number] { width: 100%; }
        .banner-item .image-preview { max-width: 200px; max-height: 100px; display: block; margin-top: 10px; }
        .meu-banner-mode-selection { margin-bottom: 20px; padding: 10px; background-color: #f0f0f1; border-left: 4px solid #7e8993; }
    </style>

    <p><strong><?php _e('Rastreamento de Visualizações', 'meu-banner'); ?></strong></p>
    <label>
        <input type="checkbox" name="meu_banner_data[tracking_enabled]" value="1" <?php checked($tracking_enabled, true); ?>>
        <?php _e('Ativar rastreamento de visualizações para este bloco.', 'meu-banner'); ?>
    </label>
    <hr>
    
    <!-- *** NOVO: Seletor de modo de exibição *** -->
    <div class="meu-banner-mode-selection">
        <p><strong><?php _e('Modo de Exibição do Banner', 'meu-banner'); ?></strong></p>
        <label style="margin-right: 20px;">
            <input type="radio" name="meu_banner_data[display_mode]" value="geral" <?php checked($display_mode, 'geral'); ?>>
            <?php _e('Geral (um banner para todos os dispositivos)', 'meu-banner'); ?>
        </label>
        <label>
            <input type="radio" name="meu_banner_data[display_mode]" value="responsivo" <?php checked($display_mode, 'responsivo'); ?>>
            <?php _e('Responsivo (banners separados para Desktop e Mobile)', 'meu-banner'); ?>
        </label>
    </div>

    <!-- *** NOVO: Wrapper para o grupo "Geral" *** -->
    <div id="meu-banner-geral-wrapper">
        <div class="meu-banner-subgroup" id="subgroup-geral">
            <h3><?php _e('Geral', 'meu-banner'); ?></h3>
            <div class="banners-container">
            <?php if (!empty($subgrupos['geral'])): ?>
                <?php foreach ($subgrupos['geral'] as $index => $banner): ?>
                    <?php meu_banner_render_banner_fields('geral', $index, $banner); ?>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
            <button type="button" class="button add-banner" data-subgroup="geral"><?php _e('Adicionar Banner', 'meu-banner'); ?></button>
        </div>
    </div>
    
    <!-- *** NOVO: Wrapper para os grupos "Responsivos" *** -->
    <div id="meu-banner-responsivo-wrapper">
        <?php foreach (['desktop', 'mobile'] as $key) : ?>
        <div class="meu-banner-subgroup" id="subgroup-<?php echo esc_attr($key); ?>">
            <h3><?php echo esc_html(ucfirst($key)); ?></h3>
            <div class="banners-container">
            <?php if (!empty($subgrupos[$key])): ?>
                <?php foreach ($subgrupos[$key] as $index => $banner): ?>
                    <?php meu_banner_render_banner_fields($key, $index, $banner); ?>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
            <button type="button" class="button add-banner" data-subgroup="<?php echo esc_attr($key); ?>"><?php _e('Adicionar Banner', 'meu-banner'); ?></button>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Template do Banner (inalterado) -->
    <template id="meu-banner-template">
        <?php meu_banner_render_banner_fields('{subgroup}', '{index}'); ?>
    </template>
    
    <!-- *** NOVO/ATUALIZADO: JavaScript para controlar a UI *** -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modeRadios = document.querySelectorAll('input[name="meu_banner_data[display_mode]"]');
            const geralWrapper = document.getElementById('meu-banner-geral-wrapper');
            const responsivoWrapper = document.getElementById('meu-banner-responsivo-wrapper');

            function toggleDisplayMode() {
                const selectedMode = document.querySelector('input[name="meu_banner_data[display_mode]"]:checked').value;
                if (selectedMode === 'geral') {
                    geralWrapper.style.display = 'block';
                    responsivoWrapper.style.display = 'none';
                } else {
                    geralWrapper.style.display = 'none';
                    responsivoWrapper.style.display = 'block';
                }
            }

            // Define o estado inicial ao carregar a página
            toggleDisplayMode();

            // Adiciona o listener para mudanças
            modeRadios.forEach(radio => radio.addEventListener('change', toggleDisplayMode));

            // Lógica para adicionar novos banners (inalterada)
            document.querySelectorAll('.add-banner').forEach(button => {
                button.addEventListener('click', function() {
                    const subgroup = this.dataset.subgroup;
                    const container = document.querySelector(`#subgroup-${subgroup} .banners-container`);
                    const index = container.children.length;
                    const template = document.getElementById('meu-banner-template').innerHTML;
                    
                    const newBannerHtml = template
                        .replace(/{subgroup}/g, subgroup)
                        .replace(/{index}/g, index);
                    
                    container.insertAdjacentHTML('beforeend', newBannerHtml);
                });
            });

            // Lógica para remover banners (inalterada)
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-banner')) {
                    e.target.closest('.banner-item').remove();
                }
            });

            // Lógica para o seletor de tipo de conteúdo (inalterada)
            document.addEventListener('change', function(e) {
                if (e.target && e.target.classList.contains('banner-type-select')) {
                    const parent = e.target.closest('.banner-item');
                    const type = e.target.value;
                    parent.querySelector('.content-html').style.display = type === 'html' ? 'block' : 'none';
                    parent.querySelector('.content-image').style.display = type === 'image' ? 'block' : 'none';
                }
            });

            // Lógica para o seletor de imagem (inalterada)
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('upload-image-button')) {
                    e.preventDefault();
                    const parent = e.target.closest('.content-image');
                    const imageIdInput = parent.querySelector('.image-id-input');
                    const imagePreview = parent.querySelector('.image-preview');

                    const mediaUploader = wp.media({
                        title: '<?php _e("Selecionar Imagem", "meu-banner"); ?>',
                        button: { text: '<?php _e("Usar esta imagem", "meu-banner"); ?>' },
                        multiple: false
                    }).on('select', function() {
                        const attachment = mediaUploader.state().get('selection').first().toJSON();
                        imageIdInput.value = attachment.id;
                        imagePreview.src = attachment.url;
                        imagePreview.style.display = 'block';
                    }).open();
                }
            });
        });
    </script>
    <?php
    wp_enqueue_media();
}

// ... (função meu_banner_render_banner_fields inalterada) ...
function meu_banner_render_banner_fields($subgroup, $index, $banner = []) {
    $type = isset($banner['type']) ? $banner['type'] : 'html';
    $content = isset($banner['content']) ? $banner['content'] : '';
    $image_id = isset($banner['image_id']) ? absint($banner['image_id']) : 0;
    $url = isset($banner['url']) ? esc_url($banner['url']) : '';
    $weight = isset($banner['weight']) ? absint($banner['weight']) : 5;
    $image_src = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
    ?>
    <div class="banner-item">
        <button type="button" class="button button-link-delete remove-banner" style="float: right;"><?php _e('Remover', 'meu-banner'); ?></button>
        <div class="field">
            <label><?php _e('Tipo de Conteúdo', 'meu-banner'); ?></label>
            <select name="meu_banner_data[subgrupos][<?php echo esc_attr($subgroup); ?>][<?php echo esc_attr($index); ?>][type]" class="banner-type-select">
                <option value="html" <?php selected($type, 'html'); ?>><?php _e('HTML Personalizado', 'meu-banner'); ?></option>
                <option value="image" <?php selected($type, 'image'); ?>><?php _e('Imagem + URL', 'meu-banner'); ?></option>
            </select>
        </div>
        <div class="content-html" style="display: <?php echo $type === 'html' ? 'block' : 'none'; ?>;">
            <div class="field">
                <label for="banner-content-<?php echo esc_attr($subgroup . $index); ?>"><?php _e('Código HTML', 'meu-banner'); ?></label>
                <textarea name="meu_banner_data[subgrupos][<?php echo esc_attr($subgroup); ?>][<?php echo esc_attr($index); ?>][content]" rows="5"><?php echo esc_textarea($content); ?></textarea>
            </div>
        </div>
        <div class="content-image" style="display: <?php echo $type === 'image' ? 'block' : 'none'; ?>;">
            <div class="field">
                <label><?php _e('Imagem', 'meu-banner'); ?></label>
                <input type="hidden" class="image-id-input" name="meu_banner_data[subgrupos][<?php echo esc_attr($subgroup); ?>][<?php echo esc_attr($index); ?>][image_id]" value="<?php echo $image_id; ?>">
                <button type="button" class="button upload-image-button"><?php _e('Selecionar Imagem', 'meu-banner'); ?></button>
                <img src="<?php echo esc_url($image_src); ?>" class="image-preview" style="display: <?php echo $image_id ? 'block' : 'none'; ?>;">
            </div>
            <div class="field">
                <label><?php _e('URL do Link', 'meu-banner'); ?></label>
                <input type="text" name="meu_banner_data[subgrupos][<?php echo esc_attr($subgroup); ?>][<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_url($url); ?>">
            </div>
        </div>
        <div class="field">
            <label><?php _e('Peso (1 a 10)', 'meu-banner'); ?></label>
            <input type="number" name="meu_banner_data[subgrupos][<?php echo esc_attr($subgroup); ?>][<?php echo esc_attr($index); ?>][weight]" min="1" max="10" value="<?php echo $weight; ?>">
        </div>
    </div>
    <?php
}

/**
 * Salva os dados da Meta Box.
 *
 * @param int $post_id ID do post que está sendo salvo.
 */
function meu_banner_save_meta_box_data($post_id) {
    if (!isset($_POST['meu_banner_meta_box_nonce']) || !wp_verify_nonce($_POST['meu_banner_meta_box_nonce'], 'meu_banner_save_meta_box_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $old_data = get_post_meta($post_id, '_meu_banner_data', true);
    $raw_data = isset($_POST['meu_banner_data']) ? $_POST['meu_banner_data'] : [];

    $sanitized_data = is_array($old_data) ? $old_data : [];

    $sanitized_data['display_mode'] = isset($raw_data['display_mode']) && in_array($raw_data['display_mode'], ['geral', 'responsivo']) ? $raw_data['display_mode'] : 'geral';

    $is_tracking_enabled_now = isset($raw_data['tracking_enabled']);
    $was_tracking_enabled = isset($old_data['tracking_enabled']) && $old_data['tracking_enabled'];

    $sanitized_data['tracking_enabled'] = $is_tracking_enabled_now;

    if ($is_tracking_enabled_now && !$was_tracking_enabled && empty($sanitized_data['tracking_start_date'])) {
        $sanitized_data['tracking_start_date'] = current_time('Y-m-d');
    }

    // Sanitiza os subgrupos e banners
    $sanitized_data['subgrupos'] = [];
    if (isset($raw_data['subgrupos']) && is_array($raw_data['subgrupos'])) {
        foreach (['geral', 'desktop', 'mobile'] as $key) {
            $sanitized_data['subgrupos'][$key] = [];
            if (isset($raw_data['subgrupos'][$key]) && is_array($raw_data['subgrupos'][$key])) {
                foreach ($raw_data['subgrupos'][$key] as $banner) {
                    $sanitized_banner = [
                        'type'     => sanitize_text_field($banner['type']),
                        'weight'   => absint($banner['weight']),
                        'content'  => '',
                        'image_id' => 0,
                        'url'      => '',
                    ];
                    if ($sanitized_banner['type'] === 'html') {
                        $sanitized_banner['content'] = wp_kses_post($banner['content']);
                    } else {
                        $sanitized_banner['image_id'] = absint($banner['image_id']);
                        $sanitized_banner['url'] = esc_url_raw($banner['url']);
                    }
                    if (!empty($sanitized_banner['content']) || $sanitized_banner['image_id'] > 0) {
                        $sanitized_data['subgrupos'][$key][] = $sanitized_banner;
                    }
                }
            }
        }
    }
    
    update_post_meta($post_id, '_meu_banner_data', $sanitized_data);
}
add_action('save_post', 'meu_banner_save_meta_box_data');

// ... (Restante do arquivo admin-functions.php permanece o mesmo) ...
// (funções set_custom_edit_columns, custom_column, add_row_actions, etc.)
// A partir daqui, o código é o mesmo da versão anterior. Incluí para completude.

/**
 * Adiciona colunas personalizadas à lista de blocos.
 */
function meu_banner_set_custom_edit_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key == 'title') {
            $new_columns['shortcode'] = __('Shortcode', 'meu-banner');
            $new_columns['taxonomy-cr_campaign'] = __('Campanha', 'meu-banner');
            $new_columns['total_views'] = __('Visualizações', 'meu-banner');
            $new_columns['report'] = __('Relatório', 'meu-banner'); // Nova coluna
        }
    }
    return $new_columns;
}
add_filter('manage_meu_banner_bloco_posts_columns', 'meu_banner_set_custom_edit_columns');

// Adiciona a coluna "Visualizações" na lista de termos da taxonomia "cr_campaign"
function meu_banner_add_campaign_views_column($columns) {
    $columns['views'] = __('Visualizações', 'meu-banner');
    return $columns;
}
add_filter('manage_edit-cr_campaign_columns', 'meu_banner_add_campaign_views_column');

// Exibe o link para o relatório de visualizações da campanha
function meu_banner_add_campaign_views_column_content($content, $column_name, $term_id) {
    if ('views' === $column_name) {
        $report_url = admin_url('admin.php?page=meu_banner_campaign_report&campaign_id=' . $term_id);
        return '<a href="' . esc_url($report_url) . '" class="button">' . __('Ver Relatório', 'meu-banner') . '</a>';
    }
    return $content;
}
add_filter('manage_cr_campaign_custom_column', 'meu_banner_add_campaign_views_column_content', 10, 3);

/**
 * Exibe o conteúdo das colunas personalizadas.
 */
function meu_banner_custom_column($column, $post_id) {
    switch ($column) {
        case 'taxonomy-cr_campaign':
            $terms = get_the_terms($post_id, 'cr_campaign');
            if (is_array($terms)) {
                $campaign_names = [];
                foreach ($terms as $term) {
                    $campaign_names[] = esc_html($term->name);
                }
                echo implode(', ', $campaign_names);
            } else {
                echo '—';
            }
            break;

        case 'shortcode':
            $post_slug = get_post_field('post_name', $post_id);
            echo '<code>[meu_banner id="' . $post_id . '"]</code><br>';
            echo '<code>[meu_banner name="' . esc_attr($post_slug) . '"]</code>';
            break;

        case 'total_views':
            $daily_counts = get_post_meta($post_id, 'meu_banner_daily_views', true);
            if (!is_array($daily_counts)) {
                $daily_counts = [];
            }

            $today_str = current_time('Y-m-d');
            $yesterday_str = date('Y-m-d', strtotime('-1 day', strtotime($today_str)));

            $today_views = isset($daily_counts[$today_str]) ? $daily_counts[$today_str] : 0;
            $yesterday_views = isset($daily_counts[$yesterday_str]) ? $daily_counts[$yesterday_str] : 0;
            $total_views = array_sum($daily_counts);

            echo '<strong>Hoje:</strong> ' . number_format_i18n($today_views) . '<br>';
            echo '<strong>Ontem:</strong> ' . number_format_i18n($yesterday_views) . '<br>';
            echo '<strong>Total:</strong> ' . number_format_i18n($total_views);
            break;

        case 'report':
            $report_url = admin_url('edit.php?post_type=meu_banner_bloco&page=meu_banner_report&bloco_id=' . $post_id);
            echo '<a href="' . esc_url($report_url) . '" class="button">' . __('Ver Relatório', 'meu-banner') . '</a>';
            break;
    }
}
add_action('manage_meu_banner_bloco_posts_custom_column', 'meu_banner_custom_column', 10, 2);

/**
 * Adiciona ações personalizadas (links) para cada item na lista de blocos.
 */
function meu_banner_add_row_actions($actions, $post) {
    if ($post->post_type === 'meu_banner_bloco') {
        $report_url = admin_url('edit.php?post_type=meu_banner_bloco&page=meu_banner_report&bloco_id=' . $post->ID);
        $actions['report'] = '<a href="' . esc_url($report_url) . '">' . __('Relatório', 'meu-banner') . '</a>';
        $reset_url = wp_nonce_url(admin_url('admin-post.php?action=meu_banner_reset_views&bloco_id=' . $post->ID), 'meu_banner_reset_views_nonce');
        $actions['reset_views'] = '<a href="' . esc_url($reset_url) . '" onclick="return confirm(\'Tem certeza que deseja zerar as visualizações deste bloco?\');" style="color:#a00;">' . __('Zerar Visualizações', 'meu-banner') . '</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'meu_banner_add_row_actions', 10, 2);





/**
 * Adiciona a página de "Inserção Automática" ao menu do plugin.
 * A página é renderizada por uma função no arquivo auto-insert-page.php
 */
function meu_banner_add_auto_insert_page() {
    add_submenu_page(
        'edit.php?post_type=meu_banner_bloco', // Parent slug
        __('Inserção Automática', 'meu-banner'), // Page title
        __('Inserção Automática', 'meu-banner'), // Menu title
        'manage_options', // Capability
        'meu_banner_auto_insert', // Menu slug
        'meu_banner_render_auto_insert_page' // Função que renderiza a página
    );
}
add_action('admin_menu', 'meu_banner_add_auto_insert_page');

function meu_banner_add_campaign_report_page() {
    add_submenu_page(
        null, // Não exibe no menu
        __('Relatório da Campanha', 'meu-banner'),
        __('Relatório da Campanha', 'meu-banner'),
        'manage_options',
        'meu_banner_campaign_report',
        'meu_banner_render_campaign_report_page'
    );
}
add_action('admin_menu', 'meu_banner_add_campaign_report_page');

/**
 * Enfileira scripts e estilos apenas na página de Inserção Automática.
 */
function meu_banner_enqueue_admin_rules_script($hook) {
    // CORREÇÃO: O hook correto usa o slug do CPT, não o título do menu.
    if ($hook !== 'meu_banner_bloco_page_meu_banner_auto_insert') {
        return;
    }

    wp_enqueue_script(
        'meu-banner-admin-rules',
        MEU_BANNER_PLUGIN_URL . 'js/admin-rules.js',
        [],
        '1.2.1', // Atualizando a versão para garantir que o cache seja limpo
        true
    );
}
add_action('admin_enqueue_scripts', 'meu_banner_enqueue_admin_rules_script');

