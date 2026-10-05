-- Les notifications sont coupees par defaut : chaque joueur active lui-meme,
-- dans son profil, celles qu'il veut recevoir. Remet aussi tous les comptes
-- existants a 'false'. A jouer apres 2026-10-05_user_notification.sql.

ALTER TABLE user
    MODIFY notification_partie ENUM('true', 'false') NOT NULL DEFAULT 'false',
    MODIFY notification_actu ENUM('true', 'false') NOT NULL DEFAULT 'false';

UPDATE user SET notification_partie = 'false', notification_actu = 'false';
