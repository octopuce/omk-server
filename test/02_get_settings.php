<?php

// TEST the API
require_once("zz_config.php");

/**
 * Returns the settings available in the transcoder.
 */
$result=call("app_get_settings");

print_r($result);


?>