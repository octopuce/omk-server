<?php

// TEST the API
require_once("zz_config.php");

/**
   * email: the email address of the subscriber (*it will be verified by sending an email*)
   * url: url of the api root of the client. will be used to call 
   * application: client application that request an account
   * version: version of the client application
   * non-mandatory parameters:
   * lang: language of the client, default to en_US (for discussion & email verification text)
   */
$result=call("app_subscribe",array(
		       "email" => ADMIN_MAIL,
		       "app_key" => CLIENT_KEY,
		       "url" => CLIENT_ROOT."zz_api.php",
		       ));

file_put_contents(__DIR__."/apikey",$result->apikey);
print_r($result);


?>