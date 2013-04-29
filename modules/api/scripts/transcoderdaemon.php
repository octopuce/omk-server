#!/usr/bin/env php
<?php


   /** ************************************************************
    * Transcoder process, may be launch as many time as needed
    * on the machine having a proper ffmpeg installed.
    * this is a daemon: it never returns
    * you should launch it using an init script
    */

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';
require_once __DIR__ . '/../libs/ffmpeg.php';
require_once MODULES.'/users/libs/users.php';

$api=new Api();
$ffmpeg=new Ffmpeg();

$api->log_caller="transcoder-daemon"; 

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
  echo "oups";
}

pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGINT, "sig_handler");
pcntl_signal(SIGQUIT,  "sig_handler");

while (true) {

  // Cleanup daemons from this host
  $api->cleanupQueueLocks();
  
  // Search for a task 
  $task=$api->getQueuedTaskLock(TASK_DO_TRANSCODE);
  
  if (!$task) { 
    // we sleep for a little while, thanks to that, we can launch that process as soon as we want: 
    // it will still do *nothing* for a little while when there is nothing to do ;) 
    $api->log(LOG_DEBUG, "Nothing to do, sleeping...");
    sleep(60);
    continue;
  }

  // Get the task's parameters */
   $params=$task["params"];
  
  $media=$api->mediaSearch(array("id"=>$task["mediaid"]));
  if (!$media) {
    $api->log(LOG_CRIT, "Got task '".$task["id"]."' but media '".$task["mediaid"]."' not found!!");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }
  $media=$media[0];

  $transcode=$api->transcodeSearch(array("id"=>$params["transcode"]));
  if (!$transcode) {
    $api->log(LOG_CRIT, "Got task '".$task["id"]."' but transcode '".$params["transcode"]."' not found!!");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }
  $transcode=$transcode[0];

  $api->me = Users::get($task["user"]);
  $adapterObject=$api->getAdapter($media["adapter"]);
  if (!$adapterObject) {
    $api->log(LOG_CRIT, "Got task '".$task["id"]."' but adapter '".$task["adapter"]."' not found!!");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }
  
  list($source,$destination) = $adapterObject->filePathTranscode($media,$params["setting"]);

  if (!$source || !$destination) {
    $api->log(LOG_CRIT, "Got task '".$task["id"]."' but media '".$task["mediaid"]."' didn't allow to find the file using adapter '".$task["adapter"]."' !!");
    $api->setTaskFailedUnlock($task["id"]);
    continue;    
  }
  
  if (!file_exists($source) || filesize($source)==0 ) {
    $api->log(LOG_CRIT, "Got task '".$task["id"]."' but file '".$filename."' not found or has zero size!!");
    $api->setTaskFailedUnlock($task["id"]);
    continue;
  }
  // We overwrite the destination file (or folder) 
  
  // ok, now we use ffmpeg to get the metadata of the downloaded media
  // depending on FFMPEG / AVCONV version, we use one parser or the other ...
  $result=$ffmpeg->transcode($media,$source,$destination,$params["setting"]);
  
  if ($result) {
    // Store the transcode object: 
    $api->transcodeUpdate($transcode["id"],array("status"=>MEDIA_TRANSCODE_PROCESSED ) );;
    // Queue the task to tell the client that we have the metadata
    $api->queueAdd(TASK_SEND_TRANSCODE,$task["mediaid"],API_RETRY, array("transcode"=>$params["transcode"] ),$media["adapter"]);
    // ok, transfer finished, let's mark it done
    $api->setTaskProcessedUnlock($task["id"]);
    $api->log(LOG_DEBUG, "Successully processed task '".$task["id"]."', transcode for media '".$task["mediaid"]."' for setting '".$params["setting"]."'");

  } else {
    // if we failed, we just mark it as failed, this will retry 5 min from now ...
    $api->setTaskFailedUnlock($task["id"]);
    $api->log(LOG_DEBUG, "Ffmpeg call failed when processing task '".$task["id"]."', transcode for media '".$task["mediaid"]."' for setting '".$params["setting"]."'");
  }
  
} // infinite loop...

