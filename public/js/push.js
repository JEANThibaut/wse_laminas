// Activation des notifications sur cet appareil, depuis la fenetre #pushModal.
// Charge uniquement pour les comptes autorises, apres pwa.js (qui enregistre
// le service worker). Chaque etat s'explique en deux phrases maximum.
(function () {
    const MESSAGES = {
        idle: "Recevez une alerte sur cet appareil quand une partie ouvre ou qu'une place se libère. Touchez « Activer », puis acceptez la demande qui s'affiche.",
        active: 'Les notifications sont activées sur cet appareil. Vous pouvez les couper à tout moment ici.',
        iosInstall: "Sur iPhone, les notifications ne fonctionnent que dans l'application installée. Installez-la, ouvrez-la depuis votre écran d'accueil, puis revenez ici.",
        denied: 'Les notifications sont bloquées pour ce site sur cet appareil. Autorisez-les dans les réglages du navigateur, puis réessayez.',
        unsupported: "Ce navigateur ne permet pas de recevoir les notifications. Essayez avec Chrome, Edge ou Safari à jour.",
        error: "L'activation n'a pas abouti. Réessayez dans un instant.",
    };

    const modal = document.getElementById('pushModal');
    if (!modal) {
        return;
    }
    const text = document.getElementById('pushModalText');
    const enableButton = document.getElementById('pushEnableButton');
    const disableButton = document.getElementById('pushDisableButton');
    const installButton = document.getElementById('pushInstallButton');

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    function show(state) {
        text.textContent = MESSAGES[state];
        enableButton.classList.toggle('d-none', !['idle', 'error'].includes(state));
        disableButton.classList.toggle('d-none', state !== 'active');
        installButton.classList.toggle('d-none', state !== 'iosInstall');
    }

    // Cle publique VAPID (base64url) au format attendu par le navigateur
    function decodeKey(base64url) {
        const base64 = (base64url + '='.repeat((4 - base64url.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
    }

    async function post(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
    }

    async function currentSubscription() {
        const registration = await navigator.serviceWorker.ready;
        return registration.pushManager.getSubscription();
    }

    async function refresh() {
        // L'iPhone n'accepte les notifications que dans l'application installee
        if (isIos && !isStandalone) {
            return show('iosInstall');
        }
        if (!supported) {
            return show('unsupported');
        }
        if (Notification.permission === 'denied') {
            return show('denied');
        }
        try {
            const subscription = await currentSubscription();
            if (subscription) {
                // Resynchronise : l'abonnement a pu etre perdu cote serveur
                await post(modal.dataset.subscribeUrl, subscription.toJSON());
                return show('active');
            }
            show('idle');
        } catch (error) {
            console.warn('Notifications :', error);
            show('error');
        }
    }

    enableButton.addEventListener('click', async () => {
        enableButton.disabled = true;
        try {
            // Premier appel du clic : l'iPhone exige que la demande suive directement le geste
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                return show(permission === 'denied' ? 'denied' : 'idle');
            }
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: decodeKey(modal.dataset.publicKey),
            });
            await post(modal.dataset.subscribeUrl, subscription.toJSON());
            show('active');
        } catch (error) {
            console.warn('Notifications :', error);
            show('error');
        } finally {
            enableButton.disabled = false;
        }
    });

    disableButton.addEventListener('click', async () => {
        disableButton.disabled = true;
        try {
            const subscription = await currentSubscription();
            if (subscription) {
                await post(modal.dataset.unsubscribeUrl, { endpoint: subscription.endpoint });
                await subscription.unsubscribe();
            }
            show('idle');
        } catch (error) {
            console.warn('Notifications :', error);
            show('error');
        } finally {
            disableButton.disabled = false;
        }
    });

    // iPhone hors application : on passe la main aux explications d'installation
    installButton.addEventListener('click', () => {
        bootstrap.Modal.getOrCreateInstance(modal).hide();
        const iosModal = document.getElementById('pwaIosModal');
        if (iosModal) {
            bootstrap.Modal.getOrCreateInstance(iosModal).show();
        }
    });

    modal.addEventListener('show.bs.modal', refresh);
})();
