#!/usr/bin/env php
<?php

   /** ************************************************************
    * Download process, may be launch as many time as needed
    * on the machine having the physical disks available.
    * returns as soon as a download is finished
    * or wait 10 seconds if no download is planned.
    */

if (!function_exists("curl_init")) {
  error_log("php-curl not installed or not enabled, exit.\n");
  exit(1);
}

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';

$useragent="OpenMediaKit-Transcoder/".OMKT_VERSION." (Download Daemon)";

$api=new Api();

// Cleanup daemons from this host
$api->cleanupQueueLocks();

// Search for a task 
$task=$api->getQueuedTaskLock(TASK_DOWNLOAD);

if (!$task) { 
  // we sleep for a little while, thanks to that, we can launch that process as soon as we want: 
  // it will still do *nothing* for a little while when there is nothing to do ;) 
  echo "nothing, sleeping...\n";
  sleep(10);
  exit(0);
}

$media=$api->mediaSearch(array("id"=>$task["mediaid"]));

if (!$media) {
  error_log("FATAL: got task '".$task["id"]."' but media '".$task["mediaid"]."' not found!!\n");
  exit(1);
}
$media=$media[0];

// ok, we try to download the file, with curl, with timeout and range management

$curl=curl_init();
//curl_setopt($curl,CURLOPT_COOKIE,"session=".$session);
curl_setopt($curl,CURLOPT_USERAGENT,$useragent);
curl_setopt($curl,CURLOPT_HEADER,false);
curl_setopt($curl,CURLOPT_FAILONERROR,true);
curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);
curl_setopt($curl, CURLOPT_TIMEOUT, 600); // no more than 10 minutes of download... if file is bigger than that, or too slow, we will retry :) so...

curl_setopt($curl,CURLOPT_URL,$media["remoteurl"]);


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
  // ok, transfer finished, let's mark it done
  $api->setTaskProcessedUnlock($task["id"]);
  // and mark the media as "locally downloaded"
  $api->mediaUpdate($task["mediaid"],array("status"=>MEDIA_LOCAL_AVAILABLE ));
  
  exit(0);
} else {
  // if we failed, we just mark it as failed, this will retry 5 min from now ...
  $api->setTaskFailedUnlock($task["id"]);
}


