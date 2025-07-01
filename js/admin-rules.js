// js/admin-rules.js (Versão 2.1 - UI Corrigida)

document.addEventListener('DOMContentLoaded', function () {
    const rulesContainer = document.getElementById('meu-banner-rules-container');
    const addRuleButton = document.getElementById('meu-banner-add-rule');
    const ruleTemplate = document.getElementById('meu-banner-rule-template');

    if (!rulesContainer || !addRuleButton || !ruleTemplate) {
        return;
    }

    /**
     * Atualiza a visibilidade dos campos da regra com base nas seleções.
     * @param {HTMLElement} ruleDiv - O elemento <div> que contém toda a regra.
     */
    function updateRuleViewState(ruleDiv) {
        const insertionType = ruleDiv.querySelector('.meu-banner-insertion-type-select').value;
        const positionType = ruleDiv.querySelector('.meu-banner-position-select').value;

        // Controla a visibilidade das seções principais (Conteúdo vs Página)
        ruleDiv.querySelectorAll('.section-content').forEach(el => {
            el.style.display = (insertionType === 'content') ? 'table-row' : 'none';
        });
        ruleDiv.querySelectorAll('.section-page').forEach(el => {
            el.style.display = (insertionType === 'page') ? 'table-row' : 'none';
        });

        // Controla a habilitação do input de número do parágrafo
        const paragraphInput = ruleDiv.querySelector('.meu-banner-paragraph-input');
        if (paragraphInput) {
            // Habilita somente se o tipo de inserção for 'content' E a posição for 'after_paragraph'
            paragraphInput.disabled = !(insertionType === 'content' && positionType === 'after_paragraph');
        }
    }

    // Adicionar nova regra
    addRuleButton.addEventListener('click', function () {
        const lastRule = rulesContainer.querySelector('.meu-banner-rule:last-child');
        const newIndex = lastRule ? 
            (parseInt(lastRule.querySelector('input').name.match(/\[(\d+)\]/)[1]) + 1) : 
            0;
        
        const templateHTML = ruleTemplate.innerHTML.replace(/{index}/g, newIndex);
        rulesContainer.insertAdjacentHTML('beforeend', templateHTML);
        
        const newRuleDiv = rulesContainer.lastElementChild;
        updateRuleViewState(newRuleDiv);
    });

    // Remover uma regra
    rulesContainer.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('meu-banner-remove-rule')) {
            if (confirm('Tem certeza que deseja remover esta regra?')) {
                e.target.closest('.meu-banner-rule').remove();
            }
        }
    });

    // Listener para todas as mudanças dentro do container de regras
    rulesContainer.addEventListener('change', function(e) {
        if (e.target && (e.target.classList.contains('meu-banner-insertion-type-select') || e.target.classList.contains('meu-banner-position-select'))) {
            const ruleDiv = e.target.closest('.meu-banner-rule');
            if (ruleDiv) {
                updateRuleViewState(ruleDiv);
            }
        }
    });

    // Garante que o estado de todas as regras existentes esteja correto ao carregar a página.
    const allRules = rulesContainer.querySelectorAll('.meu-banner-rule');
    allRules.forEach(ruleDiv => {
        updateRuleViewState(ruleDiv);
    });
});