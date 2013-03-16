<?php

require_once("config.php");


if (!empty($_REQUEST["action"])) {
  if ($_REQUEST["action"]=="transcoder_is_available") {
    // A transcoder is telling us it is available ... Let's believe him :) 
    // TODO : validate the email of the administrator.
    // TODO : validate the transcoder behavior.
    if (
	empty($_REQUEST["name"]) || 
	empty($_REQUEST["url"]) || 
	empty($_REQUEST["email"])
	) {
      fail(1,"Missing fields in transcoder registration call");
    }
    // search for an existing transcoder having this URL
    $already=@mysql_fetch_array(mysql_query("SELECT * FROM transcoder WHERE url='".addslashes($_REQUEST["url"])."';"));
    if ($already) {
      // update it 
      $sql="";
      if ($already["name"]!=$_REQUEST["name"]) {
	$sql.=", name='".addslashes($_REQUEST["name"])."'";
      }
      $settings=filterSettings($_REQUEST["settings"]);
      if ($settings!=@unserialize($already["settings"])) {
	$sql.=", settings='".addslashes(serialize($settings))."'";
      }
      if ($already["email"]!=$_REQUEST["email"]) {
	// mail changed, validate it
	validate($already["id"],$_REQUEST["email"]);
	mysql_query("UPDATE transcoder SET email='".addslashes($_REQUEST["email"])."', emailvalid=NULL, enabled=0, lastseen=NOW() ".$sql." WHERE id='".$already["id"]."';");
	fail(0,"Your transcoder has been updated, please validate your new email, clicking the link we just sent you");
      } else {
	// mail unchanged, just renew it
	if ($already["emailvalid"]) {
	  $sql.=", enabled=1";
	}
	mysql_query("UPDATE transcoder SET lastseen=NOW() ".$sql." WHERE id='".$already["id"]."';");
	fail(0,"Your transcoder has been updated");
      }
    } else {
      // New transcoder : 
      $settings=filterSettings($_REQUEST["settings"]);
      mysql_query("INSERT INTO transcoder SET url='".addslashes($_REQUEST["url"])."', name='".addslashes($_REQUEST["name"])."', email='".addslashes($_REQUEST["email"])."', settings='".addslashes(serialize($settings))."', ip='".$_SERVER["REMOTE_ADDR"]."', enabled=0, emailvalid=NULL, lastseen=NOW();");
      validate($id,$_REQUEST["email"]);
      fail(0,"Your transcoder has been registered, please validate your email, clicking the link we just sent you");
    }
    
  } // transcoder_is_available

  if ($_REQUEST["action"]=="validate") {
    // Validate an email address
    if (empty($_REQUEST["id"]) || empty($_REQUEST["key"]) || strlen($_REQUEST["key"]!=10) || !intval($_REQUEST["id"]) ) {
      fail_human(2,"The link you clicked is invalid, please check");
    }
    $id=intval($_REQUEST["id"]);
    $me=@mysql_fetch_array(mysql_query("SELECT * FROM transcoder WHERE id='$id'"));
    if (!$me) {
      fail_human(3,"The link you clicked is invalid, please check");
    }
    $key=substr(md5(RANDOM_SALT . "_" .$me["email"]),0,10);
    if ($key!=$_REQUEST["key"]) { 
      fail_human(4,"The link you clicked is invalid, please check");
    }
    mysql_query("UPDATE transcoder SET enabled=1, emailvalid=NOW() WHERE id='$id';");
    fail_human(0,"Your email has been validated, your public OpenMediakit Transcoder instance will now be used by new users.");
    
  }

} 


