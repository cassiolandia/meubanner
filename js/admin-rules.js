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

    function initializeListRule(ruleElement) {
        // Nenhuma lógica específica necessária para regras de lista ainda
    }

    // Inicializa para todas as regras já existentes
    document.querySelectorAll('#tab-content .meu-banner-rule').forEach(initializeContentRule);
    document.querySelectorAll('#tab-site .meu-banner-rule').forEach(initializeSiteRule);
    document.querySelectorAll('#tab-listas .meu-banner-rule').forEach(initializeListRule);


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
        } else if (type === 'list') {
            initializeListRule(newRuleElement);
        }
    }

    document.getElementById('meu-banner-add-content-rule').addEventListener('click', () => addNewRule('content'));
    document.getElementById('meu-banner-add-site-rule').addEventListener('click', () => addNewRule('site'));
    document.getElementById('meu-banner-add-list-rule').addEventListener('click', () => addNewRule('list'));


    // --- LÓGICA DE REMOVER REGRA ---
    wrapper.addEventListener('click', function(e) {
        if (e.target.classList.contains('meu-banner-remove-rule')) {
             e.preventDefault();
             e.target.closest('.meu-banner-rule').remove();
        }
    });
});
