-- MariaDB dump 10.19  Distrib 10.4.27-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: marine
-- ------------------------------------------------------
-- Server version	10.4.27-MariaDB

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
-- Table structure for table `app_brandings`
--

DROP TABLE IF EXISTS `app_brandings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_brandings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `app_name` varchar(255) NOT NULL DEFAULT 'MyApp',
  `primary_logo` varchar(255) DEFAULT NULL,
  `admin_logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_brandings`
--

LOCK TABLES `app_brandings` WRITE;
/*!40000 ALTER TABLE `app_brandings` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_brandings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL DEFAULT 'full',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `size` double DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration` double DEFAULT NULL,
  `created_by` varchar(255) NOT NULL DEFAULT 'System Administrator',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
INSERT INTO `backups` VALUES (1,'full','failed',NULL,NULL,'2025-09-23 14:01:49','2025-09-23 14:01:49',0.09,'System Administrator','2025-09-23 14:01:49','2025-09-23 14:01:49'),(2,'full','in_progress',NULL,NULL,'2025-09-23 14:02:28',NULL,NULL,'System Administrator','2025-09-23 14:02:28','2025-09-23 14:02:28'),(3,'full','failed',NULL,NULL,'2025-09-23 14:05:03','2025-09-23 14:05:08',1.47,'System Administrator','2025-09-23 14:05:03','2025-09-23 14:05:08'),(4,'full','failed',NULL,NULL,'2025-09-23 14:08:25','2025-09-23 14:08:26',0.15,'System Administrator','2025-09-23 14:08:25','2025-09-23 14:08:26'),(5,'full','failed',NULL,NULL,'2025-09-23 14:12:08','2025-09-23 14:12:08',0.16,'System Administrator','2025-09-23 14:12:08','2025-09-23 14:12:08'),(6,'full','failed',NULL,NULL,'2025-09-23 14:22:35','2025-09-23 14:22:35',0.13,'System Administrator','2025-09-23 14:22:35','2025-09-23 14:22:35');
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banner_pricing`
--

DROP TABLE IF EXISTS `banner_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banner_pricing` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `banner_type` enum('header','hero','sidebar','footer') NOT NULL,
  `position` enum('top','middle','bottom') DEFAULT NULL,
  `duration_type` enum('daily','weekly','monthly') NOT NULL DEFAULT 'monthly',
  `duration_value` int(11) NOT NULL DEFAULT 1,
  `base_price` decimal(10,2) NOT NULL,
  `premium_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
  `discount_tiers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`discount_tiers`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `max_concurrent` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `banner_pricing_unique` (`banner_type`,`position`,`duration_type`,`duration_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banner_pricing`
--

LOCK TABLES `banner_pricing` WRITE;
/*!40000 ALTER TABLE `banner_pricing` DISABLE KEYS */;
/*!40000 ALTER TABLE `banner_pricing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `position` varchar(255) NOT NULL,
  `banner_size` varchar(255) NOT NULL,
  `dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dimensions`)),
  `mobile_dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mobile_dimensions`)),
  `display_context` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `show_on_mobile` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_desktop` tinyint(1) NOT NULL DEFAULT 1,
  `target_category_id` bigint(20) unsigned DEFAULT NULL,
  `target_locations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_locations`)),
  `user_target` varchar(255) NOT NULL,
  `background_color` varchar(255) DEFAULT NULL,
  `text_color` varchar(255) DEFAULT NULL,
  `button_text` varchar(255) DEFAULT NULL,
  `button_color` varchar(255) DEFAULT NULL,
  `overlay_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`overlay_settings`)),
  `conversion_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `max_impressions` int(11) DEFAULT NULL,
  `max_clicks` int(11) DEFAULT NULL,
  `media_type` enum('image','video') NOT NULL,
  `media_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 1,
  `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `click_count` int(11) NOT NULL DEFAULT 0,
  `impression_count` int(11) NOT NULL DEFAULT 0,
  `revenue_earned` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `purchaser_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `purchase_status` enum('pending_payment','paid','expired','cancelled') NOT NULL DEFAULT 'pending_payment',
  `purchased_at` timestamp NULL DEFAULT NULL,
  `pricing_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing_details`)),
  `payment_reference` varchar(255) DEFAULT NULL,
  `banner_type` varchar(255) NOT NULL,
  `duration_days` int(11) NOT NULL DEFAULT 30,
  `auto_approve` tinyint(1) NOT NULL DEFAULT 0,
  `admin_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banners_created_by_foreign` (`created_by`),
  KEY `banners_status_position_index` (`status`,`position`),
  KEY `banners_start_date_end_date_index` (`start_date`,`end_date`),
  KEY `banners_priority_index` (`priority`),
  KEY `banners_purchaser_id_foreign` (`purchaser_id`),
  KEY `banners_target_category_id_foreign` (`target_category_id`),
  CONSTRAINT `banners_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `banners_purchaser_id_foreign` FOREIGN KEY (`purchaser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `banners_target_category_id_foreign` FOREIGN KEY (`target_category_id`) REFERENCES `equipment_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint(20) unsigned NOT NULL,
  `buyer_id` bigint(20) unsigned NOT NULL,
  `seller_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversations_listing_id_is_active_index` (`listing_id`,`is_active`),
  KEY `conversations_buyer_id_is_active_index` (`buyer_id`,`is_active`),
  KEY `conversations_seller_id_is_active_index` (`seller_id`,`is_active`),
  KEY `conversations_last_message_at_index` (`last_message_at`),
  CONSTRAINT `conversations_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `equipment_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_configs`
--

DROP TABLE IF EXISTS `email_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `driver` varchar(255) NOT NULL DEFAULT 'custom',
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL DEFAULT 587,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `encryption` varchar(255) NOT NULL DEFAULT 'tls',
  `enable_smtp` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_configs`
--

