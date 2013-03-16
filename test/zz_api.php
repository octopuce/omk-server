<?php

// this php script is called in a WEB SERVER by the Transcoder when it needs to tell me something ...
// the only mandatory parameter is ?action=<action name> others depends on the action itself,

if (empty($_REQUEST["action"])) {
  header("HTTP/1.1 404 Not Found");
  exit();
}

switch ($_REQUEST["action"]) {
case "cron":
  //  header("HTTP/1.1 404 Not Found"); // uncomment this to test the "when the cron is failing"
  echo "OK";
  exit();
  break;

default:
  header("HTTP/1.1 404 Not Found");
  exit();
}