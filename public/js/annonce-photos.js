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

    function showPreview(files) {
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

    form.addEventListener('submit', function (event) {
        if (pending) {
            event.preventDefault();
            pending.then(function () { form.submit(); });
            return;
        }
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';
    });
})();
