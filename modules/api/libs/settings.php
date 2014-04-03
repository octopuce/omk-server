<?php 
/*
 * These are the standard settings of OpenMediaKit
 * See http://www.open-mediakit.org/ for more information
 * WARNING: this file is automatically generated from ../utilities/update_settings.php !
 */

global $settings;
$settings=array(
     "1" => array(
          "id" => "1",
          "type" => "video",
          "slug" => "video_240p_flv",
          "name" => "Video at very low definition and flash sorenson format",
          "technical" => "flv container, sorenson video at 280kb/s, mp3 audio at 64kb/s and 22KHz, 426x240 pixels on 16:9, 25fps",
          "extension" => "flv",
          "size_43" => "320x240",
          "size_169" => "426x240",
          "cardinality" => "1",
          "command1" => "avconv -i %%SOURCE%% -acodec libmp3lame -vcodec flv -ar 44100 -ab 64k -pix_fmt yuv420p -b 280k -s %%SIZE%% %%DESTINATION%%.flv",
          "cancelcommand" => "rm %%DESTINATION%%.flv",
        ),

     "2" => array(
          "id" => "2",
          "type" => "video",
          "slug" => "video_360p_flv",
          "name" => "Video at low definition and flash sorenson format",
          "technical" => "flv container, sorenson video at 440kb/s, mp3 audio at 128kb/s and 44KHz, 640x360 pixels on 16:9, 25fps",
          "extension" => "flv",
          "size_43" => "480x360",
          "size_169" => "640x360",
          "cardinality" => "1",
          "command1" => "avconv -i %%SOURCE%% -acodec libmp3lame -vcodec flv -ar 44100 -ab 128k -pix_fmt yuv420p -b 440k -s %%SIZE%% %%DESTINATION%%.flv",
          "cancelcommand" => "rm %%DESTINATION%%.flv",
        ),

     "3" => array(
          "id" => "3",
          "type" => "video",
          "slug" => "video_480p_flv",
          "name" => "Video at low definition and flash sorenson format",
          "technical" => "flv container, sorenson video at 440kb/s, mp3 audio at 128kb/s and 44KHz, 854x480 pixels on 16:9, 25fps",
          "extension" => "flv",
          "size_43" => "640x480",
          "size_169" => "854x480",
          "cardinality" => "1",
          "command1" => "avconv -i %%SOURCE%% -acodec libmp3lame -vcodec flv -ar 44100 -ab 128k -pix_fmt yuv420p -pix_fmt yuv420p -b 440k -s %%SIZE%% %%DESTINATION%%.flv",
          "cancelcommand" => "rm %%DESTINATION%%.flv",
        ),

     "11" => array(
          "id" => "11",
          "type" => "video",
          "slug" => "video_240p_mp4",
          "name" => "Video at very low definition and portable mp4 format",
          "technical" => "mp4 container, double-pass h264 video baseline coding, between 512 and 800kbps, aac audio at 96kbps and 44KHz, 426x240 pixels on 16:9, 25fps",
          "extension" => "mp4",
          "recommend" => "1",
          "size_43" => "320x240",
          "size_169" => "426x240",
          "cardinality" => "1",
          "command1" => "avconv -y -v info -i %%SOURCE%% -pass 1 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 512k -maxrate:v 800k -bufsize 10M -profile:v baseline -strict experimental -f rawvideo -acodec copy /dev/null",
          "command2" => "avconv -y -v info -i %%SOURCE%% -pass 2 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 512k -maxrate:v 800k -bufsize 10M -profile:v baseline -strict experimental -f mp4 -codec:a aac -ar 44100 -ac 2 -b:a 96k %%DESTINATION%%.tmp.mp4",
	  "command3" => "qt-faststart %%DESTINATION%%.tmp.mp4 %%DESTINATION%%.mp4",
	  "command4" => "rm -f %%DESTINATION%%.tmp.mp4",
          "cancelcommand" => "rm %%DESTINATION%%.mp4",
        ),

     "12" => array(
          "id" => "12",
          "type" => "video",
          "slug" => "video_360p_mp4",
          "name" => "Video at low definition and portable mp4 format",
          "technical" => "mp4 container, double-pass h264 video main coding, between 800Kbps and 2Mbps, aac audio at 96kbps and 44KHz, 640x360 pixels on 16:9, 25fps",
          "extension" => "mp4",
          "size_43" => "480x360",
          "size_169" => "640x360",
          "cardinality" => "1",
          "command1" => "avconv -y -v info -i %%SOURCE%% -pass 1 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 800k -maxrate:v 2M -bufsize 20M -profile:v main -strict experimental -f rawvideo -acodec copy /dev/null",
          "command2" => "avconv -y -v info -i %%SOURCE%% -pass 2 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 800k -maxrate:v 2M -bufsize 20M -profile:v main -strict experimental -f mp4 -codec:a aac -ar 44100 -ac 2 -b:a 128k %%DESTINATION%%.tmp.mp4",
	  "command3" => "qt-faststart %%DESTINATION%%.tmp.mp4 %%DESTINATION%%.mp4",
	  "command4" => "rm -f %%DESTINATION%%.tmp.mp4",
          "cancelcommand" => "rm %%DESTINATION%%.mp4",
        ),

     "13" => array(
          "id" => "13",
          "type" => "video",
          "slug" => "video_480p_mp4",
          "name" => "Video at standard definition and portable mp4 format",
          "technical" => "mp4 container, double-pass h264 video main coding, between 2 and 4Mbps, aac audio at 128kbps and 44KHz, 854x480 pixels on 16:9, up to 30fps",
          "extension" => "mp4",
          "size_43" => "640x480",
          "size_169" => "854x480",
          "cardinality" => "1",
          "command1" => "avconv -y -v info -i %%SOURCE%% -pass 1 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 2M -maxrate:v 4M -bufsize 30M -profile:v main -strict experimental -f rawvideo -acodec copy /dev/null",
          "command2" => "avconv -y -v info -i %%SOURCE%% -pass 2 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 2M -maxrate:v 4M -bufsize 30M -profile:v main -strict experimental -f mp4 -codec:a aac -ar 44100 -ac 2 -b:a 128k %%DESTINATION%%.tmp.mp4",
	  "command3" => "qt-faststart %%DESTINATION%%.tmp.mp4 %%DESTINATION%%.mp4",
	  "command4" => "rm -f %%DESTINATION%%.tmp.mp4",
          "cancelcommand" => "rm %%DESTINATION%%.mp4",
        ),

     "14" => array(
          "id" => "14",
          "type" => "video",
          "slug" => "video_720p_mp4",
          "name" => "Video at high definition and portable mp4 format",
          "technical" => "mp4 container, double-pass h264 video main coding, between 4 and 8Mbps, aac audio at 192kbps and 44KHz, 1280x720 pixels on 16:9, up to 30fps",
          "extension" => "mp4",
          "recommend" => "1",
          "size_43" => "960x720",
          "size_169" => "1280x720",
          "cardinality" => "1",
          "command1" => "avconv -y -v info -i %%SOURCE%% -pass 1 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 4M -maxrate:v 8M -bufsize 40M -profile:v main -strict experimental -f rawvideo -acodec copy /dev/null",
          "command2" => "avconv -y -v info -i %%SOURCE%% -pass 2 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 4M -maxrate:v 8M -bufsize 40M -profile:v main -strict experimental -f mp4 -codec:a aac -ar 44100 -ac 2 -b:a 192k %%DESTINATION%%.tmp.mp4",
	  "command3" => "qt-faststart %%DESTINATION%%.tmp.mp4 %%DESTINATION%%.mp4",
	  "command4" => "rm -f %%DESTINATION%%.tmp.mp4",
          "cancelcommand" => "rm %%DESTINATION%%.mp4",
        ),

     "15" => array(
          "id" => "15",
          "type" => "video",
          "slug" => "video_1080p_mp4",
          "name" => "Video at full-hd definition and portable mp4 format",
          "technical" => "mp4 container, double-pass h264 video main coding, between 8 and 12Mbps, aac audio at 192kbps and 44KHz, 1920x1080 pixels on 16:9, up to 30fps",
          "extension" => "mp4",
          "recommend" => "1",
          "size_43" => "1440x1080",
          "size_169" => "1920x1080",
          "cardinality" => "1",
          "command1" => "avconv -y -v info -i %%SOURCE%% -pass 1 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 8M -maxrate:v 12M -bufsize 80M -profile:v main -strict experimental -f rawvideo -acodec copy /dev/null",
          "command2" => "avconv -y -v info -i %%SOURCE%% -pass 2 -s %%SIZE%% -codec:v libx264 -pix_fmt:v yuv420p -b:v 8M -maxrate:v 12M -bufsize 80M -profile:v main -strict experimental -f mp4 -codec:a aac -ar 44100 -ac 2 -b:a 192k %%DESTINATION%%.tmp.mp4",
	  "command3" => "qt-faststart %%DESTINATION%%.tmp.mp4 %%DESTINATION%%.mp4",
	  "command4" => "rm -f %%DESTINATION%%.tmp.mp4",
          "cancelcommand" => "rm %%DESTINATION%%.mp4",
        ),

     "21" => array(
          "id" => "21",
          "type" => "video",
          "slug" => "video_240p_webm",
          "name" => "Video at very low definition and webm opensource format",
          "technical" => "webm container, vp8 video at 280kb/s, vorbis audio at 64kb/s and 22KHz, 426x240 pixels on 16:9, 25fps",
          "extension" => "webm",
          "size_43" => "320x240",
          "size_169" => "426x240",
          "cardinality" => "1",
          "command1" => "avconv -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -b:a 64k -pix_fmt:v yuv420p -b:v 280k -s %%SIZE%% %%DESTINATION%%.webm",
          "cancelcommand" => "rm %%DESTINATION%%.webm",
        ),

     "22" => array(
          "id" => "22",
          "type" => "video",
          "slug" => "video_360p_webm",
          "name" => "Video at low definition and webm opensource format",
          "technical" => "webm container, vp8 video at 440kb/s, vorbis audio at 128kb/s and 44KHz, 640x360 pixels on 16:9, 25fps",
          "extension" => "webm",
          "size_43" => "480x360",
          "size_169" => "640x360",
          "cardinality" => "1",
          "command1" => "avconv -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -b:a 128k -pix_fmt:v yuv420p -b:v 440k -s %%SIZE%% %%DESTINATION%%.mp4",
          "cancelcommand" => "rm %%DESTINATION%%.webm",
        ),

     "23" => array(
          "id" => "23",
          "type" => "video",
          "slug" => "video_480p_webm",
          "name" => "Video at standard definition webm opensource format",
          "technical" => "webm container, vp8 video at 680kb/s, vorbis audio at 128kb/s and 44KHz, 854x480 pixels on 16:9, 30fps",
          "extension" => "webm",
          "size_43" => "640x480",
          "size_169" => "854x480",
          "cardinality" => "1",
          "command1" => "avconv -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -b:a 128k -pix_fmt:v yuv420p -b:v 440k -s %%SIZE%% -r 30 %%DESTINATION%%.webm",
          "cancelcommand" => "rm %%DESTINATION%%.webm",
        ),

     "24" => array(
          "id" => "24",
          "type" => "video",
          "slug" => "video_720p_webm",
          "name" => "Video at high definition and webm opensource format",
          "technical" => "webm container, vp8 video at 1650kb/s, vorbis audio at 192kb/s and 44KHz, 1280x720 pixels on 16:9, 30fps",
          "extension" => "webm",
          "size_43" => "960x720",
          "size_169" => "1280x720",
          "cardinality" => "1",
          "command1" => "avconv -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -b:a 192k -pix_fmt:v yuv420p -b:v 1650k -s %%SIZE%% -r 30 %%DESTINATION%%.webm",
          "cancelcommand" => "rm %%DESTINATION%%.webm",
        ),

     "25" => array(
          "id" => "25",
          "type" => "video",
          "slug" => "video_1080p_webm",
          "name" => "Video at full-hd definition and webm opensource format",
          "technical" => "webm container, vp8 video at 4000kb/s, vorbis audio at 192kb/s and 44KHz, 1920x1080 pixels on 16:9, 30fps",
          "extension" => "webm",
          "size_43" => "1440x1080",
          "size_169" => "1920x1080",
          "cardinality" => "1",
          "command1" => "avconv -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -b:a 192k -pix_fmt:v yuv420p -b:v 1650k -s %%SIZE%% -r 30 %%DESTINATION%%.webm",
          "cancelcommand" => "rm %%DESTINATION%%.webm",
        ),

     "31" => array(
          "id" => "31",
          "type" => "video",
          "slug" => "video_240p_mpeg",
          "name" => "Video at very low definition and mpeg broadcast format",
          "technical" => "mpeg container, mpeg2 video at 280kb/s, mpeg2 audio at 64kb/s and 22KHz, 426x240 pixels on 16:9, 25fps",
          "extension" => "mpg",
          "size_43" => "320x240",
          "size_169" => "426x240",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.mpg",
        ),

     "32" => array(
          "id" => "32",
          "type" => "video",
          "slug" => "video_360p_mpeg",
          "name" => "Video at low definition and mpeg broadcast format",
          "technical" => "mpeg container, mpeg2 video at 440kb/s, mpeg2 audio at 128kb/s and 44KHz, 640x360 pixels on 16:9, 25fps",
          "extension" => "mpg",
          "size_43" => "480x360",
          "size_169" => "640x360",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.mpg",
        ),

     "33" => array(
          "id" => "33",
          "type" => "video",
          "slug" => "video_480p_mpeg",
          "name" => "Video at standard definition mpeg broadcast format",
          "technical" => "mpeg container, mpeg2 video at 680kb/s, mpeg2 audio at 128kb/s and 44KHz, 854x480 pixels on 16:9, 30fps",
          "extension" => "mpg",
          "size_43" => "640x480",
          "size_169" => "854x480",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.mpg",
        ),

     "34" => array(
          "id" => "34",
          "type" => "video",
          "slug" => "video_720p_mpeg",
          "name" => "Video at high definition and mpeg broadcast format",
          "technical" => "mpeg container, mpeg2 video at 1650kb/s, mpeg2 audio at 192kb/s and 44KHz, 1280x720 pixels on 16:9, 30fps",
          "extension" => "mpg",
          "size_43" => "960x720",
          "size_169" => "1280x720",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.mpg",
        ),

     "35" => array(
          "id" => "35",
          "type" => "video",
          "slug" => "video_1080p_mpeg",
          "name" => "Video at full-hd definition and mpeg broadcast format",
          "technical" => "mpeg container, mpeg2 video at 4000kb/s, mpeg2 audio at 192kb/s and 44KHz, 1920x1080 pixels on 16:9, 30fps",
          "extension" => "mpg",
          "size_43" => "1440x1080",
          "size_169" => "1920x1080",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.mpg",
        ),

     "51" => array(
          "id" => "51",
          "type" => "video",
          "slug" => "audio_64k_mp3",
          "name" => "Audio track only, with low quality",
          "technical" => "mp3 container, mp3 audio track at 64Kb/s and 22KHz, stereo",
          "extension" => "mp3",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.mp3",
        ),

     "52" => array(
          "id" => "52",
          "type" => "video",
          "slug" => "audio_128k_mp3",
          "name" => "Audio track only, with standard quality",
          "technical" => "mp3 container, mp3 audio track at 128Kb/s and 44KHz, stereo",
          "extension" => "mp3",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.mp3",
        ),

     "53" => array(
          "id" => "53",
          "type" => "video",
          "slug" => "audio_v0_mp3",
          "name" => "Audio track only, with highest mp3 quality",
          "technical" => "mp3 container, mp3 audio track at V0 quality and 44KHz, stereo",
          "extension" => "mp3",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.mp3",
        ),

     "61" => array(
          "id" => "61",
          "type" => "video",
          "slug" => "audio_64k_vorbis",
          "name" => "Audio track only, with low quality and opensource vorbis codec",
          "technical" => "ogg container, vorbis audio track at 64Kb/s and 22KHz, stereo",
          "extension" => "ogg",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.ogg",
        ),

     "62" => array(
          "id" => "62",
          "type" => "video",
          "slug" => "audio_128k_vorbis",
          "name" => "Audio track only, with standard quality and opensource vorbis codec",
          "technical" => "ogg container, vorbis audio track at 128Kb/s and 44KHz, stereo",
          "extension" => "ogg",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.ogg",
        ),

     "63" => array(
          "id" => "63",
          "type" => "video",
          "slug" => "audio_v0_vorbis",
          "name" => "Audio track only, with highest quality and opensource vorbis codec",
          "technical" => "ogg container, vorbis audio track at V0 quality and 44KHz, stereo",
          "extension" => "ogg",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.ogg",
        ),

     "71" => array(
          "id" => "71",
          "type" => "video",
          "slug" => "audio_64k_aac",
          "name" => "Audio track only, with low quality and aac codec",
          "technical" => "mp4 container, aac audio track at 64kb/s quality and 22KHz, stereo",
          "extension" => "m4a",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.m4a",
        ),

     "72" => array(
          "id" => "72",
          "type" => "video",
          "slug" => "audio_128k_aac",
          "name" => "Audio track only, with standard quality and aac codec",
          "technical" => "mp4 container, aac audio track at 128kb/s quality and 44KHz, stereo",
          "extension" => "m4a",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.m4a",
        ),

     "73" => array(
          "id" => "73",
          "type" => "video",
          "slug" => "audio_192k_aac",
          "name" => "Audio track only, with highest quality and aac codec",
          "technical" => "mp4 container, aac audio track at 192kb/s quality and 44KHz, stereo",
          "extension" => "m4a",
          "size_43" => "na",
          "size_169" => "na",
          "cardinality" => "1",
          "cancelcommand" => "rm %%DESTINATION%%.m4a",
        ),

     "101" => array(
          "id" => "101",
          "type" => "video",
          "slug" => "20_original_and_small_thumbs_jpg",
          "name" => "20 thumbnails at original size and small size in JPEG",
          "technical" => "Up to 20 JPEG images at 60% quality, with at least 1 minute between each, at original WxH and at 100x100px",
          "extension" => "jpg",
          "recommend" => "1",
          "size_43" => "1440x1080",
          "size_169" => "1920x1080",
          "cardinality" => "40",
          "command1" => "scripts-thumbnails1",
        ),


 );
