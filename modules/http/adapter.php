<?php

require_once(MODULES."/api/libs/constants.php");

class HttpAdapter {

  private $hastmpdir=false;

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
      @mkdir(STORAGE_PATH."/transcoded/".$media["id"]."-".$settings["id"]);
    }
    return array(
		 STORAGE_PATH."/original/".$media["id"],
		 STORAGE_PATH."/transcoded/".$media["id"]."-".$settings["id"]
		 );
  }

  
  /* ------------------------------------------------------------ */
  /** This method is called when ffmpeg want to transcode a media
   * with multiple files as output. 
   * @param $media array() The entire media object using that Adapter
   * @param $settings array() The entire settings object that is used by Ffmpeg
   * @param $prefix string is a (non mandatory) prefix for the file, which may give information on some file's specificities
   * @param $suffix string is a (non mandatory) suffix for the file, (typically its extension)
   * like "high definition" or "small size" etc.
   * @return $destination The filepath where the transcoder will need to store 
   *  the media.
   */
  function filePathTranscodeMultiple($media,$settings,$prefix="",$suffix="") {
    $this->hastmpdir=true;
    $tmpdir="/tmp/http-adapter-".getmypid();
    if (!is_dir($tmpdir)) {
      @mkdir($tmpdir);
    }
    return $tmpdir."/".$prefix.$suffix;
  }

  
  /* ------------------------------------------------------------ */
  /** This method is called when ffmpeg ends the transcode of a media
   * @param $media array() The entire media object using that Adapter
   * @param $metadata array() The metadata array for the transcoded object.
   * @param $settings array() The entire settings object that is used by Ffmpeg
   * this function may end some treatment, purge temporary folders etc.
   * @return the function doesn't return a thing, but may have changed $metadata
   */
  function filePathTranscodeEnd($media,&$metadata,$settings) {
    global $api;
    if ($this->hastmpdir) {
      $tmpdir="/tmp/http-adapter-".getmypid();
      $zip=STORAGE_PATH."/transcoded/".$media["id"]."-".$settings["id"].".zip";
      chdir($tmpdir);
      $api->log(LOG_DEBUG,"doing the zip $zip in $tmpdir");
      exec( "zip -Z store ".escapeshellarg($zip)." *");
      exec("ls |wc -l",$out);
      $metadata["cardinality"]=intval($out[0]);
      $metadata["file_size"]=filesize($zip);
      exec( "rm -rf ".escapeshellarg($tmpdir) );
    }
    return true;
  }
  

  

  /* ------------------------------------------------------------ */
  /** This method is called when the API is about to send TRANSCODED CONTENT
   * to the OMK Client
   * @param $media array() The entire media object using that Adapter
   * @param $transcode array() The entire transcode object
   * @param $range string a range object offset1-offset2
   * @return echoes the content properly
   */
  function sendMedia($media,$transcode,$range="") {
    $this->api=new Api();

    $hasRangeHeader=false;
    include(__DIR__."/../api/libs/settings.php");
    if (!$range) {
      foreach(getallheaders() as $name=>$value) {
	if ($name=="Range") {
	  if (preg_match('#^bytes=([0-9]*-[0-9]*)$#',$value,$mat)) {
	    $range=$mat[1];
	    $hasRangeHeader=true;
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
    if ($end==0) $end=-1;
    if ($start !=0 && $end!=0 && $end!=-1) {
      if ($start > $end) $this->api->apiError(API_ERROR_BADRANGE,_("Requested Range Not Satisfiable (start > end)"));
    }
    
    $destination=STORAGE_PATH."/transcoded/".$media["id"]."-".$transcode["setting"];
    $metadata=unserialize($transcode["metadata"]);
    if ($metadata["cardinality"]!=1) {
      $dest=$destination.".zip";
    } else {
      $dest=$destination.".".$settings[$transcode["setting"]]["extension"];
    }
    $filesize=filesize($dest);
    if ($start>=$filesize) {
      $this->api->apiError(API_ERROR_BADRANGE,_("Requested Range Not Satisfiable (start > size)"));
    }
    
    // Search for the destination file ...
    if ($end==-1) $end=$filesize;
      $tosend=$end-$start;
    if ($tosend+$start>$filesize) $tosend=$filesize-$start;

    // SEND THE FILE
    $f=fopen($dest,"rb");
    if (!$f) {
      $this->api->apiError(API_ERROR_NOTFOUND,_("File not found!"));
    }
    if ($hasRangeHeader) {
      header("Content-Range: bytes ".$start."-".($end-1)."/".$filesize);
    }
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

} // class HTTPAdapter


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