// Recherche AJAX de la liste des utilisateurs (nom, prenom, email)
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('userSearch');
    const tbody = document.querySelector('#userTable tbody');
    if (!input || !tbody) return;

    let timer = null;
    let controller = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => search(input.value.trim()), 250);
    });

    function search(term) {
        // Annule la requete precedente : seule la derniere saisie compte
        if (controller) controller.abort();
        controller = new AbortController();

        fetch(`${input.dataset.url}?q=${encodeURIComponent(term)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        })
            .then(res => {
                // Session expiree : on recharge pour repasser par la connexion
                if (res.status === 403 || res.redirected) {
                    window.location.reload();
                    return null;
                }
                if (!res.ok) throw new Error(res.status);
                return res.text();
            })
            .then(html => {
                if (html !== null) tbody.innerHTML = html;
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Erreur lors de la recherche.</td></tr>';
            });
    }
});
