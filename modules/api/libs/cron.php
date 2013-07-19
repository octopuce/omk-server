<?php

require_once(__DIR__."/constants.php");
require_once(MODULES."/api/libs/api.php");

class Cron {


  /* ------------------------------------------------------------ */
  /** Return the list of cron tasks to launch.
   * The parameters are as follow : 
   */
  function cronTasksList() {
    global $db;
    // We cronifie only enabled and validated users with proper API url

    // We cronifie every 5 minutes each users whose last activity is less than one month ago
    // and whose last successfull cron is less than one week ago

    // We also cronifie every 1 hours, each users whose last activity is more than one month ago, 
    // and whose last successfull cron is less than a month ago.
    return $db->qlist("SELECT uid, url, clientkey FROM users WHERE 
    enabled=1 AND validated=1 AND url!='' AND 
    (
      ( lastactivity > DATE_SUB(NOW(), INTERVAL 31 DAY) 
      AND lastcronsuccess > DATE_SUB(NOW(), INTERVAL 7 DAY) 
      AND  lastcron < DATE_SUB(NOW(), INTERVAL 1 MINUTE)  ) 
    OR 
      ( lastactivity <= DATE_SUB(NOW(), INTERVAL 31 DAY) 
      AND lastcronsuccess > DATE_SUB(NOW(), INTERVAL 31 DAY)  
      AND  lastcron < DATE_SUB(NOW(), INTERVAL 30 MINUTE) ) 
    )
    ",NULL, PDO::FETCH_ASSOC);    
  }


  function cronCallOk($uid) {
    global $db;
    $db->q("UPDATE users SET lastcron=NOW(), lastcronsuccess=NOW() WHERE uid='".intval($uid)."';");
  }


  function cronCallFailed($uid) {
    global $db;
    $db->q("UPDATE users SET lastcron=NOW() WHERE uid='".intval($uid)."';");
  }



  /* ------------------------------------------------------------ */
  /** Function launched daily to do some kind of cleanup...
   */  
  function cronDaily() {
    global $db;
    $api=new Api();
    $api->log_caller="cron-daily"; 

    // TODO: delete the old transcodes (kept for more than MAX_KEEP_TRANSCODE_DAYS days)
    // TODO: delete the old original videos (kept for more than MAX_KEEP_ORIGINAL_DAYS days) after having no job to do with them? 

    // Announce the public transcoder to the discovery service : 
    if (defined('PUBLIC_TRANSCODER') && PUBLIC_TRANSCODER &&
	defined('TRANSCODER_NAME') && TRANSCODER_NAME!='' &&
	defined('TRANSCODER_ADMIN_EMAIL') && TRANSCODER_ADMIN_EMAIL!='') {
      $f=@fopen("http://discovery.open-mediakit.org/?action=transcoder_is_available&name=".urlencode(TRANSCODER_NAME)."&email=".urlencode(TRANSCODER_ADMIN_EMAIL)."&url=".urlencode(FULL_URL."api")."&version=".OMKT_VERSION."&application=OpenMediakitTranscoder","rb");
      if ($f) {
	$js="";
	while ($s=fgets($f,1024)) { $js.=$s; }
	fclose($f);
	$result=@json_decode($js);
	if (isset($result->code)) {
	  if ($result->code==0) 
	    $api->log(LOG_INFO, "Successfully announced ourself as a Public Transcoder service at discovery.open-mediakit.org");
	  else 
	    $api->log(LOG_INFO, "Something special happened when announcing ourself as a Public Transcoder service at discovery.open-mediakit.org. The message was : '".$result->message."'");   
	} else {
	  $api->log(LOG_INFO, "An error was received when announcing ourself as a Public Transcoder service at discovery.open-mediakit.org. No error message reported");
	}
      }
    }

    // We disable (enabled=0) (and tell it by mail) daily, any user account whose last activity is more than 2 month ago AND last successfull cron is more than a month ago
    $disables=$db->qlist("SELECT * FROM users WHERE enabled=1 AND validated=1 AND lastactivity < DATE_SUB(NOW(), INTERVAL 62 DAY) AND lastcronsuccess < DATE_SUB(NOW(), INTERVAL 31 DAY);", NULL, PDO::FETCH_ASSOC);
    foreach($disables as $disable) {
      $db->q("UPDATE users SET enabled=0 WHERE uid='".$disable["uid"]."';");
      $api->log(LOG_INFO, "disabled user ".$disable["uid"]." (url was ".$disable["url"].") as being inactive and in error for too long.");

      $to      = $disable["email"];
      $subject = _("Account disabled in the public OpenMediakit Transcoder");
      $message = sprintf(_("
Hi,

Days ago, you subscribed to a public transcoder service.

Since then, your account has not used our service for more than 2 months, and your website seems down since more than 1 month.

As a result, we just disabled your account for the application using our transcoding service.  

For your records, your website was located at %s.

Feel free to resubscribe if you need to use this service later again,

--
Regards,

The OpenMediakit Transcoder public instance at
%s
"),$disable["url"],FULL_URL);
      
      $headers = 'From: '.MAIL_FROMNAME.' <'.MAIL_FROM.'>'. "\r\n" .
	'Reply-To: '.MAIL_FROM. "\r\n" .
	'Content-type: text/plain; charset=utf-8' . "\r\n" .
	'X-Mailer: PHP/' . phpversion();
      
      mail($to, $subject, $message, $headers);          
    } // for each disabled account



  } // cronDaily()


  /** ************************************************************ 
   * Launch parallel (using MAX_SOCKETS sockets maximum) retrieval
   * of URL using CURL 
   * @param $urls array of associative array, each having the following keys : 
   *  url = url to get (of the form http[s]://login:password@host/path/file?querystring )
   *  cafile = if https, can point to a ca file, if not specified, will use a default cafile.
   *  - any other key will be sent as it is to the callback function
   * @param $callback function called for each request when completing. First argument is the $url object, second is the content (output)
   *  third is the info structure from curl for the returned page. 200 for OK, 403 for AUTH FAILED, 0 for timeout, dump it to know it ;) 
   *  this function should return as soon as possible to allow other curl calls to complete properly.
   * @param $cursom_options array of custom CURL options for all transfers
   */
  function rolling_curl($urls, $callback, $custom_options = null) {
    // make sure the rolling window isn't greater than the # of urls
    if (!isset($GLOBALS["DEBUG"])) $GLOBALS["DEBUG"]=false;
    $rolling_window = MAX_SOCKETS;
    $rolling_window = (count($urls) < $rolling_window) ? count($urls) : $rolling_window;
    
    $master = curl_multi_init();
    $curl_arr = array();
    
    // add additional curl options here
    $std_options = array(CURLOPT_RETURNTRANSFER => true,
			 CURLOPT_FOLLOWLOCATION => true,
			 CURLOPT_CONNECTTIMEOUT => 5,
			 CURLOPT_TIMEOUT => 240, // 4 minutes
			 CURLOPT_USERAGENT => "OpenMediaKit-Transcoder/".OMKT_VERSION." (Cron Daemon)",
			 CURLOPT_MAXREDIRS => 5);

    if ($GLOBALS["DEBUG"]) $std_options[CURLOPT_VERBOSE]=true;
    $options = ($custom_options) ? ($std_options + $custom_options) : $std_options;
    
    // start the first batch of requests
    for ($i = 0; $i < $rolling_window; $i++) {
      $ch = curl_init();
      $options[CURLOPT_URL] = $urls[$i]["url"];
      if ($GLOBALS["DEBUG"]) echo "URL: ".$urls[$i]["url"]."\n";
      curl_setopt_array($ch,$options);
      // Handle custom cafile for some https url
      if (strtolower(substr($options[CURLOPT_URL],0,5))=="https") { // https :) 
	if (isset($urls[$i]["cafile"]) && $urls[$i]["cafile"] && is_file($urls[$i]["cafile"])) {
	  curl_setopt($ch,CURLOPT_CAINFO,$urls[$i]["cafile"]);
	  if ($GLOBALS["DEBUG"]) echo "cainfo set to ".$urls[$i]["cafile"]."\n";
	} else {
	  curl_setopt($ch,CURLOPT_CAINFO,DEFAULT_CAFILE);
	  if ($GLOBALS["DEBUG"]) echo "cainfo set to DEFAULT\n";
	}
      }
      curl_multi_add_handle($master, $ch);
    }
    
    do {
      while(($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
      if($execrun != CURLM_OK)
	break;
      // a request was just completed -- find out which one
      while($done = curl_multi_info_read($master)) {
	$info = curl_getinfo($done['handle']);
	// TODO : since ssl_verify_result is buggy, if we have [header_size] => 0  && [request_size] => 0 && [http_code] => 0, AND https, we can pretend the SSL certificate is buggy.
	if ($GLOBALS["DEBUG"]) { echo "Info for ".$done['handle']." \n"; print_r($info); } 
	if ($info['http_code'] == 200)  {
	  $output = curl_multi_getcontent($done['handle']);
	} else {
	  // request failed.  add error handling.
	  $output="";
	}
	// request terminated.  process output using the callback function.
	// Pass the url array to the callback, so we need to search it
	foreach($urls as $url) {
	  if ($url["url"]==$info["url"]) {
	    $callback($url,$output,$info);
	    break;
	  }
	}
	
	// If there is more: start a new request
	// (it's important to do this before removing the old one)
	if ($i<count($urls)) {
	  $ch = curl_init();
	  $options[CURLOPT_URL] = $urls[$i++];  // increment i
	  curl_setopt_array($ch,$options);
	  curl_multi_add_handle($master, $ch);
	}
	// remove the curl handle that just completed
	curl_multi_remove_handle($master, $done['handle']);
      }
    } while ($running);
    
    curl_multi_close($master);
    return true;
  }
  
} // class Cron

