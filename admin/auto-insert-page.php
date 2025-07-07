<?php
if (!defined('ABSPATH')) {
    exit;
}

function meu_banner_render_auto_insert_page() {
    if (isset($_POST['meu_banner_save_rules_nonce']) && wp_verify_nonce($_POST['meu_banner_save_rules_nonce'], 'meu_banner_save_rules')) {
        $rules = [];
        if (isset($_POST['rules']) && is_array($_POST['rules'])) {
            foreach ($_POST['rules'] as $rule_data) {
                $sanitized_rule = [
                    'enabled'       => isset($rule_data['enabled']) ? 1 : 0,
                    'bloco_id'      => isset($rule_data['bloco_id']) ? intval($rule_data['bloco_id']) : 0,
                    'insertion_type'=> isset($rule_data['insertion_type']) ? sanitize_key($rule_data['insertion_type']) : 'content',
                    'position'      => isset($rule_data['position']) ? sanitize_key($rule_data['position']) : 'before_content',
                    'paragraph_num' => isset($rule_data['paragraph_num']) ? intval($rule_data['paragraph_num']) : 1,
                    'align'         => isset($rule_data['align']) ? sanitize_key($rule_data['align']) : 'center',
                    'page_format'   => isset($rule_data['page_format']) ? sanitize_key($rule_data['page_format']) : 'popup',
                    'page_style'    => isset($rule_data['page_style']) ? sanitize_key($rule_data['page_style']) : 'dark',
                    'frequency_type' => isset($rule_data['frequency_type']) ? sanitize_key($rule_data['frequency_type']) : 'always',
                    'frequency_time_value' => isset($rule_data['frequency_time_value']) ? intval($rule_data['frequency_time_value']) : 1,
                    'frequency_time_unit' => isset($rule_data['frequency_time_unit']) ? sanitize_key($rule_data['frequency_time_unit']) : 'hours',
                    'frequency_access_value' => isset($rule_data['frequency_access_value']) ? intval($rule_data['frequency_access_value']) : 5,
                    'list_position' => isset($rule_data['list_position']) ? sanitize_key($rule_data['list_position']) : 'after_item',
                    'list_item_num' => isset($rule_data['list_item_num']) ? intval($rule_data['list_item_num']) : 1,
                    'post_types'    => isset($rule_data['post_types']) && is_array($rule_data['post_types']) ? array_map('sanitize_text_field', $rule_data['post_types']) : [],
                ];
                if ($sanitized_rule['bloco_id'] > 0) {
                    $rules[] = $sanitized_rule;
                }
            }
        }
        update_option('meu_banner_auto_insert_rules', $rules);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Regras de inserção salvas com sucesso!', 'meu-banner') . '</p></div>';
    }

    $all_rules = get_option('meu_banner_auto_insert_rules', []);

    $content_rules = [];
    $site_rules = [];
    $list_rules = [];
    foreach ($all_rules as $index => $rule) {
        if (isset($rule['insertion_type']) && $rule['insertion_type'] === 'page') {
            $site_rules[$index] = $rule;
        } else if (isset($rule['insertion_type']) && $rule['insertion_type'] === 'list') {
            $list_rules[$index] = $rule;
        } else {
            $content_rules[$index] = $rule;
        }
    }
    
    add_action('admin_footer', 'meu_banner_auto_insert_page_scripts');
    ?>
    <div class="wrap">
        <h1><?php _e('Inserção Automática de Banners', 'meu-banner'); ?></h1>
        <p><?php _e('Crie regras para inserir seus blocos de anúncios automaticamente no conteúdo ou em locais do site.', 'meu-banner'); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field('meu_banner_save_rules', 'meu_banner_save_rules_nonce'); ?>

            <h2 class="nav-tab-wrapper">
                <a href="#tab-content" class="nav-tab nav-tab-active"><?php printf(__('Conteúdo (%d)', 'meu-banner'), count($content_rules)); ?></a>
                <a href="#tab-site" class="nav-tab"><?php printf(__('Site (%d)', 'meu-banner'), count($site_rules)); ?></a>
                <a href="#tab-listas" class="nav-tab"><?php printf(__('Listas (%d)', 'meu-banner'), count($list_rules)); ?></a>
            </h2>

            <div id="meu-banner-rules-wrapper">
                <div id="tab-content" class="tab-pane active">
                    <div class="rules-container">
                        <?php
                        if (!empty($content_rules)) {
                            foreach ($content_rules as $index => $rule) {
                                meu_banner_render_rule_fields($index, $rule);
                            }
                        } else {
                            echo '<p>' . __('Nenhuma regra de inserção no conteúdo encontrada.', 'meu-banner') . '</p>';
                        }
                        ?>
                    </div>
                    <p>
                        <button type="button" id="meu-banner-add-content-rule" class="button button-secondary"><?php _e('Adicionar Nova Regra de Conteúdo', 'meu-banner'); ?></button>
                    </p>
                </div>

                <div id="tab-site" class="tab-pane">
                     <div class="rules-container">
                        <?php
                        if (!empty($site_rules)) {
                            foreach ($site_rules as $index => $rule) {
                                meu_banner_render_rule_fields($index, $rule);
                            }
                        } else {
                             echo '<p>' . __('Nenhuma regra de inserção no site encontrada.', 'meu-banner') . '</p>';
                        }
                        ?>
                    </div>
                    <p>
                        <button type="button" id="meu-banner-add-site-rule" class="button button-secondary"><?php _e('Adicionar Nova Regra de Site', 'meu-banner'); ?></button>
                    </p>
                </div>

                <div id="tab-listas" class="tab-pane">
                     <div class="rules-container">
                        <?php
                        if (!empty($list_rules)) {
                            foreach ($list_rules as $index => $rule) {
                                meu_banner_render_rule_fields($index, $rule);
                            }
                        } else {
                             echo '<p>' . __('Nenhuma regra de inserção em listas encontrada.', 'meu-banner') . '</p>';
                        }
                        ?>
                    </div>
                    <p>
                        <button type="button" id="meu-banner-add-list-rule" class="button button-secondary"><?php _e('Adicionar Nova Regra de Lista', 'meu-banner'); ?></button>
                    </p>
                </div>
            </div>

            <hr>
            <?php submit_button(__('Salvar Todas as Regras', 'meu-banner'), 'primary', 'submit', false); ?>
        </form>
    </div>

    <template id="meu-banner-rule-content-template">
        <?php meu_banner_render_rule_fields('{index}', ['insertion_type' => 'content']); ?>
    </template>
    <template id="meu-banner-rule-site-template">
        <?php meu_banner_render_rule_fields('{index}', ['insertion_type' => 'page']); ?>
    </template>
    <template id="meu-banner-rule-list-template">
        <?php meu_banner_render_rule_fields('{index}', ['insertion_type' => 'list']); ?>
    </template>
    <?php
}

