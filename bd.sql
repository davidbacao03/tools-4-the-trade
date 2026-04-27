-- Tools 4 The Trade — Database Schema
-- Updated 2026-04-27


CREATE DATABASE IF NOT EXISTS tools4thetrade
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE tools4thetrade;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categoria` (
  `cat_id`   int(11)     NOT NULL AUTO_INCREMENT,
  `cat_nome` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `categoria` (`cat_id`, `cat_nome`) VALUES
  (1, 'Ferramentas Manuais'),
  (2, 'Ferramentas Elétricas'),
  (3, 'Ferramentas de Medição'),
  (4, 'Equipamento de Segurança'),
  (5, 'Jardim'),
  (6, 'Construção'),
  (7, 'Automóvel'),
  (8, 'Outro');

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `utilizador` (
  `utl_id`     int(11)      NOT NULL AUTO_INCREMENT,
  `utl_nome`   varchar(128) DEFAULT NULL,
  `utl_email`  varchar(128) DEFAULT NULL,
  `utl_passe`  varchar(256) DEFAULT NULL,
  `utl_admin`  tinyint(1)   DEFAULT 0,
  `utl_criado` datetime     DEFAULT current_timestamp(),
  `utl_foto`   varchar(255) DEFAULT NULL,
  PRIMARY KEY (`utl_id`),
  UNIQUE KEY `utl_email` (`utl_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ferramenta` (
  `fer_id`         int(11)       NOT NULL AUTO_INCREMENT,
  `fer_utl_id`     int(11)       DEFAULT NULL,
  `fer_cat_id`     int(11)       DEFAULT NULL,
  `fer_nome`       varchar(128)  DEFAULT NULL,
  `fer_descricao`  varchar(512)  DEFAULT NULL,
  `fer_preco_base` decimal(8,2)  DEFAULT NULL,
  `fer_preco`      decimal(8,2)  DEFAULT NULL,
  `fer_lat`        decimal(10,7) DEFAULT NULL,
  `fer_lng`        decimal(10,7) DEFAULT NULL,
  `fer_ativa`      tinyint(1)    DEFAULT 1,
  `fer_criada`     datetime      DEFAULT current_timestamp(),
  PRIMARY KEY (`fer_id`),
  KEY `fer_utl_id` (`fer_utl_id`),
  KEY `fer_cat_id` (`fer_cat_id`),
  CONSTRAINT `ferramenta_ibfk_1` FOREIGN KEY (`fer_utl_id`) REFERENCES `utilizador` (`utl_id`),
  CONSTRAINT `ferramenta_ibfk_2` FOREIGN KEY (`fer_cat_id`) REFERENCES `categoria` (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Images are cascade-deleted when the parent ferramenta is deleted.
CREATE TABLE IF NOT EXISTS `ferramenta_imagem` (
  `img_id`        int(11)      NOT NULL AUTO_INCREMENT,
  `img_fer_id`    int(11)      NOT NULL,
  `img_path`      varchar(255) NOT NULL,
  `img_principal` tinyint(1)   DEFAULT 0,
  `img_ordem`     int(11)      DEFAULT 0,
  PRIMARY KEY (`img_id`),
  KEY `img_fer_id` (`img_fer_id`),
  CONSTRAINT `ferramenta_imagem_ibfk_1`
    FOREIGN KEY (`img_fer_id`) REFERENCES `ferramenta` (`fer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Rental records. aluguer rows must be deleted before deleting a ferramenta
-- (perfil.php handles this manually before calling DELETE FROM ferramenta).
CREATE TABLE IF NOT EXISTS `aluguer` (
  `alu_id`        int(11)  NOT NULL AUTO_INCREMENT,
  `alu_fer_id`    int(11)  DEFAULT NULL,
  `alu_utl_id`    int(11)  DEFAULT NULL,
  `alu_inicio`    date     DEFAULT NULL,
  `alu_fim`       date     DEFAULT NULL,
  `alu_estado`    enum('Reservado','Alugado','Devolvido') NOT NULL DEFAULT 'Reservado',
  `alu_devolvido` datetime DEFAULT NULL,
  `alu_criado`    datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`alu_id`),
  KEY `alu_fer_id` (`alu_fer_id`),
  KEY `alu_utl_id` (`alu_utl_id`),
  CONSTRAINT `aluguer_ibfk_1` FOREIGN KEY (`alu_fer_id`) REFERENCES `ferramenta` (`fer_id`),
  CONSTRAINT `aluguer_ibfk_2` FOREIGN KEY (`alu_utl_id`) REFERENCES `utilizador` (`utl_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
