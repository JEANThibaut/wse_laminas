-- Journal des connexions et derniere visite des comptes.
-- A jouer une fois, de preference avant le deploiement du code (sinon les
-- connexions faites entre-temps ne sont simplement pas journalisees).

CREATE TABLE login_log (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    email VARCHAR(180) NOT NULL,
    state VARCHAR(32) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_login_log_created_at (created_at),
    INDEX idx_login_log_user (user_id),
    INDEX idx_login_log_state (state),
    CONSTRAINT fk_login_log_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

ALTER TABLE user ADD last_seen_at DATETIME DEFAULT NULL;
