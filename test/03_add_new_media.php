<?php

// TEST the API
require_once("zz_config.php");

/**
 * Add a new media to the Transcoder. The given URL is absolute and static in that test-case
 */
$id=intval(time()/60);
$result=call("app_new_media",
	     array(
		   "id" => $id,
		   "url" => CLIENT_ROOT."zz_api.php?action=getvideo&id=".$id
		   ));

print_r($result);


?>