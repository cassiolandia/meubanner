<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Função principal que renderiza a página de Inserção Automática com ABAS GERAIS.
 */
function meu_banner_render_auto_insert_page() {
    // --- LÓGICA DE SALVAMENTO (NÃO ALTERADA) ---
    if (isset($_POST['meu_banner_save_rules_nonce']) && wp_verify_nonce($_POST['meu_banner_save_rules_nonce'], 'meu_banner_save_rules')) {
        $rules = [];
        if (isset($_POST['rules']) && is_array($_POST['rules'])) {
            foreach ($_POST['rules'] as $rule_data) {
                $sanitized_rule = [
                    'enabled'       => isset($rule_data['enabled']) ? 1 : 0,
                    'bloco_id'      => isset($rule_data['bloco_id']) ? intval($rule_data['bloco_id']) : 0,
                    'insertion_type'=> isset($rule_data['insertion_type']) ? sanitize_key($rule_data['insertion_type']) : 'content',
                    // Campos de Conteúdo
                    'position'      => isset($rule_data['position']) ? sanitize_key($rule_data['position']) : 'before_content',
                    'paragraph_num' => isset($rule_data['paragraph_num']) ? intval($rule_data['paragraph_num']) : 1,
                    'align'         => isset($rule_data['align']) ? sanitize_key($rule_data['align']) : 'center',
                    // Campos de Site
                    'page_format'   => isset($rule_data['page_format']) ? sanitize_key($rule_data['page_format']) : 'popup',
                    'page_style'    => isset($rule_data['page_style']) ? sanitize_key($rule_data['page_style']) : 'dark',
                    // Campos de Frequência
                    'frequency_type' => isset($rule_data['frequency_type']) ? sanitize_key($rule_data['frequency_type']) : 'always',
                    'frequency_time_value' => isset($rule_data['frequency_time_value']) ? intval($rule_data['frequency_time_value']) : 1,
                    'frequency_time_unit' => isset($rule_data['frequency_time_unit']) ? sanitize_key($rule_data['frequency_time_unit']) : 'hours',
                    'frequency_access_value' => isset($rule_data['frequency_access_value']) ? intval($rule_data['frequency_access_value']) : 5,
                    // Onde Exibir
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

    // Carrega todas as regras
    $all_rules = get_option('meu_banner_auto_insert_rules', []);

    // Separa as regras por tipo para exibição nas abas
    $content_rules = [];
    $site_rules = [];
    foreach ($all_rules as $index => $rule) {
        // Preserva o índice original para o nome do campo do formulário
        if (isset($rule['insertion_type']) && $rule['insertion_type'] === 'page') {
            $site_rules[$index] = $rule;
        } else {
            $content_rules[$index] = $rule;
        }
    }
    
    // Adiciona o CSS e JS necessários
    add_action('admin_footer', 'meu_banner_auto_insert_page_scripts');
    ?>
    <div class="wrap">
        <h1><?php _e('Inserção Automática de Banners', 'meu-banner'); ?></h1>
        <p><?php _e('Crie regras para inserir seus blocos de anúncios automaticamente no conteúdo ou em locais do site.', 'meu-banner'); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field('meu_banner_save_rules', 'meu_banner_save_rules_nonce'); ?>

            <!-- Abas Principais -->
            <h2 class="nav-tab-wrapper">
                <a href="#tab-content" class="nav-tab nav-tab-active"><?php printf(__('Conteúdo (%d)', 'meu-banner'), count($content_rules)); ?></a>
                <a href="#tab-site" class="nav-tab"><?php printf(__('Site (%d)', 'meu-banner'), count($site_rules)); ?></a>
            </h2>

            <div id="meu-banner-rules-wrapper">
                <!-- Painel da Aba "Conteúdo" -->
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

                <!-- Painel da Aba "Site" -->
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
            </div>

            <hr>
            <?php submit_button(__('Salvar Todas as Regras', 'meu-banner'), 'primary', 'submit', false); ?>
        </form>
    </div>

    <!-- Templates para novas regras -->
    <template id="meu-banner-rule-content-template">
        <?php meu_banner_render_rule_fields('{index}', ['insertion_type' => 'content']); ?>
    </template>
    <template id="meu-banner-rule-site-template">
        <?php meu_banner_render_rule_fields('{index}', ['insertion_type' => 'page']); ?>
    </template>
    <?php
}

/**
 * Função auxiliar que renderiza os campos de UMA regra, adaptada para cada tipo.
 */
function meu_banner_render_rule_fields($index, $rule = []) {
    // Valores Padrão
    $insertion_type      = $rule['insertion_type'] ?? 'content';
    $enabled             = $rule['enabled'] ?? 1;
    $bloco_id            = $rule['bloco_id'] ?? 0;
    $selected_post_types = $rule['post_types'] ?? [];

    // Busca de dados
    $blocos = get_posts(['post_type' => 'meu_banner_bloco', 'post_status' => 'publish', 'numberposts' => -1]);
    $post_types = get_post_types(['public' => true], 'objects');
    ?>
    <div class="meu-banner-rule" style="border: 1px solid #ccd0d4; padding: 20px; margin-bottom: 20px; background: #fff;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">
            <?php echo $insertion_type === 'page' ? __('Regra de Site', 'meu-banner') : __('Regra de Conteúdo', 'meu-banner'); ?>
            <button type="button" class="button button-link-delete meu-banner-remove-rule" style="float: right;"><?php _e('Remover', 'meu-banner'); ?></button>
        </h3>
        
        <!-- Campo oculto para garantir que o tipo seja salvo corretamente -->
        <input type="hidden" name="rules[<?php echo esc_attr($index); ?>][insertion_type]" value="<?php echo esc_attr($insertion_type); ?>">

        <table class="form-table" role="presentation">
            <tbody>
                <!-- Campos Comuns -->
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

                <?php if ($insertion_type === 'content'): // --- CAMPOS DE CONTEÚDO --- ?>
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

                <?php else: // --- CAMPOS DE SITE --- ?>
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
                    <!-- Nova Seção de Frequência -->
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

                <!-- Onde Exibir (com lógica condicional) -->
                <tr>
                    <th scope="row"><label><?php _e('Onde Exibir', 'meu-banner'); ?></label></th>
                    <td>
                        <fieldset>
                            <?php if ($insertion_type === 'page'): // Apenas regras de Site têm a opção "Todo o Site" ?>
                            <label style="margin-right: 15px; font-weight: bold;">
                                <input type="checkbox" name="rules[<?php echo esc_attr($index); ?>][post_types][]" value="all_site" <?php checked(in_array('all_site', $selected_post_types)); ?>>
                                <?php _e('Todo o Site', 'meu-banner'); ?>
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

/**
 * Adiciona o CSS e JS necessários no rodapé da página de administração.
 */
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

        // --- LÓGICA DAS ABAS PRINCIPAIS ---
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


        // --- LÓGICA DE CAMPOS DEPENDENTES (PARA REGRAS DE CONTEÚDO) ---
        function initializeContentRule(ruleElement) {
            const positionSelect = ruleElement.querySelector('.meu-banner-position-select');
            if (!positionSelect) return; // Só roda para regras de conteúdo

            const paragraphInput = ruleElement.querySelector('.meu-banner-paragraph-input');

            function toggleParagraphInput() {
                paragraphInput.style.display = (positionSelect.value === 'after_paragraph') ? 'inline-block' : 'none';
            }
            toggleParagraphInput();
            positionSelect.addEventListener('change', toggleParagraphInput);
        }

        // --- LÓGICA DE CAMPOS DEPENDENTES (PARA REGRAS DE SITE - FREQUÊNCIA) ---
        function initializeSiteRule(ruleElement) {
            const frequencySelect = ruleElement.querySelector('.meu-banner-frequency-select');
            if (!frequencySelect) return; // Só roda para regras de site

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

        // Inicializa para todas as regras já existentes
        document.querySelectorAll('#tab-content .meu-banner-rule').forEach(initializeContentRule);
        document.querySelectorAll('#tab-site .meu-banner-rule').forEach(initializeSiteRule);


        // --- LÓGICA DE ADICIONAR NOVA REGRA ---
        function addNewRule(type) {
            const templateId = `meu-banner-rule-${type}-template`;
            const containerId = `tab-${type}`;
            
            const template = document.getElementById(templateId);
            const container = document.getElementById(containerId).querySelector('.rules-container');
            if (!template || !container) return;

            // Remove a mensagem "nenhuma regra" se ela existir
            const noRulesP = container.querySelector('p');
            if(noRulesP) noRulesP.remove();

            const newIndex = Date.now();
            const templateContent = template.innerHTML.replace(/{index}/g, newIndex);
            
            const newRuleWrapper = document.createElement('div');
            newRuleWrapper.innerHTML = templateContent;
            const newRuleElement = newRuleWrapper.firstElementChild;
            
            container.appendChild(newRuleElement);

            // Inicializa os scripts da nova regra
            if (type === 'content') {
                initializeContentRule(newRuleElement);
            } else if (type === 'site') {
                initializeSiteRule(newRuleElement);
            }
        }

        document.getElementById('meu-banner-add-content-rule').addEventListener('click', () => addNewRule('content'));
        document.getElementById('meu-banner-add-site-rule').addEventListener('click', () => addNewRule('site'));


        // --- LÓGICA DE REMOVER REGRA ---
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