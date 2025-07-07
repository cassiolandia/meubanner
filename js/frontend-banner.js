// js/frontend-banner.js (Versão 3.1 - Suporte a Múltiplos Banners)

document.addEventListener('DOMContentLoaded', function () {
    const popupWrappers = document.querySelectorAll('.meu-banner-popup-wrapper');
    const stickyWrappers = document.querySelectorAll('.meu-banner-sticky-wrapper');

    // --- LÓGICA DE CONTAGEM DE ACESSOS ---
    const pageAccessCounter = {
        key: 'meuBannerPageCount',
        increment: function() {
            let count = parseInt(sessionStorage.getItem(this.key) || '0', 10);
            count++;
            sessionStorage.setItem(this.key, count);
            return count;
        },
        getCount: function() {
            return parseInt(sessionStorage.getItem(this.key) || '0', 10);
        }
    };
    pageAccessCounter.increment();

    // --- FUNÇÕES DE VERIFICAÇÃO DE FREQUÊNCIA ---
    const frequencyChecker = {
        shouldShow: function(bannerElement) {
            const blocoId = bannerElement.dataset.blocoId;
            const type = bannerElement.dataset.frequencyType || 'always';

            if (type === 'always') {
                return true;
            }

            const lastClosed = JSON.parse(localStorage.getItem('meuBannerLastClosed_' + blocoId));

            if (!lastClosed) {
                return true; // Nunca foi fechado, mostrar.
            }

            if (type === 'time') {
                const value = parseInt(bannerElement.dataset.frequencyTimeValue, 10);
                const unit = bannerElement.dataset.frequencyTimeUnit || 'hours';
                let seconds_to_wait = 0;
                if (unit === 'minutes') seconds_to_wait = value * 60;
                else if (unit === 'hours') seconds_to_wait = value * 3600;
                else if (unit === 'days') seconds_to_wait = value * 86400;

                const now = new Date().getTime();
                const time_since_closed = (now - lastClosed.timestamp) / 1000;

                return time_since_closed >= seconds_to_wait;
            }

            if (type === 'access') {
                const value = parseInt(bannerElement.dataset.frequencyAccessValue, 10);
                const pages_since_closed = pageAccessCounter.getCount() - lastClosed.pageCount;
                return pages_since_closed >= value;
            }

            return true;
        },
        registerClose: function(bannerElement) {
            const blocoId = bannerElement.dataset.blocoId;
            const data = {
                timestamp: new Date().getTime(),
                pageCount: pageAccessCounter.getCount()
            };
            localStorage.setItem('meuBannerLastClosed_' + blocoId, JSON.stringify(data));
        }
    };

    // --- FUNÇÕES DE CONTROLE DO BANNER ---
    function closeBanner(wrapper) {
        if (!wrapper) return;

        // Apenas registra o fechamento para banners de página (popup/sticky)
        if (wrapper.classList.contains('meu-banner-page-container')) {
            frequencyChecker.registerClose(wrapper);
            wrapper.classList.remove('is-visible');
            
            if (wrapper.classList.contains('meu-banner-popup-wrapper')) {
                // Encontra e remove o overlay associado a este popup
                const overlay = wrapper.previousElementSibling;
                if(overlay && overlay.classList.contains('meu-banner-popup-overlay')) {
                    overlay.classList.remove('is-visible');
                }
                // Verifica se ainda existem outros popups ativos antes de remover a classe do body
                const anyPopupActive = document.querySelector('.meu-banner-popup-wrapper.is-visible');
                if (!anyPopupActive) {
                    document.body.classList.remove('meu-banner-popup-active');
                }
            }
        } else if (wrapper.classList.contains('meu-banner-wrapper')) {
            // Para banners de conteúdo (shortcode/auto-insert), simplesmente remove o elemento.
            wrapper.style.transition = 'opacity 0.3s ease';
            wrapper.style.opacity = '0';
            setTimeout(() => wrapper.remove(), 300);
        }
    }

    function showBanner(wrapper) {
        if (!wrapper || !frequencyChecker.shouldShow(wrapper)) {
            return;
        }

        // Verifica se o próprio wrapper está visível.
        // Isso é mais confiável, pois as classes hide-on-desktop/mobile são aplicadas diretamente nele.
        if (window.getComputedStyle(wrapper).display === 'none') {
            return;
        }

        const delay = wrapper.classList.contains('meu-banner-popup-wrapper') ? 1500 : 1000;

        setTimeout(() => {
            wrapper.classList.add('is-visible');
            if (wrapper.classList.contains('meu-banner-popup-wrapper')) {
                // Encontra e exibe o overlay associado
                const overlay = wrapper.previousElementSibling;
                 if(overlay && overlay.classList.contains('meu-banner-popup-overlay')) {
                    overlay.classList.add('is-visible');
                }
                document.body.classList.add('meu-banner-popup-active');
            }
        }, delay);
    }

    // --- INICIALIZAÇÃO E EVENTOS ---
    document.body.addEventListener('click', function(e) {
        // Botão de fechar genérico
        const closeButton = e.target.closest('.meu-banner-close-btn');
        if (closeButton) {
            e.preventDefault();
            const bannerWrapper = closeButton.closest('.meu-banner-page-container, .meu-banner-wrapper');
            closeBanner(bannerWrapper);
            return; // Evita que o clique no overlay seja acionado também
        }
        
        // Clique no overlay do popup
        const popupOverlay = e.target.closest('.meu-banner-popup-overlay.is-visible');
        if (popupOverlay) {
            // Encontra o wrapper do popup que vem depois do overlay e o fecha
            const popupWrapper = popupOverlay.nextElementSibling;
            if (popupWrapper && popupWrapper.classList.contains('meu-banner-popup-wrapper')) {
                closeBanner(popupWrapper);
            }
        }
    });

    // Inicia a exibição de todos os banners de página encontrados
    popupWrappers.forEach(showBanner);
    stickyWrappers.forEach(showBanner);
});
