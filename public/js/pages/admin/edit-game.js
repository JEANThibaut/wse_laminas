document.addEventListener('DOMContentLoaded', function () {
    initDeleteButtons();
    initAddPlayerSearch();
});

// Minuscules sans accents : "Jérôme" se trouve en tapant "jerome"
function normalizeSearch(text) {
    return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

function initAddPlayerSearch() {
    const modal = document.getElementById('addPlayerModal');
    const search = document.getElementById('addPlayerSearch');
    const items = document.querySelectorAll('#addPlayerList [data-search]');
    const empty = document.getElementById('addPlayerEmpty');
    if (!modal || !search) {
        return;
    }

    items.forEach(item => {
        item.dataset.searchNormalized = normalizeSearch(item.dataset.search);
    });

    search.addEventListener('input', function () {
        // Chaque mot tape doit apparaitre, dans n'importe quel ordre
        const terms = normalizeSearch(this.value).split(/\s+/).filter(Boolean);
        let visible = 0;
        items.forEach(item => {
            const match = terms.every(term => item.dataset.searchNormalized.includes(term));
            item.classList.toggle('d-none', !match);
            if (match) {
                visible++;
            }
        });
        empty?.classList.toggle('d-none', visible > 0);
    });

    modal.addEventListener('shown.bs.modal', () => search.focus());
}


function initDeleteButtons(){
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const confirmForm = document.getElementById('confirmDeleteForm');
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const form = this.closest('form');
            confirmForm.action = form.action;
            confirmForm.querySelector('input[name="id"]')?.remove(); 

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'id';
            hiddenInput.value = this.dataset.id;
            confirmForm.appendChild(hiddenInput);

            modal.show();
        });
    });
}


