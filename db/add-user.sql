INSERT INTO `users` (
`uid` ,
`login` ,
`pass` ,
`email` ,
`enabled` ,
`admin`
)
VALUES (
NULL , 'admin', SHA1( 'admin' ) , 'admin@open-mediakit.org', '1', '1'
);
