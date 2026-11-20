-- Petites annonces, version reecrite (module Annonce).
-- Remplace l'ancienne table `annonce` (ebauche jamais ouverte) : ses lignes sont abandonnees.
-- Acces regle par la config features.annonces (GOD seulement tant que l'espace n'est pas ouvert).
-- A jouer avant le deploiement.

DROP TABLE IF EXISTS annonce;

CREATE TABLE annonce (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    -- En euros ; NULL = "A donner"
    price INT DEFAULT NULL,
    -- online, withdrawn (retiree par l'auteur), removed (retiree par la moderation)
    status VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    renew_count INT DEFAULT 0 NOT NULL,
    -- Date de passage a withdrawn / removed (point de depart de la purge)
    closed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_annonce_status_expires (status, expires_at),
    INDEX idx_annonce_user (user_id),
    CONSTRAINT fk_annonce_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

CREATE TABLE annonce_photo (
    id INT AUTO_INCREMENT NOT NULL,
    annonce_id INT NOT NULL,
    filename VARCHAR(64) NOT NULL,
    position INT NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_annonce_photo_annonce (annonce_id),
    CONSTRAINT fk_annonce_photo_annonce FOREIGN KEY (annonce_id) REFERENCES annonce (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

CREATE TABLE annonce_tag (
    id INT AUTO_INCREMENT NOT NULL,
    label VARCHAR(50) NOT NULL,
    position INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1 NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_annonce_tag_position (position)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

CREATE TABLE annonce_tag_link (
    annonce_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (annonce_id, tag_id),
    INDEX idx_annonce_tag_link_tag (tag_id),
    CONSTRAINT fk_annonce_tag_link_annonce FOREIGN KEY (annonce_id) REFERENCES annonce (id) ON DELETE CASCADE,
    CONSTRAINT fk_annonce_tag_link_tag FOREIGN KEY (tag_id) REFERENCES annonce_tag (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Reglages modifiables dans GOD MODE > Petites annonces (valeurs par defaut dans le code)
CREATE TABLE annonce_setting (
    setting_key VARCHAR(50) NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    PRIMARY KEY (setting_key)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- CGU de l'espace : une ligne par version, a accepter une fois par version
CREATE TABLE annonce_cgu_version (
    id INT AUTO_INCREMENT NOT NULL,
    version INT NOT NULL,
    content LONGTEXT NOT NULL,
    published_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX uniq_annonce_cgu_version (version)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Preuve d'acceptation : qui, quelle version, quand, depuis quelle IP
CREATE TABLE annonce_cgu_acceptance (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    cgu_version_id INT NOT NULL,
    accepted_at DATETIME NOT NULL,
    ip VARCHAR(45) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX uniq_annonce_cgu_acceptance (user_id, cgu_version_id),
    CONSTRAINT fk_annonce_cgu_acceptance_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE,
    CONSTRAINT fk_annonce_cgu_acceptance_version FOREIGN KEY (cgu_version_id) REFERENCES annonce_cgu_version (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Tags de depart, airsoft uniquement (pas de "Divers" : porte ouverte au hors-sujet)
INSERT INTO annonce_tag (label, position, is_active) VALUES
('Répliques électriques (AEG)', 1, 1),
('Répliques gaz (GBB)', 2, 1),
('Répliques spring / sniper', 3, 1),
('Pièces & upgrades', 4, 1),
('Batteries & chargeurs', 5, 1),
('Chargeurs (magazines)', 6, 1),
('Optiques & visées', 7, 1),
('Protections', 8, 1),
('Équipement (gilets, ceinturons)', 9, 1),
('Tenues & camouflage', 10, 1),
('Radio & communication', 11, 1),
('Consommables (billes, gaz)', 12, 1);

-- CGU version 1 (HTML, modifiable en publiant une nouvelle version dans GOD MODE)
INSERT INTO annonce_cgu_version (version, content, published_at) VALUES (1, '<h5>Article 1 – Objet</h5>
<p>Les présentes conditions encadrent l’utilisation de l’espace « Petites annonces » du site Wolf Soft Eure, édité par l’association WolfSoftEure (contact@wolfsofteure.fr). Elles complètent les règles générales du site. L’accès à l’espace vaut acceptation pleine et entière des présentes conditions.</p>

<h5>Article 2 – Rôle du site</h5>
<p>L’association met gratuitement à disposition un espace technique permettant aux membres de publier des annonces et d’échanger entre eux. Elle agit en qualité d’<strong>hébergeur</strong> :</p>
<ul>
<li>elle n’est ni vendeur, ni acheteur, ni intermédiaire commercial ;</li>
<li>elle ne perçoit aucune commission et ne traite aucun paiement ;</li>
<li>elle ne vérifie pas, avant leur publication, l’exactitude des annonces, la qualité, la conformité ou l’existence des objets proposés, ni l’identité ou la solvabilité des membres.</li>
</ul>

<h5>Article 3 – Conditions d’accès</h5>
<p>L’espace est réservé aux membres disposant d’un compte validé et non suspendu. La publication d’annonces est en outre réservée aux membres ayant participé à au moins une partie ou inscrits depuis la durée fixée par l’association. Le membre déclare être <strong>majeur</strong>.</p>

<h5>Article 4 – Règles de publication</h5>
<p>Les annonces portent <strong>exclusivement sur du matériel lié à la pratique de l’airsoft</strong>. Le membre s’engage à :</p>
<ul>
<li>être propriétaire de l’objet proposé, ou autorisé à le vendre ;</li>
<li>en donner une description sincère et exacte, notamment sur son état et sa puissance ;</li>
<li>n’utiliser que des photos dont il détient les droits et qui représentent l’objet réellement proposé ;</li>
<li>ne pas exercer d’activité commerciale ou professionnelle au travers de l’espace ;</li>
<li>respecter les limites de nombre et de durée des annonces fixées par l’association.</li>
</ul>

<h5>Article 5 – Contenus et objets interdits</h5>
<p>Sont notamment interdits :</p>
<ul>
<li>les armes réelles, armes neutralisées, pièces et munitions d’armes réelles ;</li>
<li>les répliques modifiées en violation de la réglementation, ou présentées comme telles ;</li>
<li>les produits pyrotechniques non conformes ;</li>
<li>les objets volés, contrefaits ou dont la vente est illégale ;</li>
<li>tout objet ou service sans rapport avec l’airsoft ;</li>
<li>tout contenu injurieux, discriminatoire, violent, à caractère sexuel ou contraire à la loi ;</li>
<li>les coordonnées personnelles dans les annonces (la messagerie interne doit être utilisée).</li>
</ul>

<h5>Article 6 – Réglementation des répliques</h5>
<p>Le vendeur et l’acheteur sont seuls responsables du respect de la réglementation applicable aux répliques d’armes, notamment en matière de <strong>majorité</strong>, de <strong>puissance</strong> (au-delà de 2 joules, l’objet n’est plus une réplique et relève de la réglementation sur les armes) et de <strong>transport</strong> (en housse ou en mallette, jamais exposée sur la voie publique). La remise en main propre se fait avec discrétion.</p>

<h5>Article 7 – Transactions</h5>
<p>Les transactions sont conclues <strong>directement et exclusivement entre membres</strong>, qui en fixent librement les conditions : prix, paiement, remise, garantie. L’association n’est partie à aucune transaction et ne peut être tenue responsable d’un litige, d’un défaut de paiement ou de livraison, d’un vice de l’objet ou d’une fraude entre membres. Il est vivement recommandé de se rencontrer dans un lieu public, de vérifier l’objet avant tout paiement et de ne jamais payer à l’avance une personne inconnue.</p>

<h5>Article 8 – Messagerie interne</h5>
<p>Les échanges entre membres se font par la messagerie interne du site. L’association peut y adresser des messages d’information, auxquels il n’est pas possible de répondre. En cas de signalement, l’association peut <strong>consulter la conversation concernée</strong>, dans le seul but de traiter ce signalement.</p>

<h5>Article 9 – Signalement</h5>
<p>Tout membre peut signaler une annonce ou un message qu’il estime contraire aux présentes conditions ou à la loi, en indiquant un motif. Au-delà d’un certain nombre de signalements, une annonce peut être masquée automatiquement en attendant son examen par l’association.</p>

<h5>Article 10 – Modération et sanctions</h5>
<p>L’association peut à tout moment, sans préavis, retirer une annonce ou un message contraire aux présentes conditions. Le membre est informé du retrait et de son motif. Selon la gravité et la récidive :</p>
<ol>
<li>avertissement ;</li>
<li>interdiction temporaire de publier ;</li>
<li>interdiction définitive de publier.</li>
</ol>
<p>En cas de manquement grave (arme réelle, fraude, contenu illicite…), le compte peut être <strong>suspendu ou supprimé immédiatement</strong>, et les faits signalés aux autorités compétentes. Ces mesures n’ouvrent droit à aucune indemnité.</p>

<h5>Article 11 – Données personnelles</h5>
<p>Les annonces et leurs photos sont supprimées définitivement 30 jours après leur expiration ou leur retrait. Les métadonnées des photos (dont la localisation) sont supprimées dès l’envoi. Les preuves d’acceptation des présentes conditions et l’historique des signalements et sanctions sont conservés tant que le compte existe. Les droits d’accès, de rectification et de suppression s’exercent auprès de contact@wolfsofteure.fr.</p>

<h5>Article 12 – Responsabilité</h5>
<p>Chaque membre est seul responsable du contenu qu’il publie et des transactions qu’il conclut. Il garantit l’association contre toute réclamation liée à ses annonces ou à ses échanges. La responsabilité de l’association ne peut être engagée qu’en cas de manquement à ses obligations d’hébergeur.</p>

<h5>Article 13 – Modification des conditions</h5>
<p>Ces conditions peuvent évoluer. Toute nouvelle version doit être acceptée pour continuer à utiliser l’espace.</p>

<h5>Article 14 – Droit applicable</h5>
<p>Les présentes conditions sont soumises au droit français.</p>', NOW());
