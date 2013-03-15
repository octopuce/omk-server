<?php

define("API_ROOT","http://omk.local/api");

/**
 * Call a simple $method with key=>value $params 
 * returns the returned text, undecrypted. 
 */
function call($method,$params) {
  $url="";
  foreach($params as $k=>$v) {
    if ($url) $url.="&"; else $url.="?";
    $url.=urlencode($k)."=".urlencode($v);
  }
  $f=fopen(API_ROOT."/".$method.$url,"rb");
  if (!$f) return false;
  $content="";
  while ($s=fgets($f,1024)) {
    $content.=$s;
  }
  fclose($f);
  return $content;
}


?>