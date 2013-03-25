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
    }

    // TODO Show the API Documentation ;)     
    $headers = array(
		     'name' => _('Name'),
		     'parameters' => _('Parameters'),
		     'return' => _('Returned value'),
		     'documentation' => _('Documentation'),
		     );
    $this->render('index', array('functions' => $functions, 'headers' => $headers));
  }

  
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
    
    $this->api->logApiCall("newmedia");
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
   * when the Client tells the Transcoder that a new media must be downloaded asap from the Client.
   * The Client can ask for a metadata recognition as soon as it has been downloaded by the Transcoder.
   * Params for DOWNLOAD TASK : id (id of the video in the openmediakit) url (of the video at the omk side)
   * Params for METADATA TASK : dometadata (default true)
   * Depending on the pattern of the URL, a specific OMKTFileAdapter will be triggered for download.
   */
  public function app_get_settingsAction() {
    $this->me=$this->api->checkCallerIdentity();
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
						   "application" => array("string",true),
						   "version" => array("string",true),
						   "lang" => array("string",false,"en_US"),
						   ));
    require_once(MODULES."/users/libs/users.php");
    $this->api->logApiCall("subscribe");
    // Check for application / version blacklist
    $this->api->allowApplication($this->params['application'], $this->params['version']);
    // Create an account 
    $this->params['pass']=Users::randomPass();
    $this->params['enabled']=1;
    $this->params['validated']=0;
    $this->params['admin']=0;
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
  

} /* APIController */
