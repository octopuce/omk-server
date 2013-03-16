#!/usr/bin/env php
<?php

   /** ************************************************************
    * CRON trigger process. This daemon calls the "api.php?action=cron" 
    * of each client every 5 minutes. 
    * If the client is inactive for a long time (>1 month) we will only call it once every hour.
    * on the machine having the physical disks available.
    * returns as soon as a download is finished
    * or wait 10 seconds if no download is queued.
    */

if (!function_exists("curl_init")) {
  error_log("php-curl not installed or not enabled, exit.\n");
  exit(1);
}

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';

$api=new Api();

while (true) {

  // We search for the list of crontasks to launch (date/time selector is done there)
  $list=$api->cronTasksList();

  $urllist=array();
  foreach($list as $onecron) {
    if (substr($onecron["url"],0,7)=="http://" || substr($onecron["url"],0,8)=="https://") {
      if (strpos($onecron["url"],"?")!==false) {
	$onecron["url"].="&action=cron";
      } else {
	$onecron["url"].="?action=cron";	
      }
      $urllist[]=array("url" => $onecron["url"], "uid" => $onecron["uid"]);
    }
  }

  if (empty($urllist)) { // nothing to do : 
    sleep(60); // Let's try again in a minute
    continue;
  }
  
  // cron_callback($url, $content, $curlobj) will be called at the end of each http call.
  $api->rolling_curl($urllist, "cron_callback");

} // while true


function cron_callback($url,$content,$curl) {
  global $api;

  if (empty($url["uid"])) return; // not normal...

  if ($curl["http_code"]==200) {
    $api->cronCallOk($url["uid"]);
  } else {
    $api->cronCallFailed($url["uid"]);
  }
}
