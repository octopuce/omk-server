<?php

require_once(MODULES."/api/libs/constants.php");

define("DOWNLOAD_RETRY",10); // retry 10 times each download
define("METADATA_RETRY",4); // retry 4 times each metadata search
define("API_RETRY",10); // retry 10 times each client API call
define("TRANSCODE_RETRY",3); // retry 3 times each transcode request

class Api {

  public $me=array(); // current user

  /* ------------------------------------------------------------ */
  /** 
   * Get a task and lock it for processing.
   * @param $task integer is the task type
   * @return array the locked task, or false if no task has been found
   */ 
  public function getQueuedTaskLock($task, $adapter='') {
    global $db,$api;
    if (is_array($task)) {
      if (count($task)==1) {
	$task=$task[0];
      } elseif (count($task)==0) {
	return false;
      } else {
	$tl=array();
	foreach($task as $t) {
	  if ($t<TASK_MIN || $t>TASK_MAX) return false;
	  $tl[]=$t;
	}
	$task=$tl;
      }
    } else {
      $task=intval($task);
      if ($task<TASK_MIN || $task>TASK_MAX) return false;
      $task=array($task);
    }

    $hostname=gethostname();
    $pid=getmypid();
    $params=array(STATUS_TODO);
    if ($adapter!='') {
      $adapterfilter=" AND adapter=? ";
      $params[]=$adapter;
    } else {
      $adapterfilter="";
    }
    $db->q("LOCK TABLES queue;");
    $query="SELECT * FROM queue WHERE task IN (".implode(",",$task).") AND status=? AND lockhost='' AND datetry<=NOW() $adapterfilter ORDER BY retry DESC, datequeue ASC LIMIT 1;";
    //    $api->log(LOG_DEBUG,"getQueue: $query");
    $me=$db->qone( $query, $params, PDO::FETCH_ASSOC );
    if (!$me) {
      $db->q("UNLOCK TABLES;");
      return false;
    }
    $query="UPDATE queue SET status=?, lockhost=?, lockpid=?, datelaunch=NOW() WHERE id=?;";
    $db->q( $query,array(STATUS_PROCESSING,$hostname,$pid,$me["id"]) );
    $db->q("UNLOCK TABLES;");
    $p=@unserialize($me["params"]); if (!empty($p)) $me["params"]=$p; // We unserialize the parameters of the task.
    return $me;
  }


  /** ****************************************
   * Mark a task as "processed successfully"
   * @param $id is the task number
   * @return boolean TRUE if the task has been marked as processed
   */ 
  public function setTaskProcessedUnlock($id) {
    global $db;
    $id=intval($id);
    $query="UPDATE queue SET status=?, lockhost='', lockpid=0, datedone=NOW() WHERE id=?;";
    $db->q( $query,array(STATUS_DONE,$id) );
    return true;
  }


  /** ****************************************
   * When a task failed, decrease its retry count
   * and if 0, mark it failed
   * if the retry is not 0, set the datetry to NOW()+10 minutes.
   * @param $id is the task number
   * @return boolean TRUE if the task has been marked as failed
   */ 
  public function setTaskFailedUnlock($id) {
    global $db;
    $id=intval($id);
    $task=$db->qone("SELECT * FROM queue WHERE id=?",array($id));
    if (!$task) return false;

    if ($task->retry==1) {
      // failed for real...
      $db->q( "UPDATE queue SET datedone=NOW(), status=?, lockhost='', lockpid=0 WHERE id=?", array(STATUS_ERROR,$id) );
    } else {
      // retry in 5 min
      $retry=$task->retry-1;
      $db->q( "UPDATE queue SET status=?, retry=?, datetry=DATE_ADD(NOW(), INTERVAL 10 MINUTE), lockhost='', lockpid=0 WHERE id=?", array(STATUS_TODO,$retry,$id) );
    }
    // TODO: return the remaining number of retries : will allow the caller to tell the client if a task has failed for too long ...
    return true;
  }

  
  /** ****************************************
   * Add a task to the queue 
   * @param $task integer is the task name
   * @param $media integer is the associated media (from media table)
   * @param $retry integer how many time we should retry in case of error for this task?
   * @param $format integer is the format (from format table) if the task is a transcode.
   * @return integer the newly created queue id
   */ 
  public function queueAdd($task,$media,$retry=1,$params=null,$adapter="") {
    global $db;
    $query = "INSERT INTO queue SET datequeue=NOW(), datetry=NOW(), user=?, status=".STATUS_TODO.", retry=?, lockhost='', lockpid=0,  task=?, mediaid=?, params=?, adapter=?;";
    if (!is_array($params)) $params=array();
    $db->q($query,array($this->me["uid"],$retry,$task,$media,serialize($params),$adapter));
    return $db->lastInsertId();
  }
  

