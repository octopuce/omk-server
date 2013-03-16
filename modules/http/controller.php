<?php

require_once(__DIR__."/libs/api.php");

class HttpController extends AController {


  public function indexAction() {
    not_found();
  }

  
  /** ********************************************************************
   * when the Client want to download a transcoded video, 
   * it calls this as seen in http/adapter.php transcodedURL($media,$settings)
   * a key is used to prevent download from an unauthorized customer.
   */
  public function downloadAction() {
    global $db;
    $api=new Api();
  }
  

} /* HttpController */
