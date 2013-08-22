<?php

require_once(__DIR__."/libs/api.php");

class ApiController extends AController {


  /* Array that will contain all the user informations */
  private $me=array(); 

  /* Array that will contain API call parameters filtered from $_REQUEST */
  private $params=array(); 

  /* API object from libs/ */
  public $api; 


  public function ApiController() {
    // Instance of our lib/api.php
    $this->api=new Api();
  }


  public function indexAction() {
    if (!empty($_REQUEST["action"])) {
      $call=$_REQUEST["action"]."Action";
      if (method_exists($this,$call)) {
	return $this->$call();
      } 
      $api->apiError(API_ERROR_NOTFOUND,_("Api call doesn't exist"));
    }

    $this->render('index');
  }

  
  /* TODO : make a more complicated version of app_request_format for specific track extraction routines */
  /** ********************************************************************
   * when the Client asks the Transcoder for a transcode
   * The Client can ask for multiple transcode at the same time, 
   * Params : id (id of the video in the openmediakit) 
   * settings_id_list = list of settings-id to transcode to, either as an array
   * (using [] and setting multiple times), or as a json-encoded array of IDs
   * Return a list of error code + message for each setting.
   */
  public function app_request_formatAction() {
    $this->me=$this->api->checkCallerIdentity();
    $this->api->enforceLimits();
    // for each params, tell its name, and its type and if it is mandatory
    $this->params=$this->api->filterParams(array(/* "paramname" => array("type",mandatory?,defaultvalue), */
						 "id" => array("integer",true),
						 "settings_id_list" => array("any",true),
						 ));
    
    $this->api->logApiCall("app_request_format");
    if (! ($media=$this->api->mediaSearch(array("owner"=>$this->me["uid"], "remoteid" => $this->params["id"])))
	|| !is_array($media) 
	|| !count($media)) {
      $this->api->apiError(API_ERROR_NOTFOUND,_("This media has not been uploaded by you yet, please upload it before asking for transcodes."));      
    }
    $media=$media[0];
    if ($media["status"]!=MEDIA_METADATA_OK) {
      $this->api->apiError(API_ERROR_BADMEDIA,_("The media's metadata has not been recognized, or it had expired."));
    }
    $adapterObject=$this->api->getAdapter($media["adapter"],$this->me);

    // Now we check the settings : 
    $tmp=$this->api->getAllSettings();
    $allsettings=array();
    foreach($tmp as $setting) {
      $allsettings[]=$setting["id"];
    }
    if (!is_array($this->params["settings_id_list"]) || !count($this->params["settings_id_list"])) {
      $this->params["settings_id_list"]=@json_decode($this->params["settings_id_list"],true);
      if (!is_array($this->params["settings_id_list"]) || !count($this->params["settings_id_list"])) {
	$this->api->apiError(API_ERROR_BADPARAM,_("The settings_id_list is incorrect, it must be either a php-style array[] or a json array"));
      }
    }
    $settings=array();
    $hasgood=false;
    foreach($this->params["settings_id_list"] as $one) {
      $one=intval($one);
      if (!in_array($one,$allsettings)) {
	$settings[$one]=array("code" => API_ERROR_BADPARAM, "message" => _("Setting unknown by this transcoder"));
      } else {
	$settings[$one]="";
	$hasgood=true;
      }
    }
    if (!$hasgood) {
      $this->api->apiError(API_ERROR_BADPARAM,_("None of your settings are known by this transcoder"));
    }

    $hasgood=false;
    // We search for already-transcoded versions:
    foreach($settings as $one=>$err) {
      if ($err=="") {
	$found=$this->api->transcodeSearch( array( "mediaid" => $media["id"], "setting" => $one ) );
	if ($found) {
	  $settings[$one]=array("code" => API_ERROR_ALREADY, "message" => _("The video has already been transcoded to this setting, or this transcode is in progress."));
	} else {
	  $hasgood=true;
	}
      }
    }

    if (!$hasgood) {
      $this->api->apiError(API_ERROR_ALREADY,_("This video has already been transcoded to all the settings you just asked, or transcode is in progress."));
    }

    // We create the transcodes and add them to the transcode queue for the good settings
    $hasgood=false;
    foreach($settings as $one=>$err) {
      if ($err=="") {
	$transcode_id=$this->api->transcodeAdd(array(
						     "mediaid" => $media["id"],
						     "setting" => $one,
						     "status" => TRANSCODE_ASKED,
						     ) );
	if ($transcode_id) {
	  if ($this->api->queueAdd(TASK_DO_TRANSCODE,$media["id"],TRANSCODE_RETRY,
				   array("setting" => $one, "transcode" => $transcode_id
					 ),$media["adapter"]) ) {
	    $hasgood=true;
	    $settings[$one]=array("code" => API_ERROR_OK, "message" => _("Transcode requested") );
	  }
	} else {
	  $settings[$one]=array("code" => API_ERROR_EXEC, "message" => _("Can't request this Transcode, retry later") );
	}
      }
    } 

    if (!$hasgood) {
      $this->api->apiError(API_ERROR_EXEC,_("No transcode has been launch, retry later"));
    }

    // Return one code+message for each requested setting
    header("Content-Type: application/json");
    echo json_encode($settings,JSON_FORCE_OBJECT);
    exit();

  } /* app_request_formatAction */


