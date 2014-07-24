#!/usr/bin/php
<?php

// Configuration
require_once __DIR__ . '/common.php';

require_once MODULES.'/api/libs/ffmpeg.php';
require_once MODULES.'/api/libs/api.php';

$api=new api();
$ffmpeg=new Ffmpeg();
$ffmpeg->DEBUG=1;

if (count($argv)<2) {
  echo "USAGE: test_metadata.php <filename>\n";
  echo "tells the metadata of a file\n\n";
  exit();
}
print_r($ffmpeg->getFfmpegMetadata($argv[1]));



