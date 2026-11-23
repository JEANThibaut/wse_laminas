-- =====================================================================
-- MISE EN PRODUCTION : Petites annonces, Messagerie, Moderation, Organisation
-- Regroupe, dans l'ordre, les 4 migrations suivantes (NE PAS les rejouer en plus) :
--   1. 2026-11-20_petites_annonces.sql
--   2. 2026-11-20_messagerie.sql
--   3. 2026-11-20_moderation.sql
--   4. 2026-11-23_organisation.sql
-- A jouer UNE SEULE FOIS, avant le deploiement des commits e1081b1 a 2ce3787.
--
-- ATTENTION : la partie 1 supprime l'ancienne table `annonce` (ebauche jamais
-- ouverte). Verifier avant : SELECT COUNT(*) FROM annonce;
-- =====================================================================

SET NAMES utf8mb4;


-- =====================================================================
-- 2026-11-20_petites_annonces.sql
-- =====================================================================

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


-- =====================================================================
-- 2026-11-20_messagerie.sql
-- =====================================================================

-- Messagerie interne (module Messagerie).
-- Acces regle par la config features.messagerie (GOD seulement tant qu'elle n'est pas ouverte).
-- A jouer apres 2026-11-20_petites_annonces.sql (cle etrangere vers annonce), avant le deploiement.