  /** ****************************************
   * Add a media into the media table
   * $v is an associative array with fields name and value
   * @return integer the newly created media id 
   */
  public function mediaAdd($v) {
    global $db;
    $k=array( "status", "remoteid", "remoteurl", "owner", "adapter" );
    $sql=""; $val=array();
    foreach($k as $key) {
      if (isset($v[$key])) { 
	if ($sql) $sql.=", ";
	$sql.="$key=?";
	$val[]=$v[$key];
      }
    }
    if (!$sql) return false; // no information!
    $query = "INSERT INTO media SET datecreate=NOW(), $sql";
    $db->q($query,$val);
    return $db->lastInsertId();
  }


  /** ****************************************
   * Update a media in the media table
   * @param $id integer The media id
   * @param $v array An associative array with fields name and value
   * @return boolean true if the media has been updated
   */
  public function mediaUpdate($id,$v) {
    global $db;
    $k=array("status","remoteid","remoteurl","owner","metadata","adapter");
    $sql=""; $val=array();
    foreach($k as $key) {
      if (isset($v[$key])) { 
	if ($sql) $sql.=", ";
	$sql.="$key=?";
	$val[]=$v[$key];
      }
    }
    if (!$sql) return false; // no information!
    $val[]=$id;
    $query = "UPDATE media SET dateupdate=NOW(), $sql WHERE id=?";
    $db->q($query,$val);
    return true;
  }



  /** ****************************************
   * Search for one or more media
   * @param $search is an array of key => value to search for 
   * @param $operator the AND operator is the default, could be OR
   * @return array the list of found media
   */
  public function mediaSearch($search,$operator="AND") {
    global $db;
    $k=array("id","status","remoteid","remoteurl","owner");
    $sql=""; $val=array();
    foreach($k as $key) {
      if (isset($search[$key])) { 
	if ($sql) $sql.=" $operator ";
	$sql.="$key=?";
	$val[]=$search[$key];
      }
    }
    if (!$sql) return false; // no information!
    $query = "SELECT * FROM media WHERE $sql";
    return $db->qlist($query, $val, PDO::FETCH_ASSOC);
  }


  /** ****************************************
   * Add a transcode into the transcode table
   * $v is an associative array with fields name and value
   * @return integer the newly created transcode id 
   */
  public function transcodeAdd($v) {
    global $db;
    $k=array("id","status","mediaid","setting","subsetting","metadata");
    $sql=""; $val=array();
    foreach($k as $key) {
      if (isset($v[$key])) { 
	if ($sql) $sql.=", ";
	$sql.="$key=?";
	$val[]=$v[$key];
      }
    }
    if (!$sql) return false; // no information!
    $query = "INSERT INTO transcodes SET datecreate=NOW(), $sql";
    $db->q($query,$val);
    return $db->lastInsertId();
  }


  /** ****************************************
   * Update a transcode in the transcode table
   * @param $id integer The transcode id
   * @param $v array An associative array with fields name and value
   * @return boolean true if the media has been updated
   */
  public function transcodeUpdate($id,$v) {
    global $db;
    $k=array("status","mediaid","setting","subsetting","metadata");
    $sql=""; $val=array();
    foreach($k as $key) {
      if (isset($v[$key])) { 
	if ($sql) $sql.=", ";
	$sql.="$key=?";
	$val[]=$v[$key];
      }
    }
    if (!$sql) return false; // no information!
    $val[]=$id;
    $query = "UPDATE transcodes SET dateupdate=NOW(), $sql WHERE id=?";
    $db->q($query,$val);
    return true;
  }


