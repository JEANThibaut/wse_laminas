// GOD MODE > Tableau de bord : colonnes au format telephone, chacune affichant
// une page d'administration dans un cadre. Ordinateur uniquement (>= 1200 px) :
// en dessous, rien n'est charge. 3 colonnes, 4 a partir de 1600 px.
(function () {
    const root = document.getElementById('godDashboard');
    // Jamais de tableau de bord dans un tableau de bord
    if (!root || window.self !== window.top) return;

    const DESKTOP = 1200;
    const LARGE = 1600;
    const STORAGE_KEY = 'godDashboard.columns';
    const DEFAULTS = ['games', 'next-game', 'users', 'actus'];

    const screens = JSON.parse(root.dataset.screens || '[]');
    const byKey = Object.fromEntries(screens.map((s) => [s.key, s]));

    // localStorage peut etre indisponible : on retombe sur la disposition par defaut
    function loadChoices() {
        try {
            const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            return DEFAULTS.map((key, i) => (byKey[saved[i]] ? saved[i] : key));
        } catch (e) {
            return DEFAULTS.slice();
        }
    }
    const choices = loadChoices();
    function saveChoices() {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(choices)); } catch (e) { /* ignore */ }
    }

    function button(icon, title) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm btn-outline-light';
        b.title = title;
        b.setAttribute('aria-label', title);
        b.innerHTML = '<i class="fa-solid ' + icon + '"></i>';
        return b;
    }

    // Une colonne : en-tete (retour, choix de l'ecran, recharger, nouvel onglet) + cadre.
    // Historique propre a la colonne : le bouton retour ne fait jamais reculer une autre colonne.
    function createColumn(index) {
        const column = document.createElement('div');
        column.className = 'gd-column';

        const header = document.createElement('div');
        header.className = 'gd-header';
        const back = button('fa-arrow-left', 'Retour');
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        screens.forEach((s) => select.add(new Option(s.label, s.key)));
        select.value = choices[index];
        const reload = button('fa-rotate-right', 'Recharger');
        const open = button('fa-up-right-from-square', 'Ouvrir dans un nouvel onglet');
        header.append(back, select, reload, open);

        const frame = document.createElement('iframe');
        frame.title = byKey[choices[index]].label;
        column.append(header, frame);

        const state = { column, loaded: false, stack: [], goingBack: false };

        function currentUrl() {
            try { return frame.contentWindow.location.href; } catch (e) { return frame.src; }
        }
        function updateBack() {
            back.disabled = state.stack.length <= 1;
        }

        state.load = function () {
            if (state.loaded) return;
            state.loaded = true;
            frame.src = byKey[select.value].url;
        };

        frame.addEventListener('load', () => {
            const url = currentUrl();
            if (state.goingBack) {
                state.goingBack = false;
            } else if (state.stack[state.stack.length - 1] !== url) {
                state.stack.push(url);
            }
            updateBack();
        });

        back.addEventListener('click', () => {
            if (state.stack.length <= 1) return;
            state.stack.pop();
            state.goingBack = true;
            frame.contentWindow.location.replace(state.stack[state.stack.length - 1]);
        });
        select.addEventListener('change', () => {
            choices[index] = select.value;
            saveChoices();
            frame.title = byKey[select.value].label;
            state.stack = [];
            frame.src = byKey[select.value].url;
        });
        reload.addEventListener('click', () => frame.contentWindow.location.reload());
        open.addEventListener('click', () => window.open(currentUrl(), '_blank', 'noopener'));

        updateBack();
        return state;
    }

    const columns = DEFAULTS.map((_, i) => createColumn(i));
    columns.forEach((c) => root.append(c.column));

    // Colonnes visibles selon la largeur ; un cadre n'est charge qu'a son premier affichage.
    // Hauteur calee sur l'ecran : chaque colonne defile de son cote.
    function layout() {
        if (window.innerWidth < DESKTOP) return;
        const count = window.innerWidth >= LARGE ? 4 : 3;
        columns.forEach((c, i) => {
            const visible = i < count;
            c.column.style.display = visible ? '' : 'none';
            if (visible) c.load();
        });
        const top = root.getBoundingClientRect().top + window.scrollY;
        root.style.height = Math.max(400, window.innerHeight - top - 16) + 'px';
    }

    layout();
    window.addEventListener('resize', layout);
})();
