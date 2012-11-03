<?php

/** ************************************************************
 * List of recognized standard formats for OpenMediaKit Transcoder 
 * This list is maintained by the OMK team at http://www.open-mediakit.org/
 * 
 * a format is well-defined: audio codec, video codec, box, bitrate, frame rate, 
 * audio sampling rate, pixel size, pixel ratio, device ratio, and any other parameter
 * required for the specified codec. 
 * 
 * please contact team@open-mediakit.org if you want to register a new format.
 * You will need to give it a simple name (for the constant) and a very precise
 * description. a use-case description too will be nice, but not mandatory.
 */

/** ************************************************************ 
  box: mp4
  video: h264 at 400Kbps VBR 480x384 for 4/3 video and 480x270 for 16/9 video 12fps progressive
  audio: aac at 96Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_SMALL",1); 

/** ************************************************************ 
  box: mp4
  video: h264 at 800Kbps VBR 768x576 for 4/3 video and 768x432 for 16/9 video 25fps progressive
  audio: aac at 128Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_MEDIUM",2); 

/** ************************************************************ 
  box: mp4
  video: h264 at 2000Kbps 1280x720 on 16/9 video (4/3 will have black bands) 50fps progressive
  audio: aac at 128Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_HD720",3); 

/** ************************************************************ 
  box: mp4
  video: h264 at 2500Kbps 1920x1080 on 16/9 video (4/3 will have black bands) 50fps progressive
  audio: aac at 192Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_HD1080",4); 

/** ************************************************************ 
  box: mp4
  video: h264 at 400Kbps VBR 480x384 for 4/3 video and 480x270 for 16/9 video 12fps progressive
  audio: aac at 96Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_SMALL",1); 

/** ************************************************************ 
  box: mp4
  video: h264 at 800Kbps VBR 768x576 for 4/3 video and 768x432 for 16/9 video 25fps progressive
  audio: aac at 128Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_MEDIUM",2); 

/** ************************************************************ 
  box: mp4
  video: h264 at 2000Kbps 1280x720 on 16/9 video (4/3 will have black bands) 50fps progressive
  audio: aac at 128Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_HD720",3); 

/** ************************************************************ 
  box: mp4
  video: h264 at 2500Kbps 1920x1080 on 16/9 video (4/3 will have black bands) 50fps progressive
  audio: aac at 192Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_HD1080",4); 

/** ************************************************************ 
  box: mp4
  video: h264 at 400Kbps VBR 480x384 for 4/3 video and 480x270 for 16/9 video 12fps progressive
  audio: aac at 96Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_SMALL",1); 

/** ************************************************************ 
  box: mp4
  video: h264 at 800Kbps VBR 768x576 for 4/3 video and 768x432 for 16/9 video 25fps progressive
  audio: aac at 128Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_MEDIUM",2); 

/** ************************************************************ 
  box: mp4
  video: h264 at 2000Kbps 1280x720 on 16/9 video (4/3 will have black bands) 50fps progressive
  audio: aac at 128Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_HD720",3); 

/** ************************************************************ 
  box: mp4
  video: h264 at 2500Kbps 1920x1080 on 16/9 video (4/3 will have black bands) 50fps progressive
  audio: aac at 192Kbps 44100 Hz 2 channels 
*/
define("OMK_FORMAT_MP4_HD1080",4); 





/** ************************************************************ 
  special: 20 JPG files at 80% quality divided equally in the video duration
  the image size is 320x240 on 4/3 video, 320x180 on 16/9 video
  the image may be resized using bicubic resampling to fit that precise size.
*/
define("OMK_FORMAT_JPG_20_SMALL",5);

/** ************************************************************ 
  special: 20 JPG files at 80% quality divided equally in the video duration
  the image size is 800x600 on 4/3 video, 800x450 on 16/9 video
  the image may be resized using bicubic resampling to fit that precise size.
*/
define("OMK_FORMAT_JPG_20_MEDIUM",5);

/** ************************************************************ 
  special: 20 JPG files at 80% quality divided equally in the video duration
  the image size is 1920x1440 on 4/3 video, 1920x1080 on 16/9 video
  the image may be resized using bicubic resampling to fit that precise size.
*/
define("OMK_FORMAT_JPG_20_LARGE",5);
