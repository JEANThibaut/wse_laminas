const table = new DataTable('#userTable', {
    ajax: '/admin-get-users',
    columns: [
        { data: 'lastname' },
        { data: 'firstname' },
        {
            data: 'id',
            render: function(data, type, row) {
                return `<button class="btn-primary btn-sm"><a href="/admin-user-profil/${data}">Voir</a></button>`;
            }
        }
    ],
    perPage: 10,
    perPageSelect: [5, 10, 25, 50],
});


table.on('init', () => {
    const layoutRow = document.querySelector('.dt-layout-row');
    if (layoutRow) {
        layoutRow.classList.add('row');
        layoutRow.querySelectorAll('.dt-layout-cell').forEach(cell => {
            cell.classList.add('col-sm-3');
        });

        const select = document.getElementById('dt-length-0');
        const input = document.getElementById('dt-search-0');
        if (input) {
            input.placeholder = 'Rechercher...';
          }
        if (select) select.classList.add('form-select');
        if (input) input.classList.add('form-control');
    }
});
