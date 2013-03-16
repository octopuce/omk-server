
ALTER TABLE `users`
  ADD `lastactivity` DATETIME NULL ,
  ADD `lastcron` DATETIME NULL,
  ADD `lastcronsuccess` DATETIME NULL,
  ADD INDEX ( `lastactivity` ),
  ADD INDEX ( `lastcron` ),
  ADD INDEX ( `lastcronsuccess` );

ALTER TABLE `users` ADD INDEX ( `enabled` , `validated` ) ;

