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
  


} // class DummyAdapter

?>