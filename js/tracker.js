// js/tracker.js (Versão 3.3 - Final)

document.addEventListener('DOMContentLoaded', function() {
    function trackBannerView(blocoId) {
        if (!blocoId) {
            return;
        }
        const data = new FormData();
        data.append('action', 'meu_banner_track_view');
        data.append('bloco_id', blocoId);
        data.append('nonce', meuBannerAjax.nonce);

        fetch(meuBannerAjax.ajax_url, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                // console.warn('Falha ao rastrear banner ' + blocoId, result.data.message);
            }
        })
        .catch(error => {
            // console.error('Erro ao rastrear banner ' + blocoId, error);
        });
    }

    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1 // Rastreia quando 10% do banner está visível. Mais confiável para mobile.
    };

    const trackedBanners = new Set();

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bannerWrapper = entry.target;
                const blocoId = bannerWrapper.dataset.blocoId;

                if (blocoId && !trackedBanners.has(blocoId)) {
                    // Confirma que o banner não está oculto por CSS (display: none)
                    if (window.getComputedStyle(bannerWrapper).display !== 'none') {
                        trackBannerView(blocoId);
                        trackedBanners.add(blocoId);
                        observer.unobserve(bannerWrapper);
                    }
                }
            }
        });
    }, observerOptions);

    // Seleciona TODOS os banners com o atributo data-bloco-id para observar
    const bannersToTrack = document.querySelectorAll('[data-bloco-id]');
    bannersToTrack.forEach(banner => {
        observer.observe(banner);
    });
});
