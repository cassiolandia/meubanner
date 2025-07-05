// js/frontend-banner.js (Versão 3.0 - Lógica de Frequência)

document.addEventListener('DOMContentLoaded', function () {
    const popupWrapper = document.querySelector('.meu-banner-popup-wrapper');
    const stickyWrapper = document.querySelector('.meu-banner-sticky-wrapper');

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
        frequencyChecker.registerClose(wrapper);
        wrapper.classList.remove('is-visible');
        if (wrapper.classList.contains('meu-banner-popup-wrapper')) {
            const overlay = document.querySelector('.meu-banner-popup-overlay');
            if(overlay) overlay.classList.remove('is-visible');
            document.body.classList.remove('meu-banner-popup-active');
        }
    }

    function showBanner(wrapper) {
        if (!wrapper || !frequencyChecker.shouldShow(wrapper)) {
            return;
        }

        // Verifica se o conteúdo real do banner está visível (não oculto por CSS responsivo)
        const bannerItem = wrapper.querySelector('.meu-banner-item');
        if (!bannerItem || window.getComputedStyle(bannerItem).display === 'none') {
            return; // Não mostra o popup/sticky se o banner interno estiver oculto
        }
        
        const delay = wrapper.classList.contains('meu-banner-popup-wrapper') ? 1500 : 1000;

        setTimeout(() => {
            wrapper.classList.add('is-visible');
            if (wrapper.classList.contains('meu-banner-popup-wrapper')) {
                const overlay = document.querySelector('.meu-banner-popup-overlay');
                if(overlay) overlay.classList.add('is-visible');
                document.body.classList.add('meu-banner-popup-active');
            }
        }, delay);
    }

    // --- INICIALIZAÇÃO E EVENTOS ---
    document.body.addEventListener('click', function(e) {
        const closeButton = e.target.closest('.meu-banner-close-btn');
        if (closeButton) {
            e.preventDefault();
            const bannerWrapper = closeButton.closest('.meu-banner-page-container, .meu-banner-wrapper');
            closeBanner(bannerWrapper);
        }
        
        const popupOverlay = e.target.closest('.meu-banner-popup-overlay');
        if (popupOverlay) {
            closeBanner(popupWrapper);
        }
    });

    // Inicia a exibição dos banners de página
    showBanner(popupWrapper);
    showBanner(stickyWrapper);
});
