<?php

// this php script is called in a WEB SERVER by the Transcoder when it needs to tell me something ...
// the only mandatory parameter is ?action=<action name> others depends on the action itself,
require_once("zz_config.php");

if (empty($_REQUEST["action"])) {
  header("HTTP/1.1 404 Not Found 1");
  exit();
}

if (empty($_REQUEST["app_key"]) || $_REQUEST["app_key"]!=CLIENT_KEY) {
  header("HTTP/1.1 403 Not Authorized");
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
    header("HTTP/1.1 404 Not Found 2");
    exit();
  }
  $id=intval($_REQUEST["id"]);
  readfile("test.mp4");
  // Return the video file to the transcoder
  break;
default:
  header("HTTP/1.1 404 Not Found");
  exit();
}