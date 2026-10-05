// Installation du site comme application (PWA). Charge uniquement pour les
// comptes autorises (helper pwaAccess). Le bouton #pwaInstallButton reste
// cache tant que l'installation n'est pas possible sur cet appareil.
(function () {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    // iPadOS se presente comme un Mac : on le reconnait a l'ecran tactile
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch((error) => console.warn('Service worker :', error));
    }

    // Android, Chrome et Edge sur PC : le navigateur annonce que l'installation
    // est possible, parfois avant la fin du chargement. On garde l'evenement
    // pour ouvrir sa fenetre d'installation au clic.
    let installPrompt = null;
    const button = () => document.getElementById('pwaInstallButton');
    const show = (visible) => button()?.classList.toggle('d-none', !visible);

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        show(true);
    });
    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        show(false);
    });

    function init() {
        if (!button() || isStandalone) {
            return;
        }
        // iPhone / iPad : Apple n'autorise pas de declencher l'installation,
        // le bouton ouvre les explications
        if (installPrompt || isIos) {
            show(true);
        }
        button().addEventListener('click', async () => {
            if (installPrompt) {
                installPrompt.prompt();
                await installPrompt.userChoice;
                installPrompt = null;
                show(false);
                return;
            }
            if (isIos) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('pwaIosModal')).show();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
