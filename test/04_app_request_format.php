<?php

// TEST the API
require_once("zz_config.php");

/**
 * Request a list of transcodes from the transcoder
 */
$id=1;
$result=call("app_request_format",
	     array(
		   "id" => $id,
		   "settings_id_list[0]" => 11,
		   "settings_id_list[1]" => 15,
		   "settings_id_list[2]" => 101,
		   ));

print_r($result);


?>