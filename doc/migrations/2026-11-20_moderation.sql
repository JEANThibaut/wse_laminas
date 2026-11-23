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