  /** ****************************************
   * Search for one or more transcode
   * @param $search is an array of key => value to search for 
   * @param $operator the AND operator is the default, could be OR
   * @return array the list of found transcodes
   */
  public function transcodeSearch($search,$operator="AND") {
    global $db;
    $k=array("id","status","mediaid","setting","subsetting","metadata");
    $sql=""; $val=array();
    foreach($k as $key) {
      if (isset($search[$key])) { 
	if ($sql) $sql.=" $operator ";
	$sql.="$key=?";
	$val[]=$search[$key];
      }
    }
    if (!$sql) return false; // no information!
    $query = "SELECT * FROM transcodes WHERE $sql";
    return $db->qlist($query, $val, PDO::FETCH_ASSOC);
  }


  /* ------------------------------------------------------------ */
  /** 
   * return the list of all settings availables on this Transcoder
   */
  public function getAllSettings() {
    global $settings;
    $settings_all=array();
    Hooks::call('settingsList',$settings_all);
    if (is_file(__DIR__."/../libs/settings.php")) {
      include(__DIR__."/../libs/settings.php");
      $settings_all=$settings_all + $settings;
    }
    return $settings_all;
  }


  /* ------------------------------------------------------------ */
  /** 
   * Log the API Call to the DB, so that we know who asked for what and when
   */
  public function logApiCall($api) {
    global $db;
    if (empty($this->me["uid"])) {
      $me=0; 
    } else {
      $me=$this->me["uid"];
      $db->q("UPDATE users SET lastactivity=NOW() WHERE uid=".intval($me).";");
    }
    
    if (isset($this->params)) {
      $parray=serialize($this->params);
    } else {
      $parray=serialize(NULL);
    }
    if (!empty($_REQUEST["application"]) && !empty($_REQUEST["version"])) {
      $appversion=$db->qone("SELECT id FROM appversions WHERE application=? AND version=?",array($_REQUEST["application"],$_REQUEST["version"]));
      if (!$appversion) {
	$db->q("INSERT INTO appversions SET application=?, version=?",array($_REQUEST["application"],$_REQUEST["version"]));
	$appversion=$db->qone("SELECT LAST_INSERT_ID() AS id;");
      }
      $appversion = $appversion->id;
    } else $appversion=0;

    $query = 'INSERT INTO apicalls SET calltime=NOW(), user=?, api=?, params=?, ip=?, appversion=?;';
    $db->q($query,
	   array($me,$api,$parray,$_SERVER["REMOTE_ADDR"],$appversion)
		 );
  }


  /** ********************************************************************
   * Filter the $_REQUEST[] array from unauthorized values. 
   * Fills $this->param with allowed one, and check their type if needed.
   * allowed is an array of arrays. Each sub-array has 3 parameters : 
   * - the field name - a boolean telling if this field is mandatory - the default value for not-set parameters
   */
  public function filterParams($allowed) {
    $this->params=array();
    $error="";
    foreach($allowed as $k=>$v) {
      if (isset($_REQUEST[$k])) {
	switch ($v[0]) {
	case "integer":
	  $this->params[$k]=intval($_REQUEST[$k]);
	  break;
	case "boolean":
	  $this->params[$k]=(($_REQUEST[$k])?true:false);
	  break;
	case "string":
	case "url": // FIXME: check url format
	  $this->params[$k]=trim($_REQUEST[$k]);
	  break;
	default:
	  $this->params[$k]=$_REQUEST[$k];
	}
      } else {
	if ($v[1]) {
	  $error.=sprintf(_("Parameter %s is mandatory"),$k).", ";
	} else {
	  $this->params[$k]=$v[2];
	}
      }
    }
    if ($error) {
      $this->apiError(API_ERROR_BADPARAM,$error);
    }
    return $this->params;
  }


