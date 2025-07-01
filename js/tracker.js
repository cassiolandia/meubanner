document.addEventListener('DOMContentLoaded', function () {
    // Encontra todos os wrappers de banner que têm o atributo de rastreamento
    const bannersToTrack = document.querySelectorAll('.meu-banner-wrapper[data-bloco-id]');

    if (bannersToTrack.length > 0) {
        bannersToTrack.forEach(bannerWrapper => {
            const blocoId = bannerWrapper.dataset.blocoId;

            if (blocoId) {
                // Prepara os dados para enviar via AJAX
                const formData = new FormData();
                formData.append('action', 'meu_banner_track_view');
                formData.append('bloco_id', blocoId);
                // O 'meuBannerAjax' é o objeto que passamos do PHP com wp_localize_script
                formData.append('nonce', meuBannerAjax.nonce);

                // Usa o método fetch para enviar a requisição de forma assíncrona
                // O uso de 'navigator.sendBeacon' seria ideal para não bloquear
                // a navegação, mas 'fetch' com 'keepalive' é uma alternativa moderna e robusta.
                fetch(meuBannerAjax.ajax_url, {
                    method: 'POST',
                    body: formData,
                    keepalive: true // Garante que a requisição seja completada mesmo se o usuário navegar para outra página
                }).catch(error => {
                    // Opcional: logar erros no console para depuração
                    console.error('Meu Banner Tracking Error:', error);
                });
            }
        });
    }
});