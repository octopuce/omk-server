<?php

// TEST the API
require_once("zz_config.php");

/**
 * Returns the settings available in the transcoder.
 */
$result=call("app_get_settings",
	     array(
		   "key" => API_KEY,
		   "application" => APPLICATION_NAME,
		   "version" => APPLICATION_VERSION,
				      ));

print_r($result);


?>