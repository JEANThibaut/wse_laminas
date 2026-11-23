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
