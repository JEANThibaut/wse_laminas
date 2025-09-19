-- --------------------------------------------------------
-- Hôte:                         localhost
-- Version du serveur:           9.1.0 - MySQL Community Server - GPL
-- SE du serveur:                Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Listage de la structure de table wse. annonce
CREATE TABLE IF NOT EXISTS `annonce` (
  `idannonce` int NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `prix` int DEFAULT NULL,
  `validate` int DEFAULT NULL,
  `photo_1` varchar(50) DEFAULT NULL,
  `photo_2` varchar(50) DEFAULT NULL,
  `photo_3` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idannonce`),
  KEY `iduser` (`user_id`) USING BTREE,
  CONSTRAINT `FK_annonce_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`iduser`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

-- Listage des données de la table wse.annonce : ~2 rows (environ)
INSERT INTO `annonce` (`idannonce`, `date`, `user_id`, `title`, `description`, `prix`, `validate`, `photo_1`, `photo_2`, `photo_3`) VALUES
	(1, '2025-07-08 00:19:00', 13, 'Ma replique', 'Un replique qui fait panpan', 100, 1, NULL, NULL, NULL),
	(2, '2025-07-08 00:19:00', 13, 'Ma replique 2', 'Une autre replique qui fait panpan', 120, 0, NULL, NULL, NULL);

-- Listage de la structure de table wse. comment
CREATE TABLE IF NOT EXISTS `comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `game_id` int DEFAULT NULL,
  `date` datetime NOT NULL,
  `comment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9474526CA76ED395` (`user_id`),
  KEY `IDX_9474526CE48FD905` (`game_id`),
  CONSTRAINT `FK_9474526CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`iduser`),
  CONSTRAINT `FK_9474526CE48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`idgame`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.comment : ~0 rows (environ)

-- Listage de la structure de table wse. doctrine_migration_versions
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Listage des données de la table wse.doctrine_migration_versions : ~8 rows (environ)
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
	('DoctrineMigrations\\Version20211001082212', '2021-10-01 10:22:22', 239),
	('DoctrineMigrations\\Version20211008110319', '2021-10-08 13:03:29', 246),
	('DoctrineMigrations\\Version20220120115513', '2022-01-20 12:55:30', 250),
	('DoctrineMigrations\\Version20220120222017', '2022-01-20 23:20:22', 661),
	('DoctrineMigrations\\Version20220120225950', '2022-01-21 00:00:00', 31),
	('DoctrineMigrations\\Version20220126003030', '2022-01-26 01:30:52', 162),
	('DoctrineMigrations\\Version20220203224611', '2022-02-03 23:47:41', 44),
	('DoctrineMigrations\\Version20220203225545', '2022-02-03 23:55:50', 37);

-- Listage de la structure de table wse. forum_answer
CREATE TABLE IF NOT EXISTS `forum_answer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C27279F423EDC87` (`subject_id`),
  KEY `IDX_C27279F4A76ED395` (`user_id`),
  CONSTRAINT `FK_C27279F423EDC87` FOREIGN KEY (`subject_id`) REFERENCES `forum_subject` (`id`),
  CONSTRAINT `FK_C27279F4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`iduser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.forum_answer : ~0 rows (environ)

-- Listage de la structure de table wse. forum_category
CREATE TABLE IF NOT EXISTS `forum_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.forum_category : ~0 rows (environ)

-- Listage de la structure de table wse. forum_subject
CREATE TABLE IF NOT EXISTS `forum_subject` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A02730B12469DE2` (`category_id`),
  KEY `IDX_A02730BA76ED395` (`user_id`),
  CONSTRAINT `FK_A02730B12469DE2` FOREIGN KEY (`category_id`) REFERENCES `forum_category` (`id`),
  CONSTRAINT `FK_A02730BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`iduser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.forum_subject : ~0 rows (environ)

-- Listage de la structure de table wse. game
CREATE TABLE IF NOT EXISTS `game` (
  `idgame` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `player_max` int NOT NULL,
  `status` int NOT NULL DEFAULT (0),
  PRIMARY KEY (`idgame`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.game : ~4 rows (environ)
INSERT INTO `game` (`idgame`, `date`, `player_max`, `status`) VALUES
	(23, '2025-05-31 10:14:11', 40, 1),
	(24, '2025-06-25 10:27:21', 40, 0),
	(25, '2025-08-24 10:27:21', 40, 1),
	(26, '2025-05-18 10:27:21', 40, 1);

-- Listage de la structure de table wse. register
CREATE TABLE IF NOT EXISTS `register` (
  `idregister` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `game_id` int DEFAULT NULL,
  `paid` tinyint(1) NOT NULL,
  `member` tinyint(1) NOT NULL,
  `arrived_number` int DEFAULT NULL,
  PRIMARY KEY (`idregister`) USING BTREE,
  KEY `IDX_519D10E2A76ED395` (`user_id`),
  KEY `IDX_519D10E2E48FD905` (`game_id`),
  CONSTRAINT `FK_519D10E2A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`iduser`) ON DELETE SET NULL,
  CONSTRAINT `FK_519D10E2E48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`idgame`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.register : ~8 rows (environ)
INSERT INTO `register` (`idregister`, `user_id`, `game_id`, `paid`, `member`, `arrived_number`) VALUES
	(26, 5, 24, 0, 0, 0),
	(27, 1, 24, 1, 0, 1),
	(28, 2, 24, 0, 0, 0),
	(29, 3, 24, 1, 0, 3),
	(30, 13, 24, 1, 0, 2),
	(31, 13, 25, 1, 0, 2),
	(32, 13, 26, 1, 0, 2),
	(33, 13, 23, 1, 0, 2);

-- Listage de la structure de table wse. replique
CREATE TABLE IF NOT EXISTS `replique` (
  `idreplique` int NOT NULL AUTO_INCREMENT,
  `iduser` int DEFAULT NULL,
  `nom_replique` varchar(50) DEFAULT NULL,
  `type_replique` varchar(50) DEFAULT NULL,
  `puissance` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idreplique`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb3;

