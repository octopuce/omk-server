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
require_once __DIR__ . '/../libs/cron.php';
require_once __DIR__ . '/../libs/api.php';

$cron=new Cron();
$api=new Api();
$api->log_caller="cron-calling-daemon"; 

while (true) {

  // We search for the list of crontasks to launch (date/time selector is done there)
  $list=$cron->cronTasksList();

  $urllist=array();
  foreach($list as $onecron) {
    if (substr($onecron["url"],0,7)=="http://" || substr($onecron["url"],0,8)=="https://") {
      if (strpos($onecron["url"],"?")!==false) {
	$onecron["url"].="&action=transcoder_cron&app_key=".$onecron['clientkey'];
      } else {
	$onecron["url"].="?action=transcoder_cron&app_key=".$onecron['clientkey'];	
      }
      $urllist[]=array("url" => $onecron["url"], "uid" => $onecron["uid"]);
    }
  }

  if (empty($urllist)) { // nothing to do : 
    sleep(60); // Let's try again in a minute
    continue;
  }
  
  $api->log(LOG_INFO, "Launching ".count($urllist)." cron calls");
  // cron_callback($url, $content, $curlobj) will be called at the end of each http call.
  $cron->rolling_curl($urllist, "cron_callback");

} // while true


function cron_callback($url,$content,$curl) {
  global $cron,$api;
  
  //  $api->log(LOG_DEBUG, "return from cron call for url ".$url." has http_code ".$curl["http_code"]);

  if (empty($url["uid"])) return; // not normal...

  if ($curl["http_code"]==200) {
    $cron->cronCallOk($url["uid"]);
  } else {
    $cron->cronCallFailed($url["uid"]);
  }
}

