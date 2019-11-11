-- MySQL dump 10.13  Distrib 5.7.26, for Linux (x86_64)
--
-- Host: localhost    Database: pluginkollektiv_antispambee
-- ------------------------------------------------------
-- Server version	5.7.26

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_commentmeta`
--

DROP TABLE IF EXISTS `wp_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_commentmeta`
--

LOCK TABLES `wp_commentmeta` WRITE;
/*!40000 ALTER TABLE `wp_commentmeta` DISABLE KEYS */;
INSERT INTO `wp_commentmeta` VALUES (42,16,'antispam_bee_iphash','$P$BjYNCOZfsuH.mX/DSatAubQwrz7rNO1'),(43,16,'antispam_bee_reason','regexp');
/*!40000 ALTER TABLE `wp_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_comments`
--

DROP TABLE IF EXISTS `wp_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT '0',
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT '',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_comments`
--

LOCK TABLES `wp_comments` WRITE;
/*!40000 ALTER TABLE `wp_comments` DISABLE KEYS */;
INSERT INTO `wp_comments` VALUES (15,28,'David','webmaster@websupporter.net','','127.0.0.1','2019-06-20 11:43:16','2019-06-20 11:43:16','test123',0,'0','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36','',0,0),(16,1,'Montgomery','montgomery.c.burns.1866@aol.com','http://nuclear-secrets.com','127.0.0.1','2019-09-15 16:48:10','2019-09-15 16:48:10','you can Buy amazing Neutrons here!',0,'spam','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/77.0.3865.75 Safari/537.36','',0,0);
/*!40000 ALTER TABLE `wp_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_links`
--

DROP TABLE IF EXISTS `wp_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_image` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_target` varchar(25) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_visible` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT '1',
  `link_rating` int(11) NOT NULL DEFAULT '0',
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_notes` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `link_rss` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_links`
--

LOCK TABLES `wp_links` WRITE;
/*!40000 ALTER TABLE `wp_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_options`
--

DROP TABLE IF EXISTS `wp_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `option_value` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `autoload` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`)
) ENGINE=InnoDB AUTO_INCREMENT=851 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_options`
--

