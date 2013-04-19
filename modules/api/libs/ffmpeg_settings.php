<?php 
/*
 * These are the command lines to launch for each standard settings of OpenMediaKit
 * See http://www.open-mediakit.org/ for more information
 * the following tags are replaced at runtime : 
 * %%SOURCE%% is the escapeshellarg version of the source file
 * %%DESTINATION%% is the escapeshellarg version of the destination file
 * %%SIZE%% will be the "<width>x<height>" of the video, 
 * taking "size_43" key if we have a 4/3 source,
 * or "size_169" key if we have a 16/9 source.
 */

global $settings;
$settings=array(
		"1" => array(
			     "slug" => "video_240p_flv",
			     "size_43" => "320x240",
			     "size_169" => "426x240",
			     "command_1" => "ffmpeg -i %%SOURCE%% -acodec mp3 -vcodec flv -ar 44100 -ab 64k -b 280k -s %%SIZE%% -r 25 %%DESTINATION%%.flv",
			     "output" => "%%DESTINATION%%.flv",
			     ),

		"2" => array(
			     "slug" => "video_360p_flv",
			     "size_43" => "480x360",
			     "size_169" => "640x360",
			     "command_1" => "ffmpeg -i %%SOURCE%% -acodec mp3 -vcodec flv -ar 44100 -ab 128k -b 440k -s %%SIZE%% -r 25 %%DESTINATION%%.flv",
			     "output" => "%%DESTINATION%%.flv",
			     ),

		"3" => array(
			     "slug" => "video_480p_flv",
			     "size_43" => "640x480",
			     "size_169" => "854x480",
			     "command_1" => "ffmpeg -i %%SOURCE%% -acodec mp3 -vcodec flv -ar 44100 -ab 128k -b 440k -s %%SIZE%% -r 25 %%DESTINATION%%.flv",
			     "output" => "%%DESTINATION%%.flv",
			     ),

		"11" => array(
			      "slug" => "video_240p_mp4",
			      "size_43" => "320x240",
			      "size_169" => "426x240",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libfaac -vcodec libx264 -ar 44100 -ab 64k -b 280k -s %%SIZE%% -r 25 %%DESTINATION%%.mp4",
			      "output" => "%%DESTINATION%%.mp4",
			      ),

		"12" => array(
			      "slug" => "video_360p_mp4",
			      "size_43" => "480x360",
			      "size_169" => "640x360",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libfaac -vcodec libx264 -ar 44100 -ab 128k -b 440k -s %%SIZE%% -r 25 %%DESTINATION%%.mp4",
			      "output" => "%%DESTINATION%%.mp4",
			      ),

		"13" => array(
			      "slug" => "video_480p_mp4",
			      "size_43" => "640x480",
			      "size_169" => "854x480",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libfaac -vcodec libx264 -ar 44100 -ab 128k -b 440k -s %%SIZE%% -r 30 %%DESTINATION%%.mp4",
			      "output" => "%%DESTINATION%%.mp4",
			      ),
   
		"14" => array(
			      "slug" => "video_720p_mp4",
			      "size_43" => "960x720",
			      "size_169" => "1280x720",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libfaac -vcodec libx264 -ar 44100 -ab 192k -b 1650k -s %%SIZE%% -r 30 %%DESTINATION%%.mp4",
			      "output" => "%%DESTINATION%%.mp4",
			      ),
   
		"15" => array(
			      "slug" => "video_1080p_mp4",
			      "size_43" => "1440x1080",
			      "size_169" => "1920x1080",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libfaac -vcodec libx264 -ar 44100 -ab 192k -b 1650k -s %%SIZE%% -r 30 %%DESTINATION%%.mp4",
			      "output" => "%%DESTINATION%%.mp4",
			      ),
   
		"21" => array(
			      "slug" => "video_240p_webm",
			      "size_43" => "320x240",
			      "size_169" => "426x240",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -ab 64k -b 280k -s %%SIZE%% -r 25 %%DESTINATION%%.webm",
			      "output" => "%%DESTINATION%%.webm",
			      
			      ),
		
		"22" => array(
			      "slug" => "video_360p_webm",
			      "size_43" => "480x360",
			      "size_169" => "640x360",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -ab 128k -b 440k -s %%SIZE%% -r 25 %%DESTINATION%%.mp4",
			      "output" => "%%DESTINATION%%.webm",
			      ),
		
		"23" => array(
			      "slug" => "video_480p_webm",
			      "size_43" => "640x480",
			      "size_169" => "854x480",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -ab 128k -b 440k -s %%SIZE%% -r 30 %%DESTINATION%%.webm",
			      "output" => "%%DESTINATION%%.webm",
			      ),
		"24" => array(
			      "slug" => "video_720p_webm",
			      "size_43" => "960x720",
			      "size_169" => "1280x720",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -ab 192k -b 1650k -s %%SIZE%% -r 30 %%DESTINATION%%.webm",
			      "output" => "%%DESTINATION%%.webm",
			      ),
		
		"25" => array(
			      "slug" => "video_1080p_webm",
			      "size_43" => "1440x1080",
			      "size_169" => "1920x1080",
			      "command_1" => "ffmpeg -i %%SOURCE%% -acodec libvorbis -vcodec libvpx -ar 44100 -ab 192k -b 1650k -s %%SIZE%% -r 30 %%DESTINATION%%.webm",
			      "output" => "%%DESTINATION%%.webm",
			      ),
		
		"31" => array(
			      "slug" => "video_240p_mpeg",
			      ),

		"32" => array(
			      "slug" => "video_360p_mpeg",
			      ),

		"33" => array(
			      "slug" => "video_480p_mpeg",
			      ),

		"34" => array(
			      "slug" => "video_720p_mpeg",
			      ),

		"35" => array(
			      "slug" => "video_1080p_mpeg",
			      ),

		"51" => array(
			      "slug" => "audio_64k_mp3",
			      ),

		"52" => array(
			      "slug" => "audio_128k_mp3",
			      ),

		"53" => array(
			      "slug" => "audio_v0_mp3",
			      ),

		"61" => array(
			      "slug" => "audio_64k_vorbis",
			      ),

		"62" => array(
			      "slug" => "audio_128k_vorbis",
			      ),

		"63" => array(
			      "slug" => "audio_v0_vorbis",
			      ),

		"71" => array(
			      "slug" => "audio_64k_aac",
			      ),

		"72" => array(
			      "slug" => "audio_128k_aac",
			      ),

		"73" => array(
			      "slug" => "audio_192k_aac",
			      ),

		"101" => array(
			       "slug" => "100_240p_thumbs_jpg",
			       ),

		"102" => array(
			       "slug" => "100_360p_thumbs_jpg",
			       ),

		"103" => array(
			       "slug" => "100_480p_thumbs_jpg",
			       ),

		"104" => array(
			       "slug" => "100_720p_thumbs_jpg",
			       ),

		"105" => array(
			       "slug" => "100_1080p_thumbs_jpg",
			       ),

		"110" => array(
			       "slug" => "100_small_thumbs_jpg",
			       ),

		"111" => array(
			       "slug" => "100_medium_thumbs_jpg",
			       ),

		);
