<?php

class Ffmpeg {

  /** ****************************************
   * Get the metadata of a media using ffmpeg
   * @param $filename string the filename to parse
   * @param $cropdetect boolean Shall we cropdetect the video file ? (that will be a lot slower!)
   * @return array a complex array (see the doc)
   * of associative array with the metadata of each track.
   */
  public function getFfmpegMetadata($file,$cropdetect=false) {

    // This code is for the SQUEEZE version of deb-multimedia ffmpeg version
    $DEBUG=0;

    $attribs=array();
    $hasvideo=false;
    $hasaudio=false;
    $hassubtitle=false;

    // first, let's get the file mime type : 
    exec("file -b --mime-type ".escapeshellarg($file),$out);
    if (!empty($out[0])) {
      $attribs["mime"]=trim($out[0]);
    }
    // If we do a "stream copy" for the video track, we can't do cropdetect ... 
    if (!$cropdetect) {
      $exec="ffmpeg -i ".escapeshellarg($file)." -vcodec copy -acodec copy -vf cropdetect -f rawvideo -y /dev/null 2>&1";
    } else {
      $exec="ffmpeg -i ".escapeshellarg($file)." -vcodec rawvideo -acodec copy -vf cropdetect -f rawvideo -y /dev/null 2>&1";
    }
    if ($DEBUG) echo "exec:$exec\n";
    exec($exec,$out);
    // now we parse the lines of stdout to know the tracks

    $tracks=array(); // no track to start with
    $duration=DURATION_UNDEFINED; // undefined duration to start with
    // Each time we start a new track, we start a $track array 
    /**
     * we have 3 zones in ffmpeg output : 
     * input
     * output 
     * frame/video parsing: 
Seems stream 0 codec frame rate differs from container frame rate: 2000.00 (2000/1) -> 25.00 (25/1)
Input #0, flv, from 'le-vinvinteur--2012-10-14-20h00.flv':
  Duration: 00:25:48.53, start: 0.000000, bitrate: 1233 kb/s
    Stream #0.0: Video: h264 (Main), yuv420p, 640x360, 1137 kb/s, 25 tbr, 1k tbn, 2k tbc
    Stream #0.1: Audio: aac, 44100 Hz, stereo, s16, 96 kb/s
Output #0, rawvideo, to '/dev/null':
  Metadata:
    encoder         : Lavf53.21.0
    Stream #0.0: Video: libx264, yuv420p, 640x360, q=2-31, 1137 kb/s, 90k tbn, 1k tbc
    Stream #0.1: Audio: libvo_aacenc, 44100 Hz, stereo, 96 kb/s
    Stream #0.2: Subtitle: srt
Stream mapping:
  Stream #0.0 -> #0.0
  Stream #0.1 -> #0.1
Press ctrl-c to stop encoding
frame=38712 fps=  0 q=-1.0 Lsize=       0kB time=1548.44 bitrate=   0.0kbits/s    
video:209891kB audio:17731kB global headers:0kB muxing overhead -100.000000%

And also the crop black borders: 
[cropdetect @ 0x8214800] x1:0 x2:1023 y1:0 y2:575 w:1024 h:576 x:0 y:0 pos:0 pts:13947267 t:13.947267 crop=1024:576:0:0
when using -vf cropdetect
    */     
    $track=array(); // per-track attributes
    $mode=1;

    foreach($out as $line) {
      if ($mode==1) {
	if ($DEBUG) echo "mode1: $line\n";
	$line=trim($line);
	if (preg_match("|^Output |",$line,$mat)) {
	  $mode=2; // second part = output & cropdetect
	}
	if (preg_match("|^Input #0, ([^,]*)|",$line,$mat)) {
	  $attribs["box"]=$mat[1];
	}
	if (preg_match("|^Duration: ([^,]*).*bitrate: ([0-9]*) |",$line,$mat)) {
	  $attribs["time-estimate"]=$mat[1];
	  $attribs["bitrate"]=$mat[2];
	}
	if (preg_match("|^Stream ([^:]*): ([^:]*): (.*)$|",$line,$mat)) {
	  $track=array();
	  // get the comma-separated parameters of the track
	  $tmp=explode(",",$mat[3]);
	  $params=array();
	  foreach($tmp as $t) {
	    $params[]=trim($t);
	  }
	  
	  $lang=$mat[1];
	  // search for language code, skip "und" for undefined.
	  if (preg_match("#\(([^\)]*)#",$lang,$lmat) && $lmat[1]!="und") { 
	    $track["lang"]=$lmat[1];
	  }
	  switch ($mat[2]) {
	  case "Audio":
	    $hasaudio=true;
	    $track["type"]=TRACK_TYPE_AUDIO;
	    // Parsing an audio-type track
	    $codec=explode(" ",$params[0]);
	    $track["codec"]=$codec[0];
	    unset ($codec[0]);
	    $track["codec-sub"]=implode(" ",$codec);
	    foreach($params as $p) {
	      // Search for kb/s and Hz
	      if (preg_match("#([0-9\.]*) Hz#",$p,$mat)) {
		$track["samplerate"]=$mat[1];
	      }
	      if (preg_match("#([0-9\.]*) kb/s#",$p,$mat)) {
		$track["bitrate"]=$mat[1];
	      }
	      if (trim($p)=="stereo") 
		$track["channels"]=2;
	      if (trim($p)=="mono") 
		$track["channels"]=1;
	      // TODO: find a 5.1 or other high-end audio file, and see what ffmpeg is telling about it :)
	    }
	    break;
	  case "Video":
	    $hasvideo=true;
	    $track["type"]=TRACK_TYPE_VIDEO;
	    // Parsing a video-type track
	    $codec=explode(" ",$params[0]);
	    $track["codec"]=$codec[0];
	    unset ($codec[0]);
	    $track["codec-sub"]=implode(" ",$codec);
	    $track["pixelfmt"]=$params[1];
	    if (preg_match("#([(0-9]*)x([0-9]*)#",$params[2],$mat)) {
	      $track["width"]=$mat[1];
	      $track["height"]=$mat[2];
	    }	
	    if (preg_match("#DAR ([(0-9]*):([0-9]*)#",$params[2],$mat)) {
	      $track["DAR1"]=$mat[1];
	      $track["DAR2"]=$mat[2];
	    }
	    if (preg_match("#PAR ([(0-9]*):([0-9]*)#",$params[2],$mat)) {
	      $track["PAR1"]=$mat[1];
	      $track["PAR2"]=$mat[2];
	    }
	    foreach($params as $p) {
	      // Search for fps, tbr and kb/s
	      if (preg_match("#([0-9\.]*) kb/s#",$p,$mat)) {
		$track["bitrate"]=$mat[1];
	      }
	      if (preg_match("#([0-9\.]*) fps#",$p,$mat)) {
		$track["fps"]=$mat[1];
	      }
	      if (preg_match("#([0-9\.]*) tbr#",$p,$mat) && !isset($track["fps"])) {
		$track["fps"]=$mat[1];
	      }
	    }
	    break;
	  case "Subtitle":
	    $hassubtitle=true;
	    $track["type"]=TRACK_TYPE_SUBTITLE;
	    // Parsing a subtitle track
	    $codec=explode(" ",$params[0]);
	    $track["codec"]=$codec[0];
	    unset ($codec[0]);
	    $track["codec-sub"]=implode(" ",$codec);
	    // TODO: find a .ass (or .mkv with .ass) subtitle and see what ffmpeg is telling about it :) 
	    break;
	  default:
	    $track["type"]=TRACK_TYPE_OTHER; // TODO: tell us we found one :) It'd be clearly interesting!
	    break;
	  }
	  $tracks[]=$track;
	} // new track

      }  // mode 1

      // parsing that line : 
      // frame=13130 fps=12900 q=-1.0 Lsize=       0kB time=438.10 bitrate=   0.0kbits/s
      if ($mode==2) {
	if ($DEBUG) echo "mode2: $line\n";
	if (preg_match("#frame= *([0-9]*).*time= *([0-9\.]*)#",$line)) {
	  // well, avconv is giving ALL the frame= time= lines into ONE line with ^M to show it the nice way ... let's change that...
	  $out2=explode(chr(13),$line);
	  foreach($out2 as $line) {
	    if (preg_match("#frame= *([0-9]*).*time= *([0-9\.]*)#",$line,$mat)) {
	      $attribs["frames"]=$mat[1]; 
	      $attribs["time"]=$mat[2];
	    }	    
	    if ($cropdetect && preg_match("#crop=([0-9]*):([0-9]*):([0-9]*):([0-9]*)#",$line,$mat)) {
	      $attribs["cropw"]=$mat[1]; 
	      $attribs["croph"]=$mat[2];
	      $attribs["cropx"]=$mat[3]; 
	      $attribs["cropy"]=$mat[4];
	    }
	  }
	} // search frame/time 

      }  // mode 2

    } // parse lines, 

    $attribs["tracks"]=$tracks;

    if ($hasvideo) {
      $attribs["type"]=TRACK_TYPE_VIDEO;
    } else if ($hasaudio && !$hasvideo) {
      $attribs["type"]=TRACK_TYPE_AUDIO;
    } else if ($hassubtitle) {
      $attribs["type"]=TRACK_TYPE_SUBTITLE;
    } else {
      $attribs["type"]=TRACK_TYPE_OTHER;
    }
    return $attribs;
  }


} /* class Ffmpeg */