LOCK TABLES `email_configs` WRITE;
/*!40000 ALTER TABLE `email_configs` DISABLE KEYS */;
INSERT INTO `email_configs` VALUES (1,'outlook','local',2788,'admin@gmail.com','eyJpdiI6IkdzOW95RTFUUHVaYTN0RzV1b2VDcXc9PSIsInZhbHVlIjoicDd6N29zR1VXYndrU2pjWUpuY3htUT09IiwibWFjIjoiZGI1MzY5MzA3YjE4NzI2MWYzNTc5YjgwOGQ4ZGZjMzk5ODI4MWQzYTU4YzIyZWM5ODQ5MjM0MjgyOGYxNmI1ZCIsInRhZyI6IiJ9','noreply@marine.africa','Marine.africa System','ssl',0,'2025-09-18 16:47:05','2025-09-18 16:56:35'),(2,'gmail','local',2788,'admin@gmail.com','eyJpdiI6IjZKTXllSUJlTDE2L1Jvb1VMMW1MT0E9PSIsInZhbHVlIjoibFgzekNJVWFpaUFFNmZBNWJvSUZzZz09IiwibWFjIjoiYjhlYWQ2ZGMwMzY1Y2U3OWY0ZGMwOTBmZjE1YWRmODc1MDU5MWM5YzU1MDEzMWE2Mjk0NGRlMzVkNjFiNzBjOSIsInRhZyI6IiJ9','noreply@marine.africa','Marine.africa System','ssl',0,'2025-09-18 16:57:37','2025-09-18 16:57:59');
/*!40000 ALTER TABLE `email_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_categories`
--

DROP TABLE IF EXISTS `equipment_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_name` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_categories_slug_unique` (`slug`),
  KEY `equipment_categories_is_active_sort_order_index` (`is_active`,`sort_order`),
  KEY `equipment_categories_parent_id_index` (`parent_id`),
  CONSTRAINT `equipment_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `equipment_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_categories`
--

LOCK TABLES `equipment_categories` WRITE;
/*!40000 ALTER TABLE `equipment_categories` DISABLE KEYS */;
INSERT INTO `equipment_categories` VALUES (1,'Marine Engines','marine-engines','High-performance marine engines for various vessel types','engine',NULL,1,1,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(2,'Navigation Equipment','navigation-equipment','GPS, radar, and navigation systems for marine vessels','compass',NULL,1,2,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(3,'Safety Equipment','safety-equipment','Life jackets, flares, and emergency safety gear','shield',NULL,1,3,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(4,'Communication Systems','communication-systems','VHF radios, satellite communication equipment','radio',NULL,1,4,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(5,'Propellers & Shafts','propellers-shafts','Marine propellers, drive shafts, and transmission systems','propeller',NULL,1,5,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(6,'Hull & Deck Equipment','hull-deck-equipment','Anchors, windlasses, cleats, and deck hardware','anchor',NULL,1,6,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(7,'Electrical Systems','electrical-systems','Marine batteries, inverters, and electrical components','battery',NULL,1,7,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(8,'Plumbing & Sanitation','plumbing-sanitation','Bilge pumps, toilets, and water system components','water',NULL,1,8,'2025-09-17 19:45:11','2025-09-17 19:45:11');
/*!40000 ALTER TABLE `equipment_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_listings`
--

DROP TABLE IF EXISTS `equipment_listings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment_listings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `condition` enum('new','excellent','good','fair','poor') NOT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NGN',
  `is_price_negotiable` tinyint(1) NOT NULL DEFAULT 0,
  `is_poa` tinyint(1) NOT NULL DEFAULT 0,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `location_state` varchar(255) DEFAULT NULL,
  `location_city` varchar(255) DEFAULT NULL,
  `location_address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `hide_address` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_available` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_radius` int(11) DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_whatsapp` varchar(255) DEFAULT NULL,
  `contact_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contact_methods`)),
  `availability_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`availability_hours`)),
  `allows_inspection` tinyint(1) NOT NULL DEFAULT 1,
  `allows_test_drive` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','pending','active','sold','archived','rejected') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 0,
  `featured_until` timestamp NULL DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `inquiry_count` int(11) NOT NULL DEFAULT 0,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `moderated_by` bigint(20) unsigned DEFAULT NULL,
  `moderation_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipment_listings_seller_id_status_index` (`seller_id`,`status`),
  KEY `equipment_listings_category_id_status_index` (`category_id`,`status`),
  KEY `equipment_listings_status_published_at_index` (`status`,`published_at`),
  KEY `equipment_listings_is_featured_status_index` (`is_featured`,`status`),
  KEY `equipment_listings_location_state_location_city_index` (`location_state`,`location_city`),
  KEY `equipment_listings_expires_at_index` (`expires_at`),
  KEY `equipment_listings_moderated_by_foreign` (`moderated_by`),
  KEY `equipment_listings_priority_status_index` (`priority`,`status`),
  KEY `equipment_listings_is_featured_featured_until_index` (`is_featured`,`featured_until`),
  CONSTRAINT `equipment_listings_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `equipment_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `equipment_listings_moderated_by_foreign` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `equipment_listings_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_listings`
--

LOCK TABLES `equipment_listings` WRITE;
/*!40000 ALTER TABLE `equipment_listings` DISABLE KEYS */;
INSERT INTO `equipment_listings` VALUES (1,4,1,'Caterpillar C18 Marine Engine - 715HP','High-performance Caterpillar C18 marine engine with 715 horsepower. Excellent for commercial fishing vessels and medium-sized boats. Recently serviced with new injectors and turbocharger. Complete with engine management system and warranty.','Caterpillar','C18',2020,'excellent',12500000.00,'NGN',1,0,'\"{\\\"Power\\\":\\\"715 HP @ 2300 RPM\\\",\\\"Displacement\\\":\\\"18.1 L\\\",\\\"Cylinders\\\":\\\"6\\\",\\\"Cooling\\\":\\\"Freshwater Cooled\\\",\\\"Weight\\\":\\\"1,950 kg\\\",\\\"Transmission\\\":\\\"ZF 360A\\\"}\"','\"[\\\"Electronic Engine Management\\\",\\\"Turbocharger\\\",\\\"Heat Exchanger\\\",\\\"Raw Water Pump\\\"]\"','Rivers','Port Harcourt','628 Marine Street',5.12847000,6.90417000,0,0,160,NULL,'+234-838-588-2991','mary@deepblue.ng','+234-838-588-2991','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,1,'draft',0,0,NULL,1,68,15,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+1\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+2\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+3\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+4\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4}]\"','\"[\\\"caterpillar\\\",\\\"marine engine\\\",\\\"commercial\\\",\\\"715hp\\\"]\"','Caterpillar C18 Marine Engine - 715HP - Marine Equipment Nigeria','High-performance Caterpillar C18 marine engine with 715 horsepower. Excellent for commercial fishing vessels and medium-sized boats. Recently serviced with new ','2025-09-16 19:45:19','2025-10-29 19:45:19','2025-09-17 19:45:19','2025-09-17 19:45:19',NULL,NULL,NULL),(2,6,2,'Furuno NavNet TZtouch3 16\" Multifunction Display','Advanced multifunction navigation display with built-in GPS/WAAS receiver, fish finder, radar interface, and chart plotting capabilities. Perfect for professional marine navigation.','Furuno','TZT16F',2022,'new',850000.00,'NGN',0,0,'\"{\\\"Screen Size\\\":\\\"15.6\\\\\\\"\\\",\\\"Resolution\\\":\\\"1920 x 1080\\\",\\\"GPS\\\":\\\"Built-in GPS\\\\\\/WAAS\\\",\\\"Fish Finder\\\":\\\"1kW TruEcho CHIRP\\\",\\\"Connectivity\\\":\\\"Ethernet, NMEA 2000, WiFi\\\"}\"','\"[\\\"TruEcho CHIRP Sonar\\\",\\\"WiFi Connectivity\\\",\\\"Smartphone Integration\\\",\\\"Weather Overlay\\\"]\"','Cross River','Calabar','938 Marine Street',5.83777000,8.75776000,0,1,493,NULL,'+234-928-795-1774','grace@coastalequip.ng','+234-928-795-1774','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'active',0,0,NULL,0,189,3,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Furuno+TZT16F+1\\\",\\\"alt\\\":\\\"Furuno TZT16F - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Furuno+TZT16F+2\\\",\\\"alt\\\":\\\"Furuno TZT16F - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Furuno+TZT16F+3\\\",\\\"alt\\\":\\\"Furuno TZT16F - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3}]\"','\"[\\\"furuno\\\",\\\"gps\\\",\\\"navigation\\\",\\\"fish finder\\\"]\"','Furuno NavNet TZtouch3 16\" Multifunction Display - Marine Equipment Nigeria','Advanced multifunction navigation display with built-in GPS/WAAS receiver, fish finder, radar interface, and chart plotting capabilities. Perfect for profession','2025-09-12 19:45:20','2025-11-07 19:45:20','2025-09-17 19:45:20','2025-09-17 19:45:20',NULL,NULL,NULL),(3,5,3,'SOLAS Life Raft - 25 Person Capacity','SOLAS approved inflatable life raft suitable for commercial vessels. Includes survival equipment, emergency rations, and signaling devices. Recently inspected and certified.','Survitec','SOLAS-25P',2021,'good',2200000.00,'NGN',0,0,'\"{\\\"Capacity\\\":\\\"25 persons\\\",\\\"Compliance\\\":\\\"SOLAS\\\\\\/IMO Standards\\\",\\\"Container Type\\\":\\\"Fiberglass\\\",\\\"Weight\\\":\\\"145 kg\\\",\\\"Dimensions\\\":\\\"95 x 75 x 40 cm\\\"}\"','\"[\\\"Emergency Rations\\\",\\\"Fresh Water\\\",\\\"Signaling Equipment\\\",\\\"First Aid Kit\\\"]\"','Kano','Kano','841 Marine Street',12.01308000,8.55383000,0,0,NULL,29531.00,'+234-895-141-6712','ibrahim@northernmarine.africa','+234-895-141-6712','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'active',0,0,NULL,0,427,4,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Survitec+SOLAS-25P+1\\\",\\\"alt\\\":\\\"Survitec SOLAS-25P - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Survitec+SOLAS-25P+2\\\",\\\"alt\\\":\\\"Survitec SOLAS-25P - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Survitec+SOLAS-25P+3\\\",\\\"alt\\\":\\\"Survitec SOLAS-25P - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3}]\"','\"[\\\"life raft\\\",\\\"safety\\\",\\\"solas\\\",\\\"emergency\\\"]\"','SOLAS Life Raft - 25 Person Capacity - Marine Equipment Nigeria','SOLAS approved inflatable life raft suitable for commercial vessels. Includes survival equipment, emergency rations, and signaling devices. Recently inspected a','2025-09-06 19:45:20','2025-11-23 19:45:20','2025-09-17 19:45:20','2025-09-17 19:45:20',NULL,NULL,NULL),(4,6,5,'Bronze Propeller - 28\" x 22\" 4-Blade','High-quality manganese bronze propeller perfect for displacement hulls. Excellent condition with minor surface scratches. Suitable for engines 200-400 HP.','Michigan Wheel','Dyna-Quad',2019,'good',485000.00,'NGN',1,0,'\"{\\\"Diameter\\\":\\\"28 inches\\\",\\\"Pitch\\\":\\\"22 inches\\\",\\\"Blades\\\":\\\"4\\\",\\\"Material\\\":\\\"Manganese Bronze\\\",\\\"Bore\\\":\\\"2.5 inches\\\",\\\"Rotation\\\":\\\"Right Hand\\\"}\"','\"[\\\"Balanced\\\",\\\"Polished Finish\\\",\\\"Keyway Cut\\\",\\\"Hub Included\\\"]\"','Cross River','Calabar','938 Marine Street',5.93095000,8.23143000,0,1,138,11530.00,'+234-928-795-1774','grace@coastalequip.ng','+234-928-795-1774','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,1,'draft',0,0,NULL,1,412,7,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+1\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+2\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+3\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+4\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+5\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 5\\\",\\\"is_primary\\\":false,\\\"order\\\":5},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+6\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 6\\\",\\\"is_primary\\\":false,\\\"order\\\":6}]\"','\"[\\\"propeller\\\",\\\"bronze\\\",\\\"4-blade\\\",\\\"michigan wheel\\\"]\"','Bronze Propeller - 28\" x 22\" 4-Blade - Marine Equipment Nigeria','High-quality manganese bronze propeller perfect for displacement hulls. Excellent condition with minor surface scratches. Suitable for engines 200-400 HP.','2025-08-22 19:45:20','2025-10-31 19:45:20','2025-09-17 19:45:20','2025-09-17 19:45:20',NULL,NULL,NULL),(5,3,4,'Icom IC-M506 VHF Marine Radio with AIS','Professional VHF marine radio with built-in AIS receiver and GPS. Features Class D DSC, emergency functions, and crystal-clear communication.','Icom','IC-M506',2023,'new',185000.00,'NGN',1,0,'\"{\\\"Channels\\\":\\\"All International\\\\\\/USCG\\\",\\\"Power Output\\\":\\\"25W\\\",\\\"AIS\\\":\\\"Built-in AIS Receiver\\\",\\\"GPS\\\":\\\"Internal GPS\\\",\\\"Display\\\":\\\"2.3\\\\\\\" Color LCD\\\"}\"','\"[\\\"DSC Class D\\\",\\\"Man Overboard\\\",\\\"Noise Cancelling\\\",\\\"Active Noise Control\\\"]\"','Lagos','Lagos','462 Marine Street',6.47150000,3.25249000,0,0,NULL,NULL,'+234-934-266-9322','john@oceanmarine.africa','+234-934-266-9322','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'active',0,0,NULL,1,238,15,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+1\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+2\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+3\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+4\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+5\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 5\\\",\\\"is_primary\\\":false,\\\"order\\\":5},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+6\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 6\\\",\\\"is_primary\\\":false,\\\"order\\\":6},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+7\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 7\\\",\\\"is_primary\\\":false,\\\"order\\\":7}]\"','\"[\\\"vhf radio\\\",\\\"icom\\\",\\\"ais\\\",\\\"marine communication\\\"]\"','Icom IC-M506 VHF Marine Radio with AIS - Marine Equipment Nigeria','Professional VHF marine radio with built-in AIS receiver and GPS. Features Class D DSC, emergency functions, and crystal-clear communication.','2025-08-18 19:45:20','2025-11-07 19:45:20','2025-09-17 19:45:20','2025-09-17 19:45:20',NULL,NULL,NULL),(6,6,7,'Victron MultiPlus 12/3000/120 Inverter/Charger','Professional marine inverter/charger system with advanced battery management. Perfect for yachts and commercial vessels requiring reliable shore power connection.','Victron Energy','MultiPlus 12/3000/120',2022,'excellent',425000.00,'NGN',1,0,'\"{\\\"Input Voltage\\\":\\\"12V DC\\\",\\\"Output Power\\\":\\\"3000W continuous\\\",\\\"Charger Current\\\":\\\"120A\\\",\\\"Efficiency\\\":\\\"94%\\\",\\\"Protection\\\":\\\"IP21\\\"}\"','\"[\\\"PowerAssist Technology\\\",\\\"Remote Monitoring\\\",\\\"Battery Temperature Sensor\\\",\\\"Adaptive Charging\\\"]\"','Cross River','Calabar','938 Marine Street',5.27918000,8.52590000,0,0,133,39895.00,'+234-928-795-1774','grace@coastalequip.ng','+234-928-795-1774','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'active',1,0,NULL,1,155,21,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+1\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+2\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+3\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3}]\"','\"[\\\"inverter\\\",\\\"charger\\\",\\\"victron\\\",\\\"marine power\\\"]\"','Victron MultiPlus 12/3000/120 Inverter/Charger - Marine Equipment Nigeria','Professional marine inverter/charger system with advanced battery management. Perfect for yachts and commercial vessels requiring reliable shore power connectio','2025-08-24 19:45:20','2025-11-26 19:45:20','2025-09-17 19:45:20','2025-09-17 19:45:20',NULL,NULL,NULL),(7,5,6,'Lewmar V700 Electric Windlass - 700W','Heavy-duty electric anchor windlass suitable for boats up to 12 meters. Includes chain gypsy and rope drum. Recently overhauled with new motor brushes.','Lewmar','V700',2020,'good',320000.00,'NGN',1,0,'\"{\\\"Motor Power\\\":\\\"700W\\\",\\\"Pulling Power\\\":\\\"700 kg\\\",\\\"Chain Size\\\":\\\"8-10mm\\\",\\\"Rope Diameter\\\":\\\"12-16mm\\\",\\\"Voltage\\\":\\\"12V DC\\\"}\"','\"[\\\"Remote Control\\\",\\\"Manual Override\\\",\\\"Chain Counter\\\",\\\"Weatherproof\\\"]\"','Kano','Kano','841 Marine Street',11.99746000,8.55663000,0,1,131,NULL,'+234-895-141-6712','ibrahim@northernmarine.africa','+234-895-141-6712','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,1,'draft',0,0,NULL,1,357,20,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+1\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+2\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+3\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+4\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+5\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 5\\\",\\\"is_primary\\\":false,\\\"order\\\":5},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+6\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 6\\\",\\\"is_primary\\\":false,\\\"order\\\":6}]\"','\"[\\\"windlass\\\",\\\"anchor\\\",\\\"lewmar\\\",\\\"electric\\\"]\"','Lewmar V700 Electric Windlass - 700W - Marine Equipment Nigeria','Heavy-duty electric anchor windlass suitable for boats up to 12 meters. Includes chain gypsy and rope drum. Recently overhauled with new motor brushes.',NULL,'2025-12-11 19:45:20','2025-09-17 19:45:20','2025-09-17 19:45:20',NULL,NULL,NULL),(8,4,8,'Rule-Mate 1100 GPH Automatic Bilge Pump','Reliable automatic bilge pump with built-in float switch. Perfect for continuous bilge monitoring and water removal. Includes installation hardware and manual.','Rule','Rule-Mate 1100',2023,'new',45000.00,'NGN',1,0,'\"{\\\"Flow Rate\\\":\\\"1100 GPH\\\",\\\"Voltage\\\":\\\"12V DC\\\",\\\"Current Draw\\\":\\\"4.2A\\\",\\\"Outlet\\\":\\\"1-1\\\\\\/8\\\\\\\" hose\\\",\\\"Dimensions\\\":\\\"8\\\\\\\" x 3\\\\\\\" x 6\\\\\\\"\\\"}\"','\"[\\\"Automatic Float Switch\\\",\\\"Ignition Protection\\\",\\\"Corrosion Resistant\\\",\\\"Quick Disconnect\\\"]\"','Rivers','Port Harcourt','628 Marine Street',4.72130000,6.85015000,0,0,NULL,12781.00,'+234-838-588-2991','mary@deepblue.ng','+234-838-588-2991','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'active',1,0,NULL,1,470,18,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Rule+Rule-Mate+1100+1\\\",\\\"alt\\\":\\\"Rule Rule-Mate 1100 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Rule+Rule-Mate+1100+2\\\",\\\"alt\\\":\\\"Rule Rule-Mate 1100 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Rule+Rule-Mate+1100+3\\\",\\\"alt\\\":\\\"Rule Rule-Mate 1100 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Rule+Rule-Mate+1100+4\\\",\\\"alt\\\":\\\"Rule Rule-Mate 1100 - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4}]\"','\"[\\\"bilge pump\\\",\\\"automatic\\\",\\\"rule\\\",\\\"water pump\\\"]\"','Rule-Mate 1100 GPH Automatic Bilge Pump - Marine Equipment Nigeria','Reliable automatic bilge pump with built-in float switch. Perfect for continuous bilge monitoring and water removal. Includes installation hardware and manual.',NULL,'2025-11-21 19:45:20','2025-09-17 19:45:20','2025-09-17 19:45:20',NULL,NULL,NULL),(9,3,1,'Caterpillar C18 Marine Engine - 715HP','High-performance Caterpillar C18 marine engine with 715 horsepower. Excellent for commercial fishing vessels and medium-sized boats. Recently serviced with new injectors and turbocharger. Complete with engine management system and warranty.','Caterpillar','C18',2020,'excellent',12500000.00,'NGN',1,0,'\"{\\\"Power\\\":\\\"715 HP @ 2300 RPM\\\",\\\"Displacement\\\":\\\"18.1 L\\\",\\\"Cylinders\\\":\\\"6\\\",\\\"Cooling\\\":\\\"Freshwater Cooled\\\",\\\"Weight\\\":\\\"1,950 kg\\\",\\\"Transmission\\\":\\\"ZF 360A\\\"}\"','\"[\\\"Electronic Engine Management\\\",\\\"Turbocharger\\\",\\\"Heat Exchanger\\\",\\\"Raw Water Pump\\\"]\"','Lagos','Lagos','132 Marine Street',6.58552000,3.53198000,0,1,NULL,NULL,'+234-889-637-9003','john@oceanmarine.africa','+234-889-637-9003','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,1,'draft',0,0,NULL,0,406,18,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+1\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+2\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+3\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+4\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+5\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 5\\\",\\\"is_primary\\\":false,\\\"order\\\":5},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+6\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 6\\\",\\\"is_primary\\\":false,\\\"order\\\":6},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+7\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 7\\\",\\\"is_primary\\\":false,\\\"order\\\":7},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Caterpillar+C18+8\\\",\\\"alt\\\":\\\"Caterpillar C18 - Image 8\\\",\\\"is_primary\\\":false,\\\"order\\\":8}]\"','\"[\\\"caterpillar\\\",\\\"marine engine\\\",\\\"commercial\\\",\\\"715hp\\\"]\"','Caterpillar C18 Marine Engine - 715HP - Marine Equipment Nigeria','High-performance Caterpillar C18 marine engine with 715 horsepower. Excellent for commercial fishing vessels and medium-sized boats. Recently serviced with new ','2025-09-11 19:54:21','2025-12-06 19:54:21','2025-09-17 19:54:21','2025-09-17 19:54:21',NULL,NULL,NULL),(10,5,2,'Furuno NavNet TZtouch3 16\" Multifunction Display','Advanced multifunction navigation display with built-in GPS/WAAS receiver, fish finder, radar interface, and chart plotting capabilities. Perfect for professional marine navigation.','Furuno','TZT16F',2022,'new',850000.00,'NGN',0,0,'\"{\\\"Screen Size\\\":\\\"15.6\\\\\\\"\\\",\\\"Resolution\\\":\\\"1920 x 1080\\\",\\\"GPS\\\":\\\"Built-in GPS\\\\\\/WAAS\\\",\\\"Fish Finder\\\":\\\"1kW TruEcho CHIRP\\\",\\\"Connectivity\\\":\\\"Ethernet, NMEA 2000, WiFi\\\"}\"','\"[\\\"TruEcho CHIRP Sonar\\\",\\\"WiFi Connectivity\\\",\\\"Smartphone Integration\\\",\\\"Weather Overlay\\\"]\"','Kano','Kano','286 Marine Street',12.07242000,8.59729000,0,0,164,31643.00,'+234-944-815-7598','ibrahim@northernmarine.africa','+234-944-815-7598','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'draft',1,0,NULL,1,433,10,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Furuno+TZT16F+1\\\",\\\"alt\\\":\\\"Furuno TZT16F - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Furuno+TZT16F+2\\\",\\\"alt\\\":\\\"Furuno TZT16F - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Furuno+TZT16F+3\\\",\\\"alt\\\":\\\"Furuno TZT16F - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Furuno+TZT16F+4\\\",\\\"alt\\\":\\\"Furuno TZT16F - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4}]\"','\"[\\\"furuno\\\",\\\"gps\\\",\\\"navigation\\\",\\\"fish finder\\\"]\"','Furuno NavNet TZtouch3 16\" Multifunction Display - Marine Equipment Nigeria','Advanced multifunction navigation display with built-in GPS/WAAS receiver, fish finder, radar interface, and chart plotting capabilities. Perfect for profession',NULL,'2025-10-26 19:54:21','2025-09-17 19:54:21','2025-09-17 19:54:21',NULL,NULL,NULL),(11,4,3,'SOLAS Life Raft - 25 Person Capacity','SOLAS approved inflatable life raft suitable for commercial vessels. Includes survival equipment, emergency rations, and signaling devices. Recently inspected and certified.','Survitec','SOLAS-25P',2021,'good',2200000.00,'NGN',0,0,'\"{\\\"Capacity\\\":\\\"25 persons\\\",\\\"Compliance\\\":\\\"SOLAS\\\\\\/IMO Standards\\\",\\\"Container Type\\\":\\\"Fiberglass\\\",\\\"Weight\\\":\\\"145 kg\\\",\\\"Dimensions\\\":\\\"95 x 75 x 40 cm\\\"}\"','\"[\\\"Emergency Rations\\\",\\\"Fresh Water\\\",\\\"Signaling Equipment\\\",\\\"First Aid Kit\\\"]\"','Rivers','Port Harcourt','960 Marine Street',4.74421000,6.98254000,0,0,NULL,NULL,'+234-926-786-4788','mary@deepblue.ng','+234-926-786-4788','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'active',0,0,NULL,1,428,4,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Survitec+SOLAS-25P+1\\\",\\\"alt\\\":\\\"Survitec SOLAS-25P - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Survitec+SOLAS-25P+2\\\",\\\"alt\\\":\\\"Survitec SOLAS-25P - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Survitec+SOLAS-25P+3\\\",\\\"alt\\\":\\\"Survitec SOLAS-25P - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Survitec+SOLAS-25P+4\\\",\\\"alt\\\":\\\"Survitec SOLAS-25P - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4}]\"','\"[\\\"life raft\\\",\\\"safety\\\",\\\"solas\\\",\\\"emergency\\\"]\"','SOLAS Life Raft - 25 Person Capacity - Marine Equipment Nigeria','SOLAS approved inflatable life raft suitable for commercial vessels. Includes survival equipment, emergency rations, and signaling devices. Recently inspected a',NULL,'2025-10-24 19:54:21','2025-09-17 19:54:21','2025-09-17 19:54:21',NULL,NULL,NULL),(12,6,5,'Bronze Propeller - 28\" x 22\" 4-Blade','High-quality manganese bronze propeller perfect for displacement hulls. Excellent condition with minor surface scratches. Suitable for engines 200-400 HP.','Michigan Wheel','Dyna-Quad',2019,'good',485000.00,'NGN',0,0,'\"{\\\"Diameter\\\":\\\"28 inches\\\",\\\"Pitch\\\":\\\"22 inches\\\",\\\"Blades\\\":\\\"4\\\",\\\"Material\\\":\\\"Manganese Bronze\\\",\\\"Bore\\\":\\\"2.5 inches\\\",\\\"Rotation\\\":\\\"Right Hand\\\"}\"','\"[\\\"Balanced\\\",\\\"Polished Finish\\\",\\\"Keyway Cut\\\",\\\"Hub Included\\\"]\"','Cross River','Calabar','230 Marine Street',6.34159000,8.46108000,0,0,NULL,43745.00,'+234-847-784-1957','grace@coastalequip.ng','+234-847-784-1957','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,1,'active',0,0,NULL,1,390,4,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+1\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+2\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+3\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+4\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+5\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 5\\\",\\\"is_primary\\\":false,\\\"order\\\":5},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Michigan+Wheel+Dyna-Quad+6\\\",\\\"alt\\\":\\\"Michigan Wheel Dyna-Quad - Image 6\\\",\\\"is_primary\\\":false,\\\"order\\\":6}]\"','\"[\\\"propeller\\\",\\\"bronze\\\",\\\"4-blade\\\",\\\"michigan wheel\\\"]\"','Bronze Propeller - 28\" x 22\" 4-Blade - Marine Equipment Nigeria','High-quality manganese bronze propeller perfect for displacement hulls. Excellent condition with minor surface scratches. Suitable for engines 200-400 HP.',NULL,'2025-10-18 19:54:21','2025-09-17 19:54:21','2025-09-17 19:54:21',NULL,NULL,NULL),(13,6,4,'Icom IC-M506 VHF Marine Radio with AIS','Professional VHF marine radio with built-in AIS receiver and GPS. Features Class D DSC, emergency functions, and crystal-clear communication.','Icom','IC-M506',2023,'new',185000.00,'NGN',0,0,'\"{\\\"Channels\\\":\\\"All International\\\\\\/USCG\\\",\\\"Power Output\\\":\\\"25W\\\",\\\"AIS\\\":\\\"Built-in AIS Receiver\\\",\\\"GPS\\\":\\\"Internal GPS\\\",\\\"Display\\\":\\\"2.3\\\\\\\" Color LCD\\\"}\"','\"[\\\"DSC Class D\\\",\\\"Man Overboard\\\",\\\"Noise Cancelling\\\",\\\"Active Noise Control\\\"]\"','Cross River','Calabar','230 Marine Street',5.40839000,9.04777000,0,0,NULL,14790.00,'+234-847-784-1957','grace@coastalequip.ng','+234-847-784-1957','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'draft',0,0,NULL,1,383,12,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+1\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+2\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Icom+IC-M506+3\\\",\\\"alt\\\":\\\"Icom IC-M506 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3}]\"','\"[\\\"vhf radio\\\",\\\"icom\\\",\\\"ais\\\",\\\"marine communication\\\"]\"','Icom IC-M506 VHF Marine Radio with AIS - Marine Equipment Nigeria','Professional VHF marine radio with built-in AIS receiver and GPS. Features Class D DSC, emergency functions, and crystal-clear communication.','2025-09-12 19:54:21','2025-11-28 19:54:21','2025-09-17 19:54:21','2025-09-17 19:54:21',NULL,NULL,NULL),(14,6,7,'Victron MultiPlus 12/3000/120 Inverter/Charger','Professional marine inverter/charger system with advanced battery management. Perfect for yachts and commercial vessels requiring reliable shore power connection.','Victron Energy','MultiPlus 12/3000/120',2022,'excellent',425000.00,'NGN',0,0,'\"{\\\"Input Voltage\\\":\\\"12V DC\\\",\\\"Output Power\\\":\\\"3000W continuous\\\",\\\"Charger Current\\\":\\\"120A\\\",\\\"Efficiency\\\":\\\"94%\\\",\\\"Protection\\\":\\\"IP21\\\"}\"','\"[\\\"PowerAssist Technology\\\",\\\"Remote Monitoring\\\",\\\"Battery Temperature Sensor\\\",\\\"Adaptive Charging\\\"]\"','Cross River','Calabar','230 Marine Street',4.80956000,8.87884000,0,1,285,NULL,'+234-847-784-1957','grace@coastalequip.ng','+234-847-784-1957','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'active',0,0,NULL,1,226,10,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+1\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+2\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+3\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+4\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+5\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 5\\\",\\\"is_primary\\\":false,\\\"order\\\":5},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Victron+Energy+MultiPlus+12%2F3000%2F120+6\\\",\\\"alt\\\":\\\"Victron Energy MultiPlus 12\\\\\\/3000\\\\\\/120 - Image 6\\\",\\\"is_primary\\\":false,\\\"order\\\":6}]\"','\"[\\\"inverter\\\",\\\"charger\\\",\\\"victron\\\",\\\"marine power\\\"]\"','Victron MultiPlus 12/3000/120 Inverter/Charger - Marine Equipment Nigeria','Professional marine inverter/charger system with advanced battery management. Perfect for yachts and commercial vessels requiring reliable shore power connectio',NULL,'2025-11-04 19:54:21','2025-09-17 19:54:21','2025-09-17 19:54:21',NULL,NULL,NULL),(15,3,6,'Lewmar V700 Electric Windlass - 700W','Heavy-duty electric anchor windlass suitable for boats up to 12 meters. Includes chain gypsy and rope drum. Recently overhauled with new motor brushes.','Lewmar','V700',2020,'good',320000.00,'NGN',0,0,'\"{\\\"Motor Power\\\":\\\"700W\\\",\\\"Pulling Power\\\":\\\"700 kg\\\",\\\"Chain Size\\\":\\\"8-10mm\\\",\\\"Rope Diameter\\\":\\\"12-16mm\\\",\\\"Voltage\\\":\\\"12V DC\\\"}\"','\"[\\\"Remote Control\\\",\\\"Manual Override\\\",\\\"Chain Counter\\\",\\\"Weatherproof\\\"]\"','Lagos','Lagos','132 Marine Street',6.46538000,3.35485000,0,0,NULL,NULL,'+234-889-637-9003','john@oceanmarine.africa','+234-889-637-9003','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,1,'active',1,0,NULL,1,366,8,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+1\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+2\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Lewmar+V700+3\\\",\\\"alt\\\":\\\"Lewmar V700 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3}]\"','\"[\\\"windlass\\\",\\\"anchor\\\",\\\"lewmar\\\",\\\"electric\\\"]\"','Lewmar V700 Electric Windlass - 700W - Marine Equipment Nigeria','Heavy-duty electric anchor windlass suitable for boats up to 12 meters. Includes chain gypsy and rope drum. Recently overhauled with new motor brushes.',NULL,'2025-12-15 19:54:21','2025-09-17 19:54:21','2025-09-17 19:54:21',NULL,NULL,NULL),(16,6,8,'Rule-Mate 1100 GPH Automatic Bilge Pump','Reliable automatic bilge pump with built-in float switch. Perfect for continuous bilge monitoring and water removal. Includes installation hardware and manual.','Rule','Rule-Mate 1100',2023,'new',45000.00,'NGN',0,0,'\"{\\\"Flow Rate\\\":\\\"1100 GPH\\\",\\\"Voltage\\\":\\\"12V DC\\\",\\\"Current Draw\\\":\\\"4.2A\\\",\\\"Outlet\\\":\\\"1-1\\\\\\/8\\\\\\\" hose\\\",\\\"Dimensions\\\":\\\"8\\\\\\\" x 3\\\\\\\" x 6\\\\\\\"\\\"}\"','\"[\\\"Automatic Float Switch\\\",\\\"Ignition Protection\\\",\\\"Corrosion Resistant\\\",\\\"Quick Disconnect\\\"]\"','Cross River','Calabar','230 Marine Street',5.39620000,8.85992000,0,0,89,NULL,'+234-847-784-1957','grace@coastalequip.ng','+234-847-784-1957','\"[\\\"phone\\\",\\\"email\\\",\\\"whatsapp\\\"]\"','\"{\\\"monday\\\":\\\"08:00-18:00\\\",\\\"tuesday\\\":\\\"08:00-18:00\\\",\\\"wednesday\\\":\\\"08:00-18:00\\\",\\\"thursday\\\":\\\"08:00-18:00\\\",\\\"friday\\\":\\\"08:00-18:00\\\",\\\"saturday\\\":\\\"09:00-17:00\\\"}\"',1,0,'active',0,0,NULL,1,266,17,'\"[{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Rule+Rule-Mate+1100+1\\\",\\\"alt\\\":\\\"Rule Rule-Mate 1100 - Image 1\\\",\\\"is_primary\\\":true,\\\"order\\\":1},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Rule+Rule-Mate+1100+2\\\",\\\"alt\\\":\\\"Rule Rule-Mate 1100 - Image 2\\\",\\\"is_primary\\\":false,\\\"order\\\":2},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Rule+Rule-Mate+1100+3\\\",\\\"alt\\\":\\\"Rule Rule-Mate 1100 - Image 3\\\",\\\"is_primary\\\":false,\\\"order\\\":3},{\\\"url\\\":\\\"https:\\\\\\/\\\\\\/via.placeholder.com\\\\\\/800x600\\\\\\/0066cc\\\\\\/ffffff?text=Rule+Rule-Mate+1100+4\\\",\\\"alt\\\":\\\"Rule Rule-Mate 1100 - Image 4\\\",\\\"is_primary\\\":false,\\\"order\\\":4}]\"','\"[\\\"bilge pump\\\",\\\"automatic\\\",\\\"rule\\\",\\\"water pump\\\"]\"','Rule-Mate 1100 GPH Automatic Bilge Pump - Marine Equipment Nigeria','Reliable automatic bilge pump with built-in float switch. Perfect for continuous bilge monitoring and water removal. Includes installation hardware and manual.','2025-09-13 19:54:21','2025-12-12 19:54:21','2025-09-17 19:54:21','2025-09-17 19:54:21',NULL,NULL,NULL);
/*!40000 ALTER TABLE `equipment_listings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint(20) unsigned DEFAULT NULL,
  `inquirer_id` bigint(20) unsigned DEFAULT NULL,
  `inquirer_name` varchar(255) NOT NULL,
  `inquirer_email` varchar(255) NOT NULL,
  `inquirer_phone` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `budget_range` varchar(255) DEFAULT NULL,
  `status` enum('pending','responded','closed') NOT NULL DEFAULT 'pending',
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inquiries_listing_id_foreign` (`listing_id`),
  KEY `inquiries_inquirer_id_foreign` (`inquirer_id`),
  CONSTRAINT `inquiries_inquirer_id_foreign` FOREIGN KEY (`inquirer_id`) REFERENCES `user_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inquiries_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `equipment_listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `seller_application_id` bigint(20) unsigned DEFAULT NULL,
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
  `invoice_type` enum('subscription','commission','penalty','seller_application','other') NOT NULL DEFAULT 'other',
  `discount_type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `terms_and_conditions` text DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `company_name` varchar(255) DEFAULT NULL,
  `generated_by` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_notes` text DEFAULT NULL,
  `payment_proof_public_id` varchar(255) DEFAULT NULL,
  `payment_proof_url` varchar(255) DEFAULT NULL,
  `payment_submitted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_seller_application_id_foreign` (`seller_application_id`),
  KEY `invoices_plan_id_foreign` (`plan_id`),
  KEY `invoices_user_id_status_index` (`user_id`,`status`),
  KEY `invoices_status_due_date_index` (`status`,`due_date`),
  KEY `invoices_invoice_type_index` (`invoice_type`),
  KEY `invoices_created_at_index` (`created_at`),
  CONSTRAINT `invoices_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_seller_application_id_foreign` FOREIGN KEY (`seller_application_id`) REFERENCES `seller_applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `type` enum('text','offer','system','attachment') NOT NULL DEFAULT 'text',
  `status` enum('sent','delivered','read') NOT NULL DEFAULT 'sent',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `offer_price` decimal(15,2) DEFAULT NULL,
  `offer_currency` varchar(3) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_created_at_index` (`conversation_id`,`created_at`),
  KEY `messages_sender_id_created_at_index` (`sender_id`,`created_at`),
  KEY `messages_status_created_at_index` (`status`,`created_at`),
  KEY `messages_read_at_index` (`read_at`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_08_29_094558_create_personal_access_tokens_table',1),(5,'2025_08_29_094741_create_user_profiles_table',1),(6,'2025_08_29_094853_create_equipment_categories_table',1),(7,'2025_08_29_094856_create_equipment_listings_table',1),(8,'2025_08_29_095612_create_subscription_plans_table',1),(9,'2025_08_29_095614_create_user_subscriptions_table',1),(10,'2025_08_29_095615_create_subscription_usage_table',1),(11,'2025_08_29_095700_create_conversations_table',1),(12,'2025_08_29_095702_create_messages_table',1),(13,'2025_08_29_095704_create_banners_table',1),(14,'2025_08_29_095756_create_system_settings_table',1),(15,'2025_08_29_095757_create_inquiries_table',1),(16,'2025_08_29_095759_create_user_favorites_table',1),(17,'2025_09_02_140000_create_seller_profiles_table',1),(18,'2025_09_02_140100_create_seller_reviews_table',1),(19,'2025_09_02_140200_create_seller_applications_table',1),(20,'2025_09_02_195309_create_roles_table',1),(21,'2025_09_02_195356_add_role_id_to_users_table',1),(22,'2025_09_03_010603_add_missing_columns_to_system_settings_table',1),(23,'2025_09_13_010929_create_invoices_table',2),(24,'2025_09_13_113608_add_payment_proof_columns_to_invoices_table',2),(25,'2025_09_16_150124_create_orders_table',2),(26,'2025_09_16_150447_create_payments_table',2),(27,'2025_09_16_161251_add_payment_fields_to_banners_table',2),(28,'2025_09_16_161334_create_banner_pricing_table',2),(29,'2025_09_16_232409_add_inquiry_fields_to_inquiries_table',2),(30,'2025_09_16_234204_add_jumia_style_banner_fields',2),(31,'2025_09_16_234737_update_banner_position_column',2),(32,'2025_09_16_234919_update_banner_enum_columns',2),(33,'2025_09_17_123910_add_moderation_fields_to_equipment_listings_table',2),(34,'2025_09_17_130933_add_priority_and_featured_columns_to_equipment_listings_table',2),(35,'2025_09_17_211827_create_newsletter_templates_table',3),(36,'2025_09_17_212701_create_newsletters_table',3),(37,'2025_09_18_124903_create_email_configs_table',3),(38,'2025_09_22_181545_create_app_brandings_table',4),(39,'2025_09_22_183749_create_backups_table',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_templates`
--

DROP TABLE IF EXISTS `newsletter_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_template` varchar(255) NOT NULL,
  `html_template` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_templates`
--

LOCK TABLES `newsletter_templates` WRITE;
/*!40000 ALTER TABLE `newsletter_templates` DISABLE KEYS */;
INSERT INTO `newsletter_templates` VALUES (2,'Featured Listings Weekly','A weekly newsletter showcasing top marine equipment listings.','Marine.africa Weekly Newsletter - 4 Featured Listings','<html><body><h1>Welcome to Marine.africa</h1><p>We have 4 new featured listings this week!</p></body></html>','2025-09-18 15:04:01','2025-09-18 15:04:01'),(3,'Featured Listings Weekly','A weekly newsletter showcasing top marine equipment listings.','Marine.africa Weekly Newsletter - 4 Featured Listings','<html><body><h1>Welcome to Marine.africa</h1><p>We have 4 new featured listings this week!</p></body></html>','2025-09-18 16:22:10','2025-09-18 16:22:10'),(4,'Featured Listings Weekly','A weekly newsletter showcasing top marine equipment listings.','Marine.africa Weekly Newsletter - 4 Featured Listings','<html><body><h1>Welcome to Marine.africa</h1><p>We have 4 new featured listings this week!</p></body></html>','2025-09-18 16:25:52','2025-09-18 16:25:52'),(5,'Featured Listings Weekly','A weekly newsletter showcasing top marine equipment listings.','Marine.africa Weekly Newsletter - 4 Featured Listings','<html><body><h1>Welcome to Marine.africa</h1><p>We have 4 new featured listings this week!</p></body></html>','2025-09-18 16:26:46','2025-09-18 16:26:46');
/*!40000 ALTER TABLE `newsletter_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletters`
--

DROP TABLE IF EXISTS `newsletters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `use_default_template` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_for` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `newsletters_template_id_foreign` (`template_id`),
  CONSTRAINT `newsletters_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `newsletter_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletters`
--

LOCK TABLES `newsletters` WRITE;
/*!40000 ALTER TABLE `newsletters` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(255) NOT NULL,
  `buyer_id` bigint(20) unsigned NOT NULL,
  `seller_id` bigint(20) unsigned NOT NULL,
  `equipment_listing_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `amount` decimal(15,2) NOT NULL,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NGN',
  `delivery_method` enum('pickup','shipping','courier') NOT NULL DEFAULT 'pickup',
  `delivery_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`delivery_address`)),
  `billing_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`billing_address`)),
  `estimated_delivery` datetime DEFAULT NULL,
  `actual_delivery` datetime DEFAULT NULL,
  `payment_status` varchar(255) NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_due_date` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `buyer_notes` text DEFAULT NULL,
  `seller_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_equipment_listing_id_foreign` (`equipment_listing_id`),
  KEY `orders_buyer_id_status_index` (`buyer_id`,`status`),
  KEY `orders_seller_id_status_index` (`seller_id`,`status`),
  KEY `orders_status_created_at_index` (`status`,`created_at`),
  KEY `orders_payment_status_index` (`payment_status`),
  KEY `orders_order_number_index` (`order_number`),
  CONSTRAINT `orders_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_equipment_listing_id_foreign` FOREIGN KEY (`equipment_listing_id`) REFERENCES `equipment_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_reference` varchar(255) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `payable_type` varchar(255) NOT NULL,
  `payable_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NGN',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `gateway` varchar(255) DEFAULT NULL,
  `gateway_reference` varchar(255) DEFAULT NULL,
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(255) DEFAULT NULL,
  `gateway_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `platform_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL,
  `initiated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `refund_amount` decimal(15,2) DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_payment_reference_unique` (`payment_reference`),
  KEY `payments_payable_type_payable_id_index` (`payable_type`,`payable_id`),
  KEY `payments_user_id_status_index` (`user_id`,`status`),
  KEY `payments_status_created_at_index` (`status`,`created_at`),
  KEY `payments_gateway_gateway_reference_index` (`gateway`,`gateway_reference`),
  KEY `payments_payment_reference_index` (`payment_reference`),
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'auth_token','cee62238d1fc51a5aedc152e677cac77dc34634f410bee283791e30c50f4f389','[\"*\"]','2025-09-23 14:22:35',NULL,'2025-09-18 14:57:31','2025-09-23 14:22:35');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Administrator','Full access to all system features and administrative functions','[\"manage_users\",\"manage_roles\",\"manage_sellers\",\"manage_listings\",\"view_analytics\",\"manage_system_settings\",\"moderate_content\",\"manage_transactions\",\"access_admin_dashboard\"]',1,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(2,'seller','Seller','Can create and manage product listings, view sales analytics','[\"create_listings\",\"edit_own_listings\",\"delete_own_listings\",\"view_own_analytics\",\"manage_own_profile\",\"respond_to_messages\",\"access_seller_dashboard\"]',1,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(3,'user','User','Regular user who can browse and purchase products','[\"browse_products\",\"purchase_products\",\"manage_own_profile\",\"send_messages\",\"leave_reviews\",\"save_favorites\",\"access_user_dashboard\"]',1,'2025-09-17 19:45:11','2025-09-17 19:45:11');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_applications`
--

DROP TABLE IF EXISTS `seller_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_description` text NOT NULL,
  `business_registration_number` varchar(255) DEFAULT NULL,
  `tax_identification_number` varchar(255) DEFAULT NULL,
  `business_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`business_documents`)),
  `specialties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`specialties`)),
  `years_experience` int(11) NOT NULL,
  `previous_platforms` varchar(255) DEFAULT NULL,
  `motivation` text DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_applications_reviewed_by_foreign` (`reviewed_by`),
  KEY `seller_applications_user_id_status_index` (`user_id`,`status`),
  KEY `seller_applications_status_index` (`status`),
  CONSTRAINT `seller_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `seller_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_applications`
--

LOCK TABLES `seller_applications` WRITE;
/*!40000 ALTER TABLE `seller_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_profiles`
--

DROP TABLE IF EXISTS `seller_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `business_description` text DEFAULT NULL,
  `specialties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specialties`)),
  `years_active` int(11) NOT NULL DEFAULT 0,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `review_count` int(11) NOT NULL DEFAULT 0,
  `total_listings` int(11) NOT NULL DEFAULT 0,
  `response_time` varchar(255) NOT NULL DEFAULT '24 hours',
  `avg_response_minutes` int(11) NOT NULL DEFAULT 1440,
  `verification_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_documents`)),
  `verification_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `featured_priority` int(11) NOT NULL DEFAULT 0,
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `website` varchar(255) DEFAULT NULL,
  `social_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_media`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_profiles_user_id_foreign` (`user_id`),
  KEY `seller_profiles_verification_status_rating_index` (`verification_status`,`rating`),
  KEY `seller_profiles_is_featured_featured_priority_index` (`is_featured`,`featured_priority`),
  CONSTRAINT `seller_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_profiles`
--

LOCK TABLES `seller_profiles` WRITE;
/*!40000 ALTER TABLE `seller_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_reviews`
--

DROP TABLE IF EXISTS `seller_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `listing_id` bigint(20) unsigned DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `review_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`review_categories`)),
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_reviews_seller_id_reviewer_id_listing_id_unique` (`seller_id`,`reviewer_id`,`listing_id`),
  KEY `seller_reviews_reviewer_id_foreign` (`reviewer_id`),
  KEY `seller_reviews_listing_id_foreign` (`listing_id`),
  KEY `seller_reviews_seller_id_rating_index` (`seller_id`,`rating`),
  CONSTRAINT `seller_reviews_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `equipment_listings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `seller_reviews_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seller_reviews_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `seller_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_reviews`
--

LOCK TABLES `seller_reviews` WRITE;
/*!40000 ALTER TABLE `seller_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `tier` enum('freemium','premium','enterprise') NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` enum('monthly','yearly','lifetime') NOT NULL DEFAULT 'monthly',
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `limits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`limits`)),
  `max_listings` int(11) NOT NULL DEFAULT 0,
  `max_images_per_listing` int(11) NOT NULL DEFAULT 1,
  `priority_support` tinyint(1) NOT NULL DEFAULT 0,
  `analytics_access` tinyint(1) NOT NULL DEFAULT 0,
  `custom_branding` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_plans_created_by_foreign` (`created_by`),
  KEY `subscription_plans_tier_is_active_index` (`tier`,`is_active`),
  KEY `subscription_plans_is_active_sort_order_index` (`is_active`,`sort_order`),
  CONSTRAINT `subscription_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `user_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
INSERT INTO `subscription_plans` VALUES (1,'Freemium Plan','freemium',0.00,'monthly','[\"Up to 2 equipment listings\",\"Basic seller profile\",\"Email support\",\"Standard listing visibility\"]','{\"listings\":2,\"images_per_listing\":5,\"featured_listings\":0}',2,5,0,0,0,1,1,'Free plan with basic features for individual sellers',NULL,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(2,'Premium Plan','premium',7500.00,'monthly','[\"Up to 25 equipment listings\",\"Enhanced seller profile with company branding\",\"Priority email support\",\"Featured listing slots (2 per month)\",\"Advanced analytics dashboard\",\"Bulk upload capabilities\"]','{\"listings\":25,\"images_per_listing\":20,\"featured_listings\":2}',25,20,1,1,0,1,2,'Ideal for established marine equipment dealers',NULL,'2025-09-17 19:45:11','2025-09-17 19:45:11'),(3,'Enterprise Plan','enterprise',20000.00,'monthly','[\"Unlimited equipment listings\",\"Full company profile customization\",\"Dedicated account manager\",\"Unlimited featured listings\",\"Advanced analytics & reporting\",\"API access for integrations\",\"Custom branding options\",\"Priority customer support (24\\/7)\"]','{\"listings\":-1,\"images_per_listing\":50,\"featured_listings\":-1}',-1,50,1,1,1,1,3,'Comprehensive solution for large marine equipment companies',NULL,'2025-09-17 19:45:11','2025-09-17 19:45:11');
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_usage`
--

DROP TABLE IF EXISTS `subscription_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_usage`
--

LOCK TABLES `subscription_usage` WRITE;
/*!40000 ALTER TABLE `subscription_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`),
  KEY `system_settings_is_public_index` (`is_public`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'site_name','Marine.africa','string','The name of the website',1,'2025-09-17 19:44:51','2025-09-17 19:44:51'),(2,'site_description','Africa\'s Premier Marine Equipment Marketplace','string','Site description for SEO',1,'2025-09-17 19:44:51','2025-09-17 19:44:51'),(3,'contact_email','info@marine.africa','string','Main contact email',1,'2025-09-17 19:44:51','2025-09-17 19:44:51'),(4,'contact_phone','+234-800-MARINE','string','Main contact phone',1,'2025-09-17 19:44:51','2025-09-17 19:44:51'),(5,'social_facebook','https://facebook.com/marineng','string','Facebook page URL',1,'2025-09-17 19:44:51','2025-09-17 19:44:51'),(6,'social_twitter','https://twitter.com/marineng','string','Twitter profile URL',1,'2025-09-17 19:44:51','2025-09-17 19:44:51'),(7,'social_instagram','https://instagram.com/marineng','string','Instagram profile URL',1,'2025-09-17 19:44:51','2025-09-17 19:44:51'),(8,'maintenance_mode','false','boolean','Enable/disable maintenance mode',1,'2025-09-17 19:44:52','2025-09-17 19:44:52'),(9,'items_per_page','20','integer','Default items per page',0,'2025-09-17 19:44:52','2025-09-17 19:44:52'),(10,'max_upload_size','10485760','integer','Maximum upload size in bytes (10MB)',0,'2025-09-17 19:44:52','2025-09-17 19:44:52'),(11,'currency','NGN','string','Default currency',1,'2025-09-17 19:44:52','2025-09-17 19:44:52'),(12,'timezone','Africa/Lagos','string','Default timezone',1,'2025-09-17 19:44:52','2025-09-17 19:44:52');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_favorites`
--

DROP TABLE IF EXISTS `user_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `listing_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_favorites_user_id_listing_id_unique` (`user_id`,`listing_id`),
  KEY `user_favorites_listing_id_foreign` (`listing_id`),
  KEY `user_favorites_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `user_favorites_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `equipment_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_favorites`
--

LOCK TABLES `user_favorites` WRITE;
/*!40000 ALTER TABLE `user_favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('admin','user','moderator','seller','buyer') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `company_name` varchar(255) DEFAULT NULL,
  `company_description` text DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'Nigeria',
  `avatar` varchar(255) DEFAULT NULL,
  `verification_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_documents`)),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_profiles_user_id_role_index` (`user_id`,`role`),
  KEY `user_profiles_is_active_index` (`is_active`),
  KEY `user_profiles_is_verified_index` (`is_verified`),
  CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
INSERT INTO `user_profiles` VALUES (1,1,'System Administrator','admin',1,'Marine Engineering Nigeria','Leading marine equipment marketplace in Nigeria','+234-800-123-4567','123 Marina Street','Lagos','Lagos','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:12','2025-09-17 19:45:12'),(2,2,'Test User','user',1,NULL,NULL,'+234-800-123-4568','123 Marina Street','Lagos','Lagos','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:13','2025-09-17 19:45:13'),(3,3,'John Okafor','seller',1,'Ocean Marine Equipment Ltd','Specialized in high-quality marine engines and navigation systems','+234-889-637-9003','132 Marine Street','Lagos','Lagos','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:13','2025-09-17 19:54:12'),(4,4,'Mary Adeleke','seller',1,'Deep Blue Marine Supplies','Your trusted partner for marine safety and communication equipment','+234-926-786-4788','960 Marine Street','Port Harcourt','Rivers','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:14','2025-09-17 19:54:14'),(5,5,'Ibrahim Mohammed','seller',1,'Northern Marine Solutions','Comprehensive marine equipment solutions for the northern region','+234-944-815-7598','286 Marine Street','Kano','Kano','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:15','2025-09-17 19:54:15'),(6,6,'Grace Eze','seller',1,'Coastal Equipment Hub','Premium marine equipment distributor serving the coastal regions','+234-847-784-1957','230 Marine Street','Calabar','Cross River','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:16','2025-09-17 19:54:16'),(7,7,'Captain Samuel Udo','user',1,NULL,NULL,'+234-939-629-6968','529 Fishermen Street','Warri','Delta','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:17','2025-09-17 19:54:18'),(8,8,'Chief Engineer Kemi Balogun','user',1,NULL,NULL,'+234-739-340-8660','26 Fishermen Street','Lagos','Lagos','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:17','2025-09-17 19:54:19'),(9,9,'Fisherman Tunde Alabi','user',1,NULL,NULL,'+234-729-154-7838','605 Fishermen Street','Badagry','Lagos','Nigeria',NULL,NULL,1,NULL,'2025-09-17 19:45:18','2025-09-17 19:54:20');
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_subscriptions`
--

DROP TABLE IF EXISTS `user_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive','cancelled','expired','pending') NOT NULL DEFAULT 'pending',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `payment_method_id` varchar(255) DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_subscriptions_user_id_status_index` (`user_id`,`status`),
  KEY `user_subscriptions_plan_id_status_index` (`plan_id`,`status`),
  KEY `user_subscriptions_expires_at_index` (`expires_at`),
  KEY `user_subscriptions_stripe_subscription_id_index` (`stripe_subscription_id`),
  CONSTRAINT `user_subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_subscriptions`
--

LOCK TABLES `user_subscriptions` WRITE;
/*!40000 ALTER TABLE `user_subscriptions` DISABLE KEYS */;
INSERT INTO `user_subscriptions` VALUES (1,3,2,'active','2025-09-02 19:45:18','2025-10-02 19:45:18',NULL,1,NULL,NULL,NULL,NULL,'2025-09-17 19:45:18','2025-09-17 19:45:18'),(2,4,3,'active','2025-09-12 19:45:18','2025-10-12 19:45:18',NULL,1,NULL,NULL,NULL,NULL,'2025-09-17 19:45:18','2025-09-17 19:45:18'),(3,5,1,'active','2025-09-07 19:45:18','2025-10-07 19:45:18',NULL,0,NULL,NULL,NULL,NULL,'2025-09-17 19:45:18','2025-09-17 19:45:18'),(4,6,2,'active','2025-09-02 19:45:18','2025-10-02 19:45:18',NULL,1,NULL,NULL,NULL,NULL,'2025-09-17 19:45:18','2025-09-17 19:45:18'),(5,5,2,'cancelled','2025-07-19 19:45:18','2025-08-18 19:45:18','2025-08-13 19:45:18',0,NULL,NULL,NULL,NULL,'2025-09-17 19:45:18','2025-09-17 19:45:18'),(6,3,2,'active','2025-09-02 19:54:20','2025-10-02 19:54:20',NULL,1,NULL,NULL,NULL,NULL,'2025-09-17 19:54:20','2025-09-17 19:54:20'),(7,4,3,'active','2025-09-12 19:54:20','2025-10-12 19:54:20',NULL,1,NULL,NULL,NULL,NULL,'2025-09-17 19:54:20','2025-09-17 19:54:20'),(8,5,1,'active','2025-09-07 19:54:20','2025-10-07 19:54:20',NULL,0,NULL,NULL,NULL,NULL,'2025-09-17 19:54:20','2025-09-17 19:54:20'),(9,6,2,'active','2025-09-02 19:54:20','2025-10-02 19:54:20',NULL,1,NULL,NULL,NULL,NULL,'2025-09-17 19:54:20','2025-09-17 19:54:20'),(10,4,2,'cancelled','2025-07-19 19:54:20','2025-08-18 19:54:20','2025-08-13 19:54:20',0,NULL,NULL,NULL,NULL,'2025-09-17 19:54:20','2025-09-17 19:54:20');
/*!40000 ALTER TABLE `user_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'System Administrator','admin@marine.africa','2025-09-17 19:54:09','$2y$12$SsYhAdL0j/yV7wht4Lp5L.lOz.LLHgRoGs.CwQK6ZoYR4IlQ4DGvG',NULL,'2025-09-17 19:45:12','2025-09-17 19:54:09'),(2,3,'Test User','user@marine.africa','2025-09-17 19:54:10','$2y$12$aelhZN2W35DX9FDGcQnpgO3PI8wBFT3gPVoKzSy7vggU4fNjsGweu',NULL,'2025-09-17 19:45:13','2025-09-17 19:54:10'),(3,2,'John Okafor','john@oceanmarine.africa','2025-09-17 19:54:12','$2y$12$3iLc6hsg5WTHJkkbmqzk3.A7bi6e7tFBrrFqokmjKS4AOdPZMm6MK',NULL,'2025-09-17 19:45:13','2025-09-17 19:54:12'),(4,2,'Mary Adeleke','mary@deepblue.ng','2025-09-17 19:54:14','$2y$12$Cx//Cj7WnO24iEbeB.w1UezKOI8IjpbDqeaIThStK3a9A9hMuA.nG',NULL,'2025-09-17 19:45:14','2025-09-17 19:54:14'),(5,2,'Ibrahim Mohammed','ibrahim@northernmarine.africa','2025-09-17 19:54:15','$2y$12$QitsGM6Nx.NFmoqJklmocutcPBejQhP.wcX622evUeLdcCtMIYXsq',NULL,'2025-09-17 19:45:15','2025-09-17 19:54:15'),(6,2,'Grace Eze','grace@coastalequip.ng','2025-09-17 19:54:16','$2y$12$ftW3NSAxsEGbrL89rJIJk.zy0pHJI.VmneYg5vTb38f/uV2N6KWZi',NULL,'2025-09-17 19:45:16','2025-09-17 19:54:16'),(7,3,'Captain Samuel Udo','samuel@fishingfleet.ng','2025-09-17 19:54:18','$2y$12$CYjx6kXd.lLTRSIac/n9z..gHKSNjKuzl1lbNaQCV55aIseIYA.3C',NULL,'2025-09-17 19:45:17','2025-09-17 19:54:18'),(8,3,'Chief Engineer Kemi Balogun','kemi@shippingco.ng','2025-09-17 19:54:19','$2y$12$aFft/QtDg2ySKTUwOlWM0uMfektq0FfDe2u4eRNkQIeXVNZO7Pr4a',NULL,'2025-09-17 19:45:17','2025-09-17 19:54:19'),(9,3,'Fisherman Tunde Alabi','tunde@fisherfolk.ng','2025-09-17 19:54:20','$2y$12$aVlPMpuzMuhN0G.1JCiVhuOjpJIKmOclrOdZHMYKs6Nyjq1p7P226',NULL,'2025-09-17 19:45:18','2025-09-17 19:54:20');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-23 16:23:38
