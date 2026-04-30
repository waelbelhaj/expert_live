-- Création de la table des clients
CREATE TABLE IF NOT EXISTS `clients` (
  `id_client` VARCHAR(20) NOT NULL PRIMARY KEY,
  `code_pin` VARCHAR(20) NOT NULL,
  `nom` VARCHAR(100) NOT NULL,
  `role` VARCHAR(50) NOT NULL,
  `caisse_id` TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table principale des tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `client_id` VARCHAR(20) NOT NULL,
  `num_ticket` INT UNSIGNED NOT NULL,
  `date_ticket` DATETIME NOT NULL,
  `caisse_nom` VARCHAR(50),
  `vendeur` VARCHAR(50),
  `type_paiement` VARCHAR(50),
  `total` DECIMAL(10,3) NOT NULL DEFAULT '0.000',
  `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `id_cloture` INT UNSIGNED DEFAULT '0',
  `historique_id` VARCHAR(30) DEFAULT NULL,
  INDEX `idx_client_date` (`client_id`, `date_ticket`),
  INDEX `idx_num_ticket` (`num_ticket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des produits vendus (Lignes de ticket)
CREATE TABLE IF NOT EXISTS `ticket_details` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `nom_produit` VARCHAR(100) NOT NULL,
  `famille` VARCHAR(50) DEFAULT NULL,
  `qte` DECIMAL(8,3) NOT NULL,
  `prix_u` DECIMAL(10,3) NOT NULL,
  `prix_u_remise` DECIMAL(10,3) NOT NULL,
  `tva` DECIMAL(5,2) NOT NULL DEFAULT '0.00',
  `remise` DECIMAL(5,2) NOT NULL DEFAULT '0.00',
  INDEX `idx_ticket_id` (`ticket_id`),
  CONSTRAINT `fk_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des clotures (Sessions de caisse)
CREATE TABLE IF NOT EXISTS `clotures` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `client_id` VARCHAR(20) NOT NULL,
  `num` INT UNSIGNED NOT NULL,
  `valeur` DECIMAL(10,3) NOT NULL DEFAULT '0.000',
  `date_debut` VARCHAR(30) DEFAULT NULL,
  `date_fin` VARCHAR(30) DEFAULT NULL,
  UNIQUE INDEX `idx_client_num` (`client_id`, `num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour les dépenses (Action 4)
CREATE TABLE IF NOT EXISTS `depenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `client_id` VARCHAR(20) NOT NULL,
  `date_depense` DATETIME NOT NULL,
  `type_depense` VARCHAR(30) NOT NULL, 
  `nom` VARCHAR(100) NOT NULL,
  `qte` DECIMAL(8,3) DEFAULT '0.000',
  `total` DECIMAL(10,3) NOT NULL,
  INDEX `idx_dep_client_date` (`client_id`, `date_depense`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table de journalisation (logger)
CREATE TABLE IF NOT EXISTS `logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `client_id` VARCHAR(20) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `uri` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL,
  `user_agent` VARCHAR(255),
  INDEX `idx_logs_client_date` (`client_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optionnel : Insérer les clients actuels dans la table 'clients' pour tester
INSERT IGNORE INTO `clients` (`id_client`, `code_pin`, `nom`, `role`, `caisse_id`) VALUES
('PM', '0000', 'PROXI MARKET', 'Serveur', 1),
('4S', '0000', '4S MARKET SHOP', 'Serveur', 2),
('MLHY', '0000', 'MLHY', 'MLHY (Serveur)', 1),
('MLHY2', '0000', 'MLHY', 'MLHY (Caisse 2)', 1),
('BC', '0000', 'THE BEST CAFE MEDENINE', 'POS I5', 1),
('AYLA', '0000', 'BABY PARK AYLA', 'Caisse', 1),
('FFDEJ', '0000', 'FAST FOOD DAR EL JEM', 'Caisse', 1),
('FG75', '0000', 'FRENCH GRILL 75', 'Caisse', 2),
('LMDP', '0000', 'LES MERVEILLES DU PAIN', 'Caisse', 2),
('ARO', '1234', 'L''aromate', 'Caisse', 2),
('AP', '2025', 'L''art du pain', 'Casse 1 AURES', 2),
('HR', '0000', 'Hotel Rodes', 'Caisse cafe', 1),
('BATOUT', '0000', 'Batout', 'Caisse', 1),
('M7', '0000', 'MOSAIQUE 7', 'poste serveur', 1),
('CF', '0000', 'Coucou food', 'poste caisse', 1),
('PC', '2025', 'P''TIT CAFE', 'poste caisse CSI', 1),
('LV', '2025', 'LV Coffee Zone', 'poste caisse Digito', 1),
('DS', 'xx2025', 'Droug store', 'poste caisse', 1),
('CT', '2025', 'Cafe time', 'poste asus AIO', 1),
('RL', '2025', 'Rigal', 'Caisse 1', 1),
('BS', '2025', 'Ble sucre', 'Caisse 1', 1),
('EK', '2026', 'EPICERIE kHAMESI', 'Caisse 1', 1),
('SANSHO', '2026', 'SANSHO', 'Caisse HP', 1),
('test', '2026', 'test', 'Caisse HP', 1);
