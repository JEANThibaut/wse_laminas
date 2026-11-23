-- Petites annonces : trace de la derniere decision de moderation
-- (validation, refus, retrait ou classement d'un signalement) : qui, quand.
-- Le statut de l'annonce dit de quelle decision il s'agit.
-- A jouer une fois, apres 2026-11-23_mise-en-prod-espace-membres.sql, avant le deploiement.

ALTER TABLE annonce
    ADD moderated_by INT DEFAULT NULL,
    ADD moderated_at DATETIME DEFAULT NULL,
    ADD INDEX idx_annonce_moderated_by (moderated_by),
    ADD CONSTRAINT fk_annonce_moderated_by FOREIGN KEY (moderated_by) REFERENCES user (iduser) ON DELETE SET NULL;
