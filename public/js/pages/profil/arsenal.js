function initArsenalTab() {
    let repliqueTable = null;

    initDatatable();
    bindAddReplique();
    bindDeleteReplique();

    function initDatatable() {
        repliqueTable = new DataTable('#repliqueTable', {
            ajax: '/ajax-get-repliques',
            columns: [
                { data: 'nom' },
                { data: 'type' },
                {
                    data: 'puissance',
                    render: (data) => `${data} J`
                },
                {
                    data: 'id',
                    sortable: false,
                    render: (data) => `<span class="delete-replique-btn text-danger" data-id="${data}" style="cursor:pointer;"><i class="fas fa-trash"></i></span>`
                }
            ],
            columnDefs: [
                { width: '50px', targets: 2 },
                { width: '20px', targets: 3 }
            ],
            searchable: false,
            perPage: 10,
            perPageSelect: false,
            initComplete: () => bindDeleteReplique()
        });
    }

    function bindAddReplique() {
        const form = document.getElementById('repliqueForm');
        if (!form) return;
        form.addEventListener('submit', handleAddReplique);
    }

    function bindDeleteReplique() {
        const deleteButtons = document.querySelectorAll('.delete-replique-btn');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', handleDeleteReplique);
        });
    }

    function handleAddReplique(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        fetch('/ajax-add-replique', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    form.reset();
                    reloadRepliqueTable();
                } else {
                    console.warn('Erreur ajout :', data);
                }
            })
            .catch(err => console.error('Erreur AJAX ajout :', err));
    }

    function handleDeleteReplique(event) {
        event.preventDefault();
        const btn = event.currentTarget;
        if (!confirm('Supprimer cette réplique ?')) return;

        const id = btn.dataset.id;
        const formData = new FormData();
        formData.append('idreplique', id);

        fetch('/ajax-delete-replique', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = btn.closest('tr');
                    if (row) row.remove();
                    reloadRepliqueTable();
                } else {
                    console.warn('Erreur suppression :', data.message);
                }
            })
            .catch(err => console.error('Erreur AJAX suppression :', err));
    }

    function reloadRepliqueTable() {
        if (repliqueTable) {
            repliqueTable.ajax.reload(() => {
                bindDeleteReplique();
            });
        }
    }
}

initArsenalTab();
