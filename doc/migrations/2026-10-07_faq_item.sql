-- FAQ modifiable depuis l'application (GOD MODE > FAQ).
-- Cree la table et reprend les questions de l'ancienne FAQ fixe, a l'identique.
-- A jouer avant le deploiement (sinon la page FAQ affiche l'ancienne version fixe).

CREATE TABLE faq_item (
    id INT AUTO_INCREMENT NOT NULL,
    question VARCHAR(255) NOT NULL,
    answer LONGTEXT NOT NULL,
    position INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1 NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_faq_item_position (position)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

INSERT INTO faq_item (question, answer, position, is_active, updated_at) VALUES (
    'Où nous trouver ?',
    '<iframe width="100%" height="300" frameborder="0" style="border:0" src="https://www.google.com/maps?q=49.1871134,1.3427494&amp;hl=fr&amp;z=12&amp;output=embed" allowfullscreen>
</iframe>',
    1, 1, NOW());

INSERT INTO faq_item (question, answer, position, is_active, updated_at) VALUES (
    'Acceptez-vous les mineurs ?',
    'Les mineurs, même accompagnés, ne sont pas acceptés sur le terrain.
Ces derniers n\'ayant pas le droit d\'utiliser une réplique développant plus de 0,08 Joules, il est impossible de les faire participer à une partie.',
    2, 1, NOW());

INSERT INTO faq_item (question, answer, position, is_active, updated_at) VALUES (
    'Quel est le prix de la participation ?',
    'Le Paf est à 10 euros par personne.
Nous acceptons les paiements en espèces, ou en carte bancaire.',
    3, 1, NOW());

INSERT INTO faq_item (question, answer, position, is_active, updated_at) VALUES (
    'Quelles sont les puissances autorisées pour les répliques ?',
    'La puissance maximale autorisée pour les répliques est de 2 Joules<br>
Voici le tableau des puissances et distances d\'engagement associées.<br>
L\'énergie est mesurée avec le grammage de jeu, hopup réglé :
<div class="table-responsive mb-3">
<table class="table table-sm table-bordered">
<thead class="table-light"><tr><th>Classe</th><th>Description</th></tr></thead>
<tbody>
<tr>
<td><strong>PA / Pompe</strong></td>
<td>≤ 1 J (mesuré au grammage de jeu). Pas de distance de sécurité. Usage autorisé en CQB en semi-automatique.</td>
</tr>
<tr>
<td><strong>Assaut</strong></td>
<td>Entre 1 J et 1,3 J. Distance de sécurité : 5 m. Tir semi-aut. depuis/vers CQB autorisé. Usage interdit à l’intérieur d’une zone CQB.</td>
</tr>
<tr>
<td><strong>DMR</strong></td>
<td>Entre 1,3 J et 1,5 J. Distance de sécurité : 10 m. Tir semi-aut. depuis/vers CQB autorisé. Usage interdit en CQB.</td>
</tr>
<tr>
<td><strong>Sniper</strong></td>
<td>Entre 1,5 J et 2 J (mesuré avec hop-up réglé). Répliques à réarmement manuel uniquement (sniper). Distance de sécurité : 20 m. Usage interdit en CQB.</td>
</tr>
</tbody>
</table>
</div>',
    4, 1, NOW());

INSERT INTO faq_item (question, answer, position, is_active, updated_at) VALUES (
    'Quel matériel est interdit sur le terrain ?',
    'La pyrotechnie artisanales est interdite.<br>
Les billes métalliques, en verre ou tout matériau non-conventionnel ou dangereux sont interdits.<br>
Les fumigènes blanc ou noir sont interdits.<br>
Les armes blanches, même à destination de décorations, sont interdites.<br>',
    5, 1, NOW());

INSERT INTO faq_item (question, answer, position, is_active, updated_at) VALUES (
    'Est-il possible de louer le terrain pour un évenement privé?',
    'La location du terrain est possible, sous réserve de sa disponibilité. <br>
Merci de vous rapprocher directement d\'un membres pour obtenir plus d\'informations à ce sujet.',
    6, 1, NOW());

