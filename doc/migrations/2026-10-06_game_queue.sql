-- Nouvelle file d'attente (Game\Service\QueueManager) : une table dediee,
-- en premier arrive, premier servi. Remplace l'ancien statut 'pending' des
-- inscriptions. A jouer AVANT le deploiement du code.

CREATE TABLE game_queue (
    id INT AUTO_INCREMENT NOT NULL,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(16) NOT NULL,
    created_at DATETIME NOT NULL,
    offered_at DATETIME DEFAULT NULL,
    offer_expires_at DATETIME DEFAULT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_game_queue_game_status (game_id, status),
    INDEX idx_game_queue_user (user_id),
    INDEX idx_game_queue_expires (status, offer_expires_at),
    CONSTRAINT fk_game_queue_game FOREIGN KEY (game_id) REFERENCES game (idgame) ON DELETE CASCADE,
    CONSTRAINT fk_game_queue_user FOREIGN KEY (user_id) REFERENCES user (iduser) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Ancienne file (inscriptions 'pending') reprise dans la nouvelle, pour les
-- parties a venir, dans l'ordre d'inscription d'origine (idregister) : les
-- lignes sont inserees dans cet ordre, et a date d'arrivee egale c'est l'id
-- qui departage. Un joueur deja inscrit (active) a la meme partie n'est pas repris.
INSERT INTO game_queue (game_id, user_id, status, created_at, updated_at)
SELECT r.game_id, r.user_id, 'waiting', NOW(), NOW()
FROM game_register r
JOIN game g ON g.idgame = r.game_id
WHERE r.status = 'pending'
  AND g.date >= CURDATE()
  AND NOT EXISTS (
      SELECT 1 FROM game_register a
      WHERE a.game_id = r.game_id AND a.user_id = r.user_id AND a.status = 'active'
  )
GROUP BY r.game_id, r.user_id
ORDER BY MIN(r.idregister);

-- Les lignes 'pending' des parties a venir sont DEPLACEES (pas annulees) :
-- game_queue devient la seule source de la file. Un 'pending' d'un joueur
-- deja inscrit a la meme partie, non repris ci-dessus, est un doublon sans objet.
DELETE r FROM game_register r
JOIN game g ON g.idgame = r.game_id
WHERE r.status = 'pending' AND g.date >= CURDATE();

-- Les 'pending' des parties passees restent tels quels, comme historique :
-- le code ne compte que les inscriptions 'active', ils ne genent rien.

-- L'ancienne table waiting_list n'est plus utilisee par le code. Verifier
-- qu'elle est vide (SELECT COUNT(*) FROM waiting_list;) avant de la supprimer :
-- DROP TABLE waiting_list;
