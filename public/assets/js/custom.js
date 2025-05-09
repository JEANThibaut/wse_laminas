document.addEventListener("DOMContentLoaded", function () {
    const navLinks = document.querySelectorAll('#nav ul.links li');

    navLinks.forEach(function (li) {
        li.addEventListener('click', function () {
            // Supprimer la classe active de tous les li
            navLinks.forEach(function (item) {
                item.classList.remove('active');
            });

            // Ajouter la classe active au li cliqué
            li.classList.add('active');
        });
    });
});