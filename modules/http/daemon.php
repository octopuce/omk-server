#!/usr/bin/env php
<?php

   /** ************************************************************
    * Download process, may be launch as many time as needed
    * on the machine having the physical disks available.
    * returns as soon as a download is finished
    * or wait 10 seconds if no download is queued.
    */

define("LOG_CALLER","http-download-daemon");

if (!function_exists("curl_init")) {
  error_log("php-curl not installed or not enabled, exit.\n");
  exit(1);
}

require_once __DIR__ . '/../../common.php';
require_once MODULES . '/api/libs/api.php';

$useragent="OpenMediaKit-Transcoder/".OMKT_VERSION." (Download Daemon)";

$api=new Api();

while (true) {

  // Cleanup daemons from this host
  $api->cleanupQueueLocks();
  // Search for a task 
  $task=$api->getQueuedTaskLock(TASK_DOWNLOAD,"http");

  if (!$task) { 
    // we sleep for a little while, thanks to that, we can launch that process as soon as we want: 
    // it will still do *nothing* for a little while when there is nothing to do ;) 
    $api->log(LOG_DEBUG, "Nothing to do, sleeping...");
    sleep(60);
    continue;
  }

  $media=$api->mediaSearch(array("id"=>$task["mediaid"]));
  
  if (!$media) {
    $api->log(LOG_CRIT, "got task '".$task["id"]."' but media '".$task["mediaid"]."' not found");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }
  $media=$media[0];
  
  // ok, we try to download the file, with curl, with timeout and range management
  
  $curl=curl_init();
  $options=array(CURLOPT_USERAGENT => $useragent,
		 CURLOPT_HEADER => false,
		 CURLOPT_FAILONERROR => true,
		 CURLOPT_FOLLOWLOCATION => true,
		 CURLOPT_CONNECTTIMEOUT => 10,
		 CURLOPT_TIMEOUT => 600   // no more than 10 minutes of download... if file is bigger than that, or too slow, we will retry anyway.
		 );
  curl_setopt_array($curl,$options);
  
  curl_setopt($curl,CURLOPT_URL,$task["params"]["url"]);
  
  @mkdir(STORAGE_PATH."/original/");
  $filename=STORAGE_PATH."/original/".$media["id"];
  
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
    $api->log(LOG_CRIT, "cannot write to ".$filename." for task '".$task["id"]."'");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }
  curl_setopt($curl,CURLOPT_FILE,$f);
  $res=curl_exec($curl);
  fclose($f);
  if ($res) {
    // and mark the media as "locally downloaded"
    $api->mediaUpdate($task["mediaid"],array("status"=>MEDIA_LOCAL_AVAILABLE ));
    // and ask for its metadata if requested to: 
    if ($task["params"]["dometadata"]) {
      $api->queueAdd(TASK_DO_METADATA,$task["mediaid"],METADATA_RETRY);
    }    
    // ok, transfer finished, let's mark it done
    $api->setTaskProcessedUnlock($task["id"]);
    log(LOG_INFO,"Download task ".$task["id"]." finished for media ".$task["mediaid"]."");
  } else {
    // if we failed, we just mark it as failed, this will retry 5 min from now ...
    $api->setTaskFailedUnlock($task["id"]);
    log(LOG_WARNING,"Download task ".$task["id"]." failed or unfinished for media ".$task["mediaid"]."");
  }

} // while (true)


  