  /** ********************************************************************
   * when the Client tells the Transcoder that a new media must be downloaded asap from the Client.
   * The Client can ask for a metadata recognition as soon as it has been downloaded by the Transcoder.
   * Params for DOWNLOAD TASK : id (id of the video in the openmediakit) url (of the video at the omk side)
   * Params for METADATA TASK : dometadata (default true)
   * Depending on the pattern of the URL, a specific OMKTFileAdapter will be triggered for download.
   */
  public function app_new_mediaAction() {
    $this->me=$this->api->checkCallerIdentity();
    $this->api->enforceLimits();
    // for each params, tell its name, and its type and if it is mandatory
    $this->params=$this->api->filterParams(array(/* "paramname" => array("type",mandatory?,defaultvalue), */
						 "id" => array("integer",true),
						 "url" => array("string",true),
						 "adapter" => array("string",false,"http"),
						 "dometadata" => array("boolean",false,true),
						 ));
    
    $this->api->logApiCall("app_new_media");
    if ($this->api->mediaSearch(array("owner"=>$this->me["uid"], "remoteid" => $this->params["id"]))) {
      $this->api->apiError(API_ERROR_ALREADY,_("You already added this media ID from you to this transcoder. Cannot proceed."));      
    }
    $adapterObject=$this->api->getAdapter($this->params["adapter"],$this->me);

    // We check the validity of the url and ask for download or not? : 
    $state=$adapterObject->addNewMedia($this->params["url"]);

    if ($state==ADAPTER_NEW_MEDIA_INVALID) {
      $this->api->apiError(API_ERROR_BADURL,_("The remote url is incorrect for this adapter. Please check your code"));
    }
    if ($state==ADAPTER_NEW_MEDIA_DOWNLOAD) {
      $status=MEDIA_REMOTE_AVAILABLE;
    } elseif ($state==ADAPTER_NEW_MEDIA_NODOWNLOAD) {
      $status=MEDIA_LOCAL_AVAILABLE;
    } else {
      $this->api->apiError(API_ERROR_CODEERROR,_("BAD IMPLEMENTATION of AdapterClass in ".$this->params["adapter"].", read the docs!"));
    }

    // We create a media
    $media_id=$this->api->mediaAdd(array(
					 "owner" => $this->me["uid"],
					 "remoteid" => $this->params["id"],
					 "adapter" => $this->params["adapter"],
					 "remoteurl" => $this->params["url"],
					 "status" => $status,
					 ) );
    if (!$media_id) {
      $this->api->apiError(API_ERROR_CREATEMEDIA,_("Cannot create a new media, please retry later."));
    }
    if ($status==MEDIA_REMOTE_AVAILABLE) {
      // then we queue the download of the media
      if ($this->api->queueAdd(TASK_DOWNLOAD,$media_id,DOWNLOAD_RETRY,
				  array("url" => $this->params["url"], 
					"dometadata" => $this->params["dometadata"], 
					),$this->params["adapter"]) ) {
	$this->api->apiError(API_ERROR_OK,_("OK, Download task queued"));
      } else {
	$this->api->apiError(API_ERROR_NOQUEUE,_("Can't queue the task now, please try later."));	
      }
    } else {
      // already locally available? let's queue the metadata search:
      if ( $this->api->queueAdd(TASK_DO_METADATA,$media_id,METADATA_RETRY,null,$this->params["adapter"])) {
	$this->api->apiError(API_ERROR_OK,_("OK, Metadata task queued"));
      } else {
	$this->api->apiError(API_ERROR_NOQUEUE,_("Can't queue the task now, please try later."));	
      }
    }

  } // app_new_mediaAction


  /** ********************************************************************
   * This is a  public api which doesn't require any authentication.
   * returns the json-encoded list of settings supported by the transcoder.
   * as an array of objects. Get them from libs/settings.php
   * Also call a hook "settingsList" in case you added a module with new settings
   * those custom settings MUST HAVE an ID > 1000 ! 
   */
  public function app_get_settingsAction() {
    //    $this->me=$this->api->checkCallerIdentity();
    $this->api->enforceLimits();
    $this->api->logApiCall("app_get_settings");
    // Return the settings available on this transcoder: 
    // First trigger the hooks which adds settings ...
    $this->api->returnValue($this->api->getAllSettings());
  }


