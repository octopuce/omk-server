<?php

define("API_ROOT","http://omk.local/api");
define("CLIENT_ROOT","http://omk-client.local/");



/**
 * Call a simple $method with key=>value $params 
 * returns the returned text, undecrypted. 
 */
function call($method,$params) {
  $url="";
  foreach($params as $k=>$v) {
    $url.="&";
    $url.=urlencode($k)."=".urlencode($v);
  }
  $f=fopen(API_ROOT."/?action=".$method.$url,"rb");
  if (!$f) return false;
  $content="";
  while ($s=fgets($f,1024)) {
    $content.=$s;
  }
  fclose($f);
  return $content;
}


?>