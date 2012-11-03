#!/usr/bin/env php
<?php

   /** ************************************************************
    * Metadata maker process, may be launch as many time as needed
    * on the machine having a proper ffmpeg installed.
    * returns as soon as a metadata has been computer
    * or wait 10 seconds if no do_metadata task is queued.
    */

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';

$api=new Api();

// Cleanup daemons from this host
$api->cleanupQueueLocks();

// Search for a task 
$task=$api->getQueuedTaskLock(TASK_DO_METADATA);

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

$filename=STORAGE_PATH."/".$media["id"];

if (!file_exists($filename) || filesize($filename)==0 ) {
  error_log("FATAL: got task '".$task["id"]."' but file '".$filename."' not found or has zero size!!\n");
  exit(1);  
}

// ok, now we use ffmpeg to get the metadata of the downloaded media
// depending on FFMPEG / AVCONV version, we use one parser or the other ...
$metadata=$api->getFfmpegMetadata($filename);

if ($metadata) {

  // Store the metadata in the media object: 
  $api->mediaUpdate($task["mediaid"],array("status"=>MEDIA_METADATA_OK, "metadata" => serialize($metadata) ));

  // ok, transfer finished, let's mark it done
  $api->setTaskProcessedUnlock($task["id"]);
  exit(0);

} else {
  // if we failed, we just mark it as failed, this will retry 5 min from now ...
  $api->setTaskFailedUnlock($task["id"]);
}


