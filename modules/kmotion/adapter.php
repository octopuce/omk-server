<?php

require_once(MODULES."/api/libs/constants.php");

class KmotionAdapter {

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
    return ADAPTER_NEW_MEDIA_NODOWNLOAD;
  }
  

  /* ------------------------------------------------------------ */
  /** This method is called when ffmpeg want to recognize a media's metadata
   * @param $media array() The entire media object using that Adapter
   * @return $filepath The filepath where ffmpeg will find this media
   */
  function filePathMetadata($media) {
    // we use KMOTION_ADAPTER_SALT constant to know the path 
    $hash = substr( md5( $media["remoteid"].KMOTION_ADAPTER_SALT ),0,2); 
    return STORAGE_PATH."/files/video/$hash/".$media["remoteid"]."/original";
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
    // we use KMOTION_ADAPTER_SALT constant to know the path 
    $hash = substr( md5( $media["remoteid"].KMOTION_ADAPTER_SALT ),0,2); 
    return array(
		 STORAGE_PATH."/files/video/$hash/".$media["remoteid"]."/original",
		 STORAGE_PATH."/files/video/$hash/".$media["remoteid"]."/".$settings["id"].".".$settings["extension"]
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
    $hash = substr( md5( $media["remoteid"].KMOTION_ADAPTER_SALT ),0,2); 
    return STORAGE_PATH."/files/video/$hash/".$media["remoteid"]."/".$settings["id"]."-".$prefix.$suffix;
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
    return true;
  }

} // class KMotionAdapter

?>