  /** ********************************************************************
   * When a new client is searching for a public transcoder, it can call 
   * http://discovery.open-mediakit.org/public?application=<application>&version=<version>
   * to obtain a json-list of the currently active public transcoders.
   * then it choose one of them and call the subscribe api call
   * on this transcoder to subscribe to it and get an account there.
   * the parameters are : 
   * email: the email address of the subscriber (*it will be verified by sending an email*)
   * url: url of the api root of the client. will be used to call 
   * app_key: the api Key the client want me to use when contacting him, <=32 characters
   * application: client application that request an account
   * version: version of the client application
   * non-mandatory parameters:
   * lang: language of the client, default to en_US (for discussion & email verification text)
   * @return array the list of available settings on this transcoder. if the subscription was successfull.
   */
  public function app_subscribeAction() {
    if (!defined("PUBLIC_TRANSCODER") || !PUBLIC_TRANSCODER) {
      $this->api->apiError(API_ERROR_NOTPUBLIC,_("This server is not a public transcoder, please use another one"));
    }
    // anonymous api call   $this->me=$this->api->checkCallerIdentity();
    $this->api->enforceLimits();
    // for each params, tell its name, and its type and if it is mandatory
    $this->params = $this->api->filterParams(array(/* "paramname" => array("type",mandatory?,defaultvalue), */
						   "email" => array("string",true),
						   "url" => array("string",true),
						   "app_key" => array("string",true),
						   "application" => array("string",true),
						   "version" => array("string",true),
						   "lang" => array("string",false,"en_US"),
						   ));
    // TODO : use gettext to set the LOCALES according to the lang set by the caller.
    require_once(MODULES."/users/libs/users.php");
    $this->api->logApiCall("app_subscribe");
    // Check for application / version blacklist
    $this->api->allowApplication($this->params['application'], $this->params['version']);
    // Create an account 
    $this->params['pass']=Users::randomPass();
    $this->params['enabled']=1;
    $this->params['validated']=0;
    $this->params['admin']=0;
    $this->params['clientkey']=$this->params['app_key']; unset($this->params['app_key']);
    $uid=Users::addUser($this->params);
    if (!$uid) {
      $this->api->apiError(API_ERROR_CREATEACCOUNT,_("An error happened when creating the account. Please retry later."));
    } 
    $me=Users::get($uid);
    // Send a validation email to the user
    Users::sendValidationEmail($uid);

    $this->api->returnValue(
			    array("apikey" => $me["apikey"],
				  "settings" =>$this->api->getAllSettings()
				  ));
  }
  


  /** ********************************************************************
   * when the Client has been told by the transcoder that a transcode is READY
   * it may ask for the media itself (when the adapter is HTTP)
   * 
   */
  public function app_get_mediaAction() {
    $this->me=$this->api->checkCallerIdentity();
    // We don't enforce api calls limit here: when downloading 100's of images, we will likely do it within a few seconds
    // TODO : prevent MORE THAN X MULTIPLE PARALLEL downloads from the same IP or USER to allow the others to have more resource...
    //    $this->api->enforceLimits();
    // for each params, tell its name, and its type and if it is mandatory
    $this->params=$this->api->filterParams(array(/* "paramname" => array("type",mandatory?,defaultvalue), */
						 "id" => array("integer",true),
						 "settings_id" => array("integer",true),
						 "content_range" => array("string",false,""),
						 ));
    
    $this->api->logApiCall("app_get_media");
    if (!($media=$this->api->mediaSearch(array("owner"=>$this->me["uid"], "remoteid" => $this->params["id"])))) {
      $this->api->apiError(API_ERROR_NOTFOUND,_("Media not found."));
    }
    $media=$media[0];
    if (!($transcode=$this->api->transcodeSearch(array("mediaid"=>$media["id"], "setting" => $this->params["settings_id"])))) {
      $this->api->apiError(API_ERROR_NOTFOUND,_("Transcode not found."));
    }
    $transcode=$transcode[0];
    $adapterObject=$this->api->getAdapter($media["adapter"],$this->me);
    if (!method_exists($adapterObject,"sendMedia")) {
      $this->api->apiError(API_ERROR_NOTFOUND,_("Adapter not compatible with HTTP."));
    }
    $adapterObject->sendMedia($media,$transcode,$this->params["content_range"]);
    return;
  }
  

} /* APIController */
