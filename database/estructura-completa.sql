-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: bus_web_services
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `bus_web_services`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `bus_web_services` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `bus_web_services`;

--
-- Table structure for table `app_users`
--

DROP TABLE IF EXISTS `app_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','tester') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quality_files`
--

DROP TABLE IF EXISTS `quality_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quality_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `case_id` bigint(20) unsigned DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(100) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size_bytes` int(11) NOT NULL,
  `uploaded_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `case_id` (`case_id`),
  CONSTRAINT `quality_files_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `quality_records` (`id`),
  CONSTRAINT `quality_files_ibfk_2` FOREIGN KEY (`case_id`) REFERENCES `test_cases` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quality_records`
--

DROP TABLE IF EXISTS `quality_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quality_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(30) NOT NULL,
  `title` varchar(150) NOT NULL,
  `project` varchar(100) NOT NULL,
  `responsible` varchar(100) NOT NULL,
  `record_date` date NOT NULL,
  `status` varchar(30) NOT NULL,
  `case_id` bigint(20) unsigned DEFAULT NULL,
  `payload` longtext NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kind` (`kind`),
  KEY `case_id` (`case_id`),
  CONSTRAINT `quality_records_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `test_cases` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `search_history`
--

DROP TABLE IF EXISTS `search_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `search_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `search_term` varchar(120) NOT NULL DEFAULT '',
  `category` varchar(60) NOT NULL DEFAULT '',
  `protocol` varchar(10) NOT NULL DEFAULT '',
  `result_count` int(10) unsigned NOT NULL DEFAULT 0,
  `ip_address` varchar(45) NOT NULL DEFAULT 'local',
  `searched_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_search_date` (`searched_at`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(500) NOT NULL,
  `category` varchar(60) NOT NULL,
  `protocol` enum('REST','SOAP') NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `documentation_url` varchar(255) NOT NULL,
  `http_method` varchar(20) NOT NULL DEFAULT 'GET',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_endpoint` (`endpoint`),
  KEY `idx_service_search` (`status`,`protocol`,`category`),
  FULLTEXT KEY `idx_service_text` (`name`,`description`,`category`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `test_cases`
--

DROP TABLE IF EXISTS `test_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `module` varchar(100) NOT NULL,
  `requirement_code` varchar(30) NOT NULL DEFAULT '',
  `requirement_type` enum('Funcional','No funcional') NOT NULL DEFAULT 'No funcional',
  `technique` enum('Caja blanca','Caja negra') NOT NULL,
  `subtechnique` varchar(100) NOT NULL DEFAULT '',
  `objective` varchar(800) NOT NULL,
  `preconditions` varchar(1200) NOT NULL,
  `input_data` varchar(1200) NOT NULL,
  `steps` text NOT NULL,
  `expected_result` varchar(1200) NOT NULL,
  `actual_result` varchar(1200) NOT NULL DEFAULT '',
  `status` enum('Pendiente','Éxito','Fallo','Bloqueado') NOT NULL DEFAULT 'Pendiente',
  `priority` enum('Alta','Media','Baja') NOT NULL DEFAULT 'Media',
  `responsible` varchar(100) NOT NULL,
  `execution_date` date NOT NULL,
  `environment` varchar(150) NOT NULL DEFAULT '',
  `evidence` varchar(500) NOT NULL DEFAULT '',
  `observations` varchar(1200) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_test_case_date` (`execution_date`),
  KEY `idx_test_case_status` (`status`,`technique`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-23  3:20:00
