-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: kantinkita_db
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'login','Login berhasil: admin@kantinkita.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','UNIV','2026-04-06 10:34:03','2026-04-06 10:34:03'),(2,1,'login','Login berhasil: admin@kantinkita.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0','UNIV','2026-04-06 10:36:43','2026-04-06 10:36:43'),(3,1,'logout','Logout: admin@kantinkita.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0','UNIV','2026-04-06 10:37:20','2026-04-06 10:37:20'),(4,2,'login','Login berhasil: owner1@kantinkita.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0','UNIV','2026-04-06 10:37:30','2026-04-06 10:37:30');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
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
INSERT INTO `cache` VALUES ('laravel_cache_5c785c036466adea360111aa28563bfd556b5fba','i:2;',1775497063),('laravel_cache_5c785c036466adea360111aa28563bfd556b5fba:timer','i:1775497063;',1775497063),('laravel_cache_a75f3f172bfb296f2e10cbfc6dfc1883','i:2;',1775497063),('laravel_cache_a75f3f172bfb296f2e10cbfc6dfc1883:timer','i:1775497063;',1775497063),('laravel_cache_f1f70ec40aaa556905d4a030501c0ba4','i:4;',1775497066),('laravel_cache_f1f70ec40aaa556905d4a030501c0ba4:timer','i:1775497066;',1775497066);
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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `categories_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,1,'Nasi & Lauk',NULL,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(2,1,'Mie & Pasta',NULL,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(3,1,'Minuman',NULL,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(4,1,'Snack',NULL,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(5,1,'Dessert',NULL,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(6,2,'Makanan Sehat',NULL,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(7,2,'Jus & Smoothie',NULL,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(8,2,'Minuman Sehat',NULL,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_versions`
--

DROP TABLE IF EXISTS `config_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` int(11) NOT NULL,
  `changed_key` varchar(100) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` varchar(100) NOT NULL,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_versions`
--