function meu_banner_render_rule_fields($index, $rule = []) {
    $insertion_type      = $rule['insertion_type'] ?? 'content';
    $enabled             = $rule['enabled'] ?? 1;
    $bloco_id            = $rule['bloco_id'] ?? 0;
    $selected_post_types = $rule['post_types'] ?? [];

    $blocos = get_posts(['post_type' => 'meu_banner_bloco', 'post_status' => 'publish', 'numberposts' => -1]);
    $post_types = get_post_types(['public' => true], 'objects');
    ?>
    <div class="meu-banner-rule" style="border: 1px solid #ccd0d4; padding: 20px; margin-bottom: 20px; background: #fff;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">
            <?php 
            if ($insertion_type === 'page') {
                echo __('Regra de Site', 'meu-banner');
            } else if ($insertion_type === 'list') {
                echo __('Regra de Lista', 'meu-banner');
            } else {
                echo __('Regra de Conteúdo', 'meu-banner');
            }
            ?>
            <button type="button" class="button button-link-delete meu-banner-remove-rule" style="float: right;"><?php _e('Remover', 'meu-banner'); ?></button>
        </h3>
        
        <input type="hidden" name="rules[<?php echo esc_attr($index); ?>][insertion_type]" value="<?php echo esc_attr($insertion_type); ?>">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label><?php _e('Ativada', 'meu-banner'); ?></label></th>
                    <td><input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][enabled]" value="1" <?php checked($enabled, 1); ?>></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php _e('Bloco de Anúncio', 'meu-banner'); ?></label></th>
                    <td>
                        <select name="rules[<?php echo esc_attr($index); ?>][bloco_id]">
                            <option value="0"><?php _e('-- Selecione um Bloco --', 'meu-banner'); ?></option>
                            <?php foreach ($blocos as $bloco) : ?>
                                <option value="<?php echo esc_attr($bloco->ID); ?>" <?php selected($bloco_id, $bloco->ID); ?>><?php echo esc_html($bloco->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <?php if ($insertion_type === 'content'): ?>
                    <?php
                        $position = $rule['position'] ?? 'before_content';
                        $paragraph_num = $rule['paragraph_num'] ?? 1;
                        $align = $rule['align'] ?? 'center';
                    ?>
                    <tr>
                        <th scope="row"><label><?php _e('Posição no Conteúdo', 'meu-banner'); ?></label></th>
                        <td>
                            <select name="rules[<?php echo esc_attr($index); ?>][position]" class="meu-banner-position-select">
                                <option value="before_content" <?php selected($position, 'before_content'); ?>><?php _e('Antes do conteúdo', 'meu-banner'); ?></option>
                                <option value="after_content" <?php selected($position, 'after_content'); ?>><?php _e('Depois do conteúdo', 'meu-banner'); ?></option>
                                <option value="after_paragraph" <?php selected($position, 'after_paragraph'); ?>><?php _e('Após o parágrafo número...', 'meu-banner'); ?></option>
                            </select>
                            <input type="number" name="rules[<?php echo esc_attr($index); ?>][paragraph_num]" value="<?php echo esc_attr($paragraph_num); ?>" min="1" style="width: 60px; display:none;" class="meu-banner-paragraph-input">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Alinhamento', 'meu-banner'); ?></label></th>
                        <td>
                            <select name="rules[<?php echo esc_attr($index); ?>][align]">
                                <option value="center" <?php selected($align, 'center'); ?>><?php _e('Centralizado', 'meu-banner'); ?></option>
                                <option value="left" <?php selected($align, 'left'); ?>><?php _e('Flutuar à Esquerda', 'meu-banner'); ?></option>
                                <option value="right" <?php selected($align, 'right'); ?>><?php _e('Flutuar à Direita', 'meu-banner'); ?></option>
                            </select>
                        </td>
                    </tr>

                <?php elseif ($insertion_type === 'list'): ?>
                    <?php
                        $list_position = $rule['list_position'] ?? 'after_item';
                        $list_item_num = $rule['list_item_num'] ?? 1;
                    ?>
                    <tr>
                        <th scope="row"><label><?php _e('Posição na Lista', 'meu-banner'); ?></label></th>
                        <td>
                            <select name="rules[<?php echo esc_attr($index); ?>][list_position]">
                                <option value="before_item" <?php selected($list_position, 'before_item'); ?>><?php _e('Antes do item número', 'meu-banner'); ?></option>
                                <option value="after_item" <?php selected($list_position, 'after_item'); ?>><?php _e('Após o item número', 'meu-banner'); ?></option>
                            </select>
                            <input type="number" name="rules[<?php echo esc_attr($index); ?>][list_item_num]" value="<?php echo esc_attr($list_item_num); ?>" min="1" style="width: 60px;">
                        </td>
                    </tr>

                <?php else: ?>
                    <?php
                        $page_format = $rule['page_format'] ?? 'popup';
                        $page_style = $rule['page_style'] ?? 'dark';
                        $frequency_type = $rule['frequency_type'] ?? 'always';
                        $frequency_time_value = $rule['frequency_time_value'] ?? 1;
                        $frequency_time_unit = $rule['frequency_time_unit'] ?? 'hours';
                        $frequency_access_value = $rule['frequency_access_value'] ?? 5;
                    ?>
                    <tr>
                        <th scope="row"><label><?php _e('Formato', 'meu-banner'); ?></label></th>
                        <td>
                            <select name="rules[<?php echo esc_attr($index); ?>][page_format]">
                                <option value="popup" <?php selected($page_format, 'popup'); ?>><?php _e('Popup (Modal)', 'meu-banner'); ?></option>
                                <option value="sticky" <?php selected($page_format, 'sticky'); ?>><?php _e('Sticky (Fixo no Rodapé)', 'meu-banner'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Estilo Visual', 'meu-banner'); ?></label></th>
                        <td>
                            <select name="rules[<?php echo esc_attr($index); ?>][page_style]">
                                <option value="dark" <?php selected($page_style, 'dark'); ?>><?php _e('Escuro (Dark)', 'meu-banner'); ?></option>
                                <option value="light" <?php selected($page_style, 'light'); ?>><?php _e('Claro (Light)', 'meu-banner'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Frequência de Exibição', 'meu-banner'); ?></label></th>
                        <td>
                            <select name="rules[<?php echo esc_attr($index); ?>][frequency_type]" class="meu-banner-frequency-select">
                                <option value="always" <?php selected($frequency_type, 'always'); ?>><?php _e('Mostrar sempre', 'meu-banner'); ?></option>
                                <option value="time" <?php selected($frequency_type, 'time'); ?>><?php _e('Após um tempo', 'meu-banner'); ?></option>
                                <option value="access" <?php selected($frequency_type, 'access'); ?>><?php _e('Após X páginas visitadas', 'meu-banner'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr class="meu-banner-time-fields" style="display:none;">
                        <th scope="row"><label><?php _e('Mostrar a cada', 'meu-banner'); ?></label></th>
                        <td>
                            <input type="number" min="1" style="width: 80px;" name="rules[<?php echo esc_attr($index); ?>][frequency_time_value]" value="<?php echo esc_attr($frequency_time_value); ?>">
                            <select name="rules[<?php echo esc_attr($index); ?>][frequency_time_unit]">
                                <option value="minutes" <?php selected($frequency_time_unit, 'minutes'); ?>><?php _e('Minutos', 'meu-banner'); ?></option>
                                <option value="hours" <?php selected($frequency_time_unit, 'hours'); ?>><?php _e('Horas', 'meu-banner'); ?></option>
                                <option value="days" <?php selected($frequency_time_unit, 'days'); ?>><?php _e('Dias', 'meu-banner'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr class="meu-banner-access-fields" style="display:none;">
                        <th scope="row"><label><?php _e('Mostrar a cada', 'meu-banner'); ?></label></th>
                        <td>
                            <input type="number" min="1" style="width: 80px;" name="rules[<?php echo esc_attr($index); ?>][frequency_access_value]" value="<?php echo esc_attr($frequency_access_value); ?>">
                            <span><?php _e('páginas visitadas', 'meu-banner'); ?></span>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <th scope="row"><label><?php _e('Onde Exibir', 'meu-banner'); ?></label></th>
                    <td>
                        <fieldset>
                            <?php if ($insertion_type === 'page'): ?>
                            <label style="margin-right: 15px; font-weight: bold;">
                                <input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][post_types][]" value="all_site" <?php checked(in_array('all_site', $selected_post_types)); ?>>
                                <?php _e('Todo o Site', 'meu-banner'); ?>
                            </label>
                            <label style="margin-right: 15px; font-weight: bold;">
                                <input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][post_types][]" value="home" <?php checked(in_array('home', $selected_post_types)); ?>>
                                <?php _e('Página Inicial', 'meu-banner'); ?>
                            </label>
                            <br>
                            <?php elseif ($insertion_type === 'list'): ?>
                                <label style="margin-right: 15px; font-weight: bold;">
                                    <input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][post_types][]" value="all_lists" <?php checked(in_array('all_lists', $selected_post_types)); ?>>
                                    <?php _e('Todas as Listas', 'meu-banner'); ?>
                                </label>
                                <label style="margin-right: 15px; font-weight: bold;">
                                    <input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][post_types][]" value="home" <?php checked(in_array('home', $selected_post_types)); ?>>
                                    <?php _e('Página Inicial', 'meu-banner'); ?>
                                </label>
                                <label style="margin-right: 15px; font-weight: bold;">
                                    <input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][post_types][]" value="search" <?php checked(in_array('search', $selected_post_types)); ?>>
                                    <?php _e('Resultado de Pesquisa', 'meu-banner'); ?>
                                </label>
                                <label style="margin-right: 15px; font-weight: bold;">
                                    <input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][post_types][]" value="home_paged" <?php checked(in_array('home_paged', $selected_post_types)); ?>>
                                    <?php _e('A partir da segunda paginação da home', 'meu-banner'); ?>
                                </label>
                                <br>
                            <?php endif; ?>

                            <?php foreach ($post_types as $pt) : if ($pt->name === 'attachment' || $pt->name === 'meu_banner_bloco') continue; ?>
                                <label style="margin-right: 15px;">
                                    <input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][post_types][]" value="<?php echo esc_attr($pt->name); ?>" <?php checked(in_array($pt->name, $selected_post_types)); ?>>
                                    <?php echo esc_html($pt->label); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

