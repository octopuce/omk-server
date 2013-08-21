#!/usr/bin/php
<?php

   /**
    * Usage: scripts-thumbnails1.php <source> <destination> <duration in second>
    * Generate up to 20 thumbnails (min 1 per minute) for the video <source>
    * The thumbnails will be generated as 60% quality JPEG at original size AND 100x100
    */

if (count($argv)!=4 || 
    !is_file($argv[1]) ||
    file_exists($argv[2]) ||
    intval($argv[3])==0) {
  echo "Usage: scripts-thumbnails1.php <source> <destination> <duration in second>\n";
  echo "<source> must be a file, <destination> must not exist, <duration> must be a non-null integer\n";
  exit(1);
}
$source=$argv[1];
$destination=$argv[2];
$duration=intval($argv[3]);

$ratio="1/60";
if ($duration>(20*60)) {
  $ratio="1/".floor($duration/20)
}
mkdir($destination);
exec("ffmpeg -i ".escapeshellarg($source)." -vf fps=fps=".$ratio." -vcodec png -f image2 -an ".escapeshellarg($destination."/tmp%02d.png"),$out,$res);
if ($res!=0) {
  echo "Error launching ffmpeg for thumbnails\n";
  exit(2);
}

// Now we convert them into JPG original size AND 100x100px
for($i=0;$i<20;$i++) {
  if (!is_file($destination.sprintf("/tmp%02d",$i))) {
    break;
  }
  exec("convert ".escapeshellarg($destination.sprintf("/tmp%02d",$i))." -quality 60% ".escapeshellarg($destination.sprintf("/h%02d",$i)),$out,$res);
  if ($res!=0) {
    echo "Error launching convert for original jpeg\n";
    exit(3);
  }
  exec("convert ".escapeshellarg($destination.sprintf("/tmp%02d",$i))." -quality 60% -size 100x100 ".escapeshellarg($destination.sprintf("/l%02d",$i)),$out,$res);
  if ($res!=0) {
    echo "Error launching convert for smaller jpeg\n";
    exit(4);
  }
  unlink($destination.sprintf("/tmp%02d",$i));
}

echo "Thumbnails generated ($i)\n";
exit(0);
