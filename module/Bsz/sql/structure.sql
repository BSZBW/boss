-- MySQL dump 10.17  Distrib 10.3.25-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: libraries
-- ------------------------------------------------------
-- Server version	10.3.25-MariaDB-0ubuntu0.20.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `authentications`
--

DROP TABLE IF EXISTS `authentications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authentications`
(
    `id`   int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(45) COLLATE latin1_german1_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries`
(
    `id`       int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name`     varchar(45) COLLATE latin1_german1_ci DEFAULT NULL,
    `shortcut` varchar(16) COLLATE latin1_german1_ci NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `libraries`
--

DROP TABLE IF EXISTS `libraries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `libraries`
(
    `isil`              varchar(45) CHARACTER SET latin1       NOT NULL,
    `name`              varchar(256) COLLATE latin1_german1_ci NOT NULL,
    `shortcut`          varchar(8) COLLATE latin1_german1_ci    DEFAULT NULL,
    `sigel`             varchar(8) CHARACTER SET latin1         DEFAULT NULL,
    `is_ill_active`     binary(1) DEFAULT 0 COMMENT 'FErnleihe aktiv?',
    `is_live`           binary(1) DEFAULT 0 COMMENT 'Produktiver ZFL_Server?',
    `is_boss`           binary(1) DEFAULT 0 COMMENT 'BOSS Sicht?',
    `fk_country`        int(11) unsigned NOT NULL DEFAULT 1,
    `fk_auth`           int(11) unsigned NOT NULL DEFAULT 1,
    `fk_auth_2`         int(11) unsigned DEFAULT NULL,
    `isil_availability` varchar(2048) COLLATE latin1_german1_ci DEFAULT NULL,
    `homepage`          varchar(128) CHARACTER SET latin1       DEFAULT NULL,
    `email`             varchar(128) CHARACTER SET latin1       DEFAULT NULL,
    `openurl`           varchar(256) CHARACTER SET latin1       DEFAULT NULL,
    `daiaurl`           varchar(256) CHARACTER SET latin1       DEFAULT NULL,
    `opacurl`           varchar(256) CHARACTER SET latin1       DEFAULT NULL,
    `shibboleth_idp`    varchar(256) CHARACTER SET latin1       DEFAULT NULL,
    `shibboleth_logout` varchar(256) COLLATE latin1_german1_ci  DEFAULT NULL,
    `regex`             varchar(256) COLLATE latin1_german1_ci  DEFAULT NULL,
    `lend_copy`         binary(2) DEFAULT 11,
    PRIMARY KEY (`isil`),
    KEY                 `fk_libraries_1_idx` (`fk_country`),
    KEY                 `fk_libraries_2_idx` (`fk_auth`),
    CONSTRAINT `fk_libraries_1` FOREIGN KEY (`fk_country`) REFERENCES `countries` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
    CONSTRAINT `fk_libraries_2` FOREIGN KEY (`fk_auth`) REFERENCES `authentications` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
    CONSTRAINT `fk_libraries_3` FOREIGN KEY (`fk_auth_2`) REFERENCES `authentications` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `places`
--

DROP TABLE IF EXISTS `places`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `places`
(
    `id`      int(11) NOT NULL AUTO_INCREMENT,
    `library` varchar(45) CHARACTER SET latin1       NOT NULL,
    `name`    varchar(128) COLLATE latin1_german1_ci NOT NULL,
    `code`    varchar(32) CHARACTER SET utf8 DEFAULT '',
    `active`  tinyint(1) NOT NULL,
    `sort`    int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY       `index2` (`library`),
    KEY       `index3` (`active`),
    CONSTRAINT `fk_places_1` FOREIGN KEY (`library`) REFERENCES `libraries` (`isil`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*
 * Copyright 2021 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

--
-- Dumping routines for database 'libraries'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-03-19  9:42:05
