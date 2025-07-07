    
    document.addEventListener('DOMContentLoaded', () => {
        initDatatable();
    });
    
    
    function initDatatable() {
        // repliqueTable = new DataTable('#annonceTable', {
        //     ajax: '/ajax-get-annonces',
        //     columns: [
        //         // { data: 'nom' },
        //         // { data: 'type' },
        //         // {
        //         //     data: 'puissance',
        //         //     render: (data) => `${data} J`
        //         // },
        //         // {
        //         //     data: 'id',
        //         //     sortable: false,
        //         //     render: (data) => `<span class="delete-replique-btn text-danger" data-id="${data}" style="cursor:pointer;"><i class="fas fa-trash"></i></span>`
        //         // }
        //     ],
        //     columnDefs: [
        //         // { width: '50px', targets: 2 },
        //         // { width: '20px', targets: 3 }
        //     ],
        //     searchable: false,
        //     perPage: 10,
        //     perPageSelect: false,
        //     // initComplete: () => bindDeleteReplique()
        // });

         $('#annonces-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "/annonce/ajax-get-annonces", // endpoint que tu vas créer
            "type": "POST"
        },
        "columns": [
            { "data": "id" },
            { "data": "titre" },
            { "data": "description" },
            { "data": "date_creation" }
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json"
        }
    });
    }
