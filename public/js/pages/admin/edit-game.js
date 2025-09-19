document.addEventListener('DOMContentLoaded', function () {
    initDeleteButtons();
});


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


