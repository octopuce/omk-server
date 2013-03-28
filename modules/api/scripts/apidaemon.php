#!/usr/bin/env php
<?php

   /** ************************************************************
    * Client API Calling process, may be launch as many time as needed
    * on the machine having a proper Internet connection.
    */

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';

$useragent="OpenMediaKit-Transcoder/".OMKT_VERSION." (Client API Daemon)";

$api=new Api();

$api->log_caller="clientapi-calling-daemon"; 

$curl=curl_init();
$options=array(CURLOPT_USERAGENT => $useragent,
	       CURLOPT_HEADER => false,
	       CURLOPT_FAILONERROR => true,
	       CURLOPT_FOLLOWLOCATION => true,
	       CURLOPT_CONNECTTIMEOUT => 10,
	       CURLOPT_RETURNTRANSFER => true,
	       CURLOPT_TIMEOUT => 30   // no more than 30 seconds of waiting for the client.
	       );
curl_setopt_array($curl,$options);

declare(ticks = 1);

// In case termination signal, Ctrl-C or other exit-requesting signal: 
function sig_handler($signo) {
  global $task, $api;
  switch ($signo) {
  case SIGTERM:
  case SIGHUP:
  case SIGINT:
  case SIGQUIT:
    $api->log(LOG_INFO, "Got Signal $signo, cleanup and quit.");
    if ($task) {
      $api->setTaskFailedUnlock($task["id"]);
    }
    exit;
    break;
  }  
}

pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGINT, "sig_handler");
pcntl_signal(SIGQUIT,  "sig_handler");

while (true) {

  // Cleanup daemons from this host
  $api->cleanupQueueLocks();
  
  // Search for a task 
  $task=$api->getQueuedTaskLock( array(TASK_SEND_METADATA,TASK_SEND_TRANSCODE) );

  if (!$task) { 
    // we sleep for a little while, thanks to that, we can launch that process as soon as we want: 
    // it will still do *nothing* for a little while when there is nothing to do ;) 
    $api->log(LOG_DEBUG, "Nothing to do, sleeping...");
    sleep(60);
    continue;
  }

  $media=$api->mediaSearch(array("id"=>$task["mediaid"]));
  
  if (!$media) {
    $api->log(LOG_CRIT, "Got task '".$task["id"]."' but media '".$task["mediaid"]."' not found!!");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }
  $media=$media[0];
  $api->me = Users::get($task["user"]);
  $url=$api->me["url"];
  if (strpos($url,"?")!==false) $url.="&"; else $url.="?";
  $url.="app_key=".$api->me["clientkey"];
  // Which task is it ? 
  $ok=false;

  switch ($task["task"]) {
  case TASK_SEND_METADATA:
    $url.="&action=transcoder_send_metadata";
    $url.="&id=".$media["remoteid"];
    $url.="&metadata=".urlencode(json_encode(unserialize($media["metadata"])));
    $ok=true;
    break;
  case TASK_SEND_TRANSCODE:
    $url.="&action=transcoder_send_format";
    $url.="&id=".$media["remoteid"];
    // TODO : implement this (TASK_SEND_TRANSCODE)
    $ok=false;

    break;
  } 

  if (!$ok) {
    $api->log(LOG_CRIT, "On task '".$task["id"]."' the url can't be computed");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }
  
  curl_setopt($curl,CURLOPT_URL,$url);
  $res=curl_exec($curl);
  $info = curl_getinfo($curl);
  if (!$res || $info["http_code"]!=200 ) {
    $api->log(LOG_CRIT, "On task '".$task["id"]."' curl returned empty result or non-200 http_code (".$info["http_code"].")");
    $api->setTaskFailedUnlock($task["id"]);
    continue;    
  } 
  $res=@json_decode($res);
  if ($res->code==0 || $res->code==200) { // TODO : use http error code only here 
    $api->setTaskProcessedUnlock($task["id"]);
  } else {
    $api->log(LOG_CRIT, "On task '".$task["id"]."' the client returned code ".$res->code." and message ".$res->message.", which means fail, will try later");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }  
  
} // infinite loop...