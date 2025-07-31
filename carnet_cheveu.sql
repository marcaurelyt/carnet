-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 31 juil. 2025 à 15:11
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `carnet_cheveu`
--

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenoms` varchar(100) NOT NULL,
  `genre` enum('masculin','feminin') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `pin` varchar(4) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `photo_profil` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_pin` (`pin`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `prenoms`, `genre`, `email`, `telephone`, `pin`, `ville`, `photo_profil`, `date_creation`, `date_modification`) VALUES
(1, 'ATTOUNGBRE', 'JOSEPH', 'masculin', 'attjosephmarcaurel@gmail.com', '0151614672', '1111', 'ABIDJAN', 'Design sans titre.jpg', '2025-07-16 22:52:46', '2025-07-31 12:25:06'),
(8, 'KODJO', 'STEPHANE', 'masculin', 'attjosephmarcaurel+1@gmail.com', '0759093974', '1111', 'ABIDJAN', 'Design sans titre (2).png', '2025-07-31 13:08:45', '2025-07-31 13:08:45');

-- --------------------------------------------------------

--
-- Structure de la table `conseils_pratiques`
--

DROP TABLE IF EXISTS `conseils_pratiques`;
CREATE TABLE IF NOT EXISTS `conseils_pratiques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `conseils_pratiques`
--

INSERT INTO `conseils_pratiques` (`id`, `titre`, `description`, `date_creation`) VALUES
(1, 'Faire trois défrisages par an', 'Il est conseillé de ne pas dépasser trois défrisages par an pour éviter d\'abîmer les cheveux.', '2025-07-16 22:50:12'),
(2, 'Ne porter pas la perruque à répétition', 'Porter une perruque de manière continue peut causer des dommages aux cheveux naturels.', '2025-07-16 22:50:12'),
(3, 'Avoir une alimentation saine', 'Une alimentation équilibrée est essentielle pour la santé des cheveux.', '2025-07-16 22:50:12'),
(4, 'Éviter le séchage trop chaud à répétition', 'Le séchage à haute température peut endommager les cheveux.', '2025-07-16 22:50:12'),
(5, 'Éviter les tresses trop serrées', 'Les tresses trop serrées peuvent causer des tensions et endommager les racines des cheveux.', '2025-07-16 22:50:12'),
(6, 'Éviter les tresses après un défrisage', 'Il est préférable d\'éviter les coiffures qui tirent sur les cheveux juste après un défrisage.', '2025-07-16 22:50:12');

-- --------------------------------------------------------

--
-- Structure de la table `controles`
--

DROP TABLE IF EXISTS `controles`;
CREATE TABLE IF NOT EXISTS `controles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `date_controle` date NOT NULL,
  `reparation_rapide_fibre` tinyint(1) DEFAULT '0',
  `reparation_lente_fibre` tinyint(1) DEFAULT '0',
  `repousse_cheveux` tinyint(1) DEFAULT '0',
  `densite_cheveux` tinyint(1) DEFAULT '0',
  `elasticite_cheveux` tinyint(1) DEFAULT '0',
  `force_cheveux` tinyint(1) DEFAULT '0',
  `executant` varchar(100) DEFAULT NULL,
  `observations` text,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nom_fichier` varchar(255) DEFAULT NULL,
  `nom_fichier1` varchar(255) DEFAULT NULL,
  `nom_fichier2` varchar(255) DEFAULT NULL,
  `nom_fichier3` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `controles`
--

INSERT INTO `controles` (`id`, `client_id`, `date_controle`, `reparation_rapide_fibre`, `reparation_lente_fibre`, `repousse_cheveux`, `densite_cheveux`, `elasticite_cheveux`, `force_cheveux`, `executant`, `observations`, `date_creation`, `nom_fichier`, `nom_fichier1`, `nom_fichier2`, `nom_fichier3`) VALUES
(6, 8, '2025-07-25', 1, 1, 1, 1, 0, 0, 'HAIR COACH', 'aaaaa', '2025-07-31 13:12:36', NULL, '688b6bc4f1dbd_WhatsApp Image 2025-06-07 à 16.58.28_b6d91d47.jpg', '688b6bc4f24ea_WhatsApp Image 2025-06-07 à 16.58.28_b6d91d47.jpg', '688b6bc4f268f_WhatsApp Image 2025-06-07 à 16.58.28_b6d91d47.jpg'),
(5, 1, '2025-08-01', 1, 0, 0, 1, 1, 1, 'HAIR COACH', 'aaaaaa', '2025-07-31 12:39:07', NULL, '688b63eb1a9a1_WhatsApp Image 2025-07-13 à 19.36.28_2089f5db.jpg', '688b63eb1b15e_WhatsApp Image 2025-07-13 à 19.36.28_2089f5db.jpg', '688b63eb1b8ea_WhatsApp Image 2025-07-13 à 19.36.28_2089f5db.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `diagnostics`
--

DROP TABLE IF EXISTS `diagnostics`;
CREATE TABLE IF NOT EXISTS `diagnostics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `date_diagnostic` date NOT NULL,
  `cheveux_abimes` tinyint(1) DEFAULT '0',
  `cheveux_faibles` tinyint(1) DEFAULT '0',
  `cheveux_perte_densite` tinyint(1) DEFAULT '0',
  `cheveux_trop_gras` tinyint(1) DEFAULT '0',
  `alopecie_androgenique` tinyint(1) DEFAULT '0',
  `alopecie_androgenique_niveau` int DEFAULT NULL,
  `alopecie_traction` tinyint(1) DEFAULT '0',
  `pelade` tinyint(1) DEFAULT '0',
  `psoriasis` tinyint(1) DEFAULT '0',
  `teigne` tinyint(1) DEFAULT '0',
  `texture_naturels` tinyint(1) DEFAULT '0',
  `texture_defrises` tinyint(1) DEFAULT '0',
  `texture_demeles` tinyint(1) DEFAULT '0',
  `texture_colores` tinyint(1) DEFAULT '0',
  `executant` varchar(100) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nom_fichier1` varchar(255) DEFAULT NULL,
  `nom_fichier2` varchar(255) DEFAULT NULL,
  `nom_fichier3` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `diagnostics`
--

INSERT INTO `diagnostics` (`id`, `client_id`, `date_diagnostic`, `cheveux_abimes`, `cheveux_faibles`, `cheveux_perte_densite`, `cheveux_trop_gras`, `alopecie_androgenique`, `alopecie_androgenique_niveau`, `alopecie_traction`, `pelade`, `psoriasis`, `teigne`, `texture_naturels`, `texture_defrises`, `texture_demeles`, `texture_colores`, `executant`, `date_creation`, `nom_fichier1`, `nom_fichier2`, `nom_fichier3`) VALUES
(6, 8, '2025-07-31', 1, 1, 1, 0, 0, NULL, 1, 1, 1, 1, 0, 0, 0, 0, 'HAIR COACH', '2025-07-31 13:10:53', '688b6b5dce4dd_WhatsApp Image 2025-06-07 à 16.58.29_7cb8d45d.jpg', '688b6b5dce7b2_WhatsApp Image 2025-06-07 à 16.58.30_61100b9d.jpg', '688b6b5dce96e_WhatsApp Image 2025-06-07 à 16.58.30_61100b9d.jpg'),
(5, 1, '2025-08-01', 1, 1, 1, 0, 0, NULL, 0, 1, 1, 0, 1, 0, 0, 0, '', '2025-07-31 12:54:02', '688b676a9f4d6_WhatsApp Image 2025-06-07 à 16.58.28_b6d91d47.jpg', '688b676a9f701_WhatsApp Image 2025-06-07 à 16.58.28_b6d91d47.jpg', '688b676a9f8a3_WhatsApp Image 2025-06-07 à 16.58.28_b6d91d47.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `photos`
--

DROP TABLE IF EXISTS `photos`;
CREATE TABLE IF NOT EXISTS `photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `type_photo` text,
  `date_photo` date NOT NULL,
  `nom_fichier` varchar(255) DEFAULT NULL,
  `commentaires` text,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `photos`
