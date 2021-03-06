<?php

define("ADMIN_MAIL","benjamin@octopuce.fr");
define("API_ROOT","http://omkt.octopuce.fr/api");
define("CLIENT_KEY","aa7ohxaelu4ue4ERoo3a");
define("API_KEY",@file_get_contents(__DIR__."/apikey"));
define("APPLICATION_NAME","OMK Transcoder Test Client");
define("APPLICATION_VERSION","1.0");
// url (ending by /) where this test/ folder is pointed at
define("CLIENT_ROOT","http://omk-test-client.octopuce.fr/");



/**
 * Call a simple $method with key=>value $params 
 * returns the returned text, undecrypted. 
 */
function call($method,$params=null) {
  $url="";
  if (is_array($params) && count($params)) {
    $params["transcoder_key"] = API_KEY;
    $params["application"] = APPLICATION_NAME;
    $params["version"] = APPLICATION_VERSION;
    foreach($params as $k=>$v) {
      $url.="&";
      $url.=urlencode($k)."=".urlencode($v);
    }
  }
  $f=fopen(API_ROOT."/?action=".$method.$url,"rb");
  if (!$f) return false;
  $content="";
  while ($s=fgets($f,1024)) {
    $content.=$s;
  }
  fclose($f);
  return json_decode($content);
}

function myhash($str) {
  return substr(md5(CLIENT_ROOT."_".$str),0,10);
}

?>