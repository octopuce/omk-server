<?php

require_once(MODULES."/api/libs/constants.php");

class HttpAdapter {

  
  /* ------------------------------------------------------------ */
  /** This method is called when we receive a "app_new_media" API Call
   * and that the adapter is this one.
   * it must :
   * - validate the remote url pattern
   * - tell if this adapter requires a download cron to download the file from the remote client (it will be created by the API class)
   *   (if not, it means that the media will be locally-available to ffmpeg for metadata or transcoding purpose)
   *   If the adapter requires a download, a daemon must be defined in daemon.php and will be launched. See dummy example or http adapter example.
   * @params $url Remote URL from the OMK Client. We validate it 
   * @return ADAPTER_NEW_MEDIA_* CONSTANT (see modules/api/libs/constants.php )
   */
  public function addNewMedia($url) {
    if (substr($url,0,7)=="http://" || substr($url,0,8)=="https://") {
      return ADAPTER_NEW_MEDIA_DOWNLOAD;
    }
    return ADAPTER_NEW_MEDIA_INVALID;
  }
  

  /* ------------------------------------------------------------ */
  /** This method is called when ffmpeg want to recognize a media's metadata
   * @param $media array() The entire media object using that Adapter
   * @return $filepath The filepath where ffmpeg will find this media
   */
  function filePathMetadata($media) {
    return STORAGE_PATH."/original/".$media["id"];
  }

  
  /* ------------------------------------------------------------ */
  /** This method is called when ffmpeg want to transcode a media
   * @param $media array() The entire media object using that Adapter
   * @param $settings array() The entire settings object that is used by Ffmpeg
   * @return array($source, $destination) The filepaths where ffmpeg will find this media (source)
   *  and where ffmpeg will need to store the transcoded media (destination)
   *  please note that some settings are 'multiple-destination' one, 
   *  in that case, $destination must be an empty (but existing) folder.
   */
  function filePathTranscode($media,$settings) {
    @mkdir(STORAGE_PATH."/transcoded/");
    if ($settings["type"]=="thumbnails") {
      @mkdir(STORAGE_PATH."/transcoded/".$media["id"]."-".$settings);
    }
    return array(
		 STORAGE_PATH."/original/".$media["id"],
		 STORAGE_PATH."/transcoded/".$media["id"]."-".$settings
		 );
  }


  /* ------------------------------------------------------------ */
  /** This method is called when the API is about to tell the OMK Client
   * that a transcoded file is available.
   * @param $media array() The entire media object using that Adapter
   * @param $settings array() The entire settings object that is used by Ffmpeg 
   * @return $url The URL where the OMK Client will be able to find the transcoded file.
   *  please note that some settings are 'multiple-destination' one,
   *  in that case, $url must be explicit enough for the OMK Client 
   *  (who knows that Adapter) to understand where it will find all the files.
   */
  function transcodedURL($media,$settings) {
    // the thumbnails-type files will be served by this controller/action/ method
    // by adding ?id=0-99 to those urls
    return FULL_URL."http/download/".susbtr(md5(RANDOM_SALT."_".$media["owner"]),0,5)."/".$media["id"]."/".$settings["id"];
  }
  

  /* ------------------------------------------------------------ */
  /** This method is called when the API is about to send TRANSCODED CONTENT
   * to the OMK Client
   * @param $media array() The entire media object using that Adapter
   * @param $transcode array() The entire transcode object
   * @param $serial integer the number (if not 1) of the object to send.
   * @param $range string a range object offset1-offset2
   * @return echoes the content properly
   */
  function sendMedia($media,$transcode,$serial=1,$range="") {
    $this->api=new Api();

    include(__DIR__."/../api/libs/settings.php");
    if (!$range) {
      foreach(getallheaders() as $name=>$value) {
	if ($name=="Range") {
	  if (preg_match('#^bytes=([0-9]*-[0-9]*)$#',$value,$mat)) {
	    $range=$mat[1];
	  }
	}
      }
      if (!$range) $range="0-";
    }
    $start=0; $end=-1;
    if (preg_match("#^([0-9]*)-([0-9]*)$#",trim($range),$mat)) {
      $start=$mat[1];
      $end=$mat[2];
    }
    if ($start !=0 && $end!=0 && $end!=-1) {
      if ($start > $end) $this->api->apiError(API_ERROR_BADRANGE,_("Requested Range Not Satisfiable (start > end)"));
    }
    
    $destination=STORAGE_PATH."/transcoded/".$media["id"]."-".$transcode["setting"];
    $metadata=unserialize($transcode["metadata"]);
    if ($metadata["cardinality"]!=1) {
      $dest=$destination."/".sprintf("%05d",$serial).".".$settings[$transcode["setting"]]["extension"];
    } else {
      if ($serial!=1) {
	$this->api->apiError(API_ERROR_BADRANGE,_("Serial must be 1"));
      }
      $dest=$destination.".".$settings[$transcode["setting"]]["extension"];
    }
    $filesize=filesize($dest);
    if ($filesize<$start) {
      $this->api->apiError(API_ERROR_BADRANGE,_("Requested Range Not Satisfiable (start > size)"));
    }
    // Search for the destination file ...
    if ($end!=-1) {
      $tosend=$end-$start;
    }
    if ($end==-1 || ($tosend+$start>$filesize)) $tosend=$filesize-$start;

    // SEND THE FILE
    $f=fopen($dest,"rb");
    if (!$f) {
      $this->api->apiError(API_ERROR_NOTFOUND,_("File not found!"));
    }
    header("Content-Range: bytes ".$start."-".$end."/".filesize($dest));
    header("Content-Length: ".$tosend);
    header("Content-Type: ".$metadata["mime"]);
    if ($start!=0) fseek($f,$start);
    while ($tosend) {
      $res=fread($f,min(8192,$tosend));
      $tosend-=strlen($res);
      echo $res;
    }
    fclose($f);
    exit();
  }

} // class DummyAdapter


// NGINX compatibility
if (!function_exists('getallheaders'))
  {
    function getallheaders()
    {
      $headers = '';
      foreach ($_SERVER as $name => $value)
	{
	  if (substr($name, 0, 5) == 'HTTP_')
	    {
	      $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
	    }
	}
      return $headers;
    }
  } 

?>