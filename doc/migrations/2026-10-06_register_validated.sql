-- Le pointage (bouton "Valider" de la page Prochaine partie) passe par le
-- statut : une inscription pointee est 'validated' (arrivee et payee), une
-- inscription non pointee reste 'active'. paid ne sert plus au pointage.
--
-- Historique : toute inscription avec paid = 1 passe en 'validated', y compris
-- les anciennes lignes sans statut (parties 3, 4, 5). Les desinscriptions
-- ('cancelled') ne sont pas touchees.

-- Apercu avant execution : nombre de lignes concernees par partie
SELECT r.game_id, DATE(g.date) AS date_partie, IFNULL(r.status, 'NULL') AS statut_actuel, COUNT(*) AS nb
FROM game_register r
JOIN game g ON g.idgame = r.game_id
WHERE r.paid = 1 AND (r.status IS NULL OR r.status = 'active')
GROUP BY r.game_id, DATE(g.date), r.status
ORDER BY r.game_id;

UPDATE game_register
SET status = 'validated'
WHERE paid = 1 AND (status IS NULL OR status = 'active');
