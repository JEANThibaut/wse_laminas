-- Abonnements des appareils aux notifications (un par navigateur ou
-- application installee). A jouer une fois, avant le deploiement du code.

CREATE TABLE push_subscription (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    endpoint LONGTEXT NOT NULL,
    endpoint_hash CHAR(64) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX uniq_push_subscription_endpoint (endpoint_hash),
    INDEX idx_push_subscription_user (user_id),
    CONSTRAINT fk_push_subscription_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