-- Fil de discussion : entre membres (a propos d'une annonce) ou message de l'administration
CREATE TABLE conversation (
    id INT AUTO_INCREMENT NOT NULL,
    -- member : entre membres ; admin : message de l'administration, sans reponse possible
    type VARCHAR(10) NOT NULL,
    -- Annonce d'origine ; NULL une fois l'annonce purgee (le sujet reste)
    annonce_id INT DEFAULT NULL,
    subject VARCHAR(120) NOT NULL,
    -- Envoi groupe de l'administration : meme identifiant pour tous les destinataires
    batch VARCHAR(32) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    last_message_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_conversation_annonce (annonce_id),
    INDEX idx_conversation_batch (batch),
    CONSTRAINT fk_conversation_annonce FOREIGN KEY (annonce_id) REFERENCES annonce (id) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Qui voit le fil, et jusqu'ou il l'a lu (compteur de non-lus)
CREATE TABLE conversation_participant (
    id INT AUTO_INCREMENT NOT NULL,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    last_read_at DATETIME DEFAULT NULL,
    -- Fil masque de la boite de reception ; reapparait au message suivant
    archived_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX uniq_conversation_participant (conversation_id, user_id),
    INDEX idx_conversation_participant_user (user_id),
    CONSTRAINT fk_conversation_participant_conversation FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_participant_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

CREATE TABLE message (
    id INT AUTO_INCREMENT NOT NULL,
    conversation_id INT NOT NULL,
    -- Auteur ; NULL si son compte a ete supprime
    sender_id INT DEFAULT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_message_conversation (conversation_id, created_at),
    INDEX idx_message_sender (sender_id, created_at),
    CONSTRAINT fk_message_conversation FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE,
    CONSTRAINT fk_message_sender FOREIGN KEY (sender_id) REFERENCES user (iduser) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Membre bloque : ne peut plus ecrire a celui qui l'a bloque
CREATE TABLE user_block (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    blocked_user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX uniq_user_block (user_id, blocked_user_id),
    INDEX idx_user_block_blocked (blocked_user_id),
    CONSTRAINT fk_user_block_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE,
    CONSTRAINT fk_user_block_blocked FOREIGN KEY (blocked_user_id) REFERENCES user (iduser) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;


-- =====================================================================
-- 2026-11-20_moderation.sql
-- =====================================================================

-- Moderation (module Moderation) : signalements d'annonces et de messages, sanctions.
-- A jouer apres 2026-11-20_petites_annonces.sql et 2026-11-20_messagerie.sql, avant le deploiement.
-- Une annonce trop signalee passe au statut 'hidden' (colonne annonce.status existante).

CREATE TABLE report (
    id INT AUTO_INCREMENT NOT NULL,
    reporter_id INT NOT NULL,
    -- Cible : une annonce OU un message
    annonce_id INT DEFAULT NULL,
    message_id INT DEFAULT NULL,
    -- off_topic, forbidden, scam, inappropriate, other
    reason VARCHAR(20) NOT NULL,
    comment VARCHAR(500) DEFAULT NULL,
    -- pending, dismissed (classe sans suite), actioned (cible retiree)
    status VARCHAR(10) NOT NULL,
    created_at DATETIME NOT NULL,
    handled_by INT DEFAULT NULL,
    handled_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_report_status (status),
    INDEX idx_report_annonce (annonce_id),
    INDEX idx_report_message (message_id),
    INDEX idx_report_reporter (reporter_id, created_at),
    UNIQUE INDEX uniq_report_annonce (reporter_id, annonce_id),
    UNIQUE INDEX uniq_report_message (reporter_id, message_id),
    CONSTRAINT fk_report_reporter FOREIGN KEY (reporter_id) REFERENCES user (iduser) ON DELETE CASCADE,
    CONSTRAINT fk_report_annonce FOREIGN KEY (annonce_id) REFERENCES annonce (id) ON DELETE CASCADE,
    CONSTRAINT fk_report_message FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE,
    CONSTRAINT fk_report_handled_by FOREIGN KEY (handled_by) REFERENCES user (iduser) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Historique des sanctions d'un membre
CREATE TABLE user_sanction (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    -- warning (avertissement), suspension (interdit de publier jusqu'a ends_at),
    -- ban (interdit de publier definitivement), blacklist (compte bloque)
    type VARCHAR(12) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    ends_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    created_by INT DEFAULT NULL,
    -- Sanction levee avant son terme
    revoked_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_user_sanction_user (user_id, created_at),
    CONSTRAINT fk_user_sanction_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE,
    CONSTRAINT fk_user_sanction_created_by FOREIGN KEY (created_by) REFERENCES user (iduser) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;


-- =====================================================================
-- 2026-11-23_organisation.sql
-- =====================================================================

-- Espace membres > Organisation (module Organisation) : messages, sondages,
-- evenements avec reponse "je participe / je ne participe pas".
-- Acces regle par la config features.organisation (GOD seulement pour l'instant),
-- publication par features.organisation_edition. A jouer avant le deploiement.

-- Publication : message, sondage ou evenement
CREATE TABLE organisation_post (
    id INT AUTO_INCREMENT NOT NULL,
    -- message, poll, event
    type VARCHAR(10) NOT NULL,
    author_id INT DEFAULT NULL,
    title VARCHAR(120) NOT NULL,
    body TEXT DEFAULT NULL,
    -- Epinglee en haut du fil
    pinned TINYINT(1) DEFAULT 0 NOT NULL,
    -- Evenement : debut, fin facultative, lieu
    starts_at DATETIME DEFAULT NULL,
    ends_at DATETIME DEFAULT NULL,
    location VARCHAR(120) DEFAULT NULL,
    -- Sondage : plusieurs choix possibles
    multiple TINYINT(1) DEFAULT 0 NOT NULL,
    -- Sondage ou evenement : plus de reponse apres cette date (evenement : debut par defaut)
    closes_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_organisation_post_created (pinned, created_at),
    INDEX idx_organisation_post_starts (type, starts_at),
    CONSTRAINT fk_organisation_post_author FOREIGN KEY (author_id) REFERENCES user (iduser) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Choix d'un sondage
CREATE TABLE organisation_poll_option (
    id INT AUTO_INCREMENT NOT NULL,
    post_id INT NOT NULL,
    label VARCHAR(120) NOT NULL,
    position INT NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_organisation_poll_option_post (post_id),
    CONSTRAINT fk_organisation_poll_option_post FOREIGN KEY (post_id) REFERENCES organisation_post (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Vote d'un membre pour un choix
CREATE TABLE organisation_poll_vote (
    id INT AUTO_INCREMENT NOT NULL,
    option_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX uniq_organisation_poll_vote (option_id, user_id),
    INDEX idx_organisation_poll_vote_user (user_id),
    CONSTRAINT fk_organisation_poll_vote_option FOREIGN KEY (option_id) REFERENCES organisation_poll_option (id) ON DELETE CASCADE,
    CONSTRAINT fk_organisation_poll_vote_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Notifications "Organisation" (nouvelle publication), reglables dans le profil.
-- Activees par defaut, contrairement aux parties et actualites : seuls les
-- membres ayant acces a l'espace et un appareil abonne les recoivent.
ALTER TABLE user
    ADD notification_orga ENUM('true', 'false') NOT NULL DEFAULT 'true';

-- Reponse a un evenement : yes (je participe) ou no (je ne participe pas)
CREATE TABLE organisation_rsvp (
    id INT AUTO_INCREMENT NOT NULL,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    answer VARCHAR(3) NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX uniq_organisation_rsvp (post_id, user_id),
    INDEX idx_organisation_rsvp_user (user_id),
    CONSTRAINT fk_organisation_rsvp_post FOREIGN KEY (post_id) REFERENCES organisation_post (id) ON DELETE CASCADE,
    CONSTRAINT fk_organisation_rsvp_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