--

INSERT INTO `photos` (`id`, `client_id`, `type_photo`, `date_photo`, `nom_fichier`, `commentaires`, `date_creation`) VALUES
(16, 1, 'éceicbéicébcoécéc', '2025-08-01', 'Capture d’écran 2025-06-04 191444.png', 'aaaaaa', '2025-07-31 13:06:48');

-- --------------------------------------------------------

--
-- Structure de la table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `user_id` int NOT NULL,
  `subscription` text NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rdv`
--

DROP TABLE IF EXISTS `rdv`;
CREATE TABLE IF NOT EXISTS `rdv` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `date_rdv` date NOT NULL,
  `heure_rdv` time NOT NULL,
  `message_prevention` text,
  `statut` enum('planifie','confirme','annule','termine') DEFAULT 'planifie',
  `type_rdv` enum('diagnostic','seance','controle','consultation') DEFAULT 'seance',
  `executant` varchar(100) DEFAULT NULL,
  `rappel_envoye` tinyint(1) DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `date_rdv` (`date_rdv`),
  KEY `statut` (`statut`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `rdv`
--

INSERT INTO `rdv` (`id`, `client_id`, `date_rdv`, `heure_rdv`, `message_prevention`, `statut`, `type_rdv`, `executant`, `rappel_envoye`, `date_creation`, `date_modification`) VALUES
(1, 1, '2025-07-19', '10:00:00', 'Rappel : Rendez-vous pour séance de microneedle. Veuillez arriver 10 minutes avant l', 'termine', 'seance', 'Praticien', 0, '2025-07-17 10:54:10', '2025-07-20 17:39:25'),
(2, 1, '2025-07-19', '14:30:00', 'Contrôle de suivi après traitement. N', 'termine', 'controle', 'Praticien', 0, '2025-07-17 10:54:10', '2025-07-20 17:46:58'),
(3, 1, '2025-07-21', '12:03:00', 'venez sans aucun pretexte d&#039;annulation', 'termine', 'consultation', 'Praticien', 0, '2025-07-20 18:03:59', '2025-07-22 10:11:46'),
(4, 1, '2025-07-22', '10:13:00', '', 'termine', 'diagnostic', 'ssssss', 0, '2025-07-22 10:12:36', '2025-07-22 10:13:15'),
(5, 1, '2025-07-24', '08:40:00', 'xqwxqxqxqx', 'termine', 'diagnostic', 'Praticien', 0, '2025-07-23 08:37:48', '2025-07-24 14:51:16'),
(6, 1, '2025-07-24', '17:15:00', 'izecibciozebiz', 'termine', 'controle', 'ihé&amp;iàbdi&amp;àédbàb', 0, '2025-07-24 17:11:17', '2025-07-24 17:17:02'),
(7, 1, '2025-07-18', '21:04:00', 'ugoj', 'termine', 'controle', 'joseph', 0, '2025-07-30 18:04:28', '2025-07-30 18:05:04'),
(8, 1, '2025-07-31', '19:04:00', 'ju', 'confirme', 'consultation', 'joseph', 0, '2025-07-30 18:05:04', '2025-07-30 18:05:27'),
(9, 1, '2025-07-30', '19:04:00', 'ju', 'termine', 'consultation', 'joseph', 0, '2025-07-30 18:05:32', '2025-07-31 13:29:45');

-- --------------------------------------------------------

--
-- Structure de la table `seances`
--

DROP TABLE IF EXISTS `seances`;
CREATE TABLE IF NOT EXISTS `seances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `numero_seance` int NOT NULL,
  `date_seance` date NOT NULL,
  `soin` varchar(100) DEFAULT NULL,
  `microneedle` tinyint(1) DEFAULT '0',
  `steamer` tinyint(1) DEFAULT '0',
  `bain_huile` tinyint(1) DEFAULT '0',
  `bain_medical` tinyint(1) DEFAULT '0',
  `defrisage` tinyint(1) DEFAULT '0',
  `coloration` tinyint(1) DEFAULT '0',
  `gommage` tinyint(1) DEFAULT '0',
  `stimulation` tinyint(1) DEFAULT '0',
  `autres` varchar(255) DEFAULT NULL,
  `executant` varchar(100) DEFAULT NULL,
  `nom_fichier1` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nom_fichier2` varchar(255) DEFAULT NULL,
  `nom_fichier3` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `seances`
--

INSERT INTO `seances` (`id`, `client_id`, `numero_seance`, `date_seance`, `soin`, `microneedle`, `steamer`, `bain_huile`, `bain_medical`, `defrisage`, `coloration`, `gommage`, `stimulation`, `autres`, `executant`, `nom_fichier1`, `date_creation`, `nom_fichier2`, `nom_fichier3`) VALUES
(7, 1, 1, '2025-08-02', 'exposition', 1, 1, 1, 1, 1, 0, 0, 0, 'fécaec', 'HAIR COACH', 'Design sans titre.png', '2025-07-31 13:02:59', 'Design sans titre.png', 'Design sans titre.png'),
(8, 8, 1, '2025-07-31', 'vrai', 1, 1, 1, 1, 0, 0, 0, 0, 'fécaec', 'HAIR COACH', 'Créez votrsite internet ou votre application web sur-mesure!.png', '2025-07-31 13:11:55', 'Créez votrsite internet ou votre application web sur-mesure!.png', 'Créez votrsite internet ou votre application web sur-mesure!.png');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_utilisateur` varchar(50) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `nom_complet` varchar(100) DEFAULT NULL,
  `role` enum('admin','praticien') DEFAULT 'praticien',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom_utilisateur` (`nom_utilisateur`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom_utilisateur`, `mot_de_passe`, `nom_complet`, `role`, `date_creation`) VALUES
(5, 'qwerty', '$2y$10$89EUDFnSLOFjhPVed7q5Ju3gKrmHRLZxDR9nyQ4./q6JVHy3Wki1W', 'joseph attoungbre', 'admin', '2025-07-23 09:16:22');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
