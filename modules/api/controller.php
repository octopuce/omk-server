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
   * API CALL, Tells the transcoder that a new media must be downloaded asap.
   */
  public function newmediaAction() {
    $this->me=$this->api->checkCallerIdentity();
    $this->api->enforceLimits();
    // for each params, tell its name, and its type and if it is mandatory
    $this->api->filterParams(array("id" => array("integer",true),
			       "url" => array("url",true)
			       ));
    $this->api->logApiCall("newmedia");
    // Do it :) 
    // TODO: check that we don't already have this media in the downlaod queue...
    if ($this->api->mediaSearch(array("owner"=>$this->me["uid"], "remoteid" => $this->params["id"]))) {
      $this->api->apiError(7,_("You already added this media ID from you to this transcoder. Cannot proceed."));      
    }
    // first, we create a media
    $media_id=$this->api->mediaAdd(array("status" => MEDIA_REMOTE_AVAILABLE,
				     "remoteid" => $this->params["id"],
				     "remoteurl" => $this->params["url"],
				     "owner" => $this->me["uid"] ) );
    if (!$media_id) {
      $this->api->apiError(6,_("Cannot create a new media, please retry later."));
    }
    // then we queue the download of the media
    return $this->api->queueAdd(TASK_DOWNLOAD,$media_id,DOWNLOAD_RETRY);
  }


}
