// GOD MODE > Tableau de bord : une section par ecran d'administration, chacune
// affichant la vraie page mobile dans un cadre qui defile de son cote.
// Un lien vers une autre page s'ouvre dans la grande popup ; a sa fermeture,
// les sections sont rechargees. Ordinateur uniquement (>= 1200 px) : en dessous,
// rien n'est charge.
// Lance apres le chargement de la page : Bootstrap est inclus en fin de layout.
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('godDashboard');
    // Jamais de tableau de bord dans un tableau de bord
    if (!root || window.self !== window.top) return;

    const DESKTOP = 1200;
    const screens = JSON.parse(root.dataset.screens || '[]');
    const modalEl = document.getElementById('gdModal');
    const modalFrame = modalEl.querySelector('iframe');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const frames = [];

    // Liens vers une autre page : popup. Les liens qui restent sur la meme page
    // (pagination, filtres, ancres) et ceux deja ouverts ailleurs restent dans la section.
    function interceptLinks(frame) {
        let doc;
        try { doc = frame.contentDocument; } catch (e) { return; }
        if (!doc) return;
        doc.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || link.target || link.hasAttribute('data-bs-toggle')) return;
            const url = new URL(link.href, doc.baseURI);
            if (url.origin !== location.origin || url.pathname === doc.location.pathname) return;
            event.preventDefault();
            modalFrame.src = url.href;
            modal.show();
        });
    }

    function build() {
        if (frames.length || window.innerWidth < DESKTOP) return;
        screens.forEach((screen) => {
            const section = document.createElement('section');
            section.className = 'gd-section';
            const title = document.createElement('h5');
            title.textContent = screen.label;
            const frame = document.createElement('iframe');
            frame.title = screen.label;
            frame.loading = 'lazy';
            frame.addEventListener('load', () => interceptLinks(frame));
            frame.src = screen.url;
            section.append(title, frame);
            root.append(section);
            frames.push(frame);
        });
    }

    // Fermeture de la popup : les sections affichent les changements faits dedans
    modalEl.addEventListener('hidden.bs.modal', () => {
        modalFrame.src = 'about:blank';
        frames.forEach((frame) => {
            try { frame.contentWindow.location.reload(); } catch (e) { /* ignore */ }
        });
    });

    build();
    window.addEventListener('resize', build);
});
