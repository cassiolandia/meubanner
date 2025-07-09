<?php
if (!defined('ABSPATH')) {
    exit;
}

function meu_banner_render_campaign_report_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.', 'meu-banner'));
    }

    $campaign_id = isset($_GET['campaign_id']) ? absint($_GET['campaign_id']) : 0;

    if (!$campaign_id) {
        wp_die(__('Nenhuma campanha selecionada.', 'meu-banner'));
    }

    $campaign = get_term($campaign_id, 'cr_campaign');

    if (!$campaign || is_wp_error($campaign)) {
        wp_die(__('Campanha não encontrada.', 'meu-banner'));
    }

    $args = [
        'post_type' => 'meu_banner_bloco',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'tax_query' => [
            [
                'taxonomy' => 'cr_campaign',
                'field'    => 'term_id',
                'terms'    => $campaign_id,
            ],
        ],
    ];

    $blocos_query = new WP_Query($args);
    $total_views = 0;
    $daily_views = [];

    if ($blocos_query->have_posts()) {
        while ($blocos_query->have_posts()) {
            $blocos_query->the_post();
            $daily_counts = get_post_meta(get_the_ID(), 'meu_banner_daily_views', true);
            if (is_array($daily_counts)) {
                $total_views += array_sum($daily_counts);
                foreach ($daily_counts as $date => $count) {
                    if (isset($daily_views[$date])) {
                        $daily_views[$date] += $count;
                    } else {
                        $daily_views[$date] = $count;
                    }
                }
            }
        }
    }
    wp_reset_postdata();

    $report_days = [];
    if (!empty($daily_views)) {
        $end_date = new DateTime(current_time('Y-m-d'));
        $start_date = new DateTime(min(array_keys($daily_views)));
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

        foreach ($period as $date) {
            $day_str = $date->format('Y-m-d');
            $report_days[$day_str] = isset($daily_views[$day_str]) ? $daily_views[$day_str] : 0;
        }
        krsort($report_days);
    }

    ?>
    <div class="wrap">
        <h1><?php echo sprintf(__('Relatório da Campanha: %s', 'meu-banner'), esc_html($campaign->name)); ?></h1>
        <p><?php _e('Total de visualizações de todos os blocos de anúncio associados a esta campanha.', 'meu-banner'); ?></p>
        
        <div id="total-views-box" style="background: #fff; padding: 20px; margin-top: 20px; text-align: center; border: 1px solid #ccd0d4; width: 200px; display: inline-block; vertical-align: top;">
            <h2 style="font-size: 3em; margin: 0;"><?php echo number_format_i18n($total_views); ?></h2>
            <p style="margin: 0; font-size: 1.2em; color: #50575e;"><?php _e('Visualizações Totais', 'meu-banner'); ?></p>
        </div>

        <div id="daily-views-section" style="margin-top: 30px;">
            <h2><?php _e('Visualizações por Dia', 'meu-banner'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 50%;"><?php _e('Data', 'meu-banner'); ?></th>
                        <th scope="col"><?php _e('Visualizações', 'meu-banner'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_days)): ?>
                        <?php foreach ($report_days as $date => $count): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($date))); ?></td>
                                <td><?php echo number_format_i18n($count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2"><?php _e('Nenhuma visualização registrada para esta campanha ainda.', 'meu-banner'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p style="margin-top: 30px;">
            <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=cr_campaign')); ?>" class="button">&larr; <?php _e('Voltar para Campanhas', 'meu-banner'); ?></a>
        </p>
    </div>
    <?php
}