LOCK TABLES `wp_options` WRITE;
/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;
INSERT INTO `wp_options` VALUES (1,'siteurl','http://antispambee.pluginkollektiv','yes'),(2,'home','http://antispambee.pluginkollektiv','yes'),(3,'blogname','Antispam Bee','yes'),(4,'blogdescription','Just another WordPress site','yes'),(5,'users_can_register','0','yes'),(6,'admin_email','admin@example.com','yes'),(7,'start_of_week','1','yes'),(8,'use_balanceTags','0','yes'),(9,'use_smilies','1','yes'),(10,'require_name_email','1','yes'),(11,'comments_notify','1','yes'),(12,'posts_per_rss','10','yes'),(13,'rss_use_excerpt','0','yes'),(14,'mailserver_url','mail.example.com','yes'),(15,'mailserver_login','login@example.com','yes'),(16,'mailserver_pass','password','yes'),(17,'mailserver_port','110','yes'),(18,'default_category','1','yes'),(19,'default_comment_status','open','yes'),(20,'default_ping_status','open','yes'),(21,'default_pingback_flag','1','yes'),(22,'posts_per_page','10','yes'),(23,'date_format','F j, Y','yes'),(24,'time_format','g:i a','yes'),(25,'links_updated_date_format','F j, Y g:i a','yes'),(26,'comment_moderation','','yes'),(27,'moderation_notify','1','yes'),(28,'permalink_structure','/%postname%/','yes'),(29,'rewrite_rules','a:111:{s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:45:\"amp_validated_url/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:55:\"amp_validated_url/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:75:\"amp_validated_url/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:70:\"amp_validated_url/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:70:\"amp_validated_url/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:51:\"amp_validated_url/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:34:\"amp_validated_url/([^/]+)/embed/?$\";s:50:\"index.php?amp_validated_url=$matches[1]&embed=true\";s:38:\"amp_validated_url/([^/]+)/trackback/?$\";s:44:\"index.php?amp_validated_url=$matches[1]&tb=1\";s:46:\"amp_validated_url/([^/]+)/page/?([0-9]{1,})/?$\";s:57:\"index.php?amp_validated_url=$matches[1]&paged=$matches[2]\";s:53:\"amp_validated_url/([^/]+)/comment-page-([0-9]{1,})/?$\";s:57:\"index.php?amp_validated_url=$matches[1]&cpage=$matches[2]\";s:40:\"amp_validated_url/([^/]+)/amp(/(.*))?/?$\";s:55:\"index.php?amp_validated_url=$matches[1]&amp=$matches[3]\";s:42:\"amp_validated_url/([^/]+)(?:/([0-9]+))?/?$\";s:56:\"index.php?amp_validated_url=$matches[1]&page=$matches[2]\";s:34:\"amp_validated_url/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:44:\"amp_validated_url/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:64:\"amp_validated_url/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:59:\"amp_validated_url/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:59:\"amp_validated_url/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:40:\"amp_validated_url/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:61:\"amp_validation_error/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:59:\"index.php?amp_validation_error=$matches[1]&feed=$matches[2]\";s:56:\"amp_validation_error/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:59:\"index.php?amp_validation_error=$matches[1]&feed=$matches[2]\";s:37:\"amp_validation_error/([^/]+)/embed/?$\";s:53:\"index.php?amp_validation_error=$matches[1]&embed=true\";s:49:\"amp_validation_error/([^/]+)/page/?([0-9]{1,})/?$\";s:60:\"index.php?amp_validation_error=$matches[1]&paged=$matches[2]\";s:31:\"amp_validation_error/([^/]+)/?$\";s:42:\"index.php?amp_validation_error=$matches[1]\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:69:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:39:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$\";s:63:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]\";s:56:\"([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:51:\"([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:32:\"([0-9]{4})/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:44:\"([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:26:\"([0-9]{4})/([0-9]{1,2})/?$\";s:47:\"index.php?year=$matches[1]&monthnum=$matches[2]\";s:43:\"([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:38:\"([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:19:\"([0-9]{4})/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:31:\"([0-9]{4})/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:13:\"([0-9]{4})/?$\";s:26:\"index.php?year=$matches[1]\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";s:27:\"[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\"[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\"[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\"[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"([^/]+)/embed/?$\";s:37:\"index.php?name=$matches[1]&embed=true\";s:20:\"([^/]+)/trackback/?$\";s:31:\"index.php?name=$matches[1]&tb=1\";s:40:\"([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:35:\"([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:28:\"([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&paged=$matches[2]\";s:35:\"([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&cpage=$matches[2]\";s:22:\"([^/]+)/amp(/(.*))?/?$\";s:42:\"index.php?name=$matches[1]&amp=$matches[3]\";s:24:\"([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?name=$matches[1]&page=$matches[2]\";s:16:\"[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:26:\"[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:46:\"[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:22:\"[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";}','yes'),(30,'hack_file','0','yes'),(31,'blog_charset','UTF-8','yes'),(32,'moderation_keys','','no'),(33,'active_plugins','a:3:{i:0;s:11:\"amp/amp.php\";i:1;s:37:\"antispam-bee-dev/antispam-bee-dev.php\";i:2;s:29:\"antispam-bee/antispam_bee.php\";}','yes'),(34,'category_base','','yes'),(35,'ping_sites','http://rpc.pingomatic.com/','yes'),(36,'comment_max_links','2','yes'),(37,'gmt_offset','0','yes'),(38,'default_email_category','1','yes'),(39,'recently_edited','','no'),(40,'template','twentyseventeen','yes'),(41,'stylesheet','twentyseventeen','yes'),(42,'comment_whitelist','1','yes'),(43,'blacklist_keys','','no'),(44,'comment_registration','','yes'),(45,'html_type','text/html','yes'),(46,'use_trackback','0','yes'),(47,'default_role','subscriber','yes'),(48,'db_version','43764','yes'),(49,'uploads_use_yearmonth_folders','1','yes'),(50,'upload_path','','yes'),(51,'blog_public','1','yes'),(52,'default_link_category','2','yes'),(53,'show_on_front','posts','yes'),(54,'tag_base','','yes'),(55,'show_avatars','','yes'),(56,'avatar_rating','G','yes'),(57,'upload_url_path','','yes'),(58,'thumbnail_size_w','150','yes'),(59,'thumbnail_size_h','150','yes'),(60,'thumbnail_crop','1','yes'),(61,'medium_size_w','300','yes'),(62,'medium_size_h','300','yes'),(63,'avatar_default','mystery','yes'),(64,'large_size_w','1024','yes'),(65,'large_size_h','1024','yes'),(66,'image_default_link_type','none','yes'),(67,'image_default_size','','yes'),(68,'image_default_align','','yes'),(69,'close_comments_for_old_posts','','yes'),(70,'close_comments_days_old','14','yes'),(71,'thread_comments','1','yes'),(72,'thread_comments_depth','5','yes'),(73,'page_comments','','yes'),(74,'comments_per_page','50','yes'),(75,'default_comments_page','newest','yes'),(76,'comment_order','asc','yes'),(77,'sticky_posts','a:0:{}','yes'),(78,'widget_categories','a:2:{i:2;a:4:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:12:\"hierarchical\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),(79,'widget_text','a:0:{}','yes'),(80,'widget_rss','a:0:{}','yes'),(81,'uninstall_plugins','a:2:{s:29:\"antispam-bee/antispam_bee.php\";a:2:{i:0;s:12:\"Antispam_Bee\";i:1;s:9:\"uninstall\";}s:51:\"podlove-podcasting-plugin-for-wordpress/podlove.php\";s:17:\"Podlove\\uninstall\";}','no'),(82,'timezone_string','','yes'),(83,'page_for_posts','0','yes'),(84,'page_on_front','0','yes'),(85,'default_post_format','0','yes'),(86,'link_manager_enabled','0','yes'),(87,'finished_splitting_shared_terms','1','yes'),(88,'site_icon','0','yes'),(89,'medium_large_size_w','768','yes'),(90,'medium_large_size_h','0','yes'),(91,'initial_db_version','38590','yes'),(92,'wp_user_roles','a:5:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:63:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:22:\"podlove_read_analytics\";b:1;s:22:\"podlove_read_dashboard\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:36:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:22:\"podlove_read_analytics\";b:1;s:22:\"podlove_read_dashboard\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:12:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:22:\"podlove_read_analytics\";b:1;s:22:\"podlove_read_dashboard\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:5:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}}','yes'),(93,'fresh_site','0','yes'),(94,'widget_search','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),(95,'widget_recent-posts','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),(96,'widget_recent-comments','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),(97,'widget_archives','a:2:{i:2;a:3:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),(98,'widget_meta','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),(99,'sidebars_widgets','a:5:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:6:{i:0;s:8:\"search-2\";i:1;s:14:\"recent-posts-2\";i:2;s:17:\"recent-comments-2\";i:3;s:10:\"archives-2\";i:4;s:12:\"categories-2\";i:5;s:6:\"meta-2\";}s:9:\"sidebar-2\";a:0:{}s:9:\"sidebar-3\";a:0:{}s:13:\"array_version\";i:3;}','yes'),(100,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(101,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(102,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(103,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(104,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(105,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(106,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(107,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(108,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(109,'cron','a:13:{i:1568568719;a:1:{s:18:\"podlove_jobs_clean\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1568568757;a:1:{s:27:\"podlove_analytics_heartbeat\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1568568762;a:2:{s:32:\"podlove_cleanup_download_intents\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}s:33:\"podlove_calc_hourly_download_sums\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1568568912;a:1:{s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1568578357;a:3:{s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1568602800;a:1:{s:29:\"podlove_salt_download_intents\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1568621587;a:2:{s:19:\"wp_scheduled_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:25:\"delete_expired_transients\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1568626457;a:1:{s:30:\"wp_scheduled_auto_draft_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1568640756;a:1:{s:29:\"podlove_cleanup_logging_table\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1568640761;a:1:{s:36:\"recalculate_episode_download_average\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1568640762;a:1:{s:32:\"podlove_calc_daily_download_sums\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1568640817;a:1:{s:28:\"podlove_validate_image_cache\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}s:7:\"version\";i:2;}','yes'),(113,'theme_mods_twentyseventeen','a:1:{s:18:\"custom_css_post_id\";i:-1;}','yes'),(139,'recently_activated','a:0:{}','yes'),(170,'nonce_key','OMnpDH4)HV4c?)V}K7Tjn);MQ+iJHkTHmRORWEqmUy4=e^#-xlBi6bWsWg$XYv{(','no'),(171,'nonce_salt','A(V?QD}U4J[>,7.7&WtS<wo<O@<!psnV>3iWJ4)o*EVDG]dD%wtdh%ZUO:_@R<ZP','no'),(176,'auth_key','F`twg^gk5(A<0lF53K9Zl}AW*b|@qu8S4c>F|>M?<ylV](FY_M^%92O_XI5N+qkY','no'),(177,'auth_salt','5Zw2sc:Nt&+%23}$cOt`vW]o1,IA.F!&YD%fH+(.*`rmyNiMPcO_F).PA6Nd$J%[','no'),(178,'logged_in_key',',a!JGDgFfX&|DUm(d+)Ltb~!cH*mX,m@zC+/OEx#4Nnf7x(uX9,%Y4?~n<@%`Lhk','no'),(179,'logged_in_salt','hc^P-s~NS`=O=m9ALE<!$!AjN;LFvy^;lROn#[6$qr:]Sw@Zo`L`t+wKsHf^g@d]','no'),(231,'auto_core_update_notified','a:4:{s:4:\"type\";s:7:\"success\";s:5:\"email\";s:17:\"admin@example.com\";s:7:\"version\";s:5:\"5.0.6\";s:9:\"timestamp\";i:1568566081;}','no'),(244,'antispam_bee','a:25:{s:14:\"advanced_check\";i:0;s:12:\"regexp_check\";i:1;s:7:\"spam_ip\";i:1;s:17:\"already_commented\";i:0;s:14:\"gravatar_check\";i:0;s:10:\"time_check\";i:0;s:12:\"ignore_pings\";i:0;s:14:\"always_allowed\";i:0;s:15:\"dashboard_chart\";i:0;s:15:\"dashboard_count\";i:0;s:12:\"country_code\";i:0;s:13:\"country_black\";s:0:\"\";s:13:\"country_white\";s:0:\"\";s:13:\"translate_api\";i:0;s:14:\"translate_lang\";a:0:{}s:12:\"bbcode_check\";i:0;s:9:\"flag_spam\";i:1;s:12:\"email_notify\";i:0;s:9:\"no_notice\";i:0;s:14:\"cronjob_enable\";i:0;s:16:\"cronjob_interval\";i:0;s:13:\"ignore_filter\";i:0;s:11:\"ignore_type\";i:0;s:14:\"reasons_enable\";i:0;s:14:\"ignore_reasons\";a:0:{}}','yes'),(449,'podlove_podcast','a:2:{s:11:\"limit_items\";i:-1;s:19:\"media_file_base_uri\";s:1:\"/\";}','yes'),(450,'podlove_active_modules','a:9:{s:7:\"logging\";s:2:\"on\";s:7:\"widgets\";s:2:\"on\";s:8:\"networks\";s:2:\"on\";s:19:\"analytics_heartbeat\";s:2:\"on\";s:18:\"podlove_web_player\";s:2:\"on\";s:10:\"open_graph\";s:2:\"on\";s:6:\"oembed\";s:2:\"on\";s:13:\"import_export\";s:2:\"on\";s:16:\"subscribe_button\";s:2:\"on\";}','yes'),(451,'podlove','a:6:{s:14:\"merge_episodes\";s:2:\"on\";s:22:\"hide_wp_feed_discovery\";s:3:\"off\";s:20:\"use_post_permastruct\";s:2:\"on\";s:15:\"episode_archive\";s:2:\"on\";s:20:\"episode_archive_slug\";s:9:\"/podcast/\";s:19:\"custom_episode_slug\";s:19:\"/podcast/%podcast%/\";}','yes'),(452,'podlove_template_assignment','a:1:{s:3:\"top\";s:7:\"default\";}','yes'),(453,'podlove_webplayer_formats','a:1:{s:5:\"audio\";a:1:{s:3:\"mp3\";i:1;}}','yes'),(455,'podlove_asset_assignment','a:1:{s:5:\"image\";s:14:\"post-thumbnail\";}','yes'),(456,'podlove_global_messages','a:2:{s:6:\"errors\";a:2:{i:0;s:241:\"You are using the default WordPress permalink structure. This may cause problems with some podcast clients. Go to http://antispambee.pluginkollektiv/wp-admin/options-permalink.php and set it to anything but default (for example \"Post name\").\";i:1;s:27:\"Your podcast needs a title.\";}s:7:\"notices\";a:0:{}}','yes'),(457,'widget_podlove_podcast_license_widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(458,'widget_podlove_recent_episodes_widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(459,'widget_podlove_podcast_widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(460,'widget_podlove_render_template_widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(461,'widget_podlove_subscribe_button_widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(463,'podlove_database_version','140','yes'),(563,'antispambee_db_version','1','yes'),(625,'wp_page_for_privacy_policy','0','yes'),(626,'show_comments_cookies_opt_in','0','yes'),(627,'db_upgraded','','yes'),(640,'can_compress_scripts','1','no'),(645,'widget_akismet_widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(650,'akismet_spam_count','2','yes'),(695,'antispambee_filter','a:2:{s:13:\"country_black\";s:5:\"de,ru\";s:13:\"country_white\";s:0:\"\";}','yes'),(696,'antispambee_post_processors','a:0:{}','yes'),(732,'amp-options','a:12:{s:11:\"experiences\";a:1:{i:0;s:7:\"website\";}s:13:\"theme_support\";s:12:\"transitional\";s:20:\"supported_post_types\";a:3:{i:0;s:4:\"post\";i:1;s:4:\"page\";i:2;s:10:\"attachment\";}s:9:\"analytics\";a:0:{}s:24:\"auto_accept_sanitization\";b:1;s:23:\"all_templates_supported\";b:0;s:19:\"supported_templates\";a:1:{i:0;s:11:\"is_singular\";}s:23:\"enable_response_caching\";b:0;s:7:\"version\";s:5:\"1.2.0\";s:23:\"story_templates_version\";b:0;s:19:\"accept_tree_shaking\";b:1;s:17:\"disable_admin_bar\";b:1;}','no'),(841,'_transient_is_multi_author','0','yes'),(842,'_transient_twentyseventeen_categories','1','yes'),(845,'_site_transient_timeout_theme_roots','1568567868','no'),(846,'_site_transient_theme_roots','a:4:{s:13:\"twentyfifteen\";s:7:\"/themes\";s:14:\"twentynineteen\";s:7:\"/themes\";s:15:\"twentyseventeen\";s:7:\"/themes\";s:13:\"twentysixteen\";s:7:\"/themes\";}','no'),(848,'_site_transient_update_core','O:8:\"stdClass\":4:{s:7:\"updates\";a:3:{i:0;O:8:\"stdClass\":10:{s:8:\"response\";s:7:\"upgrade\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.2.3.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.2.3.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-5.2.3-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-5.2.3-new-bundled.zip\";s:7:\"partial\";b:0;s:8:\"rollback\";b:0;}s:7:\"current\";s:5:\"5.2.3\";s:7:\"version\";s:5:\"5.2.3\";s:11:\"php_version\";s:6:\"5.6.20\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"5.0\";s:15:\"partial_version\";s:0:\"\";}i:1;O:8:\"stdClass\":11:{s:8:\"response\";s:10:\"autoupdate\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.2.3.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.2.3.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-5.2.3-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-5.2.3-new-bundled.zip\";s:7:\"partial\";b:0;s:8:\"rollback\";b:0;}s:7:\"current\";s:5:\"5.2.3\";s:7:\"version\";s:5:\"5.2.3\";s:11:\"php_version\";s:6:\"5.6.20\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"5.0\";s:15:\"partial_version\";s:0:\"\";s:9:\"new_files\";s:1:\"1\";}i:2;O:8:\"stdClass\":11:{s:8:\"response\";s:10:\"autoupdate\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.1.2.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.1.2.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-5.1.2-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-5.1.2-new-bundled.zip\";s:7:\"partial\";b:0;s:8:\"rollback\";b:0;}s:7:\"current\";s:5:\"5.1.2\";s:7:\"version\";s:5:\"5.1.2\";s:11:\"php_version\";s:5:\"5.2.4\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"5.0\";s:15:\"partial_version\";s:0:\"\";s:9:\"new_files\";s:1:\"1\";}}s:12:\"last_checked\";i:1568566079;s:15:\"version_checked\";s:5:\"5.0.6\";s:12:\"translations\";a:0:{}}','no'),(849,'_site_transient_update_themes','O:8:\"stdClass\":4:{s:12:\"last_checked\";i:1568566080;s:7:\"checked\";a:4:{s:13:\"twentyfifteen\";s:3:\"2.0\";s:14:\"twentynineteen\";s:3:\"1.2\";s:15:\"twentyseventeen\";s:3:\"1.7\";s:13:\"twentysixteen\";s:3:\"1.5\";}s:8:\"response\";a:4:{s:13:\"twentyfifteen\";a:6:{s:5:\"theme\";s:13:\"twentyfifteen\";s:11:\"new_version\";s:3:\"2.5\";s:3:\"url\";s:43:\"https://wordpress.org/themes/twentyfifteen/\";s:7:\"package\";s:59:\"https://downloads.wordpress.org/theme/twentyfifteen.2.5.zip\";s:8:\"requires\";s:3:\"4.1\";s:12:\"requires_php\";s:5:\"5.2.4\";}s:14:\"twentynineteen\";a:6:{s:5:\"theme\";s:14:\"twentynineteen\";s:11:\"new_version\";s:3:\"1.4\";s:3:\"url\";s:44:\"https://wordpress.org/themes/twentynineteen/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/theme/twentynineteen.1.4.zip\";s:8:\"requires\";s:5:\"4.9.6\";s:12:\"requires_php\";s:5:\"5.2.4\";}s:15:\"twentyseventeen\";a:6:{s:5:\"theme\";s:15:\"twentyseventeen\";s:11:\"new_version\";s:3:\"2.2\";s:3:\"url\";s:45:\"https://wordpress.org/themes/twentyseventeen/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/theme/twentyseventeen.2.2.zip\";s:8:\"requires\";s:3:\"4.7\";s:12:\"requires_php\";s:5:\"5.2.4\";}s:13:\"twentysixteen\";a:6:{s:5:\"theme\";s:13:\"twentysixteen\";s:11:\"new_version\";s:3:\"2.0\";s:3:\"url\";s:43:\"https://wordpress.org/themes/twentysixteen/\";s:7:\"package\";s:59:\"https://downloads.wordpress.org/theme/twentysixteen.2.0.zip\";s:8:\"requires\";s:3:\"4.4\";s:12:\"requires_php\";s:5:\"5.2.4\";}}s:12:\"translations\";a:0:{}}','no'),(850,'_site_transient_update_plugins','O:8:\"stdClass\":5:{s:12:\"last_checked\";i:1568566082;s:7:\"checked\";a:8:{s:19:\"akismet/akismet.php\";s:5:\"4.0.8\";s:11:\"amp/amp.php\";s:5:\"1.2.0\";s:29:\"antispam-bee/antispam_bee.php\";s:5:\"2.9.1\";s:37:\"antispam-bee-dev/antispam-bee-dev.php\";s:0:\"\";s:51:\"antispam-bee-playground/antispam-bee-playground.php\";s:10:\"dev-master\";s:9:\"hello.php\";s:3:\"1.6\";s:27:\"torro-forms/torro-forms.php\";s:5:\"1.0.0\";s:25:\"webmention/webmention.php\";s:5:\"3.8.4\";}s:8:\"response\";a:5:{s:19:\"akismet/akismet.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:21:\"w.org/plugins/akismet\";s:4:\"slug\";s:7:\"akismet\";s:6:\"plugin\";s:19:\"akismet/akismet.php\";s:11:\"new_version\";s:5:\"4.1.2\";s:3:\"url\";s:38:\"https://wordpress.org/plugins/akismet/\";s:7:\"package\";s:56:\"https://downloads.wordpress.org/plugin/akismet.4.1.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:59:\"https://ps.w.org/akismet/assets/icon-256x256.png?rev=969272\";s:2:\"1x\";s:59:\"https://ps.w.org/akismet/assets/icon-128x128.png?rev=969272\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:61:\"https://ps.w.org/akismet/assets/banner-772x250.jpg?rev=479904\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"5.2.3\";s:12:\"requires_php\";b:0;s:13:\"compatibility\";O:8:\"stdClass\":0:{}}s:11:\"amp/amp.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:17:\"w.org/plugins/amp\";s:4:\"slug\";s:3:\"amp\";s:6:\"plugin\";s:11:\"amp/amp.php\";s:11:\"new_version\";s:5:\"1.2.2\";s:3:\"url\";s:34:\"https://wordpress.org/plugins/amp/\";s:7:\"package\";s:52:\"https://downloads.wordpress.org/plugin/amp.1.2.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:56:\"https://ps.w.org/amp/assets/icon-256x256.png?rev=1987390\";s:2:\"1x\";s:56:\"https://ps.w.org/amp/assets/icon-128x128.png?rev=1987390\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:59:\"https://ps.w.org/amp/assets/banner-1544x500.png?rev=1987390\";s:2:\"1x\";s:58:\"https://ps.w.org/amp/assets/banner-772x250.png?rev=1987390\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"5.2.3\";s:12:\"requires_php\";s:3:\"5.4\";s:13:\"compatibility\";O:8:\"stdClass\":0:{}}s:9:\"hello.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:25:\"w.org/plugins/hello-dolly\";s:4:\"slug\";s:11:\"hello-dolly\";s:6:\"plugin\";s:9:\"hello.php\";s:11:\"new_version\";s:5:\"1.7.2\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/hello-dolly/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/hello-dolly/assets/icon-256x256.jpg?rev=2052855\";s:2:\"1x\";s:64:\"https://ps.w.org/hello-dolly/assets/icon-128x128.jpg?rev=2052855\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:66:\"https://ps.w.org/hello-dolly/assets/banner-772x250.jpg?rev=2052855\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"5.2.3\";s:12:\"requires_php\";b:0;s:13:\"compatibility\";O:8:\"stdClass\":0:{}}s:27:\"torro-forms/torro-forms.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:25:\"w.org/plugins/torro-forms\";s:4:\"slug\";s:11:\"torro-forms\";s:6:\"plugin\";s:27:\"torro-forms/torro-forms.php\";s:11:\"new_version\";s:5:\"1.0.7\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/torro-forms/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/torro-forms.1.0.7.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/torro-forms/assets/icon-256x256.png?rev=1418897\";s:2:\"1x\";s:64:\"https://ps.w.org/torro-forms/assets/icon-128x128.png?rev=1418897\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/torro-forms/assets/banner-1544x500.png?rev=1859446\";s:2:\"1x\";s:66:\"https://ps.w.org/torro-forms/assets/banner-772x250.png?rev=1859446\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"5.0.6\";s:12:\"requires_php\";s:3:\"5.6\";s:13:\"compatibility\";O:8:\"stdClass\":0:{}}s:25:\"webmention/webmention.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:24:\"w.org/plugins/webmention\";s:4:\"slug\";s:10:\"webmention\";s:6:\"plugin\";s:25:\"webmention/webmention.php\";s:11:\"new_version\";s:6:\"3.8.11\";s:3:\"url\";s:41:\"https://wordpress.org/plugins/webmention/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/webmention.3.8.11.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:62:\"https://ps.w.org/webmention/assets/icon-256x256.png?rev=987553\";s:2:\"1x\";s:62:\"https://ps.w.org/webmention/assets/icon-128x128.png?rev=987553\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:65:\"https://ps.w.org/webmention/assets/banner-772x250.jpg?rev=1661490\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"5.2.3\";s:12:\"requires_php\";s:3:\"5.2\";s:13:\"compatibility\";O:8:\"stdClass\":0:{}}}s:12:\"translations\";a:0:{}s:9:\"no_update\";a:1:{s:29:\"antispam-bee/antispam_bee.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:26:\"w.org/plugins/antispam-bee\";s:4:\"slug\";s:12:\"antispam-bee\";s:6:\"plugin\";s:29:\"antispam-bee/antispam_bee.php\";s:11:\"new_version\";s:5:\"2.9.1\";s:3:\"url\";s:43:\"https://wordpress.org/plugins/antispam-bee/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/plugin/antispam-bee.2.9.1.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/antispam-bee/assets/icon-256x256.png?rev=977629\";s:2:\"1x\";s:64:\"https://ps.w.org/antispam-bee/assets/icon-128x128.png?rev=977629\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:68:\"https://ps.w.org/antispam-bee/assets/banner-1544x500.png?rev=1109432\";s:2:\"1x\";s:67:\"https://ps.w.org/antispam-bee/assets/banner-772x250.png?rev=1109432\";}s:11:\"banners_rtl\";a:0:{}}}}','no');
/*!40000 ALTER TABLE `wp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_downloadintent`
--

DROP TABLE IF EXISTS `wp_podlove_downloadintent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_downloadintent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_agent_id` int(11) DEFAULT NULL,
  `media_file_id` int(11) DEFAULT NULL,
  `request_id` varchar(32) DEFAULT NULL,
  `accessed_at` datetime DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `context` varchar(255) DEFAULT NULL,
  `geo_area_id` int(11) DEFAULT NULL,
  `lat` float DEFAULT NULL,
  `lng` float DEFAULT NULL,
  `httprange` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_agent_id` (`user_agent_id`),
  KEY `media_file_id` (`media_file_id`),
  KEY `request_id` (`request_id`),
  KEY `geo_area_id` (`geo_area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_downloadintent`
--

LOCK TABLES `wp_podlove_downloadintent` WRITE;
/*!40000 ALTER TABLE `wp_podlove_downloadintent` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_podlove_downloadintent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_downloadintentclean`
--

DROP TABLE IF EXISTS `wp_podlove_downloadintentclean`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_downloadintentclean` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_agent_id` int(11) DEFAULT NULL,
  `media_file_id` int(11) DEFAULT NULL,
  `request_id` varchar(32) DEFAULT NULL,
  `accessed_at` datetime DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `context` varchar(255) DEFAULT NULL,
  `geo_area_id` int(11) DEFAULT NULL,
  `lat` float DEFAULT NULL,
  `lng` float DEFAULT NULL,
  `httprange` varchar(255) DEFAULT NULL,
  `hours_since_release` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_agent_id` (`user_agent_id`),
  KEY `media_file_id` (`media_file_id`),
  KEY `request_id` (`request_id`),
  KEY `geo_area_id` (`geo_area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_downloadintentclean`
--

LOCK TABLES `wp_podlove_downloadintentclean` WRITE;
/*!40000 ALTER TABLE `wp_podlove_downloadintentclean` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_podlove_downloadintentclean` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_episode`
--

DROP TABLE IF EXISTS `wp_podlove_episode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_episode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) DEFAULT NULL,
  `title` text,
  `subtitle` text,
  `summary` text,
  `number` int(10) unsigned DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL,
  `enable` int(11) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `cover_art` varchar(255) DEFAULT NULL,
  `chapters` text,
  `recording_date` datetime DEFAULT NULL,
  `explicit` tinyint(4) DEFAULT NULL,
  `license_name` text,
  `license_url` text,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_episode`
--

LOCK TABLES `wp_podlove_episode` WRITE;
/*!40000 ALTER TABLE `wp_podlove_episode` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_podlove_episode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_episodeasset`
--

DROP TABLE IF EXISTS `wp_podlove_episodeasset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_episodeasset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `file_type_id` int(11) DEFAULT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `downloadable` int(11) DEFAULT NULL,
  `position` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_type_id` (`file_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_episodeasset`
--

LOCK TABLES `wp_podlove_episodeasset` WRITE;
/*!40000 ALTER TABLE `wp_podlove_episodeasset` DISABLE KEYS */;
INSERT INTO `wp_podlove_episodeasset` VALUES (1,'MP3 Audio',NULL,1,NULL,1,1);
/*!40000 ALTER TABLE `wp_podlove_episodeasset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_feed`
--

DROP TABLE IF EXISTS `wp_podlove_feed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_asset_id` int(11) DEFAULT NULL,
  `itunes_feed_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `position` float DEFAULT NULL,
  `redirect_url` varchar(255) DEFAULT NULL,
  `redirect_http_status` int(11) DEFAULT NULL,
  `enable` int(11) DEFAULT NULL,
  `discoverable` int(11) DEFAULT NULL,
  `limit_items` int(11) DEFAULT NULL,
  `embed_content_encoded` int(11) DEFAULT NULL,
  `append_name_to_podcast_title` tinyint(1) DEFAULT NULL,
  `protected` tinyint(1) DEFAULT NULL,
  `protection_type` tinyint(1) DEFAULT NULL,
  `protection_user` varchar(60) DEFAULT NULL,
  `protection_password` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `episode_asset_id` (`episode_asset_id`),
  KEY `itunes_feed_id` (`itunes_feed_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_feed`
--

LOCK TABLES `wp_podlove_feed` WRITE;
/*!40000 ALTER TABLE `wp_podlove_feed` DISABLE KEYS */;
INSERT INTO `wp_podlove_feed` VALUES (1,1,NULL,'MP3 Feed','MP3 Feed','mp3',1,NULL,NULL,1,1,0,1,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `wp_podlove_feed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_filetype`
--

DROP TABLE IF EXISTS `wp_podlove_filetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_filetype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `extension` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_filetype`
--

LOCK TABLES `wp_podlove_filetype` WRITE;
/*!40000 ALTER TABLE `wp_podlove_filetype` DISABLE KEYS */;
INSERT INTO `wp_podlove_filetype` VALUES (1,'MP3 Audio','audio','audio/mpeg','mp3'),(2,'BitTorrent (MP3 Audio)','audio','application/x-bittorrent','mp3.torrent'),(3,'MPEG-1 Video','video','video/mpeg','mpg'),(4,'MPEG-4 AAC Audio','audio','audio/mp4','m4a'),(5,'MPEG-4 ALAC Audio','audio','audio/mp4','m4a'),(6,'MPEG-4 Video','video','video/mp4','mp4'),(7,'M4V Video (Apple)','video','video/x-m4v','m4v'),(8,'Ogg Vorbis Audio','audio','audio/ogg','oga'),(9,'Ogg Vorbis Audio','audio','audio/ogg','ogg'),(10,'Ogg Theora Video','video','video/ogg','ogv'),(11,'WebM Audio','audio','audio/webm','webm'),(12,'WebM Video','video','video/webm','webm'),(13,'FLAC Audio','audio','audio/flac','flac'),(14,'Opus Audio','audio','audio/ogg;codecs=opus','opus'),(15,'Matroska Audio','audio','audio/x-matroska','mka'),(16,'Matroska Video','video','video/x-matroska','mkv'),(17,'PDF Document','ebook','application/pdf','pdf'),(18,'ePub Document','ebook','application/epub+zip','epub'),(19,'PNG Image','image','image/png','png'),(20,'JPEG Image','image','image/jpeg','jpg'),(21,'mp4chaps Chapter File','chapters','text/plain','chapters.txt'),(22,'Podlove Simple Chapters','chapters','application/xml','psc'),(23,'Subrip Captions','captions','application/x-subrip','srt'),(24,'WebVTT Captions','captions','text/vtt','vtt'),(25,'Auphonic Production Description','metadata','application/json','json'),(26,'Podigee Transcript','transcript','plain/text','txt');
/*!40000 ALTER TABLE `wp_podlove_filetype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_geoarea`
--

DROP TABLE IF EXISTS `wp_podlove_geoarea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_geoarea` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `geoname_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `code` varchar(5) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `geoname_id` (`geoname_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_geoarea`
--

LOCK TABLES `wp_podlove_geoarea` WRITE;
/*!40000 ALTER TABLE `wp_podlove_geoarea` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_podlove_geoarea` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_geoareaname`
--

DROP TABLE IF EXISTS `wp_podlove_geoareaname`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_geoareaname` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) DEFAULT NULL,
  `language` varchar(5) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `area_id` (`area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_geoareaname`
--

LOCK TABLES `wp_podlove_geoareaname` WRITE;
/*!40000 ALTER TABLE `wp_podlove_geoareaname` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_podlove_geoareaname` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_job`
--

DROP TABLE IF EXISTS `wp_podlove_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class` varchar(255) DEFAULT NULL,
  `args` longtext,
  `steps_total` int(11) DEFAULT NULL,
  `steps_progress` int(11) DEFAULT NULL,
  `active_run_time` float DEFAULT NULL,
  `state` longtext,
  `wakeups` int(11) DEFAULT NULL,
  `sleeps` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_job`
--

LOCK TABLES `wp_podlove_job` WRITE;
/*!40000 ALTER TABLE `wp_podlove_job` DISABLE KEYS */;
INSERT INTO `wp_podlove_job` VALUES (1,'Podlove\\Jobs\\RequestIdRehashJob','a:2:{s:13:\"intents_total\";i:0;s:12:\"ids_per_step\";i:1000;}',0,0,0,NULL,0,0,'2018-10-08 13:32:43','2018-10-08 13:32:43'),(2,'Podlove\\Jobs\\DownloadIntentCleanupJob','a:3:{s:13:\"intents_total\";i:0;s:16:\"intents_per_step\";i:100000;s:10:\"delete_all\";b:0;}',0,0,0,'a:1:{s:11:\"previous_id\";i:0;}',0,0,'2018-10-08 13:32:48','2018-10-08 13:32:48'),(3,'Podlove\\Jobs\\DownloadTimedAggregatorJob','a:1:{s:5:\"force\";b:0;}',0,0,0,'a:2:{s:11:\"episode_ids\";a:0:{}s:14:\"total_episodes\";i:0;}',0,0,'2018-10-08 13:32:48','2018-10-08 13:32:48'),(4,'Podlove\\Jobs\\DownloadTimedAggregatorJob','a:1:{s:5:\"force\";b:1;}',0,0,0,'a:2:{s:11:\"episode_ids\";a:0:{}s:14:\"total_episodes\";i:0;}',0,0,'2018-10-08 13:32:49','2018-10-08 13:32:49');
/*!40000 ALTER TABLE `wp_podlove_job` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_mediafile`
--

DROP TABLE IF EXISTS `wp_podlove_mediafile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_mediafile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) DEFAULT NULL,
  `episode_asset_id` int(11) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `etag` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `episode_id` (`episode_id`),
  KEY `episode_asset_id` (`episode_asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_mediafile`
--

LOCK TABLES `wp_podlove_mediafile` WRITE;
/*!40000 ALTER TABLE `wp_podlove_mediafile` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_podlove_mediafile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_modules_analyticsheartbeat_heartbeat`
--

DROP TABLE IF EXISTS `wp_podlove_modules_analyticsheartbeat_heartbeat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_modules_analyticsheartbeat_heartbeat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_start` datetime DEFAULT NULL,
  `status_end` datetime DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `beats` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_modules_analyticsheartbeat_heartbeat`
--

LOCK TABLES `wp_podlove_modules_analyticsheartbeat_heartbeat` WRITE;
/*!40000 ALTER TABLE `wp_podlove_modules_analyticsheartbeat_heartbeat` DISABLE KEYS */;
INSERT INTO `wp_podlove_modules_analyticsheartbeat_heartbeat` VALUES (1,'2018-10-08 13:32:46','2018-10-08 13:32:46','ptm_analytics',1);
/*!40000 ALTER TABLE `wp_podlove_modules_analyticsheartbeat_heartbeat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_modules_logging_logtable`
--

DROP TABLE IF EXISTS `wp_podlove_modules_logging_logtable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_modules_logging_logtable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `message` longtext,
  `context` longtext,
  `time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_modules_logging_logtable`
--

LOCK TABLES `wp_podlove_modules_logging_logtable` WRITE;
/*!40000 ALTER TABLE `wp_podlove_modules_logging_logtable` DISABLE KEYS */;
INSERT INTO `wp_podlove_modules_logging_logtable` VALUES (1,'Podlove',100,'[job] [id 1] start \\Podlove\\Jobs\\RequestIdRehashJob','[]',1539005563),(2,'Podlove',100,'[job] [id 2] start \\Podlove\\Jobs\\DownloadIntentCleanupJob','[]',1539005568),(3,'Podlove',100,'[job] [id 3] start \\Podlove\\Jobs\\DownloadTimedAggregatorJob','[]',1539005568),(4,'Podlove',100,'[job] [id 4] start \\Podlove\\Jobs\\DownloadTimedAggregatorJob','[]',1539005569),(5,'Podlove',200,'Finished validating 0 images in 2 ms','[]',1539005620);
/*!40000 ALTER TABLE `wp_podlove_modules_logging_logtable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_template`
--

DROP TABLE IF EXISTS `wp_podlove_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_template`
--

LOCK TABLES `wp_podlove_template` WRITE;
/*!40000 ALTER TABLE `wp_podlove_template` DISABLE KEYS */;
INSERT INTO `wp_podlove_template` VALUES (1,'default','{% if not is_feed() %}\n\n	{# display web player for episode #}\n	{{ episode.player }}\n	\n	{# display contributors if module is active #}\n	{% if shortcode_exists(\"podlove-episode-contributor-list\") %}\n		{# see http://docs.podlove.org/podlove-publisher/reference/shortcodes.html#contributors for parameters #}\n		[podlove-episode-contributor-list]\n	{% endif %}\n\n{% endif %}');
/*!40000 ALTER TABLE `wp_podlove_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_podlove_useragent`
--

DROP TABLE IF EXISTS `wp_podlove_useragent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_podlove_useragent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_agent` text,
  `bot` tinyint(4) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_version` varchar(255) DEFAULT NULL,
  `client_type` varchar(255) DEFAULT NULL,
  `os_name` varchar(255) DEFAULT NULL,
  `os_version` varchar(255) DEFAULT NULL,
  `device_brand` varchar(255) DEFAULT NULL,
  `device_model` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_agent` (`user_agent`(400))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_podlove_useragent`
--

LOCK TABLES `wp_podlove_useragent` WRITE;
/*!40000 ALTER TABLE `wp_podlove_useragent` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_podlove_useragent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_postmeta`
--

DROP TABLE IF EXISTS `wp_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_postmeta`
--

LOCK TABLES `wp_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;
INSERT INTO `wp_postmeta` VALUES (1,2,'_wp_page_template','default'),(2,1,'_edit_lock','1527845513:1'),(69,24,'_edit_last','1'),(70,24,'_edit_lock','1527858599:1'),(75,26,'_edit_last','1'),(76,26,'_edit_lock','1527858637:1'),(79,28,'_edit_last','1'),(80,28,'_edit_lock','1527858694:1'),(81,32,'_amp_validated_environment','a:2:{s:5:\"theme\";s:15:\"twentyseventeen\";s:7:\"plugins\";a:3:{i:0;s:11:\"amp/amp.php\";i:1;s:37:\"antispam-bee-dev/antispam-bee-dev.php\";i:2;s:29:\"antispam-bee/antispam_bee.php\";}}'),(82,32,'_wp_old_date','2019-06-03'),(83,32,'_amp_queried_object','a:2:{s:2:\"id\";i:28;s:4:\"type\";s:4:\"post\";}'),(84,32,'_edit_lock','1561029974:1');
/*!40000 ALTER TABLE `wp_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_posts`
--

DROP TABLE IF EXISTS `wp_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_title` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_excerpt` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `post_password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `post_name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `to_ping` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `pinged` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `guid` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT '0',
  `post_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_posts`
--

LOCK TABLES `wp_posts` WRITE;
/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;
INSERT INTO `wp_posts` VALUES (1,1,'2018-05-11 08:11:30','2018-05-11 08:11:30','Welcome to WordPress. This is your first post. Edit or delete it, then start writing!','Hello world!','','publish','open','open','','hello-world','','','2018-05-11 08:11:30','2018-05-11 08:11:30','',0,'http://antispambee.pluginkollektiv/?p=1',0,'post','',0),(2,1,'2018-05-11 08:11:30','2018-05-11 08:11:30','This is an example page. It\'s different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:\n\n<blockquote>Hi there! I\'m a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin\' caught in the rain.)</blockquote>\n\n...or something like this:\n\n<blockquote>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.</blockquote>\n\nAs a new WordPress user, you should go to <a href=\"http://antispambee.pluginkollektiv/wp-admin/\">your dashboard</a> to delete this page and create new pages for your content. Have fun!','Sample Page','','publish','closed','open','','sample-page','','','2018-05-11 08:11:30','2018-05-11 08:11:30','',0,'http://antispambee.pluginkollektiv/?page_id=2',0,'page','',0),(24,1,'2018-06-01 12:45:01','2018-06-01 12:45:01','Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. \r\n\r\nDuis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi. Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. \r\n\r\nUt wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi. \r\n\r\nNam liber tempor cum soluta nobis eleifend option congue nihil imperdiet doming id quod mazim placerat facer possim assum. Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. \r\n\r\nDuis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis. ','Lorem','','publish','open','open','','lorem','','\nhttp://antispambee.pluginkollektiv/?p=1','2018-06-01 12:45:10','2018-06-01 12:45:10','',0,'http://antispambee.pluginkollektiv/?p=24',0,'post','',0),(25,1,'2018-06-01 12:45:01','2018-06-01 12:45:01','Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. \r\n\r\nDuis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi. Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. \r\n\r\nUt wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi. \r\n\r\nNam liber tempor cum soluta nobis eleifend option congue nihil imperdiet doming id quod mazim placerat facer possim assum. Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. \r\n\r\nDuis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis. ','Lorem','','inherit','closed','closed','','24-revision-v1','','','2018-06-01 12:45:01','2018-06-01 12:45:01','',24,'http://antispambee.pluginkollektiv/?p=25',0,'revision','',0),(26,1,'2018-06-01 13:12:50','2018-06-01 13:12:50','This is another test in English <a href=\"http://antispambee.pluginkollektiv/?p=24\">while we should have</a> german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. ','An english blog post','','publish','open','open','','an-english-blog-post','','\nhttp://antispambee.pluginkollektiv/?p=24','2018-06-01 13:12:50','2018-06-01 13:12:50','',0,'http://antispambee.pluginkollektiv/?p=26',0,'post','',0),(27,1,'2018-06-01 13:12:50','2018-06-01 13:12:50','This is another test in English <a href=\"http://antispambee.pluginkollektiv/?p=24\">while we should have</a> german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. This is another test in English while we should have german. ','An english blog post','','inherit','closed','closed','','26-revision-v1','','','2018-06-01 13:12:50','2018-06-01 13:12:50','',26,'http://antispambee.pluginkollektiv/?p=27',0,'revision','',0),(28,1,'2018-06-01 13:13:50','2018-06-01 13:13:50','lets run into <a href=\"http://antispambee.pluginkollektiv/?p=26\">the spam</a> check again.','another one in english','','publish','open','open','','another-one-in-english','','','2018-06-01 13:13:50','2018-06-01 13:13:50','',0,'http://antispambee.pluginkollektiv/?p=28',0,'post','',0),(29,1,'2018-06-01 13:13:50','2018-06-01 13:13:50','lets run into <a href=\"http://antispambee.pluginkollektiv/?p=26\">the spam</a> check again.','another one in english','','inherit','closed','closed','','28-revision-v1','','','2018-06-01 13:13:50','2018-06-01 13:13:50','',28,'http://antispambee.pluginkollektiv/?p=29',0,'revision','',0),(32,1,'2019-06-20 11:23:38','2019-06-20 11:23:38','[]','https://antispambee.pluginkollektiv/another-one-in-english/','','publish','closed','closed','','2528854de098c246b6b21c14c18cb0ef','','','2019-06-20 11:23:38','2019-06-20 11:23:38','',0,'http://antispambee.pluginkollektiv/amp_validated_url/2528854de098c246b6b21c14c18cb0ef/',0,'amp_validated_url','',0);
/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_relationships`
--

DROP TABLE IF EXISTS `wp_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_relationships`
--

LOCK TABLES `wp_term_relationships` WRITE;
/*!40000 ALTER TABLE `wp_term_relationships` DISABLE KEYS */;
INSERT INTO `wp_term_relationships` VALUES (1,1,0),(24,1,0),(26,1,0),(28,1,0);
/*!40000 ALTER TABLE `wp_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_taxonomy`
--

DROP TABLE IF EXISTS `wp_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `taxonomy` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_taxonomy`
--

LOCK TABLES `wp_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wp_term_taxonomy` DISABLE KEYS */;
INSERT INTO `wp_term_taxonomy` VALUES (1,1,'category','',0,4);
/*!40000 ALTER TABLE `wp_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_termmeta`
--

DROP TABLE IF EXISTS `wp_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_termmeta`
--

LOCK TABLES `wp_termmeta` WRITE;
/*!40000 ALTER TABLE `wp_termmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_terms`
--

DROP TABLE IF EXISTS `wp_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `slug` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_terms`
--

LOCK TABLES `wp_terms` WRITE;
/*!40000 ALTER TABLE `wp_terms` DISABLE KEYS */;
INSERT INTO `wp_terms` VALUES (1,'Uncategorized','uncategorized',0);
/*!40000 ALTER TABLE `wp_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_usermeta`
--

DROP TABLE IF EXISTS `wp_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_usermeta`
--

LOCK TABLES `wp_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_usermeta` DISABLE KEYS */;
INSERT INTO `wp_usermeta` VALUES (1,1,'nickname','admin'),(2,1,'first_name',''),(3,1,'last_name',''),(4,1,'description',''),(5,1,'rich_editing','true'),(6,1,'syntax_highlighting','true'),(7,1,'comment_shortcuts','false'),(8,1,'admin_color','fresh'),(9,1,'use_ssl','0'),(10,1,'show_admin_bar_front','true'),(11,1,'locale',''),(12,1,'wp_capabilities','a:1:{s:13:\"administrator\";b:1;}'),(13,1,'wp_user_level','10'),(15,1,'show_welcome_panel','1'),(17,1,'wp_dashboard_quick_press_last_post_id','33'),(18,1,'community-events-location','a:1:{s:2:\"ip\";s:9:\"127.0.0.0\";}'),(19,1,'closedpostboxes_dashboard','a:0:{}'),(20,1,'metaboxhidden_dashboard','a:0:{}'),(21,1,'closedpostboxes_post','a:0:{}'),(22,1,'metaboxhidden_post','a:3:{i:0;s:10:\"postcustom\";i:1;s:7:\"slugdiv\";i:2;s:9:\"authordiv\";}'),(25,1,'wp_user-settings','editor=tinymce'),(26,1,'wp_user-settings-time','1537797106'),(27,1,'dismissed_wp_pointers','wp496_privacy,amp_stories_support_pointer_12'),(28,1,'session_tokens','a:1:{s:64:\"f3121344a51e6763cd7b52082419aa67b3a2367d58d32334ba83f387ef72b6f5\";a:4:{s:10:\"expiration\";i:1561202537;s:2:\"ip\";s:9:\"127.0.0.1\";s:2:\"ua\";s:76:\"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:66.0) Gecko/20100101 Firefox/66.0\";s:5:\"login\";i:1561029737;}}');
/*!40000 ALTER TABLE `wp_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_users`
--

DROP TABLE IF EXISTS `wp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_pass` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_nicename` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_url` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT '0',
  `display_name` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_users`
--

LOCK TABLES `wp_users` WRITE;
/*!40000 ALTER TABLE `wp_users` DISABLE KEYS */;
INSERT INTO `wp_users` VALUES (1,'admin','$P$BxHYgJ3Bsg42rUUYNnT15dUwUgW2tN.','admin','admin@example.com','','2018-05-11 08:11:29','',0,'admin');
/*!40000 ALTER TABLE `wp_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-09-15 19:50:08