-- Listage des données de la table wse.replique : 1 rows
/*!40000 ALTER TABLE `replique` DISABLE KEYS */;
INSERT INTO `replique` (`idreplique`, `iduser`, `nom_replique`, `type_replique`, `puissance`) VALUES
	(29, 13, 'Pistolet', 'Fusil d\'assault', '0.8');
/*!40000 ALTER TABLE `replique` ENABLE KEYS */;

-- Listage de la structure de table wse. user
CREATE TABLE IF NOT EXISTS `user` (
  `iduser` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:json)',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `member` tinyint(1) NOT NULL,
  `blacklist` tinyint(1) NOT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthdate` datetime NOT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isActive` tinyint DEFAULT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`iduser`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.user : ~11 rows (environ)
INSERT INTO `user` (`iduser`, `email`, `roles`, `password`, `firstname`, `lastname`, `member`, `blacklist`, `city`, `birthdate`, `nickname`, `isActive`, `admin`) VALUES
	(1, 'user1@mail.fr', '[]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'user 1', 'last 1', 0, 0, 'city 1', '2021-10-01 10:24:12', NULL, NULL, 0),
	(2, 'user2@mail.fr', '[]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'user 2', 'last 2', 0, 0, 'city 2', '2021-10-01 10:24:12', NULL, NULL, 0),
	(3, 'user3@mail.fr', '[]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'user 3', 'last 3', 0, 0, 'city 3', '2021-10-01 10:24:13', NULL, NULL, 0),
	(4, 'user4@mail.fr', '[]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'user 4', 'last 4', 0, 0, 'city 4', '2021-10-01 10:24:13', NULL, NULL, 0),
	(5, 'user5@mail.fr', '[]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'user 5', 'last 5', 0, 0, 'city 5', '2021-10-01 10:24:13', NULL, NULL, 0),
	(6, 'user6@mail.fr', '["ROLE_USER"]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'user 6', 'last 6', 1, 0, 'city 1', '2021-10-01 10:24:00', NULL, NULL, 0),
	(7, 'user7@mail.fr', '["ROLE_USER"]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'user 7', 'last 7', 1, 0, 'city 2', '2021-10-01 10:24:00', NULL, NULL, 0),
	(8, 'user8@mail.fr', '[]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'user 8', 'last 8', 1, 0, 'city 3', '2021-10-01 10:24:14', NULL, NULL, 0),
	(10, 'superAdmin@mail.fr', '["ROLE_SUPER_ADMIN"]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'Tibo', 'Admin', 1, 0, 'city Admin', '2021-11-22 19:18:49', NULL, NULL, 0),
	(11, 'user6@mail.fr', '[]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'last6', 'user6', 1, 0, 'kjhfds', '2011-06-23 00:00:00', NULL, NULL, 0),
	(13, 'thibaut-jean@live.fr', '["USER","GOD"]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'Thibaut', 'JEAN', 1, 0, '', '1988-08-10 00:00:00', 'SIPH', 1, 1);

-- Listage de la structure de table wse. waiting_list
CREATE TABLE IF NOT EXISTS `waiting_list` (
  `idwaiting` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `user_id` int NOT NULL,
  `is_validate` int NOT NULL,
  `email_send` int NOT NULL,
  `order_list` int NOT NULL,
  PRIMARY KEY (`idwaiting`),
  KEY `game_id` (`game_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `FK_waiting_list_game` FOREIGN KEY (`game_id`) REFERENCES `game` (`idgame`),
  CONSTRAINT `FK_waiting_list_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`iduser`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

-- Listage des données de la table wse.waiting_list : ~0 rows (environ)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