  /** ********************************************************************
   * Check the identity of the caller, exit with an error in case of wrong identity
   * or returns $me table with user information.
   */
  public function checkCallerIdentity() {
    global $db;
    if (!isset($_REQUEST["transcoder_key"]) || !isset($_REQUEST["application"]) || !isset($_REQUEST["version"])) {
      $this->apiError(API_ERROR_MANDATORY,_("API key, application name and version number are mandatory."));
    }
    // Search for the user api key
    $query = 'SELECT * FROM users WHERE apikey = ?';
    $this->me=$db->qone($query, array($_REQUEST["transcoder_key"]), PDO::FETCH_ASSOC);
    if (!$this->me) {
      $this->apiError(API_ERROR_NOKEY,_("The specified APIKEY does not exist in this transcoder."));
    }
    if (!$this->me["enabled"])  {
      $this->apiError(API_ERROR_DISABLED,_("Your account is disabled, please contact the administrator of this transcoder."));
    }
    $this->allowApplication($_REQUEST["application"], $_REQUEST["version"]);
    return $this->me;
  }


  /* ------------------------------------------------------------ */
  /** Check that the user is allowed to use that adapter. 
   * Trigger apiError() if not (therefore quit) 
   * @param $user array the user data
   * @param $adapter string the adapter 
   * @return true
   */
  public function checkAllowedAdapter($user,$adapter) {
    if (!empty($user["allowedadapters"])) {
      $list=explode(",",$user["allowedadapters"]);
      if (in_array($adapter,$list)) {
	return true;
      }
    }
    $this->apiError(API_ERROR_ADAPTERNOTALLOWED,_("Adapter not allowed"));
  }

  
  /* ------------------------------------------------------------ */
  /** Returns an instance of the specified Adapter class object.
   * @param $adapter string the adapter to search for using Hooks
   * @param $user array the user data, if set, will check that this user 
   *  is allowed to use this adapter. If not, triggers an apiError.
   * @return AdapterObject
   */
  public function getAdapter($adapter,$user=NULL) {
    // Use the adapter to validate the URL and save the media : 
    $adapter=strtolower(trim($adapter));
    $adapterClass=array();
    Hooks::call('adapterList',$adapterClass);
    //    error_log(implode(' ',$user));
    if (!in_array($adapter,$adapterClass)) {
      $this->apiError(API_ERROR_ADAPTERNOTSUPPORTED,_("The requested adapter is not supported on this Transcoder"));
    }
    if (!empty($user)) {
      $this->checkAllowedAdapter($user,$adapter);
    }
    require_once(MODULES."/".$adapter."/adapter.php");
    $adapter=strtoupper(substr($adapter,0,1)).substr($adapter,1)."Adapter";
    return new $adapter();
  }


  /** ********************************************************************
   * Enforce rate limit on API call for each user. 
   * default limit is 10 requests per minute 
   * (TODO: allow configuration of this globally and per user (field "rate" used but doesn't exist yet ;) ) )
   * the $this->me variable MUST be set and the function call exit if over 
   * limits
   */
  public function enforceLimits() {
    global $db;
    if (!empty($this->me['uid'])) {
      $query = 'SELECT COUNT(*) FROM apicalls WHERE calltime>DATE_SUB(NOW(),INTERVAL 60 SECOND) AND user=?;';
      $rate=$db->qonefield($query,array($this->me["uid"]));
    } else {
      // unidentified api calls have an allowed rate of half the identified one
      $query = 'SELECT COUNT(*) FROM apicalls WHERE calltime>DATE_SUB(NOW(),INTERVAL 120 SECOND) AND ip=?;';
      $rate=$db->qonefield($query,array($_SERVER["REMOTE_ADDR"]));      
    }
    if (isset($this->me["rate"]) && $this->me["rate"]) $myrate=$this->me["rate"]; else $myrate=RATE_DEFAULT;
    if ($rate>=$myrate) {
      $this->apiError(API_ERROR_RATELIMIT,_("You sent too many queries per minute, please wait a little bit before sending more..."));
    }
    return true;
  }


  /** ********************************************************************
   * Emit a json_encoded api error code and message
   * then EXIT THE PAGE
   */ 
  public function apiError($code,$msg) {
    header("Content-Type: application/json");
    if ($code>100) {
      header("HTTP/1.0 {$code}");
    } else {
      header("HTTP/1.0 200 OK");
    }
    echo json_encode(
		     array("result"=>array("code" => $code, "message" => $msg))
		     ,JSON_FORCE_OBJECT);
    exit(); // FATAL
  }


