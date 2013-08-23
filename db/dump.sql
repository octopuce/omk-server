
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
-- Table structure for table `apicalls`
--

DROP TABLE IF EXISTS `apicalls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apicalls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `calltime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user` int(10) unsigned NOT NULL,
  `api` varchar(64) NOT NULL,
  `params` text NOT NULL,
  `ip` varchar(64) NOT NULL,
  `appversion` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `calltime` (`calltime`),
  KEY `user` (`user`),
  KEY `call` (`api`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Log of api calls from users, allow limit enforcment and log';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `appversions`
--

DROP TABLE IF EXISTS `appversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appversions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application` varchar(128) NOT NULL,
  `version` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application` (`application`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Applications and Versions using the API Calls';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appversions`
--

LOCK TABLES `appversions` WRITE;
/*!40000 ALTER TABLE `appversions` DISABLE KEYS */;
INSERT INTO `appversions` VALUES (1,'OMK Transcoder Test Client','1.0');
/*!40000 ALTER TABLE `appversions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner` int(10) unsigned NOT NULL,
  `remoteid` bigint(20) unsigned NOT NULL,
  `remoteurl` text NOT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `datecreate` datetime NOT NULL,
  `dateupdate` datetime NOT NULL,
  `metadata` text NOT NULL,
  `adapter` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_2` (`owner`,`remoteid`),
  KEY `adapter` (`adapter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Media and their status';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `queue`
--

DROP TABLE IF EXISTS `queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Queue Job Number',
  `user` int(10) unsigned NOT NULL,
  `datequeue` datetime NOT NULL,
  `datetry` datetime NOT NULL,
  `datelaunch` datetime NOT NULL,
  `datedone` datetime NOT NULL,
  `status` tinyint(3) unsigned NOT NULL COMMENT 'The task status (see STATUS_ constants)',
  `retry` int(10) unsigned NOT NULL,
  `lockhost` char(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'who is locking that job (hostname)',
  `lockpid` int(10) unsigned NOT NULL COMMENT 'who is locking that job (pid)',
  `task` int(10) unsigned NOT NULL COMMENT 'The task that job shall do',
  `params` text NOT NULL,
  `mediaid` bigint(20) unsigned NOT NULL,
  `formatid` int(10) unsigned DEFAULT NULL,
  `adapter` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `locked` (`lockhost`),
  KEY `task` (`task`),
  KEY `user` (`user`),
  KEY `retry` (`retry`),
  KEY `adapter` (`adapter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The Tasks that should be achieved by the transcoder';
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `transcodes`
--
DROP TABLE IF EXISTS `transcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transcodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mediaid` int(10) unsigned NOT NULL,
  `setting` int(10) unsigned NOT NULL,
  `subsetting` int(10) unsigned NOT NULL,
  `status` int(10) unsigned NOT NULL,
  `datecreate` datetime NOT NULL,
  `dateupdate` datetime NOT NULL,
  `metadata` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Transcodes asked by the users';
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pass` varchar(80) NOT NULL COMMENT 'crypt',
  `email` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `validated` tinyint(4) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `apikey` char(32) NOT NULL,
  `clientkey` char(32) NOT NULL,
  `url` text NOT NULL COMMENT 'API URL of the client',
  `lastactivity` datetime NOT NULL,
  `lastcron` datetime NOT NULL,
  `lastcronsuccess` datetime NOT NULL, 
  `allowedadapters` varchar(255) NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `lastactivity` (`lastactivity`),
  KEY `lastcron` (`lastcron`),
  KEY `lastcronsuccess` (`lastcronsuccess`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The User accounts of people (clients) registered in this omk-server';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`uid`, `pass`, `email`, `enabled`, `validated`, `admin`, `apikey`, `clientkey`, `url`, `lastactivity`, `lastcron`, `lastcronsuccess`, `allowedadapters`) VALUES
(1, '$5$rounds=1000$Lp6Th0j9iUpTWhcL$0pIPH5EI4TD4AJY4VdSmfk/gauIQvYXj7RSqqT.M6x2', 'admin@open-mediakit.org', 1, 0, 1, 'poipoi', '', '', '2013-07-20 00:45:12', '2013-07-20 00:41:09', '2013-07-20 00:41:09', 'http');
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

