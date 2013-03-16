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

$useragent="OpenMediaKit-Transcoder/".OMKT_VERSION." (Cron Daemon)";

$api=new Api();

// We search for the list of crontasks to launch
$list=$api->cronTasksList();


$curl=curl_init();
//curl_setopt($curl,CURLOPT_COOKIE,"session=".$session);
curl_setopt($curl,CURLOPT_USERAGENT,$useragent);
curl_setopt($curl,CURLOPT_HEADER,false);
curl_setopt($curl,CURLOPT_FAILONERROR,true);
curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);
curl_setopt($curl, CURLOPT_TIMEOUT, 600); // no more than 10 minutes of download... if file is bigger than that, or too slow, we will retry :) so...

curl_setopt($curl,CURLOPT_URL,$params["url"]);

$filename=STORAGE_PATH."/".$media["id"];

if (file_exists($filename)) {
  // retry from the end of the file
  $sizebefore=filesize($filename);
  curl_setopt( $curl,CURLOPT_RESUME_FROM,$sizebefore ); 
  // FIXME: what happen if we already have all the content ??!!
  $f=fopen($filename,"ab");
} else {
  $sizebefore=0;
  $f=fopen($filename,"wb");
}

if (!$f) { 
  error_log("FATAL: cannot write to ".$filename."\n"); 
  exit(1); 
}
curl_setopt($curl,CURLOPT_FILE,$f);
$res=curl_exec($curl);
fclose($f);
if ($res) {
  // and mark the media as "locally downloaded"
  $api->mediaUpdate($task["mediaid"],array("status"=>MEDIA_LOCAL_AVAILABLE ));
  // and ask for its metadata if requested to: 
  if ($params["dometadata"]) {
    $api->queueAdd(TASK_DO_METADATA,$task["mediaid"],METADATA_RETRY,array("cropdetect"=>$params["cropdetect"]));
  }
  
  // ok, transfer finished, let's mark it done
  $api->setTaskProcessedUnlock($task["id"]);
  exit(0);
} else {
  // if we failed, we just mark it as failed, this will retry 5 min from now ...
  $api->setTaskFailedUnlock($task["id"]);
}


