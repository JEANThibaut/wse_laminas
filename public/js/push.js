// Notifications : fenetre #pushModal (activation sur cet appareil), etat et
// interrupteurs du profil (#notificationSettings). Charge uniquement pour les
// comptes autorises, apres pwa.js qui enregistre le service worker.
// Chaque etat s'explique en deux phrases maximum.
(function () {
    const MESSAGES = {
        idle: "Recevez une alerte sur cet appareil quand une partie ouvre ou qu'une place se libère. Touchez « Activer », puis acceptez la demande qui s'affiche.",
        active: 'Les notifications sont activées sur cet appareil. Choisissez lesquelles recevoir dans votre profil.',
        iosInstall: "Sur iPhone, les notifications ne fonctionnent que dans l'application installée. Installez-la, ouvrez-la depuis votre écran d'accueil, puis revenez ici.",
        denied: 'Les notifications sont bloquées pour ce site sur cet appareil. Autorisez-les dans les réglages du navigateur, puis réessayez.',
        unsupported: "Ce navigateur ne permet pas de recevoir les notifications. Essayez avec Chrome, Edge ou Safari à jour.",
        error: "L'activation n'a pas abouti. Réessayez dans un instant.",
    };
    // Etat de l'appareil, affiche dans le profil
    const DEVICE_STATUS = {
        idle: 'Cet appareil : notifications désactivées',
        active: 'Cet appareil : notifications activées',
        iosInstall: "Cet appareil : installez l'application pour les recevoir",
        denied: 'Cet appareil : bloquées par le navigateur',
        unsupported: 'Cet appareil : non compatible',
        error: 'Cet appareil : état inconnu',
    };
    // Demande a l'ouverture : pas plus d'une fois par semaine si on la ferme sans activer
    const PROMPT_KEY = 'pushPromptDismissedAt';
    const PROMPT_DELAY = 7 * 24 * 3600 * 1000;

    const modal = document.getElementById('pushModal');
    if (!modal) {
        return;
    }
    const text = document.getElementById('pushModalText');
    const enableButton = document.getElementById('pushEnableButton');
    const disableButton = document.getElementById('pushDisableButton');
    const installButton = document.getElementById('pushInstallButton');
    const deviceStatus = document.getElementById('pushDeviceStatus');

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    let activatedInModal = false;

    // localStorage peut etre indisponible (navigation privee, blocage) : jamais bloquant
    const storage = {
        get: (key) => { try { return localStorage.getItem(key); } catch (e) { return null; } },
        set: (key, value) => { try { localStorage.setItem(key, value); } catch (e) { /* ignore */ } },
    };

    function show(state) {
        text.textContent = MESSAGES[state];
        enableButton.classList.toggle('d-none', !['idle', 'error'].includes(state));
        disableButton.classList.toggle('d-none', state !== 'active');
        installButton.classList.toggle('d-none', state !== 'iosInstall');
        if (deviceStatus) {
            deviceStatus.textContent = DEVICE_STATUS[state];
        }
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
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            // Message du serveur quand il y en a un (migration manquante, acces refuse...)
            throw new Error(data.error || 'Erreur serveur (HTTP ' + response.status + ').');
        }
        return data;
    }

    async function currentSubscription() {
        const registration = await navigator.serviceWorker.ready;
        return registration.pushManager.getSubscription();
    }

    async function detectState() {
        // L'iPhone n'accepte les notifications que dans l'application installee
        if (isIos && !isStandalone) {
            return 'iosInstall';
        }
        if (!supported) {
            return 'unsupported';
        }
        if (Notification.permission === 'denied') {
            return 'denied';
        }
        try {
            const subscription = await currentSubscription();
            if (!subscription) {
                return 'idle';
            }
            // Resynchronise une fois par session : l'abonnement a pu etre perdu cote serveur
            let synced = false;
            try { synced = sessionStorage.getItem('pushSynced') === '1'; } catch (e) { /* ignore */ }
            if (!synced) {
                await post(modal.dataset.subscribeUrl, subscription.toJSON());
                try { sessionStorage.setItem('pushSynced', '1'); } catch (e) { /* ignore */ }
            }
            return 'active';
        } catch (error) {
            console.warn('Notifications :', error);
            return 'error';
        }
    }

    async function refresh() {
        const state = await detectState();
        show(state);
        return state;
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
            activatedInModal = true;
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

    modal.addEventListener('show.bs.modal', () => {
        activatedInModal = false;
        refresh();
    });
    // Fermee sans activer : on ne redemande pas avant une semaine
    modal.addEventListener('hidden.bs.modal', () => {
        if (!activatedInModal) {
            storage.set(PROMPT_KEY, String(Date.now()));
        }
    });

    // Interrupteurs Parties / Actualites du profil
    const settingsError = document.getElementById('notificationError');
    document.querySelectorAll('#notificationSettings input[data-category]').forEach((input) => {
        const saved = document.querySelector(`[data-saved-for="${input.dataset.category}"]`);
        input.addEventListener('change', async () => {
            input.disabled = true;
            settingsError?.classList.add('d-none');
            try {
                const data = await post(modal.dataset.preferencesUrl, { category: input.dataset.category, enabled: input.checked });
                // L'etat affiche suit ce que le serveur a reellement enregistre
                if (data.preferences && input.dataset.category in data.preferences) {
                    input.checked = data.preferences[input.dataset.category];
                }
                if (saved) {
                    saved.classList.add('show');
                    setTimeout(() => saved.classList.remove('show'), 2000);
                }
            } catch (error) {
                console.warn('Notifications :', error);
                input.checked = !input.checked;
                if (settingsError) {
                    settingsError.textContent = "Réglage non enregistré : " + error.message;
                    settingsError.classList.remove('d-none');
                }
            } finally {
                input.disabled = false;
            }
        });
    });

    // A l'ouverture : etat de l'appareil, et proposition d'activer si c'est possible
    // ici, jamais demande, et pas refuse recemment
    function init() {
        refresh().then((state) => {
            const dismissedAt = Number(storage.get(PROMPT_KEY) || 0);
            if (state === 'idle' && Notification.permission === 'default' && Date.now() - dismissedAt > PROMPT_DELAY) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
