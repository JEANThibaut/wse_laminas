// Service worker minimal : rend le site installable comme une application.
// Aucun cache : tout passe par le reseau, comme sans service worker. Seules
// les navigations sans reseau recoivent une page "hors ligne" au lieu de
// l'erreur du navigateur. Servi a la racine pour couvrir tout le site.

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

const OFFLINE_PAGE = `<!doctype html><html lang="fr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1"><title>Hors ligne</title></head>
<body style="background:#212529;color:#fff;font-family:sans-serif;text-align:center;padding:3rem 1rem">
<h1 style="font-size:1.4rem">Pas de connexion</h1>
<p>Vérifiez votre réseau puis réessayez.</p>
<button onclick="location.reload()" style="padding:.6rem 1.2rem;font-size:1rem">Réessayer</button>
</body></html>`;

// Notification envoyee par le serveur (Application\Service\PushService) :
// { title, body, url }
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (error) {
        data = { body: event.data ? event.data.text() : '' };
    }
    event.waitUntil(self.registration.showNotification(data.title || 'Wolf Soft Eure', {
        body: data.body || '',
        icon: '/images/pwa/icon-192.png',
        badge: '/images/pwa/icon-192.png',
        data: { url: data.url || '/' },
    }));
});

// Toucher la notification : ouvre la page, en reutilisant une fenetre du site deja ouverte
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/', self.location.origin).href;
    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of windows) {
            if (new URL(client.url).origin === self.location.origin && 'focus' in client) {
                await client.focus();
                return client.navigate(target);
            }
        }
        return self.clients.openWindow(target);
    })());
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    // Uniquement les pages consultees : formulaires, images, scripts... suivent le chemin normal
    if (request.mode !== 'navigate' || request.method !== 'GET') {
        return;
    }
    event.respondWith(
        fetch(request).catch(() => new Response(OFFLINE_PAGE, {
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        }))
    );
});
