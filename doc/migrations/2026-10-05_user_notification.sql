-- Notifications souhaitees par chaque joueur, valables pour tous ses
-- appareils : gestion des parties et actualites. Activees par defaut ici,
-- puis coupees par defaut par 2026-10-05_user_notification_off.sql.
-- Colonnes lues et ecrites en SQL direct (PushService), non mappees sur
-- l'entite User : le site fonctionne meme si ce script n'est pas encore joue.

ALTER TABLE user
    ADD notification_partie ENUM('true', 'false') NOT NULL DEFAULT 'true',
    ADD notification_actu ENUM('true', 'false') NOT NULL DEFAULT 'true';
