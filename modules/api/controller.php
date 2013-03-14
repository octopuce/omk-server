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
   * Params for METADATA TASK : dometadata (default true) [cropdetect (default false)]
   * Depending on the pattern of the URL, a specific OMKTFileAdapter will be triggered for download.
   */
  public function newmediaAction() {
    $this->me=$this->api->checkCallerIdentity();
    $this->api->enforceLimits();
    // for each params, tell its name, and its type and if it is mandatory
    $this->params=$this->api->filterParams(array(/* "paramname" => array("type",mandatory?,defaultvalue), */
						 "id" => array("integer",true),
						 "url" => array("string",true),
						 "dometadata" => array("boolean",false,true),
						 // "cropdetect" => array("boolean",false,false),
						 ));
    
    $this->api->logApiCall("newmedia");
    if ($this->api->mediaSearch(array("owner"=>$this->me["uid"], "remoteid" => $this->params["id"]))) {
      $this->api->apiError(7,_("You already added this media ID from you to this transcoder. Cannot proceed."));      
    }
    // first, we create a media
    $media_id=$this->api->mediaAdd(array("status" => MEDIA_REMOTE_AVAILABLE,
					 "remoteid" => $this->params["id"],
					 "owner" => $this->me["uid"] ) );
    if (!$media_id) {
      $this->api->apiError(6,_("Cannot create a new media, please retry later."));
    }

    // then we queue the download of the media
    return $this->api->queueAdd(TASK_DOWNLOAD,$media_id,DOWNLOAD_RETRY,
				array("url" => $this->params["url"], 
				      "dometadata" => $this->params["dometadata"], 
				      //"cropdetect" => $this->params["cropdetect"]
				      ) );
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
   */
  public function subscribeAction() {
    if (!defined("PUBLIC_TRANSCODER") || !PUBLIC_TRANSCODER) {
      $this->api->apiError(8,_("This server is not a public transcoder, please use another one"));
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
    
    $this->api->logApiCall("subscribe");
    // Check for application / version blacklist
    $this->api->allowApplication($this->params['application'], $this->params['version']);
    // Create an account 
    $this->params['pass']=$user->randomPass();
    $this->params['enabled']=1;
    $this->params['validated']=0;
    $this->params['admin']=1;
    $uid=$user-addUser($this->params);
    if (!$uid) {
      $this->api
    } 
    // Send a validation email to the user
    $user->sendValidationEmail($uid);
  }
  

} /* APIController */