LOCK TABLES `config_versions` WRITE;
/*!40000 ALTER TABLE `config_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `config_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `level` varchar(20) NOT NULL DEFAULT 'error',
  `message` text NOT NULL,
  `stack_trace` longtext DEFAULT NULL,
  `endpoint` varchar(500) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `resolved_status` enum('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  `resolved_by` varchar(100) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `error_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `error_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_logs`
--

LOCK TABLES `error_logs` WRITE;
/*!40000 ALTER TABLE `error_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_logs` ENABLE KEYS */;
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
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `photo` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menus_tenant_id_foreign` (`tenant_id`),
  KEY `menus_category_id_foreign` (`category_id`),
  CONSTRAINT `menus_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `menus_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES (1,1,1,'Nasi Ayam Goreng',NULL,15000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(2,1,1,'Nasi Rendang',NULL,18000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(3,1,1,'Nasi Capcay',NULL,13000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(4,1,1,'Nasi Telur Balado',NULL,12000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(5,1,2,'Mie Goreng Spesial',NULL,13000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(6,1,2,'Mie Rebus',NULL,12000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(7,1,2,'Kwetiau Goreng',NULL,15000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(8,1,3,'Es Teh Manis',NULL,5000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(9,1,3,'Es Jeruk',NULL,7000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(10,1,3,'Air Mineral',NULL,4000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(11,1,3,'Jus Alpukat',NULL,12000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(12,1,4,'Gorengan Mix',NULL,8000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(13,1,4,'Tempe Mendoan',NULL,5000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(14,1,5,'Puding Coklat',NULL,8000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(15,1,5,'Es Cendol',NULL,7000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(16,2,6,'Salad Buah',NULL,15000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(17,2,6,'Nasi Merah + Ayam Bakar',NULL,22000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(18,2,6,'Gado-Gado',NULL,18000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(19,2,7,'Jus Alpukat',NULL,15000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(20,2,7,'Smoothie Mangga',NULL,18000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(21,2,7,'Jus Semangka',NULL,12000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(22,2,8,'Air Kelapa Muda',NULL,10000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(23,2,8,'Teh Hijau',NULL,8000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(24,2,8,'Infused Water',NULL,7000.00,NULL,1,0,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44');
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_01_01_000010_create_kantinkita_tables',1),(5,'2026_04_06_165654_create_personal_access_tokens_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `menu_id` bigint(20) unsigned DEFAULT NULL,
  `menu_name` varchar(200) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_menu_id_foreign` (`menu_id`),
  CONSTRAINT `order_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `status` enum('cart','pending_payment','paid','processing','completed','expired','cancelled','refunded') NOT NULL DEFAULT 'cart',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `service_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `orders_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
  `order_id` bigint(20) unsigned NOT NULL,
  `transaction_id` varchar(200) DEFAULT NULL,
  `snap_token` varchar(500) DEFAULT NULL,
  `payment_type` varchar(100) DEFAULT NULL,
  `status` enum('pending','paid','expired','refunded','failed') NOT NULL DEFAULT 'pending',
  `gross_amount` decimal(12,2) NOT NULL,
  `midtrans_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`midtrans_response`)),
  `paid_at` timestamp NULL DEFAULT NULL,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_order_id_unique` (`order_id`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (3,'App\\Models\\User',2,'auth_token','f98d8ef51795512ec7b9e818f55ffaeb3e07d6e3626bd03c76ad378f0bfaeb88','[\"*\"]',NULL,NULL,'2026-04-06 10:37:30','2026-04-06 10:37:30');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
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
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `plan` varchar(50) NOT NULL,
  `billing_start` date NOT NULL,
  `billing_end` date NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `billing_status` enum('active','expired','cancelled','trial') NOT NULL DEFAULT 'trial',
  `invoice_number` varchar(100) DEFAULT NULL,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `subscriptions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES (1,1,'professional','2026-04-01','2026-05-31',299000.00,'active',NULL,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(2,2,'starter','2026-04-01','2026-05-31',99000.00,'active',NULL,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'string',
  `group` varchar(50) NOT NULL DEFAULT 'general',
  `label` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'app_name','KantinKita','string','general','Nama Aplikasi',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(2,'app_logo',NULL,'string','general','Logo Aplikasi',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(3,'fee_type','percentage','string','payment','Tipe Fee (percentage/fixed)',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(4,'fee_value','5','float','payment','Nilai Fee',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(5,'fee_label','Biaya Layanan','string','payment','Label Fee',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(6,'payment_timeout','30','integer','payment','Timeout Pembayaran (menit)',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(7,'trial_days','14','integer','subscription','Masa Trial (hari)',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(8,'price_starter','99000','integer','subscription','Harga Paket Starter',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(9,'price_professional','299000','integer','subscription','Harga Paket Professional',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(10,'price_enterprise','799000','integer','subscription','Harga Paket Enterprise',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(11,'notif_order_created','1','boolean','notification','Notif Order Dibuat',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(12,'notif_order_paid','1','boolean','notification','Notif Order Dibayar',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(13,'notif_order_processing','1','boolean','notification','Notif Order Diproses',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(14,'notif_order_completed','1','boolean','notification','Notif Order Selesai',NULL,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_user`
--

DROP TABLE IF EXISTS `tenant_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenant_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_user_tenant_id_user_id_unique` (`tenant_id`,`user_id`),
  KEY `tenant_user_user_id_foreign` (`user_id`),
  CONSTRAINT `tenant_user_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_user`
--

LOCK TABLES `tenant_user` WRITE;
/*!40000 ALTER TABLE `tenant_user` DISABLE KEYS */;
INSERT INTO `tenant_user` VALUES (1,1,4,NULL,NULL);
/*!40000 ALTER TABLE `tenant_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `tenant_name` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `min_order` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_open` tinyint(1) NOT NULL DEFAULT 0,
  `open_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`open_hours`)),
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_slug_unique` (`slug`),
  KEY `tenants_user_id_foreign` (`user_id`),
  CONSTRAINT `tenants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (1,2,'Warung Makan Barokah','warung-makan-barokah','Masakan rumahan enak dan murah, cocok untuk mahasiswa.','Gedung A Lt.1, Kampus UNIV','081211110001',NULL,NULL,10000.00,1,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44'),(2,3,'Kantin Sehat Ibu Siti','kantin-sehat-ibu-siti','Menu sehat dan bergizi untuk sivitas akademika.','Gedung B Lt.2, Kampus UNIV','081222220002',NULL,NULL,15000.00,1,NULL,1,0,'UNIV','system','system','2026-04-06 10:32:44','2026-04-06 10:32:44');
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','owner','staff','customer') NOT NULL DEFAULT 'customer',
  `email_notif` tinyint(1) NOT NULL DEFAULT 1,
  `wa_notif` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `company_code` varchar(50) NOT NULL DEFAULT 'UNIV',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','Administrator KantinKita','Administrator KantinKita','admin@kantinkita.com','081200000000','admin',1,0,1,0,'UNIV','system','system',NULL,'$2y$12$hZxHHa1YrizFpDLmVHKhJuw16JGWf2kbDigIZ4w5kWXvImrRm/d4i',NULL,'2026-04-06 10:32:43','2026-04-06 10:32:43'),(2,'owner1','Budi Santoso','Budi Santoso','owner1@kantinkita.com','081211111111','owner',1,1,1,0,'UNIV','system','system',NULL,'$2y$12$QIjjI464PpabsgeQvHtNcOr2G22Ss.qnC6BW7ibp9XKIWCvIWvhom',NULL,'2026-04-06 10:32:44','2026-04-06 10:32:44'),(3,'owner2','Siti Rahayu','Siti Rahayu','owner2@kantinkita.com','081222222222','owner',1,0,1,0,'UNIV','system','system',NULL,'$2y$12$P6OqkLiphu/3.fHlimLor.X9O3RqwCB0rx.vuAKmuBIR/.1oE/NlS',NULL,'2026-04-06 10:32:44','2026-04-06 10:32:44'),(4,'staff1','Ahmad Staff','Ahmad Staff','staff1@kantinkita.com','081233333333','staff',1,0,1,0,'UNIV','system','system',NULL,'$2y$12$rux3SlUdhde84uyxvRiuueVTTeK2zrHI.jwvXjrB3liKiski7j5Ia',NULL,'2026-04-06 10:32:44','2026-04-06 10:32:44'),(5,'customer1','Dewi Mahasiswi','Dewi Mahasiswi','customer1@kantinkita.com','081244444444','customer',1,1,1,0,'UNIV','system','system',NULL,'$2y$12$H6x/G3fn7fPd5kk.5bYjneZtFEL8R4OAhk1OjDQ4dtKOBv7JcatsO',NULL,'2026-04-06 10:32:44','2026-04-06 10:32:44');
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

-- Dump completed on 2026-04-07  0:38:01
