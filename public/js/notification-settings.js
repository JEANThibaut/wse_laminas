// Interrupteurs Parties / Actualites du profil : chaque interrupteur enregistre
// son reglage par un appel AJAX dedie (adresse dans data-url), independamment
// de l'activation des notifications sur l'appareil (push.js).
(function () {
    function init() {
        document.querySelectorAll('input[data-notification-category]').forEach((input) => {
            const row = input.closest('[data-notification-row]');
            const status = row ? row.querySelector('[data-notification-status]') : null;

            // Icone Font Awesome + texte (insere comme texte : il peut venir du serveur)
            const setStatus = (icon, text, className) => {
                if (!status) {
                    return;
                }
                status.className = 'd-block small mt-1 ' + className;
                status.textContent = '';
                if (icon) {
                    const i = document.createElement('i');
                    i.className = 'fa-solid ' + icon + ' me-1';
                    status.appendChild(i);
                }
                status.appendChild(document.createTextNode(text));
            };

            input.addEventListener('change', async () => {
                const enabled = input.checked;
                input.disabled = true;
                setStatus('fa-spinner fa-spin', 'Enregistrement…', 'text-muted');
                try {
                    const response = await fetch(input.dataset.url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ category: input.dataset.notificationCategory, enabled: enabled }),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error((data.error || 'Erreur serveur') + ' (HTTP ' + response.status + ')');
                    }
                    // L'etat affiche suit ce que le serveur a reellement enregistre
                    const saved = data.preferences ? data.preferences[input.dataset.notificationCategory] : enabled;
                    input.checked = Boolean(saved);
                    setStatus('fa-check', 'Enregistré', 'text-success');
                    setTimeout(() => setStatus('', '', ''), 2500);
                } catch (error) {
                    console.warn('Notifications :', error);
                    input.checked = !enabled;
                    setStatus('fa-triangle-exclamation', 'Non enregistré : ' + error.message, 'text-danger');
                } finally {
                    input.disabled = false;
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
