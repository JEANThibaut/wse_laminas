/**
 * Formulaire d'annonce (Petites annonces).
 *
 * Photos : chaque photo choisie est redimensionnee dans le navigateur (1600 px
 * maximum, JPEG) avant l'envoi. Envoi plus rapide, orientation du telephone
 * appliquee, et le HEIC des iPhone devient un JPEG que le serveur sait lire.
 * Si le navigateur ne sait pas faire, la photo d'origine part telle quelle :
 * le serveur la redimensionne de toute facon.
 *
 * Aussi : "A donner" desactive le prix, 3 categories maximum, compteur de la description.
 */
(function () {
    'use strict';

    var MAX_SIDE = 1600;
    var QUALITY = 0.85;

    var form = document.getElementById('annonceForm');
    if (!form) {
        return;
    }

    // Prix / A donner
    var free = document.getElementById('free');
    var price = document.getElementById('price');
    if (free && price) {
        free.addEventListener('change', function () {
            price.disabled = free.checked;
            if (free.checked) {
                price.value = '';
            }
        });
    }

    // Compteur de la description
    var description = document.getElementById('description');
    var counter = document.getElementById('descriptionCount');
    if (description && counter) {
        var updateCount = function () { counter.textContent = description.value.length; };
        description.addEventListener('input', updateCount);
        updateCount();
    }

    // Categories : 3 maximum
    var tagList = document.getElementById('tagList');
    if (tagList) {
        var boxes = tagList.querySelectorAll('input[type=checkbox]');
        var limitTags = function () {
            var checked = tagList.querySelectorAll('input[type=checkbox]:checked').length;
            boxes.forEach(function (box) {
                box.disabled = !box.checked && checked >= 3;
            });
        };
        boxes.forEach(function (box) { box.addEventListener('change', limitTags); });
        limitTags();
    }

    // Photos
    var input = document.getElementById('photos');
    var preview = document.getElementById('photosPreview');
    var help = document.getElementById('photosHelp');
    var submit = document.getElementById('annonceSubmit');
    var pending = null;

    function remainingSlots() {
        var existing = parseInt(input.dataset.existing || '0', 10);
        var removed = form.querySelectorAll('.js-remove-photo:checked').length;
        return parseInt(input.dataset.max || '4', 10) - existing + removed;
    }

    function resize(file) {
        return new Promise(function (resolve) {
            if (!window.URL || !window.HTMLCanvasElement) {
                resolve(file);
                return;
            }
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                var scale = Math.min(1, MAX_SIDE / Math.max(img.naturalWidth, img.naturalHeight));
                var canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(img.naturalWidth * scale));
                canvas.height = Math.max(1, Math.round(img.naturalHeight * scale));
                var context = canvas.getContext('2d');
                context.fillStyle = '#fff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                canvas.toBlob(function (blob) {
                    if (!blob) {
                        resolve(file);
                        return;
                    }
                    var name = (file.name || 'photo').replace(/\.[^.]*$/, '') + '.jpg';
                    try {
                        resolve(new File([blob], name, { type: 'image/jpeg' }));
                    } catch (e) {
                        resolve(file);
                    }
                }, 'image/jpeg', QUALITY);
            };
            img.onerror = function () {
                // Format que le navigateur ne sait pas afficher (HEIC hors Safari...) : envoi tel quel
                URL.revokeObjectURL(url);
                resolve(file);
            };
            img.src = url;
        });
    }

    // Photos nouvellement choisies (deja redimensionnees), pour l'apercu avant publication
    var newFiles = [];

    function showPreview(files) {
        newFiles = files;
        preview.innerHTML = '';
        files.forEach(function (file) {
            var img = document.createElement('img');
            img.className = 'annonce-cover';
            img.style.width = '80px';
            img.alt = '';
            img.src = URL.createObjectURL(file);
            preview.appendChild(img);
        });
    }

    if (input) {
        input.addEventListener('change', function () {
            var files = Array.prototype.slice.call(input.files || []);
            var slots = remainingSlots();
            if (files.length > slots) {
                alert(slots > 0
                    ? 'Vous pouvez encore ajouter ' + slots + ' photo(s) : seules les premières sont gardées.'
                    : 'Nombre maximum de photos atteint : cochez « Supprimer » sur une photo pour la remplacer.');
                files = files.slice(0, Math.max(0, slots));
            }
            help.textContent = 'Préparation des photos…';
            submit.disabled = true;
            pending = Promise.all(files.map(resize)).then(function (resized) {
                try {
                    var transfer = new DataTransfer();
                    resized.forEach(function (file) { transfer.items.add(file); });
                    input.files = transfer.files;
                } catch (e) {
                    // Navigateur ancien : les fichiers d'origine partent tels quels
                }
                showPreview(resized);
                help.textContent = resized.length + ' photo(s) prête(s).';
            }).catch(function () {
                help.textContent = 'Les photos seront envoyées telles quelles.';
            }).then(function () {
                submit.disabled = false;
                pending = null;
            });
        });
    }

    // ---------------------------------------------------------------
    // Apercu avant publication : le bouton du formulaire ouvre l'apercu,
    // seul le bouton de confirmation envoie vraiment le formulaire.
    // Construit avec textContent : aucun texte saisi n'est interprete en HTML.
    // ---------------------------------------------------------------
    var modalElement = document.getElementById('annoncePreview');
    var confirmButton = document.getElementById('previewConfirm');
    var confirmed = false;

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    function priceText() {
        if (free && free.checked) { return 'À donner'; }
        var value = parseInt(price ? price.value : '', 10);
        return isNaN(value) ? '' : value.toLocaleString('fr-FR') + ' €';
    }

    // Photos telles qu'elles apparaitront : celles gardees, puis les nouvelles
    function photoUrls() {
        var urls = [];
        form.querySelectorAll('.js-remove-photo').forEach(function (box) {
            var img = box.closest('label') ? box.closest('label').querySelector('img') : null;
            if (img && !box.checked) { urls.push(img.src); }
        });
        newFiles.forEach(function (file) { urls.push(URL.createObjectURL(file)); });
        return urls;
    }

    function selectedTags() {
        var labels = [];
        form.querySelectorAll('input[name="tags[]"]:checked').forEach(function (box) {
            var label = form.querySelector('label[for="' + box.id + '"]');
            if (label) { labels.push(label.textContent.trim()); }
        });
        return labels;
    }

    function buildPreview() {
        var title = document.getElementById('title').value.trim();
        var urls = photoUrls();
        var today = new Date();
        var day = String(today.getDate()).padStart(2, '0') + '/' + String(today.getMonth() + 1).padStart(2, '0');

        // Vignette de la liste
        var card = el('div', 'card annonce-card mb-0');
        if (urls[0]) {
            var cover = el('img', 'annonce-cover');
            cover.src = urls[0];
            cover.alt = '';
            card.appendChild(cover);
        } else {
            var empty = el('div', 'annonce-cover-empty');
            empty.appendChild(el('i', 'fa-regular fa-image'));
            card.appendChild(empty);
        }
        var body = el('div', 'annonce-card-body');
        body.appendChild(el('div', 'annonce-card-title', title));
        var line = el('div', 'd-flex justify-content-between align-items-end mt-1');
        line.appendChild(el('span', 'annonce-price', priceText()));
        line.appendChild(el('small', 'text-muted', day));
        body.appendChild(line);
        card.appendChild(body);
        var cardBox = document.getElementById('previewCard');
        cardBox.innerHTML = '';
        cardBox.appendChild(card);

        // Fiche
        var view = el('div', 'card p-0 mb-0');
        if (urls.length) {
            var carousel = el('div', 'carousel slide annonce-carousel');
            carousel.id = 'previewCarousel';
            var inner = el('div', 'carousel-inner');
            urls.forEach(function (url, index) {
                var item = el('div', 'carousel-item' + (index === 0 ? ' active' : ''));
                var img = el('img');
                img.src = url;
                img.alt = 'Photo ' + (index + 1);
                item.appendChild(img);
                inner.appendChild(item);
            });
            carousel.appendChild(inner);
            if (urls.length > 1) {
                [['prev', 'Précédente'], ['next', 'Suivante']].forEach(function (pair) {
                    var control = el('button', 'carousel-control-' + pair[0]);
                    control.type = 'button';
                    control.setAttribute('data-bs-target', '#previewCarousel');
                    control.setAttribute('data-bs-slide', pair[0]);
                    control.appendChild(el('span', 'carousel-control-' + pair[0] + '-icon'));
                    control.appendChild(el('span', 'visually-hidden', pair[1]));
                    carousel.appendChild(control);
                });
            }
            view.appendChild(carousel);
        }
        var content = el('div', 'p-3');
        content.appendChild(el('h4', 'mb-1 text-break', title));
        var priceLine = el('div', 'fs-5 mb-2');
        priceLine.appendChild(el('span', 'annonce-price', priceText()));
        content.appendChild(priceLine);
        var tags = selectedTags();
        if (tags.length) {
            var tagLine = el('div', 'd-flex flex-wrap mb-3');
            tagLine.style.gap = '.25rem';
            tags.forEach(function (tag) { tagLine.appendChild(el('span', 'badge bg-secondary', tag)); });
            content.appendChild(tagLine);
        }
        content.appendChild(el('div', 'annonce-description mb-3', description ? description.value.trim() : ''));
        content.appendChild(el('div', 'small text-muted', (form.dataset.seller || 'Vous') + ' · publiée le ' + day + '/' + today.getFullYear()));
        view.appendChild(content);
        var viewBox = document.getElementById('previewView');
        viewBox.innerHTML = '';
        viewBox.appendChild(view);
    }

    // Bootstrap est charge apres ce script (bas du layout) : ne pas le tester ici
    if (modalElement && confirmButton) {
        confirmButton.addEventListener('click', function () {
            confirmed = true;
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';
            submit.disabled = true;
            // submit() n'est pas intercepte : le formulaire part tel quel
            form.submit();
        });
    }

    form.addEventListener('submit', function (event) {
        // Sans fenetre d'apercu (script Bootstrap absent) : envoi direct, comme avant
        if (confirmed || !modalElement || !window.bootstrap) {
            submit.disabled = true;
            return;
        }
        event.preventDefault();
        (pending || Promise.resolve()).then(function () {
            buildPreview();
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
    });
})();
