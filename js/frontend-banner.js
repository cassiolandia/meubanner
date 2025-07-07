// js/frontend-banner.js (Versão 4.1 - Verificação de Visibilidade Corrigida)

document.addEventListener('DOMContentLoaded', function () {
    const pageAccessCounter = {
        key: 'meuBannerPageCount',
        increment: function() {
            let count = parseInt(sessionStorage.getItem(this.key) || '0', 10);
            count++;
            sessionStorage.setItem(this.key, count);
        },
        getCount: function() {
            return parseInt(sessionStorage.getItem(this.key) || '0', 10);
        }
    };
    pageAccessCounter.increment();

    const trackedBanners = new Set();

    function trackBannerView(blocoId) {
        if (!blocoId || trackedBanners.has(blocoId) || typeof meuBannerAjax === 'undefined') {
            return;
        }
        trackedBanners.add(blocoId);
        const data = new FormData();
        data.append('action', 'meu_banner_track_view');
        data.append('bloco_id', blocoId);
        data.append('nonce', meuBannerAjax.nonce);
        fetch(meuBannerAjax.ajax_url, { method: 'POST', body: data })
            .catch(error => console.error('Erro ao rastrear banner:', error));
    }

    const frequencyChecker = {
        shouldShow: function(bannerElement) {
            const blocoId = bannerElement.dataset.blocoId;
            const type = bannerElement.dataset.frequencyType || 'always';
            if (type === 'always') return true;
            const lastClosed = JSON.parse(localStorage.getItem('meuBannerLastClosed_' + blocoId));
            if (!lastClosed) return true;
            if (type === 'time') {
                const value = parseInt(bannerElement.dataset.frequencyTimeValue, 10);
                const unit = bannerElement.dataset.frequencyTimeUnit || 'hours';
                let seconds_to_wait = 0;
                if (unit === 'minutes') seconds_to_wait = value * 60;
                else if (unit === 'hours') seconds_to_wait = value * 3600;
                else if (unit === 'days') seconds_to_wait = value * 86400;
                const time_since_closed = (new Date().getTime() - lastClosed.timestamp) / 1000;
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
            localStorage.setItem('meuBannerLastClosed_' + blocoId, JSON.stringify({ timestamp: new Date().getTime(), pageCount: pageAccessCounter.getCount() }));
        }
    };

    function closeBanner(wrapper) {
        if (!wrapper) return;
        if (wrapper.classList.contains('meu-banner-page-container')) {
            frequencyChecker.registerClose(wrapper);
            wrapper.classList.remove('is-visible');
            if (wrapper.classList.contains('meu-banner-popup-wrapper')) {
                const overlay = wrapper.previousElementSibling;
                if (overlay && overlay.classList.contains('meu-banner-popup-overlay')) {
                    overlay.classList.remove('is-visible');
                }
                if (!document.querySelector('.meu-banner-popup-wrapper.is-visible')) {
                    document.body.classList.remove('meu-banner-popup-active');
                }
            }
        } else if (wrapper.classList.contains('meu-banner-wrapper')) {
            wrapper.style.transition = 'opacity 0.3s ease';
            wrapper.style.opacity = '0';
            setTimeout(() => wrapper.remove(), 300);
        }
    }

    function showBanner(wrapper) {
        if (!wrapper || !frequencyChecker.shouldShow(wrapper)) {
            return;
        }

        // **A VERIFICAÇÃO CORRETA**
        // Verifica se algum dos banners *internos* está visível. As classes hide-on-* são aplicadas neles.
        const isAnyBannerItemVisible = Array.from(wrapper.querySelectorAll('.meu-banner-item')).some(item => window.getComputedStyle(item).display !== 'none');
        if (!isAnyBannerItemVisible) {
            return;
        }

        const blocoId = wrapper.dataset.blocoId;
        const delay = wrapper.classList.contains('meu-banner-popup-wrapper') ? 1500 : 1000;

        setTimeout(() => {
            wrapper.classList.add('is-visible');
            if (blocoId) {
                trackBannerView(blocoId);
            }
            if (wrapper.classList.contains('meu-banner-popup-wrapper')) {
                const overlay = wrapper.previousElementSibling;
                if (overlay && overlay.classList.contains('meu-banner-popup-overlay')) {
                    overlay.classList.add('is-visible');
                }
                document.body.classList.add('meu-banner-popup-active');
            }
        }, delay);
    }

    document.body.addEventListener('click', function(e) {
        const closeButton = e.target.closest('.meu-banner-close-btn');
        if (closeButton) {
            e.preventDefault();
            closeBanner(closeButton.closest('.meu-banner-page-container, .meu-banner-wrapper'));
            return;
        }
        const popupOverlay = e.target.closest('.meu-banner-popup-overlay.is-visible');
        if (popupOverlay) {
            const popupWrapper = popupOverlay.nextElementSibling;
            if (popupWrapper && popupWrapper.classList.contains('meu-banner-popup-wrapper')) {
                closeBanner(popupWrapper);
            }
        }
    });

    // Inicia a exibição para banners de página (popup/sticky)
    document.querySelectorAll('.meu-banner-popup-wrapper, .meu-banner-sticky-wrapper').forEach(showBanner);

    // Rastreia banners de conteúdo (shortcode/auto-insert) que não têm delay
    document.querySelectorAll('.meu-banner-wrapper[data-bloco-id]').forEach(banner => {
        if (window.getComputedStyle(banner).display !== 'none') {
            trackBannerView(banner.dataset.blocoId);
        }
    });
});
