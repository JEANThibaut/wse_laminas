document.addEventListener('DOMContentLoaded', function () {
    initDeleteButtons();
    initResetPasswordButtons();
    initGeneratePasswordButtons();
    initCopyGeneratedPassword();
});


function initDeleteButtons(){
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const confirmForm = document.getElementById('confirmDeleteForm');
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const form = this.closest('form');
            confirmForm.action = form.action;
            confirmForm.querySelector('input[name="iduser"]')?.remove();

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'iduser';
            hiddenInput.value = this.dataset.iduser;
            confirmForm.appendChild(hiddenInput);

            modal.show();
        });
    });
}

function initResetPasswordButtons(){
    const resetButtons = document.querySelectorAll('.btn-reset-password');
    const confirmForm = document.getElementById('confirmResetForm');
    const modal = new bootstrap.Modal(document.getElementById('confirmResetModal'));

    resetButtons.forEach(button => {
        button.addEventListener('click', function () {
            const form = this.closest('form');
            confirmForm.action = form.action;
            confirmForm.querySelector('input[name="iduser"]')?.remove();

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'iduser';
            hiddenInput.value = this.dataset.iduser;
            confirmForm.appendChild(hiddenInput);

            modal.show();
        });
    });
}

function initGeneratePasswordButtons(){
    const generateButtons = document.querySelectorAll('.btn-generate-password');
    const confirmForm = document.getElementById('confirmGenerateForm');
    const modalEl = document.getElementById('confirmGenerateModal');
    if (!generateButtons.length || !confirmForm || !modalEl) return;
    const modal = new bootstrap.Modal(modalEl);

    generateButtons.forEach(button => {
        button.addEventListener('click', function () {
            const form = this.closest('form');
            confirmForm.action = form.action;
            confirmForm.querySelector('input[name="iduser"]')?.remove();

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'iduser';
            hiddenInput.value = this.dataset.iduser;
            confirmForm.appendChild(hiddenInput);

            modal.show();
        });
    });
}

function initCopyGeneratedPassword(){
    const btn = document.getElementById('btnCopyGeneratedPassword');
    const value = document.getElementById('generatedPasswordValue');
    if (!btn || !value) return;

    btn.addEventListener('click', function () {
        navigator.clipboard.writeText(value.textContent.trim()).then(() => {
            const originalLabel = btn.textContent;
            btn.textContent = 'Copié !';
            setTimeout(() => { btn.textContent = originalLabel; }, 2000);
        });
    });
}


