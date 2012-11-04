<?php

require_once(__DIR__."/constants.php");

define("DOWNLOAD_RETRY",10); // retry 10 times each download
define("METADATA_RETRY",4); // retry 4 times each metadata search

class Api {


  /** ****************************************
   * Get a task and lock it for processing.
   * @param $task integer is the task type
   * @return array the locked task, or false if no task has been found
   */ 
  public function getQueuedTaskLock($task) {
    global $db;
    $task=intval($task);
    if ($task<TASK_MIN || $task>TASK_MAX) return false;
    $hostname=gethostname();
    $pid=getmypid();
    $db->q("LOCK TABLES queue;");
    $query="SELECT * FROM queue WHERE task=? AND status=? AND lockhost='' AND datetry<=NOW() ORDER BY retry DESC, datequeue ASC LIMIT 1;";
    $me=$db->qone( $query,array($task,STATUS_TODO),PDO::FETCH_ASSOC );
    if (!$me) {
      $db->q("UNLOCK TABLES queue;");
      return false;
    }
    $query="UPDATE queue SET status=?, lockhost=?, lockpid=?, datelaunch=NOW() WHERE id=?;";
    $db->q( $query,array(STATUS_PROCESSING,$hostkname,$pid,$me["id"]) );
    $db->q("UNLOCK TABLES queue;");
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
   * if the retry is not 0, set the datetry to NOW()+5 minutes.
   * @param $id is the task number
   * @return boolean TRUE if the task has been marked as failed
   */ 
  public function setTaskFailedUnlock($id) {
    global $db;
    $id=intval($id);
    $task=$db->qone("SELECT * FROM task WHERE id=?",array($id));
    if (!$task) return false;

    if ($task["retry"]==1) {
      // failed for real...
      $db->q( "UPDATE task SET datedone=NOW(), status=?, lockhost='', lockpid=0 WHERE id=?", array(STATUS_ERROR,$id) );
    } else {
      // retry in 5 min
      $retry=$task["retry"]-1;
      $db->q( "UPDATE task SET status=?, retry=?, datetry=DATE_ADD(NOW(), INTERVAL 5 MINUTE), lockhost='', lockpid=0 WHERE id=?", array(STATUS_TODO,$retry,$id) );
    }
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
  public function queueAdd($task,$media,$retry=1,$format=null) {
    global $db;
    $query = "INSERT INTO queue SET datequeue=NOW(), datetry=NOW(), user=?, status=".STATUS_TODO.", retry=?, lockhost='', lockpid=0,  task=?, mediaid=?, formatid=?;";
    $db->q($query,array($this->me["uid"],$retry,$task,$media,$format));
    return $db->lastInsertId();    
  }
  

  /** ****************************************
   * Add a media into the media table
   * $v is an associative array with fields name and value
   * @return integer the newly created media id 
   */
  public function mediaAdd($v) {
    global $db;
    $k=array("status","remoteid","remoteurl","owner");
    $sql=""; $val=array();
    foreach($k as $key) {
      if ($v[$key]) { 
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
    $k=array("status","remoteid","remoteurl","owner","metadata");
    $sql=""; $val=array();
    foreach($k as $key) {
      if ($v[$key]) { 
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
   * Get the metadata of a media using ffmpeg
   * @param $filename string the filename to parse
   * @return array a complex array (see the doc)
   * of associative array with the metadata of each track.
   */
  public function getFfmpegMetadata($file) {

    // This code is for the SQUEEZE version of deb-multimedia ffmpeg version

    exec("ffmpeg -i ".escapeshellarg($file)." -vcodec copy -acodec copy -f rawvideo /dev/null 2>&1",$out);
    // now we parse the lines of stdout to know the tracks

    $tracks=array(); // no track to start with
    $duration=DURATION_UNDEFINED; // undefined duration to start with
    // Each time we start a new track, we start a $track array 
    /**
     * we have 3 zones in ffmpeg output : 
     * input
     * output 
     * frame/video parsing: 
Seems stream 0 codec frame rate differs from container frame rate: 2000.00 (2000/1) -> 25.00 (25/1)
Input #0, flv, from 'le-vinvinteur--2012-10-14-20h00.flv':
  Duration: 00:25:48.53, start: 0.000000, bitrate: 1233 kb/s
    Stream #0.0: Video: h264 (Main), yuv420p, 640x360, 1137 kb/s, 25 tbr, 1k tbn, 2k tbc
    Stream #0.1: Audio: aac, 44100 Hz, stereo, s16, 96 kb/s
Output #0, rawvideo, to '/dev/null':
  Metadata:
    encoder         : Lavf53.21.0
    Stream #0.0: Video: libx264, yuv420p, 640x360, q=2-31, 1137 kb/s, 90k tbn, 1k tbc
    Stream #0.1: Audio: libvo_aacenc, 44100 Hz, stereo, 96 kb/s
    Stream #0.2: Subtitle: srt
Stream mapping:
  Stream #0.0 -> #0.0
  Stream #0.1 -> #0.1
Press ctrl-c to stop encoding
frame=38712 fps=  0 q=-1.0 Lsize=       0kB time=1548.44 bitrate=   0.0kbits/s    
video:209891kB audio:17731kB global headers:0kB muxing overhead -100.000000%
    */     
    $track=array(); // per-track attributes
    $attribs=array(); // entire file's attributes
    foreach($out as $line) {
      $line=trim($line);
      if (preg_match("|^Output |",$line,$mat)) {
	break; // second part = output
      }
      if (preg_match("|^Input #0, ([^,]*)|",$line,$mat)) {
	$attribs["box"]=$mat[1];
      }
      if (preg_match("|^Duration: ([^,]*).*bitrate: ([0-9]*) |",$line,$mat)) {
	$attribs["duration1"]=$mat[1];
	$attribs["bitrate"]=$mat[2];
      }
      if (preg_match("|^Stream [^:]*: ([^:]*): (.*)$|",$line,$mat)) {
	$track=array();
	// get the comma-separated parameters of the track
	$tmp=explode(",",$mat[2]);
	$params=array();
	foreach($tmp as $t) {
	  $params=trim($t);
	}

	switch ($mat[1]) {
	case "Audio":
	  $track["type"]=TRACK_TYPE_AUDIO;
	  // Parsing an audio-type track

	  break;
	case "Video":
	  $track["type"]=TRACK_TYPE_VIDEO;
	  // Parsing a video-type track
	  $codec=explode(" ",$params[0]);
	  $track["codec"]=$codec[0];
	  unset ($codec[0]);
	  $track["codec-sub"]=implode(" ",$codec);
	  $track["pixelfmt"]=$params[1];
	  if (preg_match("#([(0-9]*)x([0-9]*)#",$params[2],$mat)) {
	    $track["width"]=$mat[1];
	    $track["height"]=$mat[2];
	  }	
	  if (preg_match("#DAR ([(0-9]*):([0-9]*)#",$params[2],$mat)) {
	    $track["DAR1"]=$mat[1];
	    $track["DAR2"]=$mat[2];
	  }
	  if (preg_match("#PAR ([(0-9]*):([0-9]*)#",$params[2],$mat)) {
	    $track["PAR1"]=$mat[1];
	    $track["PAR2"]=$mat[2];
	  }
	  foreach($params as $p) {
	    // Search for fps, tbr and kb/s
	    if (preg_match("#([0-9\.]*) kb/s#",$p,$mat)) {
	      $track["bitrate"]=$mat[1];
	    }
	    if (preg_match("#([0-9\.]*) fps#",$p,$mat)) {
	      $track["fps"]=$mat[1];
	    }
	    if (preg_match("#([0-9\.]*) tbr#",$p,$mat) && !$track["fps"]) {
	      $track["fps"]=$mat[1];
	    }
	  }
	  break;
	case "Subtitle":
	  $track["type"]=TRACK_TYPE_SUBTITLE;
	  // Parsing a subtitle track
	  
	  break;
	default:
	  $track["type"]=TRACK_TYPE_OTHER; // TODO: tell us we found one :) It'd be clearly interesting!
	  break;
	}
	$tracks[]=$track;
      } // new track

    } // parse lines, first part (input)


    // The total duration cannot be told for sure without reading the entire file. 
    // so we do a second (slow) pass to know that.

  }


  /** ****************************************
   * Search for one or more media
   * @param $search is an array of key => value to search for 
   * @param $operator the AND operator is the default, could be OR
   * @return integer the newly created media id 
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


  /** ********************************************************************
   * Log the API Call to the DB, so that we know who asked for what and when
   */
  public function logApiCall($api) {
    global $db;
    $parray="";
    foreach($this->params as $k=>$v) $parray.=$k."=".$v." | ";
    $query = 'INSERT INTO apicalls SET calltime=NOW(), user=?, api=?, params=?, ip=?;';
    $db->q($query,
		 array($this->me["uid"],$api,$parray,$_SERVER["REMOTE_ADDR"])
		 );
  }


  /** ********************************************************************
   * Filter the $_REQUEST[] array from unauthorized values. 
   * Fills $this->param with allowed one, and check their type if needed.
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
	case "string":
	case "url": // FIXME: check url format
	  $this->params[$k]=trim($_REQUEST[$k]);
	  break;
	default:
	  $this->params[$k]=$_REQUEST[$k];
	}
      } elseif ($v[1]) {
	$error.=sprintf(_("Parameter %s is mandatory"),$k).", ";
      }
    }
    if ($error) {
      $this->apiError(5,$error);
    }
  }


  /** ********************************************************************
   * Check the identity of the caller, exit with an error in case of wrong identity
   * or returns $me table with user information.
   */
  public function checkCallerIdentity() {
    global $db;
    if (!isset($_REQUEST["key"]) || !isset($_REQUEST["application"]) || !isset($_REQUEST["version"])) {
      $this->apiError(1,_("API key, application name and version number are mandatory."));
    }
    // Search for the user api key
    $query = 'SELECT uid, login, email, admin,enabled  '
      . 'FROM users '
      . 'WHERE apikey = ?';
    $this->me=$db->qone($query, array($_REQUEST["key"]), PDO::FETCH_ASSOC);
    if (!$this->me) {
      $this->apiError(2,_("The specified APIKEY does not exist in this transcoder."));
    }
    if (!$this->me["enabled"])  {
      $this->apiError(3,_("Your account is disabled, please contact the administrator of this transcoder."));
    }
    // TODO : handle blacklist of application and/or specific version
    return $this->me;
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
    $query = 'SELECT COUNT(*) FROM apicalls WHERE calltime>DATE_SUB(NOW(),INTERVAL 60 SECOND) AND user=?;';
    $rate=$db->qonefield($query,array($this->me["uid"]));
    if (isset($this->me["rate"]) && $this->me["rate"]) $myrate=$this->me["rate"]; else $myrate=RATE_DEFAULT;
    if ($rate>=$myrate) {
      $this->apiError(4,_("You sent too many queries per minute, please wait a little bit before sending more..."));
    }
    return true;
  }


  /** ********************************************************************
   * Emit a json_encoded api error code and message
   * then exit the page
   */ 
  public function apiError($code,$msg) {
    header("Content-Type: application/json");
    $o=new StdClass();
    $o->code=$code; $o->msg=$msg;
    echo json_encode($o);
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


} // Class Api

