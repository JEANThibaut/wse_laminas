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


-- Listage de la structure de la base pour wse
CREATE DATABASE IF NOT EXISTS `wse` /*!40100 DEFAULT CHARACTER SET utf8mb3 */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `wse`;

-- Listage de la structure de table wse. booking
CREATE TABLE IF NOT EXISTS `booking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_in_game_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E00CEDDE85B3C07` (`user_in_game_id`),
  KEY `IDX_E00CEDDE4584665A` (`product_id`),
  CONSTRAINT `FK_E00CEDDE4584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  CONSTRAINT `FK_E00CEDDE85B3C07` FOREIGN KEY (`user_in_game_id`) REFERENCES `user_in_game` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.booking : ~35 rows (environ)
INSERT INTO `booking` (`id`, `user_in_game_id`, `product_id`, `quantity`) VALUES
	(1, 1, 35, 1),
	(2, 1, 22, 1),
	(3, 2, 35, 1),
	(4, 3, 35, 1),
	(5, 3, 22, 1),
	(6, 3, 28, 1),
	(7, 3, 29, 1),
	(8, 4, 35, 1),
	(9, 5, 35, 1),
	(10, 5, 29, 2),
	(11, 6, 35, 1),
	(12, 7, 35, 1),
	(13, 7, 22, 1),
	(14, 7, 25, 1),
	(15, 8, 35, 1),
	(16, 8, 22, 1),
	(17, 8, 28, 1),
	(18, 9, 35, 1),
	(19, 10, 35, 1),
	(20, 11, 35, 1),
	(21, 11, 22, 1),
	(22, 12, 35, 1),
	(23, 13, 35, 1),
	(24, 14, 35, 1),
	(25, 14, 22, 1),
	(26, 14, 27, 1),
	(27, 15, 35, 1),
	(28, 16, 35, 1),
	(29, 17, 35, 1),
	(30, 18, 35, 1),
	(31, 18, 27, 1),
	(32, 19, 35, 1),
	(33, 19, 22, 1),
	(34, 20, 35, 1),
	(35, 21, 35, 1);

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
  CONSTRAINT `FK_9474526CE48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`)
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
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `player_max` int NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.game : ~12 rows (environ)
INSERT INTO `game` (`id`, `date`, `player_max`, `status`) VALUES
	(1, '2021-10-01 10:24:11', 29, 1),
	(2, '2021-10-01 10:24:11', 13, 0),
	(3, '2021-10-01 10:24:11', 40, 0),
	(4, '2021-10-01 10:24:11', 12, 0),
	(6, '2021-10-10 00:00:00', 40, 0),
	(7, '2021-10-24 00:00:00', 40, 0),
	(8, '2021-11-21 00:00:00', 40, 0),
	(9, '2021-11-27 00:00:00', 40, 0),
	(10, '2021-11-28 00:00:00', 40, 0),
	(11, '2021-12-10 00:00:00', 40, 0),
	(12, '2021-12-16 00:00:00', 35, 0),
	(13, '2023-03-10 00:00:00', 50, 0);

-- Listage de la structure de table wse. operation
CREATE TABLE IF NOT EXISTS `operation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `user_in_game_id` int DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `type` tinyint(1) NOT NULL,
  `amount` decimal(6,2) NOT NULL,
  `quantity` decimal(6,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1981A66D4584665A` (`product_id`),
  KEY `IDX_1981A66D85B3C07` (`user_in_game_id`),
  CONSTRAINT `FK_1981A66D4584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_1981A66D85B3C07` FOREIGN KEY (`user_in_game_id`) REFERENCES `user_in_game` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.operation : ~51 rows (environ)
INSERT INTO `operation` (`id`, `product_id`, `user_in_game_id`, `label`, `date`, `type`, `amount`, `quantity`) VALUES
	(20, 35, 3, 'Réservation Partie', '2021-10-11 14:24:12', 0, 10.00, 1.00),
	(21, 22, 3, 'Fumigène', '2021-10-11 14:24:12', 0, 8.00, 1.00),
	(22, 28, 3, 'Traceur', '2021-10-11 14:24:12', 0, 40.00, 1.00),
	(23, 29, 3, 'Billes 0.20g -1kg', '2021-10-11 14:24:12', 0, 10.00, 1.00),
	(24, 35, 4, 'Réservation Partie', '2021-10-11 14:25:32', 0, 10.00, 1.00),
	(25, 35, 5, 'Réservation Partie', '2021-10-11 14:29:50', 0, 0.00, 1.00),
	(26, 29, 5, 'Billes 0.20g -1kg', '2021-10-11 14:29:50', 0, 14.00, 2.00),
	(27, 37, NULL, 'Coca-cola', '2021-10-17 19:04:53', 0, 2.00, 2.00),
	(28, 36, NULL, 'Café', '2021-10-21 16:13:21', 0, 1.00, 2.00),
	(29, 36, NULL, 'Café', '2021-10-21 16:14:13', 0, 1.00, 2.00),
	(30, 38, NULL, 'Ice-Tea', '2021-10-21 16:14:13', 0, 1.00, 1.00),
	(31, 37, NULL, 'Coca-cola', '2021-10-21 16:14:59', 0, 2.00, 2.00),
	(32, 36, NULL, 'Café', '2021-10-21 16:16:05', 0, 1.00, 2.00),
	(33, 42, NULL, 'Grande Bouteille d\'eau', '2021-10-21 16:16:05', 0, 2.00, 2.00),
	(34, 36, NULL, 'Café', '2021-10-21 18:40:37', 0, 2.00, 4.00),
	(35, 38, NULL, 'Ice-Tea', '2021-10-21 18:40:37', 0, 2.00, 2.00),
	(36, 36, NULL, 'Café', '2021-10-25 14:19:55', 0, 2.00, 4.00),
	(37, 36, NULL, 'Café', '2021-11-19 13:26:51', 0, 1.00, 2.00),
	(38, 35, 8, 'Réservation Partie', '2021-11-25 15:56:28', 0, 10.00, 1.00),
	(39, 22, 8, 'Fumigène', '2021-11-25 15:56:28', 0, 8.00, 1.00),
	(40, 28, 8, 'Traceur', '2021-11-25 15:56:28', 0, 40.00, 1.00),
	(41, 35, 12, 'Réservation Partie', '2021-11-27 10:11:05', 0, 10.00, 1.00),
	(42, 35, 15, 'Réservation Partie', '2021-11-29 15:51:29', 0, 0.00, 1.00),
	(43, 35, 18, 'Réservation Partie', '2021-12-21 15:43:01', 0, 10.00, 1.00),
	(44, 27, 18, 'Lunettes', '2021-12-21 15:43:01', 0, 30.00, 1.00),
	(53, NULL, NULL, 'Test de facturation', '2016-01-01 00:00:00', 1, 120.00, 1.00),
	(55, NULL, NULL, 'Remboursement', '2021-12-27 00:00:00', 1, 120.00, 1.00),
	(56, NULL, NULL, 'Remboursement', '2016-01-01 00:00:00', 1, 150.00, 1.00),
	(57, 36, NULL, 'Café', '2022-01-04 13:15:12', 0, 1.00, 2.00),
	(58, 36, NULL, 'Café', '2022-01-04 13:33:41', 0, 1.00, 2.00),
	(59, 37, NULL, 'Coca-cola', '2022-01-04 13:36:30', 0, 2.00, 2.00),
	(60, 38, NULL, 'Ice-Tea', '2022-01-04 13:36:30', 0, 1.00, 1.00),
	(61, 23, NULL, 'Sparclette CO2', '2022-01-04 13:36:55', 0, 1.20, 2.00),
	(62, 26, NULL, 'Anti-buée', '2022-01-04 13:36:55', 0, 32.00, 2.00),
	(63, 27, NULL, 'Lunettes', '2022-01-04 13:36:55', 0, 30.00, 1.00),
	(64, 22, NULL, 'Fumigène', '2022-01-04 14:21:58', 0, 16.00, 2.00),
	(65, 26, NULL, 'Anti-buée', '2022-01-04 16:47:07', 0, 16.00, 1.00),
	(66, 28, NULL, 'Traceur', '2022-01-04 16:47:07', 0, 40.00, 1.00),
	(67, 29, NULL, 'Billes 0.20g -1kg', '2022-01-04 16:47:07', 0, 10.00, 1.00),
	(68, 32, NULL, 'Billes 0.28g -1Kg', '2022-01-04 16:47:07', 0, 15.00, 1.00),
	(69, 26, NULL, 'Anti-buée', '2022-01-05 01:33:58', 0, 16.00, 1.00),
	(70, 26, NULL, 'Anti-buée', '2022-01-05 01:39:37', 0, 16.00, 1.00),
	(71, 26, NULL, 'Anti-buée', '2022-01-05 01:44:51', 0, 12.00, 1.00),
	(72, 36, NULL, 'Café', '2022-01-15 15:08:39', 0, 0.50, 1.00),
	(73, 38, NULL, 'Ice-Tea', '2022-01-15 15:08:39', 0, 2.00, 2.00),
	(74, 22, NULL, 'Fumigène', '2022-01-28 00:38:58', 0, 16.00, 2.00),
	(75, 36, NULL, 'Café', '2022-01-28 11:26:02', 0, 1.00, 2.00),
	(76, 37, NULL, 'Coca-cola', '2022-01-28 11:26:02', 0, 3.00, 3.00),
	(77, 38, NULL, 'Ice-Tea', '2022-01-28 11:26:02', 0, 1.00, 1.00),
	(78, 36, NULL, 'Café', '2022-01-28 11:27:11', 0, 1.50, 3.00),
	(79, 37, NULL, 'Coca-cola', '2022-01-28 11:27:11', 0, 3.00, 3.00);

-- Listage de la structure de table wse. product
CREATE TABLE IF NOT EXISTS `product` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` int DEFAULT NULL,
  `sell_price` decimal(6,2) DEFAULT NULL,
  `sell_member_price` decimal(6,2) DEFAULT NULL,
  `buy_price` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.product : ~21 rows (environ)
INSERT INTO `product` (`id`, `label`, `category`, `stock`, `sell_price`, `sell_member_price`, `buy_price`) VALUES
	(22, 'Fumigène', 'consommable', NULL, 8.00, 8.00, NULL),
	(23, 'Sparclette CO2', 'consommable', NULL, 0.60, 0.50, NULL),
	(24, 'Sparclette CO2(X5)', 'consommable', NULL, 3.00, 2.00, NULL),
	(25, 'Bouteille de gaz', 'consommable', NULL, 7.00, 5.00, NULL),
	(26, 'Anti-buée', 'Equipement', NULL, 16.00, 12.00, NULL),
	(27, 'Lunettes', 'Equipement', NULL, 30.00, 26.00, NULL),
	(28, 'Traceur', 'Equipement', NULL, 40.00, 36.00, NULL),
	(29, 'Billes 0.20g -1kg', 'consommable', NULL, 10.00, 7.00, NULL),
	(30, 'Billes 0.23g -1Kg', 'consommable', NULL, 10.00, 8.00, NULL),
	(31, 'Billes 0.25g -1Kg', 'consommable', NULL, 11.00, 9.00, NULL),
	(32, 'Billes 0.28g -1Kg', 'consommable', NULL, 15.00, 11.00, NULL),
	(33, 'Billes traçantes 0.25g -1kg Rouge', 'consommable', NULL, 36.00, 34.00, NULL),
	(34, 'Billes traçantes 0.25g -1kg Verte', 'consommable', NULL, 25.00, 23.00, NULL),
	(35, 'Réservation Partie', 'paf', NULL, 10.00, 0.00, NULL),
	(36, 'Café', 'Boisson', NULL, 0.50, 0.50, NULL),
	(37, 'Coca-cola', 'Boisson', NULL, 1.00, 1.00, NULL),
	(38, 'Ice-Tea', 'Boisson', NULL, 1.00, 1.00, NULL),
	(39, 'Orangina', 'Boisson', NULL, 1.00, 1.00, NULL),
	(40, 'Shweppes agrume', 'Boisson', NULL, 1.00, 1.00, NULL),
	(41, 'Petite Bouteille d\'eau', 'Boisson', NULL, 0.50, 0.50, NULL),
	(42, 'Grande Bouteille d\'eau', 'Boisson', NULL, 1.00, 1.00, NULL);

-- Listage de la structure de table wse. replique
CREATE TABLE IF NOT EXISTS `replique` (
  `idreplique` int NOT NULL AUTO_INCREMENT,
  `iduser` int DEFAULT NULL,
  `nom_replique` varchar(50) DEFAULT NULL,
  `type_replique` varchar(50) DEFAULT NULL,
  `puissance` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idreplique`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3;

-- Listage des données de la table wse.replique : 2 rows
/*!40000 ALTER TABLE `replique` DISABLE KEYS */;
INSERT INTO `replique` (`idreplique`, `iduser`, `nom_replique`, `type_replique`, `puissance`) VALUES
	(1, 13, 'MA REPLIQUE', 'AEG', '1.2');
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
  PRIMARY KEY (`iduser`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.user : ~11 rows (environ)
INSERT INTO `user` (`iduser`, `email`, `roles`, `password`, `firstname`, `lastname`, `member`, `blacklist`, `city`, `birthdate`, `nickname`, `isActive`) VALUES
	(1, 'admin@mail.fr', '["ROLE_ADMIN"]', '$2y$13$MfSExL42v0tBhRexHAmG4uQulFYbG840lDqPilO1S9xmhDc0wartW', 'adminFirstname', 'adminLastname', 1, 0, 'AdminCity', '2021-10-01 10:24:11', NULL, NULL),
	(2, 'user1@mail.fr', '[]', '$2y$13$OiRwxHFCWzR7dmUyTnkBkOXq559bOPRGRfiUakLCH/TTs8aUlrlvy', 'user 1', 'last 1', 0, 0, 'city 1', '2021-10-01 10:24:12', NULL, NULL),
	(3, 'user2@mail.fr', '[]', '$2y$13$8mTlb.kPJLvTMV1vRQVWpOmaymaKjw/Fdx5xbL9DTwz.NMYICZKlW', 'user 2', 'last 2', 0, 0, 'city 2', '2021-10-01 10:24:12', NULL, NULL),
	(4, 'user3@mail.fr', '[]', '$2y$13$AHACgpMKQTYH0cUUNgnnO.5lTgWOuFBIEeATE.1Io/9sCg6ZRUMuq', 'user 3', 'last 3', 0, 0, 'city 3', '2021-10-01 10:24:13', NULL, NULL),
	(5, 'user4@mail.fr', '[]', '$2y$13$755gtrzQ2A4B/xX0i2HAUOux4HaMAxrvn9LVPJXpgEIyFrpFocm8K', 'user 4', 'last 4', 0, 0, 'city 4', '2021-10-01 10:24:13', NULL, NULL),
	(6, 'member1@mail.fr', '["ROLE_USER"]', '$2y$13$6zf/RV5w8miu1mrtbB107e70EVPEMqWZu7j71fXsR6KgCk6E7w2pe', 'member 1', 'last 1', 1, 0, 'city 1', '2021-10-01 10:24:00', NULL, NULL),
	(7, 'member2@mail.fr', '["ROLE_USER"]', '$2y$13$hmGJFE49WTs4i4pSAlIMiuL/xJdBRwnzycVFthEeZzv55C6XeV4bO', 'member 2', 'last 2', 1, 0, 'city 2', '2021-10-01 10:24:00', NULL, NULL),
	(8, 'member3@mail.fr', '[]', '$2y$13$3ETEDNzwORwJZmaMJzauRuRuHpIUNRflS/1iqdKvxFrfV1ShxbIWS', 'member 3', 'last 3', 1, 0, 'city 3', '2021-10-01 10:24:14', NULL, NULL),
	(10, 'superAdmin@mail.fr', '["ROLE_SUPER_ADMIN"]', '$2y$13$7IhCyvYR8drLMywfhZLCouHIGtA2fOuZwvEThu4Ga4GuCLvEFNJ7G', 'Tibo', 'Admin', 1, 0, 'city Admin', '2021-11-22 19:18:49', NULL, NULL),
	(11, 'user6@mail.fr', '[]', '$2y$13$Smfr7DAkv7soljhBJq4soe4osYTCgwxGBYJ4zV/xJp3yBuASTHJ9O', 'last6', 'user6', 1, 0, 'kjhfds', '2011-06-23 00:00:00', NULL, NULL),
	(13, 'thibaut-jean@live.fr', '["user"]', '$2y$10$0RY.1FHPLOpVuzdILa.rV.ptVdhxsuD1r6BraygcKQwK/WrGGpqhS', 'Thibaut', 'JEAN', 1, 0, '', '1988-08-10 00:00:00', 'SIPH', 1);

-- Listage de la structure de table wse. user_in_game
CREATE TABLE IF NOT EXISTS `user_in_game` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `game_id` int DEFAULT NULL,
  `presence` tinyint(1) NOT NULL,
  `paid` tinyint(1) NOT NULL,
  `member` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_519D10E2A76ED395` (`user_id`),
  KEY `IDX_519D10E2E48FD905` (`game_id`),
  CONSTRAINT `FK_519D10E2A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`iduser`) ON DELETE SET NULL,
  CONSTRAINT `FK_519D10E2E48FD905` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table wse.user_in_game : ~21 rows (environ)
INSERT INTO `user_in_game` (`id`, `user_id`, `game_id`, `presence`, `paid`, `member`) VALUES
	(1, 2, 1, 0, 0, 0),
	(2, 2, 6, 1, 0, 0),
	(3, 2, 7, 1, 1, 0),
	(4, 3, 7, 1, 1, 0),
	(5, 6, 7, 0, 1, 1),
	(6, 5, 7, 0, 0, 0),
	(7, 1, 7, 0, 0, 1),
	(8, 2, 9, 1, 1, 0),
	(9, 5, 9, 0, 0, 0),
	(10, 3, 9, 0, 0, 0),
	(11, 10, 2, 0, 0, 1),
	(12, 2, 10, 1, 1, 0),
	(13, 2, 11, 1, 0, 0),
	(14, 3, 11, 0, 0, 0),
	(15, 6, 11, 1, 1, 1),
	(16, 2, 2, 0, 0, 0),
	(17, 10, 12, 0, 0, 1),
	(18, 2, 13, 1, 1, 0),
	(19, 10, 13, 0, 0, 1),
	(20, 3, 13, 1, 0, 0),
	(21, 5, 13, 1, 0, 0);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