function meu_banner_auto_insert_page_scripts() {
    ?>
    <style>
        .tab-pane { display: none; padding-top: 15px; border: 1px solid #ccd0d4; border-top: 0; padding: 20px; background: #fdfdfd; }
        .tab-pane.active { display: block; }
        .rules-container p { font-style: italic; color: #777; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.getElementById('meu-banner-rules-wrapper');
        if (!wrapper) return;

        const tabs = wrapper.previousElementSibling.querySelectorAll('.nav-tab');
        const panes = wrapper.querySelectorAll('.tab-pane');

        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const targetPaneId = this.getAttribute('href').substring(1);

                tabs.forEach(t => t.classList.remove('nav-tab-active'));
                this.classList.add('nav-tab-active');

                panes.forEach(p => {
                    p.style.display = 'none';
                    p.classList.remove('active');
                });
                
                const targetPane = document.getElementById(targetPaneId);
                targetPane.style.display = 'block';
                targetPane.classList.add('active');
            });
        });

        function initializeContentRule(ruleElement) {
            const positionSelect = ruleElement.querySelector('.meu-banner-position-select');
            if (!positionSelect) return;

            const paragraphInput = ruleElement.querySelector('.meu-banner-paragraph-input');

            function toggleParagraphInput() {
                paragraphInput.style.display = (positionSelect.value === 'after_paragraph') ? 'inline-block' : 'none';
            }
            toggleParagraphInput();
            positionSelect.addEventListener('change', toggleParagraphInput);
        }

        function initializeSiteRule(ruleElement) {
            const frequencySelect = ruleElement.querySelector('.meu-banner-frequency-select');
            if (!frequencySelect) return;

            const timeFields = ruleElement.querySelector('.meu-banner-time-fields');
            const accessFields = ruleElement.querySelector('.meu-banner-access-fields');

            function toggleFrequencyFields() {
                const selected = frequencySelect.value;
                timeFields.style.display = (selected === 'time') ? 'table-row' : 'none';
                accessFields.style.display = (selected === 'access') ? 'table-row' : 'none';
            }
            toggleFrequencyFields();
            frequencySelect.addEventListener('change', toggleFrequencyFields);
        }

        function initializeListRule(ruleElement) { }

        document.querySelectorAll('#tab-content .meu-banner-rule').forEach(initializeContentRule);
        document.querySelectorAll('#tab-site .meu-banner-rule').forEach(initializeSiteRule);
        document.querySelectorAll('#tab-listas .meu-banner-rule').forEach(initializeListRule);

        function addNewRule(type) {
            const templateId = `meu-banner-rule-${type}-template`;
            const containerId = `tab-${type}`;
            
            const template = document.getElementById(templateId);
            const container = document.getElementById(containerId).querySelector('.rules-container');
            if (!template || !container) return;

            const noRulesP = container.querySelector('p');
            if(noRulesP) noRulesP.remove();

            const newIndex = Date.now();
            const templateContent = template.innerHTML.replace(/{index}/g, newIndex);
            
            const newRuleWrapper = document.createElement('div');
            newRuleWrapper.innerHTML = templateContent;
            const newRuleElement = newRuleWrapper.firstElementChild;
            
            container.appendChild(newRuleElement);

            if (type === 'content') {
                initializeContentRule(newRuleElement);
            } else if (type === 'site') {
                initializeSiteRule(newRuleElement);
            } else if (type === 'list') {
                initializeListRule(newRuleElement);
            }
        }

        document.getElementById('meu-banner-add-content-rule').addEventListener('click', () => addNewRule('content'));
        document.getElementById('meu-banner-add-site-rule').addEventListener('click', () => addNewRule('site'));
        document.getElementById('meu-banner-add-list-rule').addEventListener('click', () => addNewRule('list'));

        wrapper.addEventListener('click', function(e) {
            if (e.target.classList.contains('meu-banner-remove-rule')) {
                 e.preventDefault();
                 e.target.closest('.meu-banner-rule').remove();
            }
        });
    });
    </script>
    <?php
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