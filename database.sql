/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: rm_properti
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

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
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(220) NOT NULL,
  `type` varchar(80) NOT NULL,
  `price` bigint(20) NOT NULL DEFAULT 0,
  `location` varchar(120) NOT NULL,
  `beds` int(11) NOT NULL DEFAULT 0,
  `baths` int(11) NOT NULL DEFAULT 0,
  `land` int(11) NOT NULL DEFAULT 0,
  `building` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `features_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features_json`)),
  `videos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`videos_json`)),
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `sales_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_properties_status` (`status`),
  KEY `idx_properties_location` (`location`),
  KEY `idx_properties_type` (`type`),
  KEY `fk_properties_sales` (`sales_id`),
  KEY `fk_properties_created_by` (`created_by`),
  CONSTRAINT `fk_properties_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_properties_sales` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `properties`
--

LOCK TABLES `properties` WRITE;
/*!40000 ALTER TABLE `properties` DISABLE KEYS */;
INSERT INTO `properties` VALUES
(1,'Rumah Minimalis 3KT Dekat Kampus','Rumah',850000000,'Palembang',3,2,120,90,'Rumah siap huni dengan akses jalan lebar. Dekat minimarket dan kampus.','[\"Carport\",\"Dapur luas\",\"Air PDAM\",\"Listrik 2200W\"]',NULL,'active',1,NULL,'2026-01-03 00:56:22','2026-01-03 05:45:08'),
(2,'Ruko 2 Lantai Pinggir Jalan Utama','Ruko',1350000000,'Palembang',2,2,90,150,'Lokasi strategis dengan traffic tinggi. Cocok untuk usaha retail/office.','[\"Balkon\",\"Parkir luas\",\"CCTV area\",\"Dekat pusat kuliner\"]',NULL,'active',2,NULL,'2026-01-03 00:56:22','2026-01-03 02:50:02'),
(3,'Tanah Kavling SHM Siap Bangun','Tanah hitam',420000000,'Tangerang',0,0,100,0,'Kavling matang, SHM, akses mudah ke tol. Lingkungan berkembang.','[\"SHM\",\"Akses tol\",\"Jalan cor\",\"Kawasan berkembang\"]',NULL,'sold',2,NULL,'2026-01-03 00:56:22','2026-01-03 02:44:44'),
(5,'sasa','sasa',0,'sas',0,0,0,0,'',NULL,NULL,'draft',NULL,NULL,'2026-01-03 02:31:55','2026-01-03 02:44:30');
/*!40000 ALTER TABLE `properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_images`
--

DROP TABLE IF EXISTS `property_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 10,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_images_property` (`property_id`),
  CONSTRAINT `fk_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_images`
--

LOCK TABLES `property_images` WRITE;
/*!40000 ALTER TABLE `property_images` DISABLE KEYS */;
INSERT INTO `property_images` VALUES
(1,1,'uploads/properties/84e9183e822b114d2f02bb68dff379d2.jpg',1,'2026-01-03 05:45:08'),
(2,1,'uploads/properties/49202dec04822701b571ed9b8d19cd3c.jpg',11,'2026-01-03 05:45:08'),
(3,1,'uploads/properties/d1dd288547adb0e0f361a224d0d452f6.png',21,'2026-01-03 05:45:55'),
(4,1,'uploads/properties/5f793351278c15d817f1f1338be96d3f.jpg',31,'2026-01-03 05:45:55'),
(5,1,'uploads/properties/9cc983b0fe2668b5f1a3aa33c9cf1b22.jpg',41,'2026-01-03 05:45:55'),
(6,1,'uploads/properties/9415002f4b2bb8820dad0df3e763585b.jpg',51,'2026-01-03 05:45:55');
/*!40000 ALTER TABLE `property_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL,
  `title` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `areas` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES
(1,'Rizki Pratama','Property Consultant','081234567890','6281234567890','rizki@rmproperti.id',NULL,'Spesialis rumah dan ruko. Fokus proses cepat, transparan, dan aman sampai serah terima.','Palembang,Ogan Ilir',4,'2026-01-03 00:56:22','2026-01-03 00:56:22'),
(2,'Dina Aulia','Senior Sales','081298765432','6281298765432','dina@rmproperti.id','uploads/sales/25a6e71b96fba2cc6a43115ba1c8a5b1.jpg','Spesialis apartemen dan tanah. Membantu negosiasi, legalitas, hingga closing.','Jakarta,Tangerang',7,'2026-01-03 00:56:22','2026-01-03 02:46:39');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(120) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'site_name','RM Properti','2026-01-03 01:56:58'),
(2,'site_tagline','Listing elegan & kontak sales','2026-01-03 01:56:58'),
(3,'footer_text','© {year} RM Properti. All rights reserved.','2026-01-03 01:56:58'),
(4,'logo_path','uploads/branding/3063db53052960322e6a3cc97df44883.jpg','2026-01-03 05:34:02');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'admin',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `credit_limit` int(11) NOT NULL DEFAULT 5,
  `credit_used` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin@rm.local','$2y$10$CJ1cG3e4HV3AP.xx0lG4UOeDBTrrhXnJhF07F8B7bdOmpr9AkqO56','superadmin','active',5,0,'2026-01-03 01:34:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `editor_sales`
--

DROP TABLE IF EXISTS `editor_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `editor_sales` (
  `editor_id` int(11) NOT NULL,
  `sales_id` int(11) NOT NULL,
  `granted_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`editor_id`,`sales_id`),
  KEY `fk_editor_sales_sales` (`sales_id`),
  KEY `fk_editor_sales_granted_by` (`granted_by`),
  CONSTRAINT `fk_editor_sales_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_editor_sales_sales` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_editor_sales_user` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `editor_properties`
--

DROP TABLE IF EXISTS `editor_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `editor_properties` (
  `editor_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `granted_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`editor_id`,`property_id`),
  KEY `fk_editor_props_property` (`property_id`),
  KEY `fk_editor_props_granted_by` (`granted_by`),
  CONSTRAINT `fk_editor_props_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_editor_props_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_editor_props_user` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-03 16:06:16




CREATE USER 'nah'@'localhost' IDENTIFIED BY 'djlajshj';