  /** ********************************************************************
   * Emit a json_encoded api response
   * then EXIT THE PAGE
   */ 
  public function returnValue($val) {
    header("Content-Type: application/json");
    echo json_encode($val,JSON_FORCE_OBJECT);
    exit(); // FATAL
  }


  /** ********************************************************************
   * For each currently locked process *on the same machine*, we check that
   * the process still exists, and if not, we unlock the queued task as if 
   * it failed (retry count -1)
   */ 
  public function cleanupQueueLocks() {
    global $db;
    $hostname=gethostname();
    // We cleanup queue locks, unless there has been a cleanup for this hostname less than 5 minutes ago :) 
    if (file_exists(TMP_PATH."/cleanup-".$hostname) && filemtime(TMP_PATH."/cleanup-".$hostname)+300>time()) {
      return true; // do nothing
    }
    $locked=$db->qlist("SELECT * FROM queue WHERE lockhost=?;",array($hostname),PDO::FETCH_ASSOC);
    if (is_array($locked) && count($locked)) {
      foreach($locked as $l) {
	if (!is_dir("/proc/".$l["lockpid"])) {
	  // Free that lock, unlock the task, retry it in 5 min
	  error_log("Task ".$l["id"]." running on ".$hostname." on process ".$l["lockpid"]." but process not found.");
	  if ($l["retry"]==1) {
	    // failed for real...
	    error_log("Process retry down to 0, canceling");
	    $db->q( "UPDATE queue SET datedone=NOW(), status=? WHERE id=?", array(STATUS_ERROR,$l["id"]) );
	  } else {
	    // retry in 5 min
	    $retry=$l["retry"]-1;
	    error_log("Process retry down to ".$retry." will retry in 5 minutes.");
	    $db->q( "UPDATE queue SET status=?, retry=?, datetry=DATE_ADD(NOW(), INTERVAL 5 MINUTE), lockhost='', lockpid=0 WHERE id=?", array(STATUS_TODO,$retry,$l["id"]) );
	  }
	} // proc exist ? 
      } // for each locked proc
    }
    touch(TMP_PATH."/cleanup-".$hostname);
  } // cleanupQueueLocks


  function allowApplication($application, $version) {
    // TODO : allow a list of blacklisted Application or Application/Version to trigger an error
    // if (!$allowed) $this->apiError(API_ERROR_APPNOTALLOWED,_("This application is not allowed, you may ask the transcoder owner why or try to use another public transcoder"));
    return true;
  }

  public $log_caller="unknown process";

  /* ------------------------------------------------------------ */
  /** Log a message into the system log
   * @param integer $priority The priority, see below $aprio for available values
   * @param string $message the Message to log
   */
  public function log($priority, $message) {
    static $logopened=false;
    if (!defined("LOGGER")) define("LOGGER","nowhere");
    if (!defined("LOGGER_DEBUG")) define("LOGGER_DEBUG",true);

    if (!LOGGER_DEBUG && $priority==LOG_DEBUG) return;

    if (LOGGER=="syslog") {
      if (!$logopened) {
	$logopened=true; 
	if (!empty($_SERVER["HTTP_HOST"])) {
	  openlog("OpenMediaKit-Transcoder", LOG_NDELAY, LOG_DAEMON);
	} else {
	  openlog("OpenMediaKit-Transcoder", LOG_NDELAY | LOG_PID, LOG_DAEMON);
	}
      }
      syslog($priority,"(".$this->log_caller.") ".$message);
    } 
    if (LOGGER=="file") {
      $f=@fopen(LOGGER_FILE,"ab");
      if ($f) {
	fputs($f,"[".date("Y-m-d H:i:s")."] (".$this->log_caller.") ".$this->aprio[$priority].": ".str_replace("\n"," ",$message)."\n");
	fclose($f);
      }
    }
  } // log
  
  private $aprio=array(LOG_CRIT => "Critical",
		       LOG_ERR => "Error",
		       LOG_WARNING  => "Warning",
		       LOG_INFO => "Info",
		       LOG_DEBUG => "Debug",
		       );

} // Class Api

