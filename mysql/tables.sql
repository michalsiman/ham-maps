-- Adminer 4.8.1 MySQL 5.5.5-10.6.16-MariaDB-0ubuntu0.22.04.1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

SET NAMES utf8mb4;

CREATE TABLE `country` (
  `name` varchar(100) NOT NULL,
  `code` varchar(2) NOT NULL,
  `wwff_code` varchar(20) NOT NULL,
  `pota_code` varchar(20) NOT NULL,
  `sota_code` varchar(200) NOT NULL,
  `gma_code` varchar(200) NOT NULL,
  KEY `name` (`name`),
  KEY `name_code` (`name`,`code`),
  KEY `code` (`code`),
  KEY `code_name` (`code`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `gma_area` (
  `reference` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `altitude` varchar(50) DEFAULT NULL,
  `activation` int(11) DEFAULT NULL,
  `lastact` date DEFAULT NULL,
  KEY `reference` (`reference`),
  KEY `name` (`name`),
  KEY `reference_name` (`reference`,`name`),
  KEY `name_reference` (`name`,`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `pota_area` (
  `reference` varchar(50) NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `latitude` varchar(20) DEFAULT NULL,
  `longitude` varchar(20) DEFAULT NULL,
  `altitude` int(11) DEFAULT NULL,
  `activation` int(11) DEFAULT NULL,
  KEY `reference` (`reference`),
  KEY `name` (`name`),
  KEY `reference_name` (`reference`,`name`),
  KEY `name_reference` (`name`,`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `sota_area` (
  `reference` varchar(50) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `latitude` varchar(20) DEFAULT NULL,
  `longitude` varchar(20) DEFAULT NULL,
  `altitude` int(11) DEFAULT NULL,
  `activation` int(11) DEFAULT NULL,
  `lastact` date DEFAULT NULL,
  KEY `reference` (`reference`),
  KEY `name` (`name`),
  KEY `reference_name` (`reference`,`name`),
  KEY `name_reference` (`name`,`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `wwff_area` (
  `reference` varchar(50) NOT NULL,
  `status` varchar(10) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `program` varchar(10) NOT NULL,
  `dxcc` varchar(10) DEFAULT NULL,
  `latitude` varchar(20) DEFAULT NULL,
  `longitude` varchar(20) DEFAULT NULL,
  `qsoCount` int(10) DEFAULT 0,
  `lastAct` date NOT NULL DEFAULT '1980-01-01',
  KEY `reference` (`reference`),
  KEY `qsoCount_status_lastAct` (`qsoCount`,`status`,`lastAct`),
  KEY `reference_status` (`reference`,`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 2024-05-17 08:55:25
