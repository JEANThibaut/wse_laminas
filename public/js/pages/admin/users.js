// Recherche AJAX de la liste des utilisateurs (nom, prenom, email), appliquee
// a la fois aux comptes actifs et aux comptes desactives
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('userSearch');
    const lists = document.querySelectorAll('tbody[data-status]');
    const inactiveCount = document.getElementById('inactiveCount');
    if (!input || !lists.length) return;

    let timer = null;
    let controller = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => search(input.value.trim()), 250);
    });

    function fetchRows(term, status, signal) {
        const url = `${input.dataset.url}?q=${encodeURIComponent(term)}&status=${encodeURIComponent(status)}`;
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal,
        }).then(res => {
            // Session expiree : on recharge pour repasser par la connexion
            if (res.status === 403 || res.redirected) {
                window.location.reload();
                return null;
            }
            if (!res.ok) throw new Error(res.status);
            return res.text();
        });
    }

    function search(term) {
        // Annule les requetes precedentes : seule la derniere saisie compte
        if (controller) controller.abort();
        controller = new AbortController();
        const signal = controller.signal;

        lists.forEach(tbody => {
            fetchRows(term, tbody.dataset.status, signal)
                .then(html => {
                    if (html === null) return;
                    tbody.innerHTML = html;
                    if (tbody.dataset.status === 'inactive' && inactiveCount) {
                        inactiveCount.textContent = tbody.querySelectorAll('[data-user-row]').length;
                    }
                })
                .catch(err => {
                    if (err.name === 'AbortError') return;
                    tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Erreur lors de la recherche.</td></tr>';
                });
        });
    }
});
