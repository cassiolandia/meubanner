// js/tracker.js

document.addEventListener('DOMContentLoaded', function() {
    // Função para enviar a requisição AJAX
    function trackBannerView(blocoId) {
        if (!blocoId) {
            console.warn('meu-banner-tracker: Bloco ID não fornecido para rastreamento.');
            return;
        }

        // Evita múltiplos rastreamentos para o mesmo banner na mesma sessão de página
        if (sessionStorage.getItem('meu_banner_tracked_' + blocoId)) {
            return;
        }
        sessionStorage.setItem('meu_banner_tracked_' + blocoId, 'true');

        const data = new FormData();
        data.append('action', 'meu_banner_track_view');
        data.append('bloco_id', blocoId);
        data.append('nonce', meuBannerAjax.nonce); // Nonce global do WordPress

        fetch(meuBannerAjax.ajax_url, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('meu-banner-tracker: Visualização do banner ' + blocoId + ' rastreada com sucesso.');
            } else {
                console.warn('meu-banner-tracker: Falha ao rastrear visualização do banner ' + blocoId + ':', result.data.message);
            }
        })
        .catch(error => {
            console.error('meu-banner-tracker: Erro na requisição AJAX para o banner ' + blocoId + ':', error);
        });
    }

    // Rastreamento para banners inline (shortcode)
    document.querySelectorAll('.meu-banner-wrapper[data-bloco-id]').forEach(banner => {
        const blocoId = banner.dataset.blocoId;
        if (blocoId) {
            trackBannerView(blocoId);
        }
    });

    // Rastreamento para banners de página (popup/sticky)
    // Estes são geralmente carregados após um delay e se tornam visíveis.
    // Monitora a visibilidade dos wrappers de popup e sticky.
    const observerOptions = {
        root: null, // viewport
        rootMargin: '0px',
        threshold: 0.1 // 10% do elemento visível
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bannerElement = entry.target;
                const blocoId = bannerElement.dataset.blocoId;
                if (blocoId) {
                    trackBannerView(blocoId);
                }
                // Uma vez rastreado, não precisa mais observar
                observer.unobserve(bannerElement);
            }
        });
    }, observerOptions);

    // Observa os wrappers de popup e sticky se existirem
    const popupWrapper = document.querySelector('.meu-banner-popup-wrapper[data-bloco-id]');
    const stickyWrapper = document.querySelector('.meu-banner-sticky-wrapper[data-bloco-id]');

    if (popupWrapper) {
        observer.observe(popupWrapper);
    }
    if (stickyWrapper) {
        observer.observe(stickyWrapper);
    }
});
