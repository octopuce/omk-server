<?php
class ApiController extends AController {

  /* Array that will contain all the user informations */
  private $me=array(); 

  /* Array that will contain API call parameters filtered from $_REQUEST */
  private $params=array(); 


  public function indexAction() {
    // TODO Show the API Documentation ;)     
    $headers = array(
		     'name' => _('Name'),
		     'parameters' => _('Parameters'),
		     'return' => _('Returned value'),
		     'documentation' => _('Documentation'),
		     );
    $this->render('list', array('functions' => $functions, 'headers' => $headers));
  }

  
  /** ********************************************************************
   * API CALL, Tells the transcoder that a new media must be downloaded asap.
   */
  public function newmediaAction() {
    $this->_checkCallerIdentity();
    $this->_enforceLimits();
    // for each params, tell its name, and its type and if it is mandatory
    $this->_filterParams(array("id"=>array("integer",true),
			       "url"=>array("url",true)
			       ));
    $this->_logApiCall("newmedia");
  }


  /** ********************************************************************
   * Log the API Call to the DB, so that we know who asked for what and when
   */
  private _logApiCall($call) {
    global $db;
    $parray="";
    foreach($params as $k=>$v) $parray.=$k."=".$v." | ";
    $query = 'INSERT INTO apicalls SET calltime=NOW(), user=?, call=?, params=?, ip=?;';
    $rate=$db->q($query,
		 array($this->me["uid"],$call,$parray,$_SERVER["REMOTE_ADDR"])
		 );
  }


  /** ********************************************************************
   * Filter the $_REQUEST[] array from unauthorized values. 
   * Fills $this->param with allowed one, and check their type if needed.
   */
  private _filterParams($allowed) {
    $this->params=array();
    $error="";
    foreach($allowed as $k=>$v) {
      if (isset($_REQUEST[$k])) {
	switch ($v[1]) {
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
	$error.=sprintf(_("Parameter %s is mandatory"),$k)."\n";
      }
    }
    if ($error) {
      $this->_apiError(5,$error);
    }
  }


  /** ********************************************************************
   * Check the identity of the caller, exit with an error in case of wrong identity
   * or returns $me table with user information.
   */
  private function _checkCallerIdentity() {
    global $db;
    if (!isset($_REQUEST["key"]) || !isset($_REQUEST["application"]) || !isset($_REQUEST["version"])) {
      $this->_apiError(1,_("API key, application name and version number are mandatory"));
    }
    // Search for the user api key
    $query = 'SELECT uid, login, email, admin,enabled  '
      . 'FROM users '
      . 'WHERE apikey = ?';
    $this->me=$db->qone($query, array($_REQUEST["key"]), PDO::FETCH_ASSOC);
    if (!$this->me) {
      $this->_apiError(2,_("The specified APIKEY does not exist in this transcoder"));
    }
    if (!$this->me["enabled"])  {
      $this->_apiError(3,_("Your account is disabled, please contact the administrator of this transcoder"));
    }
    // TODO : handle blacklist of application and/or specific version
    return true;
  }


  /** ********************************************************************
   * Enforce rate limit on API call for each user. 
   * default limit is 10 requests per minute 
   * (TODO: allow configuration of this globally and per user (field "rate" used but doesn't exist yet ;) ) )
   * the $this->me variable MUST be set and the function call exit if over 
   * limits
   */
  private function _enforceLimits() {
    global $db;
    $query = 'SELECT COUNT(*) FROM apicalls WHERE calltime>DATE_SUB(NOW(),INTERVAL 60 SECOND) AND user=?;';
    $rate=$db->qonefield($query,array($this->me["uid"]));
    if (isset($this->me["rate"]) && $this->me["rate"]) $myrate=$this->me["rate"]; else $myrate=RATE_DEFAULT;
    if ($rate>=$myrate) {
      $this->_apiError(4,_("You sent too many queries per minutes, please wait a little bit before sending more..."));
    }
    return true;
  }

  /** ********************************************************************
   * Emit a json_encoded api error code and message
   * then exit the page
   */ 
  private _apiError($code,$msg) {
    header("Content-Type: application/json");
    $o=new StdClass();
    $o->code=$code; $o->msg=$msg;
    echo json_encode($o);
    exit(); // FATAL
  }

}
