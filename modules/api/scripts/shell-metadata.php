#!/usr/bin/env php
<?php

   /** ************************************************************
    * Metadata maker process for a single file
    * Use this to test the metadata processor
    */

if (!isset($argv[1])) {
  echo "Usage: ".$argv[0]." <filename> \n Shows metadata of <filename> from the ffmpeg parser\n";
  exit(127);
}

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';
require_once __DIR__ . '/../libs/ffmpeg.php';

$ffmpeg=new Ffmpeg();

$m=$ffmpeg->getFfmpegMetadata($argv[1]);

print_r($m);

