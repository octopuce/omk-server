<?php

require_once("config.php");

  // every hour, check all the transcoders.
  // remove immediately a non-running transcoder
  // reinsert a transcoder running fine for more than 2 hours
  // get its settings and save them to the database if different than the current settings
  // only remember the settings ID, not their details
$r=mysql_query("SELECT *,UNIX_TIMESTAMP(transcodervalid) AS tsvalid  FROM transcoder WHERE enabled>0;");
while ($c=mysql_fetch_array($r)) {
  // get transcoder settings : 
  $url=$c["url"];
  if (strpos($url,"?")!==false) $url.="&"; else $url.="?";
  $f=fopen($url."action=app_get_settings","rb");
  if (!$f) {
    echo "Error reading ".$url."action=app_get_settings for public transcoder '".$c["name"]."', disabling \n";
    mysql_query("UPDATE transcoder SET enabled=1 WHERE id='".$c["id"]."';");
  } else {
    $content="";
    while ($s=fgets($f,1024)) {
      $content.=$s;
    }
    fclose($f);
    $settings=@json_decode($content);
    if (!is_array($settings) || !count($settings)) {
      echo "Error reading settings for public transcoder '".$c["name"]."', disabling \n";
      mysql_query("UPDATE transcoder SET enabled=1 WHERE id='".$c["id"]."';");
    } else {
      $sets=array();
      foreach($settings as $setting) {
	$sets[]=intval($setting->id);
      }
      $sets=json_encode($sets);
      $sql="";
      if ($sets!=$c["settings"]) {
	$sql.=", settings='".addslashes($sets)."'";
      }
      echo "Update of settings for transcoder '".$c["name"]."' \n";
      if ($c["enabled"]==1 && $c["tsvalid"]>time()-4000) {
	$sql.=", enabled=2";
      } 
      mysql_query("UPDATE transcoder SET transcodervalid=NOW() $sql WHERE id='".$c["id"]."';");
    } // settings OK
  } // can connect to http
  
} // for each transcoder.


