// js/frontend-banner.js (Versão 2.1 - Botão de Fechar Corrigido)

document.addEventListener('DOMContentLoaded', function () {
    const popupOverlay = document.querySelector('.meu-banner-popup-overlay');
    const popupWrapper = document.querySelector('.meu-banner-popup-wrapper');
    const stickyWrapper = document.querySelector('.meu-banner-sticky-wrapper');

    /**
     * Fecha o Popup e o Overlay.
     */
    function closePopup() {
        if (!popupOverlay || !popupWrapper) return;
        
        // Remove as classes de visibilidade para acionar as animações de saída.
        popupOverlay.classList.remove('is-visible');
        popupWrapper.classList.remove('is-visible');
        
        // Remove a classe do body para liberar o scroll.
        document.body.classList.remove('meu-banner-popup-active');
    }
    
    /**
     * Fecha o Banner Sticky.
     */
    function closeSticky() {
        if (!stickyWrapper) return;
        stickyWrapper.classList.remove('is-visible');
    }

    /**
     * Fecha um Banner Inline.
     * @param {HTMLElement} closeButton - O botão de fechar que foi clicado.
     */
    function closeInline(closeButton) {
        const bannerWrapper = closeButton.closest('.meu-banner-wrapper');
        if (bannerWrapper) {
            bannerWrapper.style.display = 'none';
        }
    }

    /**
     * Delegação de eventos no BODY para capturar todos os cliques.
     */
    document.body.addEventListener('click', function(e) {
        // Verifica se o alvo do clique (ou um de seus pais) é o botão de fechar.
        const closeButton = e.target.closest('.meu-banner-close-btn');
        if (!closeButton) return;
            
        e.preventDefault();
        
        // **LÓGICA CORRIGIDA**
        // Verifica qual o container principal do botão e chama a função correta.
        if (closeButton.closest('.meu-banner-popup-wrapper')) {
            closePopup();
        } else if (closeButton.closest('.meu-banner-sticky-wrapper')) {
            closeSticky();
        } else {
            // Se não for popup nem sticky, assume que é inline.
            closeInline(closeButton);
        }
    });

    // Clicar no overlay também fecha o popup.
    if (popupOverlay) {
        popupOverlay.addEventListener('click', closePopup);
    }

    /**
     * Lógica para exibir os Banners de Página (Popup e Sticky).
     */
    if (popupWrapper) {
        setTimeout(() => {
            document.body.classList.add('meu-banner-popup-active');
            if (popupOverlay) popupOverlay.classList.add('is-visible');
            popupWrapper.classList.add('is-visible');
        }, 1500); // Delay de 1.5 segundos
    }

    if (stickyWrapper) {
        setTimeout(() => {
            stickyWrapper.classList.add('is-visible');
        }, 1000); // Delay de 1 segundo
    }
});