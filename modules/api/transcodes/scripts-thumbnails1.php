#!/usr/bin/php
<?php


   /**
    * Generate up to 20 thumbnails (min 1 per minute) for the video <source>
    * The thumbnails will be generated as 60% quality JPEG at original size AND 100x100
    */
function thumbnails1($media,$source,$destination,$setting,$adapterObject) {
  
  $metadata=@unserialize($media["metadata"]);
  $duration=intval($metadata["time"]);
  
  $ratio="1/60";
  if ($duration>(20*60)) {
    $ratio="1/".floor($duration/20);
  }
  $tmpdir="/tmp/thumbs-".getmypid();
  mkdir($tmpdir);
  exec("ffmpeg -i ".escapeshellarg($source)." -vf fps=fps=".$ratio." -vcodec png -f image2 -an ".escapeshellarg($tmpdir."/tmp%02d.png"),$out,$res);
  if ($res!=0) {
    $GLOBALS["error"]="Error launching ffmpeg for thumbnails";
    return false;
  }
  
  // Now we convert them into JPG original size AND 100x100px
  $id=0;
  for($i=0;$i<20;$i++) {
    if (!is_file($tmpdir.sprintf("/tmp%02d.png",$i+1))) {
      break;
    }
    $destfile = $adapterObject->filePathTranscodeMultiple($media,$setting,sprintf("h%02d",$i),".jpg");
    
    exec("convert ".escapeshellarg($tmpdir.sprintf("/tmp%02d.png",$i+1))." -quality 60% ".escapeshellarg($destfile),$out,$res);
    if ($res!=0) {
      $GLOBALS["error"]="Error launching convert for original jpeg";
      return false;
    }
    
    $destfile = $adapterObject->filePathTranscodeMultiple($media,$setting,sprintf("l%02d",$i),".jpg");
    
    exec("convert ".escapeshellarg($tmpdir.sprintf("/tmp%02d.png",$i+1))." -quality 60% -resize 100x100 ".escapeshellarg($destfile),$out,$res);
    if ($res!=0) {
      $GLOBALS["error"]="Error launching convert for smaller jpeg";
      return false;
    }
    unlink($tmpdir.sprintf("/tmp%02d.png",$i+1));
  }
  rmdir($tmpdir);

  $GLOBALS["error"]="Thumbnails generated ($i)";
  return true;
   }

