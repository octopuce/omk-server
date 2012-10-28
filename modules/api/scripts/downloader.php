#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';

$useragent="OpenMediaKit-Transcoder/".OMKT_VERSION." (Download Daemon)";

if ($argv[1]=="debug") $GLOBALS["DEBUG"]=true;

// Search for a task 
//$servers_snmp = $db->qassoc("SELECT fqdn, 'snmp' FROM servers_servers s LEFT JOIN accounting_method m ON s.sid=m.sid WHERE m.method = 'snmp'");
$api=new ApiController();

$task=$api->getQueuedTaskLock(TASK_DOWNLOAD);

if (!$task) { 
  // we sleep for a little while, thanks to that, we can launch that process as soon as we want: 
  // it will still do *nothing* for a little while when there is nothing to do ;) 
  sleep(10);
  exit(0);
}

$media=$api->_mediaSearch(array("id"=>$task["media"]));

if (!$media) {
  error_log("FATAL: got task ".$task["id"]." but media ".$task["media"]." not found!!\n");
  exit(1);
}

// ok, we try to download the file, with curl, with timeout and range management

$curl=curl_init();
//curl_setopt($curl,CURLOPT_COOKIE,"session=".$session);
curl_setopt($curl,CURLOPT_USERAGENT,$useragent);
curl_setopt($curl,CURLOPT_HEADER,false);
curl_setopt($curl,CURLOPT_FAILONERROR,true);
curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);

curl_setopt($curl,CURLOPT_URL,$media["remoteurl"]);


$filename=STORAGE."/".$media["id"];

if (file_exists($filename)) {
  // retry from the end of the file
  $sizebefore=filesize($filename);
  curl_setopt( $curl,CURLOPT_RESUME_FROM,$sizebefore );
  $f=fopen($filename,"ab");
} else {
  $sizebefore=0;
  $f=fopen($filename,"wb");
}

if (!$f) { 
  error_log("FATAL: cannot write to ".$filename."\n"); 
  exit(1); 
}
curl_setopt($curl,CURLOPT_FILE,$f);
$res=curl_exec($curl);
fclose($f);
if ($res) {
  // ok, transfer finished, let's mark it done
  $api->setTaskProcessedUnlock($task["id"]);
  exit(0);
} else {
  // failed, did we even get some bytes ?
  clearstatcache();
  // if we failed, we just mark it as failed, this will retry 5 min from now ...
  $api->setTaskFailedUnlock($task["id"]);
}


