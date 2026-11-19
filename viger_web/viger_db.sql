-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: viger_db
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `eyebrow` text COLLATE utf8mb4_unicode_ci,
  `desc` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt` text COLLATE utf8mb4_unicode_ci,
  `button_primary_label` text COLLATE utf8mb4_unicode_ci,
  `button_primary_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_secondary_label` text COLLATE utf8mb4_unicode_ci,
  `button_secondary_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES (1,'{\"vi\":\"Miễn học phí – Chất lượng quốc tế\",\"en\":\"Tuition-free – World-class quality\"}','{\"vi\":\"Du học Đức\",\"en\":\"Study in Germany\"}','{\"vi\":\"Khám phá hệ thống giáo dục Đức: học tại các trường công lập uy tín với mức học phí bằng 0 dành cho sinh viên quốc tế.\",\"en\":\"Discover the German education system: study at reputable public universities with zero tuition fees for international students.\"}','https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1920&q=80','{\"vi\":\"Khuôn viên đại học tại Đức\",\"en\":\"University campus in Germany\"}','{\"vi\":\"Khám phá giáo dục Đức\",\"en\":\"Explore German education\"}','/giao-duc-tai-duc','{\"vi\":\"Nhận tư vấn miễn phí\",\"en\":\"Get free advice\"}','/lien-he',1,1,'2026-11-04 22:32:36','2026-11-04 22:32:36'),(2,'{\"vi\":\"Từ chọn ngành đến ngày nhập học tại Đức\",\"en\":\"From choosing a major to enrolling in Germany\"}','{\"vi\":\"Lộ trình cá nhân hóa\",\"en\":\"Personalized roadmap\"}','{\"vi\":\"Đội ngũ chuyên gia từng học tập và làm việc tại Đức đồng hành cùng bạn trong từng bước của hành trình.\",\"en\":\"Our experts, who have studied and worked in Germany, guide you through every step of the journey.\"}','https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1920&q=80','{\"vi\":\"Sinh viên trong khuôn viên trường\",\"en\":\"Students on campus\"}','{\"vi\":\"Xem quy trình tư vấn\",\"en\":\"See our consulting process\"}','/tu-van/quy-trinh-tu-van','{\"vi\":\"Về chúng tôi\",\"en\":\"About us\"}','/ve-chung-toi',2,1,'2026-11-04 22:32:36','2026-11-04 22:32:36'),(3,'{\"vi\":\"Học tập hôm nay, làm việc tại Châu Âu ngày mai\",\"en\":\"Study today, work in Europe tomorrow\"}','{\"vi\":\"Cơ hội nghề nghiệp\",\"en\":\"Career opportunities\"}','{\"vi\":\"Sinh viên quốc tế được ở lại đến 18 tháng sau tốt nghiệp để tìm việc tại nền kinh tế lớn nhất Châu Âu.\",\"en\":\"International students can stay up to 18 months after graduation to find a job in Europe\'s largest economy.\"}','https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?auto=format&fit=crop&w=1920&q=80','{\"vi\":\"Thư viện trường đại học\",\"en\":\"University library\"}','{\"vi\":\"Cơ hội nghề nghiệp\",\"en\":\"Career opportunities\"}','/giao-duc-tai-duc/co-hoi-nghe-nghiep',NULL,NULL,3,1,'2026-11-04 22:32:36','2026-11-04 22:32:36'),(4,'{\"vi\":\"Hồ sơ, visa, tài chính, nhà ở – chúng tôi lo cùng bạn\",\"en\":\"Applications, visa, finances, housing – we handle it with you\"}','{\"vi\":\"Đồng hành trọn gói\",\"en\":\"All-in-one support\"}','{\"vi\":\"Hỗ trợ dịch thuật, APS, tài khoản phong tỏa, luyện phỏng vấn visa và đón tại sân bay Đức.\",\"en\":\"Support with translations, APS, blocked accounts, visa interview practice and airport pick-up in Germany.\"}','https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1920&q=80','{\"vi\":\"Nhóm sinh viên cùng học tập\",\"en\":\"Group of students studying together\"}','{\"vi\":\"Dịch vụ tư vấn\",\"en\":\"Consulting services\"}','/tu-van','{\"vi\":\"Đặt lịch tư vấn\",\"en\":\"Book a consultation\"}','/lien-he',4,1,'2026-11-04 22:32:36','2026-11-04 22:32:36'),(5,'{\"vi\":\"Hơn 500 học viên đã nhập học tại Đức\",\"en\":\"More than 500 students enrolled in Germany\"}','{\"vi\":\"Thành công đã được chứng minh\",\"en\":\"Proven success\"}','{\"vi\":\"Tỷ lệ xin visa thành công 95% cùng hơn 10 năm kinh nghiệm đồng hành với học sinh, sinh viên Việt Nam.\",\"en\":\"A 95% visa success rate and over 10 years of experience supporting Vietnamese students.\"}','https://images.unsplash.com/photo-1599940824399-b87987ceb72a?auto=format&fit=crop&w=1920&q=80','{\"vi\":\"Sinh viên quốc tế tại Đức\",\"en\":\"International students in Germany\"}','{\"vi\":\"Đọc kinh nghiệm từ blog\",\"en\":\"Read experiences on our blog\"}','/blog',NULL,NULL,5,1,'2026-11-04 22:32:36','2026-11-04 22:32:36'),(6,'{\"vi\":\"Trắc Nghiệm Hướng Nghiệp Holland Chuẩn Quốc Tế\",\"en\":\"International Holland Career Test\"}','{\"vi\":\"KHÁM PHÁ TIỀM NĂNG - CHỌN ĐÚNG LỘ TRÌNH\",\"en\":\"DISCOVER YOUR POTENTIAL - CHOOSE THE RIGHT PATH\"}','{\"vi\":\"Bạn chưa biết mình phù hợp với ngành học nào khi du học? Chỉ với 5 phút làm bài trắc nghiệm mật mã Holland, hệ thống sẽ giúp bạn nhận diện nhóm tính cách nổi trội và gợi ý chương trình đào tạo phù hợp nhất.\",\"en\":\"Unsure which major fits you best for studying abroad? Take our 5-minute Holland Code test to identify your dominant personality traits and get personalized study program recommendations.\"}','banners/TUjLd1vSppxQ82pHD1o7oYcAtdx0HzFFZUtGE8n9.png','{\"en\":null}','{\"vi\":\"Làm bài ngay\",\"en\":\"Discover now\"}','/#','{\"vi\":\"Nhận tư vấn miễn phí\",\"en\":\"Get free advice\"}','/lien-he',0,1,'2026-11-12 03:54:21','2026-11-12 03:55:31');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `menu_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint DEFAULT NULL,
  `translation_of_id` bigint unsigned DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_in_menu` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UKoul14ho7bctbefv8jywp5v3i2` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'{\"vi\":\"Uncategorized\",\"en\":\"Uncategorized\"}','uncategorized',NULL,NULL,NULL,NULL,0,99,NULL),(5,'{\"vi\":\"Tổng quan\",\"en\":\"Overview\"}','tong-quan','giao-duc-tai-duc',NULL,NULL,NULL,1,1,NULL),(6,'{\"vi\":\"Hệ thống giáo dục\",\"en\":\"Education System\"}','he-thong-giao-duc','giao-duc-tai-duc',NULL,NULL,NULL,1,2,NULL),(8,'{\"vi\":\"Chi phí và Cơ hội\",\"en\":\"Costs & Opportunities\"}','chi-phi-va-co-hoi','giao-duc-tai-duc',NULL,NULL,NULL,1,4,NULL),(9,'{\"vi\":\"Cơ hội nghề nghiệp\",\"en\":\"Career Opportunities\"}','co-hoi-nghe-nghiep','giao-duc-tai-duc',NULL,NULL,NULL,1,5,NULL),(10,'{\"vi\":\"Du học Đại học\",\"en\":\"Bachelor Studies\"}','du-hoc-dai-hoc','tu-van',NULL,NULL,NULL,1,1,NULL),(11,'{\"vi\":\"Du học Cao học\",\"en\":\"Master Studies\"}','du-hoc-cao-hoc','tu-van',NULL,NULL,NULL,1,2,NULL),(13,'{\"vi\":\"Hiểu về Đức\",\"en\":\"Understanding Germany\"}','hieu-ve-duc','blog',NULL,NULL,NULL,1,1,NULL),(14,'{\"vi\":\"Cuộc sống ở Đức\",\"en\":\"Life in Germany\"}','cuoc-song-o-duc','blog',NULL,NULL,NULL,1,2,NULL),(15,'{\"vi\":\"Nghiên cứu và Ứng dụng\",\"en\":\"Research & Applied Sciences\"}','nghien-cuu-va-ung-dung','giao-duc-tai-duc',NULL,NULL,NULL,1,1,NULL),(21,'{\"vi\":\"Quy trình cấp Visa\",\"en\":\"Visa Application Process\"}','quy-trinh-cap-visa','tu-van',NULL,NULL,NULL,1,3,NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'2026_10_30_000001_create_categories_table',1),(3,'2026_10_30_000002_create_posts_table',1),(4,'0001_01_01_000001_create_cache_table',2),(5,'0001_01_01_000002_create_jobs_table',2),(6,'2026_10_30_000003_create_contact_messages_table',2),(7,'2026_11_05_045516_add_email_and_remember_token_to_users_table',3),(8,'2026_11_05_051820_create_banners_table',4),(9,'2026_11_05_051826_create_settings_table',4),(10,'2026_11_05_051834_create_pages_table',4),(11,'2026_11_05_052330_add_menu_fields_to_categories_table',4),(12,'2026_11_05_060000_add_parent_and_url_to_categories_table',5),(13,'2026_11_11_092918_make_content_columns_translatable',6),(14,'2026_11_12_054130_add_english_names_to_menu_categories',7),(15,'2026_11_12_065559_add_translation_of_id_to_categories_table',8),(16,'2026_11_12_083654_add_english_translations_to_default_banners',9);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` text COLLATE utf8mb4_unicode_ci,
  `banner_image` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `sections` json DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'{\"vi\":\"Về chúng tôi\"}','ve-chung-toi','{\"vi\":\"Đối Tác Tin Cậy Trên Hành Trình Giáo Dục Quốc Tế Cùng VIGER EDU\"}','https://viger.edu.vn/wp-content/uploads/2026/04/pexels-photo-7972313.jpeg','{\"vi\":\"<p>Sứ mệnh của VIGER EDU là tiếp sức cho các bạn trẻ thông qua những quyết định sáng suốt về lộ trình học tập và sự nghiệp, từ đó kiến tạo một tương lai bền vững trong môi trường toàn cầu.</p>\"}','{\"vi\": [{\"body\": \"Sứ mệnh của VIGER EDU là tiếp sức cho các bạn trẻ thông qua những quyết định sáng suốt về lộ trình học tập và sự nghiệp, từ đó kiến tạo một tương lai bền vững trong môi trường toàn cầu.\", \"title\": \"Cam Kết Của Chúng Tôi Đối Với Sự Thành Công Của Bạn\"}, {\"body\": \"Chúng tôi hướng tới mục tiêu trở thành đơn vị tư vấn du học hàng đầu tại Việt Nam, là cầu nối đưa học viên đến với các trường đại học quốc tế danh tiếng, đồng thời thúc đẩy sự phát triển cá nhân và thành công trong sự nghiệp.\", \"title\": \"Tầm Nhìn Về Một Tương Lai Với Nhiều Cơ Hội Toàn Cầu\"}, {\"body\": \"Các giá trị cốt lõi của VIGER EDU bao gồm sự chính trực, dịch vụ cá nhân hóa, cam kết hướng tới sự xuất sắc và niềm đam mê đồng hành cùng học viên đạt được các mục tiêu học tập cũng như sự nghiệp.\", \"title\": \"Triết Lý Dẫn Đường Trong Công Việc Của Chúng Tôi\"}, {\"body\": \"VIGER EDU đã nhận được nhiều lời tán dương và đánh giá cao cho những đóng góp nổi bật đối với sự thành công của học viên cũng như trong lĩnh vực tư vấn giáo dục quốc tế tại Việt Nam.\", \"title\": \"Ghi Nhận Cho Sự Xuất Sắc Của Chúng Tôi\"}]}','{\"vi\":\"Về chúng tôi - VIGER EDU: Đồng hành cùng người trẻ Việt Nam chinh phục giáo dục Đức.\"}',1,'2026-11-04 22:32:36','2026-11-04 22:32:36'),(2,'{\"vi\":\"Giáo dục tại Đức\"}','giao-duc-tai-duc','{\"vi\":\"Khám phá nền giáo dục hàng đầu Châu Âu với chi phí tối ưu\"}','https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1920&q=80','{\"vi\":\"<p>Nước Đức nổi tiếng với nền giáo dục học thuật chuẩn mực, các trường đại học hàng đầu thế giới và chính sách miễn học phí cho sinh viên quốc tế.</p>\"}','{\"vi\": []}','{\"vi\":\"Giới thiệu về hệ thống giáo dục đại học và sau đại học tại CHLB Đức.\"}',1,'2026-11-04 22:32:36','2026-11-04 22:32:36'),(3,'{\"vi\":\"Tư vấn du học Đức\"}','tu-van','{\"vi\":\"Lộ trình tối ưu và đồng hành trọn gói từ chuyên gia\"}','https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1920&q=80','{\"vi\":\"<p>Dịch vụ tư vấn chuyên nghiệp từ định hướng chọn ngành, hoàn thiện hồ sơ APS, mở tài khoản phong tỏa đến xin visa và hòa nhập cuộc sống tại Đức.</p>\"}','{\"vi\": []}','{\"vi\":\"Dịch vụ tư vấn du học Đức uy tín, chuyên nghiệp từ VIGER EDU.\"}',1,'2026-11-04 22:32:36','2026-11-04 22:32:36'),(4,'{\"vi\":\"Quy trình tư vấn\"}','tu-van/quy-trinh-tu-van','{\"vi\":\"Các bước đồng hành rõ ràng, minh bạch từ bước đầu tiên đến ngày nhập học\"}','https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1920&q=80','{\"vi\":\"<p>Quy trình tư vấn của VIGER EDU gồm các bước: Đánh giá hồ sơ, chọn trường & ngành, chuẩn bị hồ sơ visa, tài chính và chuẩn bị hành trang bay sang Đức.</p>\"}','{\"vi\": []}','{\"vi\":\"Quy trình tư vấn du học Đức từng bước chi tiết tại VIGER EDU.\"}',1,'2026-11-04 22:32:36','2026-11-04 22:32:36');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `featured_image` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('DRAFT','PUBLISHED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UKqmmso8qxjpbxwegdtp0l90390` (`slug`),
  KEY `FKijnwr3brs8vaosl80jg9rp7uc` (`category_id`),
  CONSTRAINT `FKijnwr3brs8vaosl80jg9rp7uc` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,'{\"vi\":\"<h2>1. Giới thiệu tổng quan</h2><p>Du học Đức đang là xu hướng <strong>hàng đầu</strong> cho sinh viên Việt Nam với <i>nhiều ưu đãi học phí</i>.</p><p><img src=\\\"/uploads/sample.jpg\\\" alt=\\\"Sample\\\"></p><ul><li>Miễn học phí đại học công lập</li><li>Cơ hội việc làm sau tốt nghiệp</li></ul>\"}','2026-10-16 11:02:43.599715','{\"vi\":\"Tổng hợp toàn bộ thông tin về du học Đức, chi phí và điều kiện tuyển sinh.\"}','','huong-dan-du-hoc-duc-2026-chi-tiet','DRAFT','{\"vi\":\"Hướng dẫn du học Đức 2026 chi tiết\"}',NULL),(2,'{\"vi\":\"<h2>1. Tong quan</h2><p>Bai viet voi <strong>in dam</strong> va <i>in nghieng</i></p>\"}','2026-10-16 11:03:10.920875','{\"vi\":\"\"}','','huong-dan-du-hoc-duc-2026','DRAFT','{\"vi\":\"Huong dan du hoc Duc 2026\"}',NULL),(3,'{\"vi\":\"<p>Welcome to WordPress. This is your first post. Edit or delete it, then start writing!</p>\"}','2026-04-17 02:20:09.000000','{\"vi\":\"\"}','','hello-world','DRAFT','{\"vi\":\"Hello world!\"}',1),(4,'{\"en\":\"<p>Germany’s education system is widely recognized as one of the most sustainable models in the world—not only for its academic excellence, but also for its emphasis <strong>on developing real competencies, independent thinking, and holistic skills in students.</strong> This is one of the key reasons why an increasing number of Vietnamese parents consider Germany a strategic study destination.</p><h4><strong>1. What makes German education distinctive?</strong></h4><p>Germany prioritizes the development of essential 21st-century competencies:</p><ul><li>Self-directed learning and personal responsibility</li><li>Critical and creative thinking</li><li>Problem-solving skills</li><li>Adaptability in international environments</li></ul><p>Unlike the “learning for exams” model that remains prevalent in many countries, students and families in Germany focus on <strong>mastering knowledge and applying it in real-world contexts</strong>.</p><h4><strong>2. Evidence of effectiveness – Key indicators of German education</strong></h4><p>&nbsp;</p><figure class=\\\"table\\\"><table><thead><tr><th><strong>Indicator</strong></th><th><strong>Germany</strong></th><th><strong>EU Average</strong></th><th><strong>United States</strong></th></tr></thead><tbody><tr><td>Students with high reading proficiency (PISA)</td><td>524</td><td>503</td><td>505</td></tr><tr><td>Share of STEM graduates</td><td>23%</td><td>19%</td><td>17%</td></tr><tr><td>Graduate employment rate within 12 months</td><td>91%</td><td>85%</td><td>88%</td></tr><tr><td>Average annual tuition (public universities)</td><td>~0–300 EUR</td><td>1,500 EUR</td><td>~30,000 USD</td></tr></tbody></table></figure><p><i>Source: OECD &amp; Eurostat (2023)</i></p><p>These figures indicate that:</p><p>Graduates in Germany tend to <strong>enter the labor market more quickly</strong> and are less dependent on purely theoretical credentials.</p><p>Germany produces graduates with <strong>practical competencies</strong>, not only academic knowledge but also professional skills.</p><h4><strong>3. The German education system – From primary to higher education</strong></h4><p><strong>3.1 Primary education – Building cognitive foundations</strong></p><p>From an early stage, German students are encouraged to:</p><ul><li>Ask logical and structured questions</li><li>Work collaboratively</li><li>Solve problems through hands-on learning</li></ul><p>This fosters <strong>independent thinking</strong>, a critical skill for both academic success and future careers.</p><p><strong>3.2 Secondary tracking – Respecting individual strengths</strong></p><p>The tracking system after primary education helps to:</p><ul><li>Identify suitable academic pathways</li><li>Optimize individual potential instead of enforcing a single standardized model</li></ul><p>This creates a more equitable system, where each student develops according to their <strong>abilities and strengths</strong>.</p><p><strong>3.3 Higher education – Emphasis on applied competence</strong></p><p>Germany has two main types of higher education institutions:</p><ul><li><strong>Universität</strong>: research-oriented universities</li><li><strong>Fachhochschule (University of Applied Sciences)</strong>: practice-oriented, closely linked with industry</li></ul><p>Students are trained to:</p><ul><li>Understand underlying concepts</li><li>Combine theory with practical application</li><li>Solve real-world problems</li></ul><h4><strong>4. German education as a foundation for global citizenship</strong></h4><p>Research by the Organisation for Economic Co-operation and Development shows that students with strong <strong>global competence</strong> typically come from education systems that emphasize:</p><ul><li>Critical thinking</li><li>Intercultural communication</li><li>Lifelong learning</li></ul><p>Germany is among the countries that clearly reflect this philosophy in its curriculum design.</p><h4><strong>5. Benefits for international students (especially Vietnamese students)</strong></h4><p>Choosing to study in Germany offers:</p><ul><li>A rigorous academic environment</li><li>Strong internship and industry linkage opportunities</li><li>Clear pathways to post-graduation employment</li><li>Long-term residency opportunities for qualified graduates</li></ul><h4><strong>6. VIGER EDU – Supporting a deep understanding of the German system</strong></h4><p>Choosing Germany means choosing a <strong>distinct and demanding education system</strong>. Therefore:</p><ul><li>Understanding the system before planning a study pathway is essential</li><li>Not every university suits every student’s ability and career goals</li><li>A wrong pathway can lead to years of inefficient study</li></ul><p>VIGER EDU goes beyond application support by:</p><ul><li>Assessing students’ actual capabilities</li><li>Identifying strengths and weaknesses</li><li>Recommending institutions aligned with career objectives</li><li>Designing clear, personalized academic pathways</li></ul><h4><strong>Conclusion</strong></h4><p>German education is not just about acquiring knowledge—<br>it is about becoming an independent thinker, a critical problem-solver, and a globally adaptable individual.</p><p>For Vietnamese families who view overseas education as a long-term investment, Germany offers <strong>sustainable value</strong>, provided the journey is <strong>strategically planned from the beginning</strong>.</p>\"}','2026-04-21 11:23:25.000000','{\"en\":null}','posts/3Kl5kLK5sTJPE20MUxyzReQZ0FJCTID5LrnfkN7J.jpg','post-3','PUBLISHED','{\"en\":\"German Education – A Foundation for Developing Global Citizens\"}',5),(5,'{\"en\":\"<p style=\\\"margin-bottom:var(--wp--preset--spacing--70)\\\">Primary education in Germany goes beyond the transmission of basic knowledge; it places strong emphasis on developing independence, logical thinking, and essential life skills. This foundational stage shapes students’ learning attitudes in ways that differ significantly from the “rote-learning” models still prevalent in many countries.</p>\\n\\n\\n\\n<h4 class=\\\"wp-block-heading\\\"><strong>Objectives of Primary Education in Germany</strong></h4>\\n\\n\\n\\n<p style=\\\"margin-bottom:var(--wp--preset--spacing--70)\\\">Primary education in Germany (<em>Grundschule</em>, Grades 1–4) is designed around several key priorities:</p>\\n\\n\\n\\n<ul class=\\\"wp-block-list\\\">\\n<li><strong>Self-directed learning and exploration</strong>: Students are encouraged to ask questions and attempt problem-solving independently before receiving teacher intervention.</li>\\n\\n\\n\\n<li><strong>Collaborative learning</strong>: Group activities constitute a significant portion of classroom time, fostering communication skills and the ability to understand diverse perspectives.</li>\\n\\n\\n\\n<li><strong>Logical and creative thinking</strong>: Developed through games, project-based tasks, and experiential learning activities.</li>\\n</ul>\\n\\n\\n\\n<p style=\\\"margin-bottom:var(--wp--preset--spacing--70)\\\">A study by the European Educational Research Association indicates that primary education programs emphasizing these competencies can improve students’ self-directed learning and self-management abilities by up to 30% compared to traditional approaches (EERA, 2021).</p>\\n\\n\\n\\n<h4 class=\\\"wp-block-heading\\\"><strong>Key Skills Developed at the Primary Level and Their Impact on Future Study Abroad Success</strong></h4>\\n\\n\\n\\n<p>Early development of these competencies provides significant long-term advantages, particularly when transitioning into academically rigorous environments such as Germany:</p>\\n\\n\\n\\n<p><strong>1. Independent Thinking and Self-Management</strong></p>\\n\\n\\n\\n<p>Students learn to organize their study schedules and address challenges proactively before seeking assistance—an essential expectation in international education systems.</p>\\n\\n\\n\\n<p><strong>2. Teamwork and Collaboration Skills</strong></p>\\n\\n\\n\\n<p>German primary education strongly emphasizes group work, enabling students to develop cooperation and interpersonal skills that are critical in both academic and professional contexts.</p>\\n\\n\\n\\n<p><strong>3. Critical and Creative Thinking</strong></p>\\n\\n\\n\\n<h4 class=\\\"wp-block-heading\\\"><strong>Empirical Evidence of Early Educational Effectiveness</strong></h4>\\n\\n\\n\\n<figure class=\\\"wp-block-table\\\"><table class=\\\"has-fixed-layout\\\"><thead><tr><td><strong>Competency Indicator (Primary Level)</strong></td><td><strong>Germany</strong></td><td><strong>OECD Average</strong></td></tr></thead><tbody><tr><td>Reading proficiency (PISA Primary)</td><td>520</td><td>500</td></tr><tr><td>Mathematics proficiency (PISA Primary)</td><td>530</td><td>505</td></tr><tr><td>Self-management and independent learning (%)</td><td>81%</td><td>65%</td></tr></tbody></table></figure>\\n\\n\\n\\n<p><em>Source: OECD Primary Education Data (2022)</em></p>\\n\\n\\n\\n<p>These figures demonstrate that German primary students outperform the OECD average in both reading and mathematics, while also exhibiting significantly higher levels of independent learning and self-management.</p>\\n\\n\\n\\n<h4 class=\\\"wp-block-heading\\\"><strong>Implications for Vietnamese Students and Parents</strong></h4>\\n\\n\\n\\n<p>For Vietnamese families considering early educational pathways toward studying abroad, it is essential to recognize that:</p>\\n\\n\\n\\n<ul class=\\\"wp-block-list\\\">\\n<li>Academic success is not solely determined by subject knowledge, but by the ability to learn independently and adapt effectively.</li>\\n\\n\\n\\n<li>Early preparation in developing independence significantly reduces academic and cultural adjustment challenges when studying in Germany.</li>\\n\\n\\n\\n<li>Strong foundational skills lead to faster integration and long-term success.</li>\\n</ul>\\n\\n\\n\\n<h4 class=\\\"wp-block-heading\\\"><strong>The Role of VIGER EDU</strong></h4>\\n\\n\\n\\n<p>At VIGER EDU, we go beyond application support by helping families:</p>\\n\\n\\n\\n<ul class=\\\"wp-block-list\\\">\\n<li>Develop a deep understanding of the German education system</li>\\n\\n\\n\\n<li>Identify essential skills that should be cultivated early</li>\\n\\n\\n\\n<li>Design structured preparation plans 3–5 years in advance</li>\\n\\n\\n\\n<li>Provide early-stage career and academic orientation, even from primary school, for long-term pathways</li>\\n</ul>\\n\\n\\n\\n<h4 class=\\\"wp-block-heading\\\"><strong>Conclusion</strong></h4>\\n\\n\\n\\n<p>Primary education is not merely the beginning of formal schooling—it is the stage at which core competencies are formed. In Germany, these competencies include independence, critical thinking, and collaborative ability.</p>\\n\\n\\n\\n<p>For Vietnamese parents, understanding these values is key to effectively preparing their children for studying abroad. VIGER EDU serves as a trusted partner in designing and guiding this long-term educational journey.</p>\\n\\n\\n\\n<p></p>\"}','2026-04-21 11:23:25.000000','{\"en\":null}','posts/w197wjrdWV0fp9txySasOdSdCrzmUCRKbn1zoijF.jpg','post-2','PUBLISHED','{\"en\":\"Primary Education in Germany – Building Independence from an Early Age\"}',5),(6,'{\"en\":\"<p>One of the most distinctive characteristics of the German education system is the early tracking of students immediately after primary education. This is not a mechanism of labeling or discrimination, but rather a structured and evidence-based approach to aligning educational pathways with each student’s abilities—an approach that many Vietnamese parents may not yet be familiar with.</p><h4><strong>Post-Primary Tracking – Optimizing Individual Development</strong></h4><p>In Germany, after completing primary education (<i>Grundschule</i>, typically Grades 1–4), students are guided into one of three main educational pathways:</p><figure class=\\\"table\\\"><table><thead><tr><th><strong>Track</strong></th><th><strong>Development Focus</strong></th><th><strong>Objective</strong></th></tr></thead><tbody><tr><td><strong>Gymnasium</strong></td><td>Academically intensive</td><td>Preparation for university education</td></tr><tr><td><strong>Realschule</strong></td><td>Balanced academic and practical learning</td><td>Preparation for technical professions and specialized careers</td></tr><tr><td><strong>Hauptschule</strong></td><td>Vocational and practice-oriented</td><td>Early development of occupational skills</td></tr></tbody></table></figure><p>This tracking process is based on a comprehensive evaluation of students’ actual competencies, rather than relying solely on examination scores. A study by the Institut für Qualitätsentwicklung im Bildungswesen (IQB) found that students placed in appropriately matched pathways demonstrate approximately 25% higher levels of academic confidence and learning motivation compared to those without clearly defined developmental guidance (IQB National Assessment, 2022).</p><h4><strong>Not “Segregation,” but Recognition of Individual Strengths</strong></h4><p>In many traditional education systems, parents often expect their children to excel uniformly across all subjects, operating under the assumption that:</p><p>“A successful student must achieve top performance in every subject.”</p><p>The German tracking model challenges this assumption by:</p><ul><li>Identifying each student’s authentic strengths and aptitudes</li><li>Providing early and appropriate academic direction</li><li>Avoiding the imposition of unsuitable educational pathways</li></ul><p>As a result, students:</p><p>Are better prepared for future academic or vocational pathways</p><p>Develop more sustainable learning motivation</p><p>Focus on skill development rather than purely on grades</p><h4><strong>Practical Benefits for Students and Parents</strong></h4><p><strong>1. Emphasis on Competence over Credentials</strong></p><p>The German system does not define excellence solely as achieving top grades in all subjects. Instead, it prioritizes competence and individual strengths, which helps:</p><ul><li>Reduce academic pressure</li></ul><p>Foster skills aligned with labor market demands</p><p><strong>2. Efficient Educational Pathways</strong></p><p>Incorrect placement or delayed orientation may result in extended study time of 1–2 additional years. Early and accurate tracking enables students to progress more efficiently and purposefully.</p><p><strong>3. Inclusivity for Diverse Learners</strong></p><p>Not all students thrive in purely academic environments. The tracking system provides multiple pathways, allowing each student to find a route aligned with their abilities and learning styles—an important advantage in today’s diversified career landscape.</p><h4><strong>The Role of VIGER EDU – Guiding Families from the Start</strong></h4><p>For Vietnamese parents, the concept of early tracking may initially raise concerns due to its difference from traditional educational expectations. VIGER EDU supports families by:</p><ul><li>Providing a clear understanding of the German tracking system</li><li>Conducting structured and evidence-based student assessments</li><li>Identifying the most suitable educational pathway from the outset</li><li>Preventing costly and time-consuming misalignment in academic planning</li></ul><h4><strong>Conclusion</strong></h4><p>Early academic tracking is not about labeling students; it is about optimizing each individual’s potential. When parents understand this philosophy, students can be guided toward educational pathways that align with their true abilities, leading to more balanced and sustainable development. At VIGER EDU, this approach goes beyond study abroad consulting—<br>it represents a <strong>strategic framework for building a sustainable and future-oriented career pathway</strong></p>\"}','2026-04-21 11:23:25.000000','{\"en\":null}','posts/rwDqy20rUsBAcxtdTHe0SaDv8deQHSpAsCoR4FBC.jpg','early-academic-tracking-a-distinctive-feature-of-german-education-and-its-benefits-for-vietnamese-students','PUBLISHED','{\"en\":\"Early Academic Tracking – A Distinctive Feature of German Education and Its Benefits for Vietnamese Students\"}',5),(8,'{\"vi\":\"<p>Trong hệ thống giáo dục của Đức, <strong>Gymnasium</strong> được xem là lộ trình học thuật rõ ràng nhất dành cho những học sinh có định hướng đại học. Đây không chỉ là một “trường cấp 2–3”, mà là môi trường đào tạo tư duy, nghiên cứu và khả năng tự học ở mức cao – đúng chuẩn đầu vào của đại học Đức.</p><p><strong>Gymnasium là gì trong hệ thống giáo dục Đức?</strong></p><p><img src=\\\"/img/uploads/ca43979a-de32-49ac-a74b-e8d03842bb76.jpeg\\\" alt=\\\"Tourists relaxing on the lawn in front of the historic Reichstag Building in Berlin, Germany during a summer day.\\\" srcset=\\\"/img/uploads/ca43979a-de32-49ac-a74b-e8d03842bb76.jpeg ,http://viger.edu.vn/wp-content/uploads/2026/04/pexels-photo-2871654.jpeg 780w, http://viger.edu.vn/wp-content/uploads/2026/04/pexels-photo-2871654.jpeg 360w\\\" sizes=\\\"100vw\\\" width=\\\"1024\\\" height=\\\"682\\\"></p><p>Sau tiểu học, những học sinh có năng lực học thuật tốt sẽ được định hướng vào <strong>Gymnasium</strong>. Chương trình kéo dài đến lớp 12 hoặc 13 (tùy bang), kết thúc bằng kỳ thi <strong>Abitur</strong> – điều kiện bắt buộc để vào đại học.</p><p>Đặc điểm nổi bật của Gymnasium:</p><ul><li>Khối lượng kiến thức học thuật cao</li><li>Yêu cầu tự học và tư duy độc lập</li><li>Chú trọng phân tích, lập luận, nghiên cứu</li><li>Ít kiểm tra ghi nhớ, nhiều bài tập tư duy</li></ul><p>Theo báo cáo của <i>Standing Conference of the Ministers of Education and Cultural Affairs</i> (KMK), hơn <strong>50% sinh viên đại học Đức</strong> xuất thân từ Gymnasium – cho thấy đây là con đường chuẩn bị trực tiếp và hiệu quả nhất cho bậc đại học.</p><p><strong>Tư duy Gymnasium: học để hiểu, không học để thi</strong></p><p>Khác với môi trường phổ thông thiên về điểm số, Gymnasium yêu cầu học sinh:</p><ul><li>Tự đọc tài liệu trước khi lên lớp</li><li>Thảo luận và phản biện trong giờ học</li><li>Viết bài luận, phân tích vấn đề</li><li>Làm việc nhóm và nghiên cứu độc lập</li></ul><p>Chính cách học này tạo ra sự khác biệt lớn khi bước vào đại học: sinh viên không bị “sốc phương pháp học”, vì đã được rèn luyện từ nhiều năm trước.</p><p><strong>Vì sao phụ huynh Việt nên hiểu mô hình này sớm?</strong></p><p>Nhiều học sinh Việt có học lực tốt nhưng gặp khó khăn khi sang Đức vì:</p><ul><li>Quen học thụ động, phụ thuộc giáo viên</li><li>Thiếu kỹ năng tự nghiên cứu</li><li>Chưa quen với việc lập luận và phản biện học thuật</li></ul><p>Nếu hiểu tiêu chuẩn của Gymnasium, phụ huynh có thể chuẩn bị cho con từ sớm:</p><ul><li>Rèn thói quen tự học</li><li>Tăng cường kỹ năng đọc hiểu và viết luận</li><li>Khuyến khích tư duy phản biện thay vì học thuộc</li></ul><p><strong>Vai trò của VIGER EDU trong việc “đối chiếu Gymnasium” với học sinh Việt</strong></p><p>VIGER EDU thường sử dụng chuẩn Gymnasium như một thước đo để:</p><ul><li>Phân tích năng lực học sinh Việt</li><li>Dự đoán khả năng thích nghi khi sang Đức</li><li>Xây dựng lộ trình rèn kỹ năng trước khi du học</li></ul><p>Đây là bước chuẩn bị quan trọng giúp học sinh không chỉ <strong>đủ điều kiện vào đại học Đức</strong>, mà còn <strong>học tốt và theo kịp chương trình</strong>.</p><p><strong>Kết luận</strong></p><p>Gymnasium không đơn thuần là một bậc học, mà là <strong>mô hình đào tạo tư duy đại học từ sớm</strong>. Khi phụ huynh hiểu rõ triết lý này, việc chuẩn bị du học Đức cho con sẽ không còn là chuẩn bị hồ sơ, mà là chuẩn bị <strong>năng lực học thuật thực chất</strong>.</p><p>VIGER EDU đồng hành cùng gia đình để đảm bảo học sinh Việt có nền tảng phù hợp với chuẩn Gymnasium trước khi bước vào hành trình đại học tại Đức.</p><p>&nbsp;</p>\"}','2026-05-06 15:29:44.000000','{\"vi\":\"\"}','','gymnasium-tai-duc-con-duong-hoc-thuat-chuan-bi-truc-tiep-cho-dai-hoc','PUBLISHED','{\"vi\":\"Gymnasium tại Đức – Con đường học thuật chuẩn bị trực tiếp cho đại học\"}',5),(9,'{\"en\":\"<p><br>Gymnasium is considered the clearest academic pathway for students aiming for university. It\'s not just a \\\"secondary or high school,\\\" but an environment that fosters critical thinking, research, and self-learning skills at a high level – meeting the entrance requirements of German universities.</p><p>&nbsp;</p><p><br>What is Gymnasium in the German Education System?</p><h2>&nbsp;</h2><h2>&nbsp;</h2><p>What is Gymnasium in the German Education System?</p><h2>&nbsp;</h2>\"}','2026-07-14 13:26:06.000000','{\"en\":null}',NULL,'gymnasium-in-germany--a-direct-academic-pathway-to-university','DRAFT','{\"en\":\"Gymnasium in Germany – A Direct Academic Pathway to University\"}',5),(10,'{\"vi\":\"<p>Có người từng hỏi mình: \\\"Du học ở Đức có vui không?\\\"</p>\\n\\n\\n\\n<p>Mình nghĩ vài giây rồi trả lời: \\\"Có. Nhưng không phải kiểu vui như mọi người vẫn tưởng.\\\"</p>\\n\\n\\n\\n<p>Cuộc sống của một du học sinh ở Đức không chỉ có những bức ảnh check-in trước các tòa lâu đài, những chuyến du lịch cuối tuần hay những quán cà phê đẹp như trong vlog.</p>\\n\\n\\n\\n<p>Phần lớn thời gian là những ngày rất bình thường.</p>\\n\\n\\n\\n<p>Đi học.</p>\\n\\n\\n\\n<p>Đi tàu.</p>\\n\\n\\n\\n<p>Đi làm thêm.</p>\\n\\n\\n\\n<p>Tự nấu ăn.</p>\\n\\n\\n\\n<p>Tự giặt quần áo.</p>\\n\\n\\n\\n<p>Tự giải quyết mọi chuyện.</p>\\n\\n\\n\\n<p>Nghe có vẻ đơn điệu, nhưng chính những điều bình dị ấy lại khiến mình trưởng thành từng ngày.</p>\\n\\n\\n\\n<p>Đây là một ngày quen thuộc của mình.</p>\\n\\n\\n\\n<p>7:00 - Bắt đầu ngày mới</p>\\n\\n\\n\\n<p>Đồng hồ báo thức reo.</p>\\n\\n\\n\\n<p>Việc đầu tiên mình làm là kéo rèm cửa và nhìn ra ngoài trời. Ở Đức, thời tiết thay đổi khá nhanh, nên xem hôm nay nắng hay mưa gần như đã trở thành một thói quen.</p>\\n\\n\\n\\n<p>Mình chuẩn bị bữa sáng thật đơn giản. Vài lát bánh mì, một quả trứng, thêm ly cà phê là đủ để bắt đầu ngày mới.</p>\\n\\n\\n\\n<p>Không còn ai gọi dậy đi học hay chuẩn bị bữa sáng như ở nhà. Mọi thứ đều do mình tự sắp xếp.</p>\\n\\n\\n\\n<p>8:00 - Lên tàu đến trường</p>\\n\\n\\n\\n<p>Những chuyến tàu buổi sáng luôn khá đông.</p>\\n\\n\\n\\n<p>Ai cũng tranh thủ đọc sách, làm việc hoặc đơn giản là lặng lẽ nhìn qua ô cửa kính.</p>\\n\\n\\n\\n<p>Mình cũng thích khoảng thời gian này. Chỉ khoảng hai mươi phút ngồi trên tàu, nghe một bản nhạc yêu thích và chuẩn bị tinh thần cho một ngày mới.</p>\\n\\n\\n\\n<p>Có những điều rất nhỏ nhưng dần trở thành một phần trong cuộc sống.</p>\\n\\n\\n\\n<p>9:00 - Một buổi học ở trường</p>\\n\\n\\n\\n<p>Ở Đức, việc học đòi hỏi sự chủ động rất nhiều.</p>\\n\\n\\n\\n<p>Giảng viên sẽ hướng dẫn, đặt vấn đề và gợi mở, còn việc tìm hiểu sâu hơn là trách nhiệm của sinh viên.</p>\\n\\n\\n\\n<p>Ban đầu mình khá áp lực vì không còn cảm giác được \\\"cầm tay chỉ việc\\\".</p>\\n\\n\\n\\n<p>Nhưng càng học, mình càng thấy đây là cách giúp bản thân trưởng thành và biết tự chịu trách nhiệm với việc học của mình.</p>\\n\\n\\n\\n<p>12:30 - Bữa trưa ở Mensa</p>\\n\\n\\n\\n<p>Mensa là nơi mình gặp bạn bè nhiều nhất.</p>\\n\\n\\n\\n<p>Một bữa ăn không quá cầu kỳ, nhưng đủ dinh dưỡng và phù hợp với túi tiền sinh viên.</p>\\n\\n\\n\\n<p>Chúng mình vừa ăn vừa kể nhau nghe đủ thứ chuyện.</p>\\n\\n\\n\\n<p>Hôm nay ai vừa thi xong.</p>\\n\\n\\n\\n<p>Ai mới tìm được việc làm thêm.</p>\\n\\n\\n\\n<p>Ai đang đau đầu vì bài thuyết trình tuần tới.</p>\\n\\n\\n\\n<p>Có lẽ, những cuộc trò chuyện ấy cũng là một phần rất đẹp của tuổi sinh viên.</p>\\n\\n\\n\\n<p>14:00 - Thư viện, nơi mình dành nhiều thời gian nhất</p>\\n\\n\\n\\n<p>Sau giờ học, mình thường lên thư viện.</p>\\n\\n\\n\\n<p>Không gian ở đây luôn rất yên tĩnh.</p>\\n\\n\\n\\n<p>Ai cũng chăm chú vào công việc của mình.</p>\\n\\n\\n\\n<p>Có hôm mình chỉ định ngồi một lúc rồi về, cuối cùng ngẩng đầu lên thì ngoài trời đã bắt đầu tối.</p>\\n\\n\\n\\n<p>Ở Đức, thư viện không chỉ là nơi học bài mà còn là nơi mình học được tính kỷ luật và khả năng tập trung.</p>\\n\\n\\n\\n<p>17:30 - Đi làm thêm</p>\\n\\n\\n\\n<p>Vài buổi mỗi tuần, mình đi làm thêm sau giờ học.</p>\\n\\n\\n\\n<p>Dù hơi mệt, nhưng mình luôn xem đó là một trải nghiệm đáng giá.</p>\\n\\n\\n\\n<p>Ngoài việc có thêm thu nhập để trang trải cuộc sống, mình còn học được cách giao tiếp với đồng nghiệp, quản lý thời gian và sử dụng tiếng Đức trong những tình huống thực tế.</p>\\n\\n\\n\\n<p>Đo là những điều không có trong giáo trình.</p>\\n\\n\\n\\n<p>20:00 - Bữa cơm tự nấu</p>\\n\\n\\n\\n<p>Kết thúc một ngày dài, điều mình mong nhất là được về nhà.</p>\\n\\n\\n\\n<p>Có hôm chỉ nấu một nồi mì, có hôm làm vài món đơn giản giống ở Việt Nam.</p>\\n\\n\\n\\n<p>Không phải vì ăn ngoài không ngon.</p>\\n\\n\\n\\n<p>Mà vì sau một ngày bận rộn, một bữa cơm tự nấu luôn mang lại cảm giác rất đặc biệt.</p>\\n\\n\\n\\n<p>Vừa tiết kiệm, vừa khiến mình đỡ nhớ nhà hơn.</p>\\n\\n\\n\\n<p>22:00 - Khép lại một ngày</p>\\n\\n\\n\\n<p>Trước khi đi ngủ, mình thường xem lại lịch học ngày mai, trả lời vài tin nhắn của gia đình rồi tắt đèn.</p>\\n\\n\\n\\n<p>Có những ngày rất mệt.</p>\\n\\n\\n\\n<p>Có những ngày bài tập vẫn còn dang dở.</p>\\n\\n\\n\\n<p>Có những ngày chỉ muốn nằm yên vì chẳng còn chút năng lượng nào.</p>\\n\\n\\n\\n<p>Nhưng sáng hôm sau, mình lại thức dậy và bắt đầu một ngày mới.</p>\\n\\n\\n\\n<p>Cuộc sống du học không phải lúc nào cũng đẹp như trên mạng</p>\\n\\n\\n\\n<p>Nếu chỉ xem những bức ảnh được đăng lên, có lẽ nhiều người sẽ nghĩ cuộc sống du học luôn đầy màu sắc.</p>\\n\\n\\n\\n<p>Nhưng phía sau mỗi bức ảnh là những buổi sáng phải dậy thật sớm, những lần chạy vội để kịp chuyến tàu, những ca làm thêm kéo dài đến tối và những hôm tự mình giải quyết mọi khó khăn nơi đất khách.</p>\\n\\n\\n\\n<p>Dĩ nhiên sẽ có lúc mệt, có lúc chạnh lòng vì nhớ nhà.</p>\\n\\n\\n\\n<p>Nhưng cũng chính những ngày bình thường ấy đã dạy mình cách tự lập, biết quản lý thời gian, biết trân trọng từng đồng tiền mình kiếm được và biết rằng trưởng thành không đến từ những điều lớn lao.</p>\\n\\n\\n\\n<p>Nó bắt đầu từ việc tự thức dậy mỗi sáng, tự chăm sóc bản thân và kiên trì với lựa chọn của mình.</p>\\n\\n\\n\\n<p>Đó có lẽ là điều quý giá nhất mà cuộc sống du học ở Đức mang lại.</p>\"}','2026-07-20 14:50:30.000000','{\"vi\":\"\"}',NULL,'elementor-954','DRAFT','{\"vi\":\"Elementor #954\"}',1),(11,'{\"vi\":\"<p>Có người từng hỏi mình: \\\"Du học ở Đức có vui không?\\\"</p><p>Mình nghĩ vài giây rồi trả lời: \\\"Có. Nhưng không phải kiểu vui như mọi người vẫn tưởng.\\\"</p><p>Cuộc sống của một du học sinh ở Đức không chỉ có những bức ảnh check-in trước các tòa lâu đài, những chuyến du lịch cuối tuần hay những quán cà phê đẹp như trong vlog.</p><p>Phần lớn thời gian là những ngày rất bình thường.</p><p>Đi học.</p><p>Đi tàu.</p><p>Đi làm thêm.</p><p>Tự nấu ăn.</p><p>Tự giặt quần áo.</p><p>Tự giải quyết mọi chuyện.</p><p><img src=\\\"/img/uploads/953aeeb8-6b27-433c-9725-1114d6c05786.png\\\" alt=\\\"\\\" srcset=\\\"/img/uploads/953aeeb8-6b27-433c-9725-1114d6c05786.png ,/img/uploads/953aeeb8-6b27-433c-9725-1114d6c05786.png 780w, /img/uploads/953aeeb8-6b27-433c-9725-1114d6c05786.png 360w\\\" sizes=\\\"100vw\\\" width=\\\"1336\\\" height=\\\"891\\\"></p><p>Nghe có vẻ đơn điệu, nhưng chính những điều bình dị ấy lại khiến mình trưởng thành từng ngày.</p><p>Đây là một ngày quen thuộc của mình.</p><p><strong>7:00 - Bắt đầu ngày mới</strong></p><p>Đồng hồ báo thức reo.</p><p>Việc đầu tiên mình làm là kéo rèm cửa và nhìn ra ngoài trời. Ở Đức, thời tiết thay đổi khá nhanh, nên xem hôm nay nắng hay mưa gần như đã trở thành một thói quen.</p><p>Mình chuẩn bị bữa sáng thật đơn giản. Vài lát bánh mì, một quả trứng, thêm ly cà phê là đủ để bắt đầu ngày mới.</p><p>Không còn ai gọi dậy đi học hay chuẩn bị bữa sáng như ở nhà. Mọi thứ đều do mình tự sắp xếp.</p><p><strong>8:00 - Lên tàu đến trường</strong></p><p>Những chuyến tàu buổi sáng luôn khá đông.</p><p>Ai cũng tranh thủ đọc sách, làm việc hoặc đơn giản là lặng lẽ nhìn qua ô cửa kính.</p><p>Mình cũng thích khoảng thời gian này. Chỉ khoảng hai mươi phút ngồi trên tàu, nghe một bản nhạc yêu thích và chuẩn bị tinh thần cho một ngày mới.</p><p>Có những điều rất nhỏ nhưng dần trở thành một phần trong cuộc sống.</p><p><strong>9:00 - Một buổi học ở trường</strong></p><p><img src=\\\"/img/uploads/93b85e2b-2ab7-4bf5-b69e-57dd43caaac4.jpg\\\" alt=\\\"\\\" srcset=\\\"/img/uploads/93b85e2b-2ab7-4bf5-b69e-57dd43caaac4.jpg ,/img/uploads/93b85e2b-2ab7-4bf5-b69e-57dd43caaac4.jpg 780w, /img/uploads/93b85e2b-2ab7-4bf5-b69e-57dd43caaac4.jpg 360w\\\" sizes=\\\"100vw\\\" width=\\\"640\\\" height=\\\"411\\\"></p><p>Ở Đức, việc học đòi hỏi sự chủ động rất nhiều.</p><p>Giảng viên sẽ hướng dẫn, đặt vấn đề và gợi mở, còn việc tìm hiểu sâu hơn là trách nhiệm của sinh viên.</p><p>Ban đầu mình khá áp lực vì không còn cảm giác được \\\"cầm tay chỉ việc\\\".</p><p>Nhưng càng học, mình càng thấy đây là cách giúp bản thân trưởng thành và biết tự chịu trách nhiệm với việc học của mình.</p><p><strong>12:30 - Bữa trưa ở Mensa</strong></p><p>Mensa là nơi mình gặp bạn bè nhiều nhất.</p><p>Một bữa ăn không quá cầu kỳ, nhưng đủ dinh dưỡng và phù hợp với túi tiền sinh viên.</p><p>Chúng mình vừa ăn vừa kể nhau nghe đủ thứ chuyện.</p><p>Hôm nay ai vừa thi xong.</p><p>Ai mới tìm được việc làm thêm.</p><p>Ai đang đau đầu vì bài thuyết trình tuần tới.</p><p>Có lẽ, những cuộc trò chuyện ấy cũng là một phần rất đẹp của tuổi sinh viên.</p><p><strong>14:00 - Thư viện, nơi mình dành nhiều thời gian nhất</strong></p><p>Sau giờ học, mình thường lên thư viện.</p><p>Không gian ở đây luôn rất yên tĩnh.</p><p>Ai cũng chăm chú vào công việc của mình.</p><p>Có hôm mình chỉ định ngồi một lúc rồi về, cuối cùng ngẩng đầu lên thì ngoài trời đã bắt đầu tối.</p><p>Ở Đức, thư viện không chỉ là nơi học bài mà còn là nơi mình học được tính kỷ luật và khả năng tập trung.</p><p><strong>17:30 - Đi làm thêm</strong></p><p>Vài buổi mỗi tuần, mình đi làm thêm sau giờ học.</p><p>Dù hơi mệt, nhưng mình luôn xem đó là một trải nghiệm đáng giá.</p><p>Ngoài việc có thêm thu nhập để trang trải cuộc sống, mình còn học được cách giao tiếp với đồng nghiệp, quản lý thời gian và sử dụng tiếng Đức trong những tình huống thực tế.</p><p>Đo là những điều không có trong giáo trình.</p><p><strong>20:00 - Bữa cơm tự nấu</strong></p><p>Kết thúc một ngày dài, điều mình mong nhất là được về nhà.</p><p>Có hôm chỉ nấu một nồi mì, có hôm làm vài món đơn giản giống ở Việt Nam.</p><p>Không phải vì ăn ngoài không ngon.</p><p>Mà vì sau một ngày bận rộn, một bữa cơm tự nấu luôn mang lại cảm giác rất đặc biệt.</p><p>Vừa tiết kiệm, vừa khiến mình đỡ nhớ nhà hơn.</p><p><strong>22:00 - Khép lại một ngày</strong></p><p>Trước khi đi ngủ, mình thường xem lại lịch học ngày mai, trả lời vài tin nhắn của gia đình rồi tắt đèn.</p><p>Có những ngày rất mệt.</p><p>Có những ngày bài tập vẫn còn dang dở.</p><p>Có những ngày chỉ muốn nằm yên vì chẳng còn chút năng lượng nào.</p><p>Nhưng sáng hôm sau, mình lại thức dậy và bắt đầu một ngày mới.</p><p>Cuộc sống du học không phải lúc nào cũng đẹp như trên mạng</p><p>Nếu chỉ xem những bức ảnh được đăng lên, có lẽ nhiều người sẽ nghĩ cuộc sống du học luôn đầy màu sắc.</p><p>Nhưng phía sau mỗi bức ảnh là những buổi sáng phải dậy thật sớm, những lần chạy vội để kịp chuyến tàu, những ca làm thêm kéo dài đến tối và những hôm tự mình giải quyết mọi khó khăn nơi đất khách.</p><p>Dĩ nhiên sẽ có lúc mệt, có lúc chạnh lòng vì nhớ nhà.</p><p>Nhưng cũng chính những ngày bình thường ấy đã dạy mình cách tự lập, biết quản lý thời gian, biết trân trọng từng đồng tiền mình kiếm được và biết rằng trưởng thành không đến từ những điều lớn lao.</p><p>Nó bắt đầu từ việc tự thức dậy mỗi sáng, tự chăm sóc bản thân và kiên trì với lựa chọn của mình.</p><p>Đó có lẽ là điều quý giá nhất mà cuộc sống du học ở Đức mang lại.</p><p>&nbsp;</p>\"}','2026-07-20 15:14:07.000000','{\"vi\":\"\"}','http://viger.edu.vn/wp-content/uploads/2026/04/pexels-photo-27007397.jpeg','mot-ngay-cua-du-hoc-sinh-tai-duc-dien-ra-nhu-the-nao','PUBLISHED','{\"vi\":\"Một Ngày Của Du Học Sinh Tại Đức Diễn Ra Như Thế Nào?\"}',14),(12,'{\"vi\":\"\"}','2026-04-22 14:41:04.000000','{\"vi\":\"\"}',NULL,'elementor-554','DRAFT','{\"vi\":\"Elementor #554\"}',1);
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','VIGER.EDU.VN','general','text','Tên website / Thương hiệu','2026-11-04 22:32:36','2026-11-04 22:32:36'),(2,'site_slogan','From Vietnam to Europe','general','text','Slogan phụ','2026-11-04 22:32:36','2026-11-04 22:32:36'),(3,'site_logo','img/logo.png','general','image','Đường dẫn logo','2026-11-04 22:32:36','2026-11-04 22:32:36'),(4,'header_cta_text','Bắt đầu ngay','header','text','Chữ trên nút CTA Header','2026-11-04 22:32:36','2026-11-04 22:32:36'),(5,'header_cta_url','/lien-he','header','text','Liên kết nút CTA Header','2026-11-04 22:32:36','2026-11-04 22:32:36'),(6,'footer_description','Đồng hành cùng người trẻ Việt Nam trên hành trình chinh phục giáo dục Đức - miễn học phí, chất lượng quốc tế.','footer','textarea','Đoạn giới thiệu ở Footer','2026-11-04 22:32:36','2026-11-04 22:32:36'),(7,'contact_email','info@spn.com.vn','footer','text','Email liên hệ','2026-11-04 22:32:36','2026-11-04 22:32:36'),(8,'social_facebook','https://www.facebook.com/duhocVIGER','social','text','Link Facebook','2026-11-04 22:32:36','2026-11-04 22:32:36'),(9,'social_youtube','https://www.youtube.com/@SPN.Invest','social','text','Link YouTube','2026-11-04 22:32:36','2026-11-04 22:32:36'),(10,'social_tiktok','https://www.tiktok.com/@spn.invest','social','text','Link TikTok','2026-11-04 22:32:36','2026-11-04 22:32:36'),(11,'footer_copyright','Copyright © VIGER.EDU.VN. All rights reserved.','footer','text','Dòng bản quyền Footer','2026-11-04 22:32:36','2026-11-04 22:32:36'),(28,'contact_hero_pretitle','Liên hệ với chúng tôi','contact','text','Liên hệ: Dòng nhỏ trên banner','2026-11-05 00:20:42','2026-11-05 00:20:42'),(29,'contact_hero_title','KẾT NỐI VỚI VIGER EDU','contact','text','Liên hệ: Tiêu đề banner','2026-11-05 00:20:42','2026-11-05 00:20:42'),(30,'contact_hero_description','Đội ngũ VIGER EDU luôn sẵn sàng hỗ trợ và giải đáp mọi thắc mắc của bạn về lộ trình du học.','contact','text','Liên hệ: Mô tả banner','2026-11-05 00:20:42','2026-11-05 00:20:42'),(31,'contact_hero_image',NULL,'contact','image','Liên hệ: Ảnh nền banner','2026-11-05 00:20:42','2026-11-05 00:20:42'),(32,'contact_heading','Liên Hệ Ngay','contact','text','Liên hệ: Tiêu đề khối thông tin','2026-11-05 00:20:42','2026-11-05 00:20:42'),(33,'contact_heading_highlight','VIGER EDU','contact','text','Liên hệ: Tiêu đề khối thông tin (phần tô màu)','2026-11-05 00:20:42','2026-11-05 00:20:42'),(34,'contact_phone','(084) 819949968','contact','text','Số điện thoại liên hệ','2026-11-05 00:20:42','2026-11-05 00:20:42'),(35,'contact_address','6 Phùng Khắc Khoan,\nPhường Sài Gòn, TP.HCM','contact','text','Địa chỉ liên hệ','2026-11-05 00:20:42','2026-11-05 00:20:42'),(36,'contact_side_image',NULL,'contact','image','Liên hệ: Ảnh minh họa','2026-11-05 00:20:42','2026-11-05 00:20:42'),(37,'contact_form_pretitle','Bắt đầu hành trình của riêng bạn','contact','text','Liên hệ: Dòng nhỏ trên form','2026-11-05 00:20:42','2026-11-05 00:20:42'),(38,'contact_form_title','**Đức** đang chờ bạn.\nViger **EDU** cũng vậy.','contact','text','Liên hệ: Tiêu đề form','2026-11-05 00:20:42','2026-11-05 00:20:42'),(39,'contact_form_description','Để lại email - chúng tôi sẽ liên hệ tư vấn miễn phí trong 24 giờ.','contact','text','Liên hệ: Mô tả form','2026-11-05 00:20:42','2026-11-05 00:20:42'),(40,'contact_form_button','Đăng ký ngay','contact','text','Liên hệ: Chữ trên nút gửi','2026-11-05 00:20:42','2026-11-05 00:20:42'),(41,'contact_success_message','Cảm ơn bạn! VIGER EDU sẽ liên hệ trong vòng 24 giờ.','contact','text','Liên hệ: Thông báo gửi thành công','2026-11-05 00:20:42','2026-11-05 00:20:42'),(42,'contact_show_map','1','contact','text','Liên hệ: Hiển thị bản đồ','2026-11-05 00:20:42','2026-11-05 00:20:42'),(43,'contact_map_url','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.3500190135123!2d106.69857019999999!3d10.7844813!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f35e7dcdebb%3A0x9f036d91414b18fa!2zNiBQaMO5bmcgS2jhuq9jIEtob2FuLCBTw6BpIEfDsm4sIEjhu5MgQ2jDrSBNaW5oLCBWaeG7h3QgTmFt!5e0!3m2!1svi!2sus!4v1791193058666!5m2!1svi!2sus','contact','text','Liên hệ: Link nhúng Google Maps','2026-11-05 00:20:42','2026-11-05 00:20:42'),(110,'home_hero_heading','Hành trình của bạn','home','text','Trang chủ › Banner (home_hero_heading)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(111,'home_hero_subheading','Bắt đầu từ nước Đức...','home','text','Trang chủ › Banner (home_hero_subheading)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(112,'home_hero_description','Viger EDU đồng hành cùng bạn từ lựa chọn ngành học, chuẩn bị hồ sơ đến khi đặt chân đến giảng đường Đức — với đội ngũ chuyên gia đã từng học tập và làm việc tại đây.','home','text','Trang chủ › Banner (home_hero_description)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(113,'home_hero_background',NULL,'home','image','Trang chủ › Banner (home_hero_background)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(114,'home_hero_image',NULL,'home','image','Trang chủ › Banner (home_hero_image)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(115,'home_hero_stats','[{\"number\":500,\"suffix\":\"+\",\"label\":\"H\\u1ecdc vi\\u00ean nh\\u1eadp h\\u1ecdc\"},{\"number\":95,\"suffix\":\"%\",\"label\":\"Xin Visa th\\u00e0nh c\\u00f4ng\"},{\"number\":10,\"suffix\":\"+\",\"label\":\"N\\u0103m kinh nghi\\u1ec7m\"}]','home','json','Trang chủ › Banner (home_hero_stats)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(116,'home_hero_button_text','Đặt lịch tư vấn miễn phí','home','text','Trang chủ › Banner (home_hero_button_text)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(117,'home_hero_button_url','/lien-he','home','text','Trang chủ › Banner (home_hero_button_url)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(118,'home_roadmap_button_text','Xem lộ trình du học','home','text','Trang chủ › Lộ trình (home_roadmap_button_text)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(119,'home_roadmap_image',NULL,'home','image','Trang chủ › Lộ trình (home_roadmap_image)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(120,'home_partners','[\"Technical University of Munich (TUM)\",\"Ludwig-Maximilians-Universit\\u00e4t M\\u00fcnchen (LMU)\",\"Ruprecht-Karls-Universit\\u00e4t Heidelberg\",\"Freie Universit\\u00e4t Berlin\"]','home','json','Trang chủ › Đối tác (home_partners)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(121,'home_about_label','✨ Về chúng tôi','home','text','Trang chủ › Về chúng tôi (home_about_label)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(122,'home_about_heading','Người bạn đồng hành đáng tin cậy nhất trên con đường đến Đức','home','text','Trang chủ › Về chúng tôi (home_about_heading)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(123,'home_about_description','Chúng tôi không chỉ tư vấn — chúng tôi đồng hành. Đội ngũ Viger EDU gồm các chuyên gia tốt nghiệp từ các trường đại học Đức hàng đầu, hiểu rõ từng bước của hành trình từ ngày đầu đến ngày nhập học.','home','text','Trang chủ › Về chúng tôi (home_about_description)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(124,'home_about_link_text','Tìm hiểu về chúng tôi','home','text','Trang chủ › Về chúng tôi (home_about_link_text)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(125,'home_about_link_url','/ve-chung-toi','home','text','Trang chủ › Về chúng tôi (home_about_link_url)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(126,'home_about_image_1',NULL,'home','image','Trang chủ › Về chúng tôi (home_about_image_1)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(127,'home_about_image_2',NULL,'home','image','Trang chủ › Về chúng tôi (home_about_image_2)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(128,'home_about_image_3',NULL,'home','image','Trang chủ › Về chúng tôi (home_about_image_3)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(129,'home_about_stat_number','300+','home','text','Trang chủ › Về chúng tôi (home_about_stat_number)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(130,'home_about_stat_label','Đối tác trường Đại học','home','text','Trang chủ › Về chúng tôi (home_about_stat_label)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(131,'home_process_pretitle','✨ Quy trình du học','home','text','Trang chủ › Quy trình (home_process_pretitle)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(132,'home_process_title','05 bước đến giảng đường nước Đức','home','text','Trang chủ › Quy trình (home_process_title)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(133,'home_process_steps','[{\"title\":\"T\\u01b0 v\\u1ea5n mi\\u1ec5n ph\\u00ed\",\"description\":\"\\u0110\\u00e1nh gi\\u00e1 h\\u1ed3 s\\u01a1 v\\u00e0 \\u0111\\u1ecbnh h\\u01b0\\u1edbng l\\u1ed9 tr\\u00ecnh ph\\u00f9 h\\u1ee3p\"},{\"title\":\"Luy\\u1ec7n ti\\u1ebfng \\u0110\\u1ee9c\",\"description\":\"\\u0110\\u1ea1t tr\\u00ecnh \\u0111\\u1ed9 ng\\u00f4n ng\\u1eef theo y\\u00eau c\\u1ea7u c\\u1ee7a tr\\u01b0\\u1eddng\"},{\"title\":\"Chu\\u1ea9n b\\u1ecb h\\u1ed3 s\\u01a1\",\"description\":\"Ho\\u00e0n thi\\u1ec7n \\u0111\\u1ea7y \\u0111\\u1ee7 gi\\u1ea5y t\\u1edd v\\u00e0 n\\u1ed9p \\u0111\\u01a1n\"},{\"title\":\"Xin visa\",\"description\":\"H\\u1ed7 tr\\u1ee3 to\\u00e0n b\\u1ed9 th\\u1ee7 t\\u1ee5c visa sinh vi\\u00ean\"},{\"title\":\"Nh\\u1eadp h\\u1ecdc & \\u1ed5n \\u0111\\u1ecbnh\",\"description\":\"\\u0110\\u1ed3ng h\\u00e0nh t\\u1eeb ng\\u00e0y \\u0111\\u1ea7u \\u0111\\u1ebfn \\u0110\\u1ee9c\"}]','home','json','Trang chủ › Quy trình (home_process_steps)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(134,'home_process_link_text','Xem lộ trình chi tiết','home','text','Trang chủ › Quy trình (home_process_link_text)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(135,'home_process_link_url','/tu-van/quy-trinh-tu-van','home','text','Trang chủ › Quy trình (home_process_link_url)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(136,'home_testimonial_title','Những hành trình đáng tự hào','home','text','Trang chủ › Cảm nhận (home_testimonial_title)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(137,'home_testimonials','[{\"content\":\"Nh\\u1edd c\\u00f3 VIGER EDU, em \\u0111\\u00e3 t\\u00ecm \\u0111\\u01b0\\u1ee3c ng\\u00f4i tr\\u01b0\\u1eddng \\u0111\\u1ea1i h\\u1ecdc ph\\u00f9 h\\u1ee3p nh\\u1ea5t t\\u1ea1i \\u0110\\u1ee9c cho ng\\u00e0nh k\\u1ef9 thu\\u1eadt c\\u1ee7a m\\u00ecnh, s\\u1ef1 h\\u1ed7 tr\\u1ee3 t\\u1eeb c\\u00e1c anh ch\\u1ecb th\\u1ef1c s\\u1ef1 tuy\\u1ec7t v\\u1eddi!\",\"name\":\"Duc Nguyen\",\"avatar\":null,\"default_avatar\":\"img\\/duc_nguyen.jpg\"},{\"content\":\"Em ch\\u01b0a t\\u1eebng ngh\\u0129 vi\\u1ec7c du h\\u1ecdc l\\u1ea1i c\\u00f3 th\\u1ec3 d\\u1ec5 d\\u00e0ng \\u0111\\u1ebfn th\\u1ebf! VIGER EDU \\u0111\\u00e3 gi\\u00fap m\\u1ecdi th\\u1ee7 t\\u1ee5c tr\\u1edf n\\u00ean v\\u00f4 c\\u00f9ng nhanh ch\\u00f3ng v\\u00e0 nh\\u1eb9 nh\\u00e0ng.\",\"name\":\"Mai Tran\",\"avatar\":null,\"default_avatar\":\"img\\/mai_tran.jpg\"}]','home','json','Trang chủ › Cảm nhận (home_testimonials)','2026-11-05 02:08:13','2026-11-06 00:05:05'),(138,'home_cta_pretitle','✨ Bắt đầu hành trình của riêng bạn','home','text','Trang chủ › CTA (home_cta_pretitle)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(139,'home_cta_title','Khởi đầu hành trình chinh phục tương lai','home','text','Trang chủ › CTA (home_cta_title)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(140,'home_cta_description','Liên hệ với chúng tôi ngay hôm nay để được tư vấn lộ trình học tập và sự nghiệp vững chắc tại nước ngoài.','home','text','Trang chủ › CTA (home_cta_description)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(141,'home_cta_button_text','Bắt đầu ngay','home','text','Trang chủ › CTA (home_cta_button_text)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(142,'home_cta_button_url','/lien-he','home','text','Trang chủ › CTA (home_cta_button_url)','2026-11-05 02:08:13','2026-11-05 02:08:13'),(164,'about_hero_pretitle','Câu chuyện của chúng tôi','about','text','Về chúng tôi › Banner (about_hero_pretitle)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(165,'about_hero_title','VỀ CHÚNG TÔI','about','text','Về chúng tôi › Banner (about_hero_title)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(166,'about_hero_description','Đối Tác Tin Cậy Trên Hành Trình Giáo Dục Quốc Tế Cùng VIGER EDU','about','text','Về chúng tôi › Banner (about_hero_description)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(167,'about_hero_image',NULL,'about','image','Về chúng tôi › Banner (about_hero_image)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(168,'about_intro_label','KHÁM PHÁ LỘ TRÌNH ','about','text','Về chúng tôi › Giới thiệu (about_intro_label)','2026-11-05 03:44:11','2026-11-05 03:44:24'),(169,'about_intro_heading','Khám Phá Lộ Trình Bước Vào Giáo Dục Bậc Cao','about','text','Về chúng tôi › Giới thiệu (about_intro_heading)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(170,'about_intro_highlight','**VIGER EDU** là đơn vị tư vấn du học chuyên sâu, tập trung vào các lộ trình giáo dục bậc cao tại Châu Âu, đặc biệt là CHLB Đức.','about','text','Về chúng tôi › Giới thiệu (about_intro_highlight)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(171,'about_intro_description','Tại VIGER EDU, chúng tôi đã đồng hành và giúp đỡ vô số học viên hiện thực hóa ước mơ của mình bằng cách cung cấp các lộ trình giáo dục cá nhân hóa, sự hướng dẫn chuyên sâu và quy trình nộp hồ sơ đại học thành công, mang lại tỷ lệ đỗ cao vào các trường quốc tế.','about','text','Về chúng tôi › Giới thiệu (about_intro_description)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(172,'about_intro_image',NULL,'about','image','Về chúng tôi › Giới thiệu (about_intro_image)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(173,'about_values_heading','NÂNG BƯỚC HỌC VIÊN\nBẰNG SỰ ĐỒNG HÀNH\nCHUYÊN BIỆT','about','text','Về chúng tôi › Giá trị (about_values_heading)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(174,'about_values_description','Chúng tôi thiết kế dịch vụ phù hợp với nhu cầu riêng biệt của từng học viên, hỗ trợ các bạn nhập học thành công tại các học viện uy tín khắp Châu Âu và xây dựng sự nghiệp bền vững.','about','text','Về chúng tôi › Giá trị (about_values_description)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(175,'about_values_items','[{\"title\":\"Cam K\\u1ebft C\\u1ee7a Ch\\u00fang T\\u00f4i \\u0110\\u1ed1i V\\u1edbi S\\u1ef1 Th\\u00e0nh C\\u00f4ng C\\u1ee7a B\\u1ea1n\",\"body\":\"S\\u1ee9 m\\u1ec7nh c\\u1ee7a VIGER EDU l\\u00e0 ti\\u1ebfp s\\u1ee9c cho c\\u00e1c b\\u1ea1n tr\\u1ebb th\\u00f4ng qua nh\\u1eefng quy\\u1ebft \\u0111\\u1ecbnh s\\u00e1ng su\\u1ed1t v\\u1ec1 l\\u1ed9 tr\\u00ecnh h\\u1ecdc t\\u1eadp v\\u00e0 s\\u1ef1 nghi\\u1ec7p, t\\u1eeb \\u0111\\u00f3 ki\\u1ebfn t\\u1ea1o m\\u1ed9t t\\u01b0\\u01a1ng lai b\\u1ec1n v\\u1eefng trong m\\u00f4i tr\\u01b0\\u1eddng to\\u00e0n c\\u1ea7u.\"},{\"title\":\"T\\u1ea7m Nh\\u00ecn V\\u1ec1 M\\u1ed9t T\\u01b0\\u01a1ng Lai V\\u1edbi Nhi\\u1ec1u C\\u01a1 H\\u1ed9i To\\u00e0n C\\u1ea7u\",\"body\":\"Ch\\u00fang t\\u00f4i h\\u01b0\\u1edbng t\\u1edbi m\\u1ee5c ti\\u00eau tr\\u1edf th\\u00e0nh \\u0111\\u01a1n v\\u1ecb t\\u01b0 v\\u1ea5n du h\\u1ecdc h\\u00e0ng \\u0111\\u1ea7u t\\u1ea1i Vi\\u1ec7t Nam, l\\u00e0 c\\u1ea7u n\\u1ed1i \\u0111\\u01b0a h\\u1ecdc vi\\u00ean \\u0111\\u1ebfn v\\u1edbi c\\u00e1c tr\\u01b0\\u1eddng \\u0111\\u1ea1i h\\u1ecdc qu\\u1ed1c t\\u1ebf danh ti\\u1ebfng, \\u0111\\u1ed3ng th\\u1eddi th\\u00fac \\u0111\\u1ea9y s\\u1ef1 ph\\u00e1t tri\\u1ec3n c\\u00e1 nh\\u00e2n v\\u00e0 th\\u00e0nh c\\u00f4ng trong s\\u1ef1 nghi\\u1ec7p.\"},{\"title\":\"Tri\\u1ebft L\\u00fd D\\u1eabn \\u0110\\u01b0\\u1eddng Trong C\\u00f4ng Vi\\u1ec7c C\\u1ee7a Ch\\u00fang T\\u00f4i\",\"body\":\"C\\u00e1c gi\\u00e1 tr\\u1ecb c\\u1ed1t l\\u00f5i c\\u1ee7a VIGER EDU bao g\\u1ed3m s\\u1ef1 ch\\u00ednh tr\\u1ef1c, d\\u1ecbch v\\u1ee5 c\\u00e1 nh\\u00e2n h\\u00f3a, cam k\\u1ebft h\\u01b0\\u1edbng t\\u1edbi s\\u1ef1 xu\\u1ea5t s\\u1eafc v\\u00e0 ni\\u1ec1m \\u0111am m\\u00ea \\u0111\\u1ed3ng h\\u00e0nh c\\u00f9ng h\\u1ecdc vi\\u00ean \\u0111\\u1ea1t \\u0111\\u01b0\\u1ee3c c\\u00e1c m\\u1ee5c ti\\u00eau h\\u1ecdc t\\u1eadp c\\u0169ng nh\\u01b0 s\\u1ef1 nghi\\u1ec7p.\"},{\"title\":\"Ghi Nh\\u1eadn Cho S\\u1ef1 Xu\\u1ea5t S\\u1eafc C\\u1ee7a Ch\\u00fang T\\u00f4i\",\"body\":\"VIGER EDU \\u0111\\u00e3 nh\\u1eadn \\u0111\\u01b0\\u1ee3c nhi\\u1ec1u l\\u1eddi t\\u00e1n d\\u01b0\\u01a1ng v\\u00e0 \\u0111\\u00e1nh gi\\u00e1 cao cho nh\\u1eefng \\u0111\\u00f3ng g\\u00f3p n\\u1ed5i b\\u1eadt \\u0111\\u1ed1i v\\u1edbi s\\u1ef1 th\\u00e0nh c\\u00f4ng c\\u1ee7a h\\u1ecdc vi\\u00ean c\\u0169ng nh\\u01b0 trong l\\u0129nh v\\u1ef1c t\\u01b0 v\\u1ea5n gi\\u00e1o d\\u1ee5c qu\\u1ed1c t\\u1ebf t\\u1ea1i Vi\\u1ec7t Nam.\"}]','about','json','Về chúng tôi › Giá trị (about_values_items)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(176,'about_history_label','HÀNH TRÌNH PHÁT TRIỂN','about','text','Về chúng tôi › Hành trình (about_history_label)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(177,'about_history_heading','Di Sản Kiến Tạo Tương Lai Qua Đường Giáo Dục','about','text','Về chúng tôi › Hành trình (about_history_heading)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(178,'about_history_description','Kể từ khi thành lập, VIGER EDU luôn cống hiến hết mình để định hướng cho học viên Việt Nam đạt được những thành tựu học thuật xuất sắc và các cơ hội nghề nghiệp quốc tế giá trị.\n\nVới nhiều năm kinh nghiệm, chúng tôi đã khẳng định uy tín vững chắc trong việc đồng hành cùng học viên suốt chặng đường, mang lại vô số kết quả nhập học thành công tại Đức và Châu Âu.','about','text','Về chúng tôi › Hành trình (about_history_description)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(179,'about_history_image',NULL,'about','image','Về chúng tôi › Hành trình (about_history_image)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(180,'about_cta_pretitle','✨ Bắt đầu hành trình của riêng bạn','about','text','Về chúng tôi › CTA (about_cta_pretitle)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(181,'about_cta_title','Khởi đầu hành trình chinh phục tương lai','about','text','Về chúng tôi › CTA (about_cta_title)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(182,'about_cta_description','Liên hệ với chúng tôi ngay hôm nay để được tư vấn lộ trình học tập và sự nghiệp vững chắc tại nước ngoài.','about','text','Về chúng tôi › CTA (about_cta_description)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(183,'about_cta_button_text','Bắt đầu ngay','about','text','Về chúng tôi › CTA (about_cta_button_text)','2026-11-05 03:44:11','2026-11-05 03:44:11'),(184,'about_cta_button_url','/lien-he','about','text','Về chúng tôi › CTA (about_cta_button_url)','2026-11-05 03:44:11','2026-11-05 03:44:11');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `active` bit(1) NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime(6) DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UKr43af9ap4edm43mmtq01oddj6` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,_binary '','XESFDeWhkCk4YtqTQ5tf3c0VBwzqo4fw6HvbXFGbzwGR02mvHg4S5y23wPy8','2026-10-23 10:48:29.517034','Quản trị SPN','info@spn.com.vn','$2y$12$ijWUJBYCt8iN9lAE9a67o.FEUFkhiw5E/pRTNSGvGOK2HFYjLBHna','ADMIN','2026-11-05 05:32:36.000000','SPN');
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

-- Dump completed on 2026-11-14 12:05:24
