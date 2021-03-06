<?php

require_once(MODULES."/api/libs/constants.php");

// This class may define constants. In that case, prefix them by the adapter name
define("DUMMY_ADAPTER_PATH","/tmp/dummy");


class DummyAdapter {

  
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
    if (substr($url,0,8)=="dummy://") {
      // you could check it a more thoroughly though
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
    return DUMMY_ADAPTER_PATH."/".$media["id"];
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
    return array(
		 DUMMY_ADAPTER_PATH."/".$media["id"],
		 DUMMY_ADAPTER_PATH."/transcoded/".$media["id"]."-".$settings["id"]
		 );
  }


  


} // class DummyAdapter
