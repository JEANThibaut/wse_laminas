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
