<?php

// TEST the API
require_once("zz_config.php");

/**
 * Add a new media to the Transcoder. The given URL is absolute and static in that test-case
 */
$id=1;
$result=call("app_new_media",
	     array(
		   "key" => API_KEY,
		   "application" => APPLICATION_NAME,
		   "version" => APPLICATION_VERSION,

		   "id" => $id,
		   "url" => CLIENT_ROOT."zz_api.php?action=getvideo&key=".myhash($id)."id=".$id
		   ));

print_r($result);


?>