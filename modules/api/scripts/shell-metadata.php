#!/usr/bin/env php
<?php

   /** ************************************************************
    * Metadata maker process, may be launch as many time as needed
    * on the machine having a proper ffmpeg installed.
    * returns as soon as a metadata has been computer
    * or wait 10 seconds if no do_metadata task is queued.
    */

if (!isset($argv[1])) {
  echo "Usage: ".$argv[0]." <filename> \n Shows metadata of <filename> from the ffmpeg parser\n";
  exit(127);
}

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';

$api=new Api();

$m=$api->getFfmpegMetadata($argv[1]);

print_r($m);

