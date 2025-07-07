<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adiciona a página de relatório ao menu do CPT.
 */
function meu_banner_add_report_page() {
    add_submenu_page(
        'edit.php?post_type=meu_banner_bloco', // Slug do menu pai (CPT)
        __('Relatório de Visualizações', 'meu-banner'), // Título da página
        __('Relatório', 'meu-banner'), // Título do menu
        'edit_posts', // Capacidade
        'meu_banner_report', // Slug da página
        'meu_banner_render_report_page' // Função de renderização
    );
}
add_action('admin_menu', 'meu_banner_add_report_page');

/**
 * Oculta a página de relatório do menu com CSS.
 */
function meu_banner_hide_report_page_css() {
    echo '<style>#adminmenu a[href="edit.php?post_type=meu_banner_bloco&page=meu_banner_report"] { display: none; }</style>';
}
add_action('admin_head', 'meu_banner_hide_report_page_css');

/**
 * Renderiza a página de relatório com contagens diárias.
 */
function meu_banner_render_report_page() {
    if (!isset($_GET['bloco_id']) || !current_user_can('edit_posts')) {
        wp_die(__('Acesso inválido.', 'meu-banner'));
    }

    $bloco_id = absint($_GET['bloco_id']);
    $bloco = get_post($bloco_id);

    if (!$bloco || $bloco->post_type !== 'meu_banner_bloco') {
        wp_die(__('Bloco não encontrado.', 'meu-banner'));
    }

    $data = get_post_meta($bloco_id, '_meu_banner_data', true);
    $tracking_enabled = !empty($data['tracking_enabled']);
    $daily_counts = get_post_meta($bloco_id, 'meu_banner_daily_views', true);
    if (!is_array($daily_counts)) {
        $daily_counts = [];
    }
    krsort($daily_counts); // Ordena por data, da mais recente para a mais antiga

    ?>
    <div class="wrap">
        <h1><?php _e('Relatório de Visualizações para:', 'meu-banner'); ?> "<?php echo esc_html($bloco->post_title); ?>"</h1>
        
        <div style="margin: 20px 0; padding: 15px; border: 1px solid #ccd0d4; background: #f6f7f7; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong><?php _e('Status do Rastreamento:', 'meu-banner'); ?></strong>
                <span style="color: <?php echo $tracking_enabled ? '#228B22' : '#DC143C'; ?>; font-weight: bold; margin-left: 10px;">
                    <?php echo $tracking_enabled ? __('Ativo', 'meu-banner') : __('Inativo', 'meu-banner'); ?>
                </span>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=meu_banner_toggle_tracking&bloco_id=' . $bloco_id), 'meu_banner_toggle_tracking_nonce'); ?>" class="button button-secondary" style="margin-left: 15px;">
                    <?php echo $tracking_enabled ? __('Desativar', 'meu-banner') : __('Ativar', 'meu-banner'); ?>
                </a>
            </div>
            <div>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=meu_banner_reset_views&bloco_id=' . $bloco_id), 'meu_banner_reset_views_nonce'); ?>" onclick="return confirm('<?php _e('Tem certeza que deseja zerar as visualizações deste bloco? Esta ação não pode ser desfeita.', 'meu-banner'); ?>');" class="button button-link-delete"><?php _e('Zerar Contagem', 'meu-banner'); ?></a>
            </div>
        </div>

        <p><a href="<?php echo admin_url('edit.php?post_type=meu_banner_bloco'); ?>">← <?php _e('Voltar para a lista de blocos', 'meu-banner'); ?></a></p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 200px;"><?php _e('Data', 'meu-banner'); ?></th>
                    <th><?php _e('Visualizações', 'meu-banner'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($daily_counts)): ?>
                <tr><td colspan="2"><?php _e('Ainda não há visualizações registradas.', 'meu-banner'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($daily_counts as $date => $count): ?>
                    <tr>
                        <td><?php echo date_i18n('d/m/Y', strtotime($date)); ?></td>
                        <td><?php echo number_format_i18n($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Manipula a ação de zerar as visualizações (versão com contagem diária).
 */
function meu_banner_handle_reset_views_action() {
    if (!isset($_GET['bloco_id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'meu_banner_reset_views_nonce')) {
        wp_die(__('Ação inválida ou falha de segurança.', 'meu-banner'));
    }
    if (!current_user_can('edit_posts')) {
        wp_die(__('Você não tem permissão para fazer isso.', 'meu-banner'));
    }

    $bloco_id = absint($_GET['bloco_id']);

    // Deleta o metadado com as contagens diárias.
    delete_post_meta($bloco_id, 'meu_banner_daily_views');

    // Redireciona de volta para a página de onde o usuário veio.
    $redirect_url = add_query_arg('message', 'views_reset', wp_get_referer());
    if (!$redirect_url) {
        $redirect_url = admin_url('edit.php?post_type=meu_banner_bloco');
    }
    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_meu_banner_reset_views', 'meu_banner_handle_reset_views_action');

/**
 * Manipula a ação de ativar/desativar o rastreamento a partir da página de relatório.
 */
function meu_banner_toggle_tracking_action() {
    if (!isset($_GET['bloco_id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'meu_banner_toggle_tracking_nonce')) {
        wp_die(__('Ação inválida ou falha de segurança.', 'meu-banner'));
    }
    if (!current_user_can('edit_posts')) {
        wp_die(__('Você não tem permissão para fazer isso.', 'meu-banner'));
    }

    $bloco_id = absint($_GET['bloco_id']);
    $data = get_post_meta($bloco_id, '_meu_banner_data', true);

    if (is_array($data)) {
        $data['tracking_enabled'] = empty($data['tracking_enabled']);

        if ($data['tracking_enabled'] && empty($data['tracking_start_date'])) {
            $data['tracking_start_date'] = current_time('Y-m-d');
        }

        update_post_meta($bloco_id, '_meu_banner_data', $data);
    }

    wp_redirect(wp_get_referer());
    exit;
}
add_action('admin_post_meu_banner_toggle_tracking', 'meu_banner_toggle_tracking_action');

/**
 * Exibe a notificação de sucesso após zerar as visualizações.
 */
function meu_banner_show_reset_notice() {
    if (isset($_GET['message']) && $_GET['message'] === 'views_reset') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('As visualizações foram zeradas com sucesso!', 'meu-banner') . '</p></div>';
    }
}
add_action('admin_notices', 'meu_banner_show_reset_notice');
