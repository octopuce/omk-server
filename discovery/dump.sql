
CREATE TABLE IF NOT EXISTS `transcoder` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `url` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `settings` text NOT NULL,
  `ip` varchar(64) NOT NULL,
  `enabled` tinyint(3) unsigned NOT NULL default '1',
  `emailvalid` datetime default NULL,
  `transcodervalid` datetime default NULL,
  `lastseen` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  INDEX (`enabled`),
  INDEX (`lastseen`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Public Transcoder and their status.' AUTO_INCREMENT=1 ;
