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
		   "settings_id_list[0]" => 1,
		   "settings_id_list[1]" => 12,
		   "settings_id_list[2]" => 13,
		   ));

print_r($result);


?>