<?php

// this php script is called in a WEB SERVER by the Transcoder when it needs to tell me something ...
// the only mandatory parameter is ?action=<action name> others depends on the action itself,

if (empty($_REQUEST["action"])) {
  header("HTTP/1.1 404 Not Found");
  exit();
}

switch ($_REQUEST["action"]) {
case "transcoder_cron":
  //  header("HTTP/1.1 404 Not Found"); // uncomment this to test the "when the cron is failing"
  echo "OK";
  exit();
  break;

case "getvideo":
  if (empty($_REQUEST["id"])) {
    header("HTTP/1.1 404 Not Found");
    exit();
  }
  $id=intval($_REQUEST["id"]);
  if ($_REQUEST["key"]!=myhash($id)) {
    header("HTTP/1.1 404 Not Found");
    exit();
  }
  readfile("test.mp4");
  // Return the video file to the transcoder
  break;
default:
  header("HTTP/1.1 404 Not Found");
  exit();
}