
ALTER TABLE `users`
  ADD `lastactivity` DATETIME NULL ,
  ADD `lastcron` DATETIME NULL,
  ADD `lastcronsuccess` DATETIME NULL,
  ADD INDEX ( `lastactivity` ),
  ADD INDEX ( `lastcron` ),
  ADD INDEX ( `lastcronsuccess` );

ALTER TABLE `users` 
      ADD INDEX ( `enabled` , `validated` ) ;

ALTER TABLE `users` 
      ADD `allowedadapters` VARCHAR( 255 ) NOT NULL DEFAULT 'http';

ALTER TABLE `queue` 
      ADD `adapter` VARCHAR( 64 ) NOT NULL ,
      ADD INDEX ( `adapter` );

