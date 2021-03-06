<?php

class Ffmpeg {

  /** ****************************************
   * Get the metadata of a media using ffmpeg
   * @param $filename string the filename to parse
   * @param $cropdetect boolean Shall we cropdetect the video file ? (that will be a lot slower!)
   * @return array a complex array (see the doc)
   * of associative array with the metadata of each track.
   */
  public $DEBUG=0;
    
  public function getFfmpegMetadata($file,$cropdetect=false) {
    global $api;
    // This code is for the WHEEZY version of deb-multimedia ffmpeg version

    $attribs=array();
    $hasvideo=false;
    $hasaudio=false;
    $hassubtitle=false;

    // first, let's get the file mime type : 
    exec("file -b --mime-type ".escapeshellarg($file),$out);
    if (!empty($out[0])) {
      $attribs["mime"]=trim($out[0]);
    }
    $attribs["file_size"]=filesize($file);

    // If we do a "stream copy" for the video track, we can't do cropdetect ... 
    if (!$cropdetect) {
      $exec="ffmpeg -i ".escapeshellarg($file)." -vcodec copy -acodec copy -vf cropdetect -f rawvideo -y /dev/null 2>&1";
    } else {
      $exec="ffmpeg -i ".escapeshellarg($file)." -vcodec rawvideo -acodec copy -vf cropdetect -f rawvideo -y /dev/null 2>&1";
    }
    $api->log(LOG_DEBUG,"[ffmpeg::metadata] getting metadata, launching $exec");
    exec($exec,$out,$ret);
    // now we parse the lines of stdout to know the tracks
      
    $tracks=array(); // no track to start with
    $duration=DURATION_UNDEFINED; // undefined duration to start with
    // Each time we start a new track, we start a $track array 
    /**
     * we have 3 zones in ffmpeg output : 
     * input
     * output 
     * frame/video parsing: 
Input #0, mov,mp4,m4a,3gp,3g2,mj2, from './original/44':
  Metadata:
    major_brand     : isom
    minor_version   : 512
    compatible_brands: isomiso2avc1mp41
    encoder         : Lavf53.21.1
  Duration: 00:00:30.75, start: 0.000000, bitrate: 3081 kb/s
    Stream #0:0(und): Video: h264 (Main) (avc1 / 0x31637661), yuv420p, 720x576 [SAR 16:15 DAR 4:3], 3002 kb/s, 12 fps, 12 tbr, 12 tbn, 24 tbc
    Metadata:
      handler_name    : VideoHandler
    Stream #0:1(und): Audio: aac (mp4a / 0x6134706D), 48000 Hz, stereo, s16, 74 kb/s
    Metadata:
      handler_name    : SoundHandler
Output #0, rawvideo, to '/dev/null':
  Metadata:
    major_brand     : isom
    minor_version   : 512
    compatible_brands: isomiso2avc1mp41
    encoder         : Lavf54.29.104
    Stream #0:0(und): Video: h264 (avc1 / 0x31637661), yuv420p, 720x576 [SAR 16:15 DAR 4:3], q=2-31, 3002 kb/s, 12 fps, 90k tbn, 12 tbc
    Metadata:
      handler_name    : VideoHandler
Stream mapping:
  Stream #0:0 -> #0:0 (copy)
Press [q] to stop, [?] for help
frame=  369 fps=0.0 q=-1.0 Lsize=       0kB time=00:00:30.66 bitrate=   0.0kbits/s    
video:11271kB audio:0kB subtitle:0 global headers:0kB muxing overhead -100.000000%

And also the crop black borders: 
[cropdetect @ 0x8214800] x1:0 x2:1023 y1:0 y2:575 w:1024 h:576 x:0 y:0 pos:0 pts:13947267 t:13.947267 crop=1024:576:0:0
when using -vf cropdetect
    */     
    $track=array(); // per-track attributes
    $mode=1;

    foreach($out as $line) {
      if ($mode==1) {
	if ($this->DEBUG) echo "mode1: $line\n";
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
	if (preg_match("|^Stream #[0-9]*:[0-9]([^:]*): ([^:]*): (.*)$|",$line,$mat)) {
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
	    if (preg_match("#SAR ([(0-9]*):([0-9]*)#",$params[2],$mat)) {
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
	if ($this->DEBUG) echo "mode2: $line\n";
	if (preg_match("#frame= *([0-9]*).*time= *([0-9\.]*)#",$line)) {
	  // well, avconv is giving ALL the frame= time= lines into ONE line with ^M to show it the nice way ... let's change that...
	  $out2=explode(chr(13),$line);
	  foreach($out2 as $line) {
	    if (preg_match("#frame= *([0-9]*).*time= *([0-9]*):([0-9]*):([0-9]*)\.([0-9]*)#",$line,$mat)) {
	      $attribs["frames"]=$mat[1]; 
	      $attribs["time"]=intval($mat[2])*3600+intval($mat[3])*60+intval($mat[4])+doubleval($mat[5])/100;
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
    if (isset($attribs["mime"]) && isset($attribs["box"])) {
      if ($attribs["mime"]=="application/octet-stream") {
	// strange case : we need to find if it's a mp4, avi etc.
	if ($attribs["box"]=="mov") {
	  if ($hasvideo) {
	    $attribs["mime"]="video/mp4";
	  }
	  if ($hasaudio && !$hasvideo) {
	    $attribs["mime"]="audio/mp4";
	  }
	}
	if ($attribs["box"]=="flv") {
	  $attribs["mime"]="video/x-flv";
	}
	if ($attribs["box"]=="mp3") {
	  $attribs["mime"]="audio/mpeg";
	}
	if ($attribs["box"]=="mpegts") {
	  $attribs["mime"]="video/mp2t";
	}
	if ($attribs["box"]=="matroska") {
	  if ($hasvideo) {
	    $attribs["mime"]="video/x-matroska";
	  }
	  if ($hasaudio && !$hasvideo) {
	    $attribs["mime"]="audio/x-matroska";
	  }
	}
	if ($attribs["box"]=="mpeg") {
	  if ($hasvideo) {
	    $attribs["mime"]="video/mpeg";
	  }
	  if ($hasaudio && !$hasvideo) {
	    $attribs["mime"]="audio/mpeg";
	  }
	}
	if ($attribs["box"]=="dv") {
	  if ($hasvideo) {
	    $attribs["mime"]="video/x-dv";
	  }
	}
	
      } // /strange mime
    }
    return $attribs;
  } /* getFfmpegMetadata */


  public function transcode($media,$source,$destination,$setting,$adapterObject) {
    global $api;
    $api->log(LOG_DEBUG, "[ffmpeg::transcode] media:".$media["id"]." source:$source destination:$destination setting:$setting");      
    $metadata=@unserialize($media["metadata"]);
    // Standard settings are <10000.
    // Non-standard are using a plugin system, in that case we launch the hook ...
    if ($setting>=10000) {
      $all=array("result"=>false,
		 "media" => $media, 
		 "source"=>$source,
		 "destination"=>$destination,
		 "setting"=>$setting
		 );
      Hooks::call('transcodeCustom',$all);
      return $all["result"];
    }
    // standard settings are managed here : 
    include(__DIR__."/../libs/settings.php");
    if (!isset($settings[$setting])) {
      $api->log(LOG_ERR, "[ffmpeg::transcode] Setting not found");      
      return false; 
    }
    
    // is it 16/9, 4/3, or ... 
    $params=$this->computeOutputSize($metadata);
    if ($params===false) {
      // no video track ??
      $api->log(LOG_ERR, "[ffmpeg::transcode] No video track found");      
      return false; 
    }

    $ratio=$params["ratio"];

    switch($params["ratio"]) {
    case "16:9":
      $api->log(LOG_DEBUG, "[ffmpeg::transcode] ratio is 16:9, size will be $size");
      $size=$settings[$setting]["size_169"];
      break;
    case "4:3":
      $api->log(LOG_DEBUG, "[ffmpeg::transcode] ratio is 4:3, size will be $size");
      $size=$settings[$setting]["size_43"];
      break;
    case "1:1":
      $api->log(LOG_DEBUG, "[ffmpeg::transcode] ratio is 1:1, size will be $size");
      $size=$settings[$setting]["size_169"];
      list($w,$h)=explode("x",$size);
      $size=$h."x".$h;
      break;
    default:
      //                [ffmpeg::transcode] ratio is DEFAULT, size will be 0x / 426x240 / x , realratio will be 1.6
      $sss=$size=$settings[$setting]["size_169"];
      list($w,$h)=explode("x",$size);
      $size=intval(round($w/4)*4)."x".intval(round($h/4)*4);
      $ratio=$params["realratio"];
      $api->log(LOG_DEBUG, "[ffmpeg::transcode] ratio is DEFAULT, size will be $size / $sss / $w $h, realratio will be ".$params["realratio"]."");
      break;
    }
    if ($params["invert"]) {
      list($w,$h)=explode($size,"x");
      $size=$h."x".$w;
    }
    $failed=false;
    // substitution
    foreach($settings[$setting] as $k=>$v) {
	$v=str_replace("%%SIZE%%",$size,$v);
	$v=str_replace("%%SOURCE%%",escapeshellarg($source),$v);
	$v=str_replace("%%DESTINATION%%",escapeshellarg($destination),$v);
	$v=str_replace("%%RATIO%%",$ratio,$v);
	$v=str_replace("%%DURATION%%",floor($metadata["time"]),$v);
	$settings[$setting][$k]=$v;
    }
    // Execution
    // We cd to a temporary folder where we can write (for statistics files)
    $TMP="/tmp/transcode-".getmypid();
    mkdir($TMP); $push=getcwd(); chdir($TMP);
    $metadata=false;
    foreach($settings[$setting] as $k=>$v) {
      if (substr($k,0,7)=="command") {
	$api->log(LOG_DEBUG, "[ffmpeg::transcode] exec: $v");

	if (substr($v,0,8)=="scripts-") { // scripts-functionname.php in transcodes/
	  if (!function_exists(substr($v,8))) {
	    require_once(dirname(__FILE__)."/../transcodes/".$v.".php");
	  }
	  $called=substr($v,8);
	  $result=$called($media,$source,$destination,$settings[$setting],$adapterObject,$metadata);
	  if ($result) {
	    $ret=0;
	  } else {
	    $out=array($GLOBALS["error"]);
	    $ret=1;
	  }

	} else { // or simple executable
	  exec($v." </dev/null 2>&1",$out,$ret);
	}
	if ($ret!=0) {
	  $cancel=$settings[$setting]["cancelcommand"];
	  $api->log(LOG_ERR,"[ffmpeg::transcode] previous exec failed, output was ".substr(implode("|",$out),-100));
	  if ($cancel) {
	    // Launch cancel command
	    $api->log(LOG_DEBUG, "[ffmpeg::transcode] CANCEL exec: $cancel");
	    exec($cancel);
	  }
	  // Command FAILED
	  $failed=true;
	  break;
	}
      }
    }
    exec("rm -rf ".escapeshellarg($TMP)); chdir($push);
    if ($failed) {
      return false;
    }

    // Get the metadata of the stored file :
    if (is_file($destination.".".$settings[$setting]["extension"])) {
      $metadata=$this->getFfmpegMetadata($destination.".".$settings[$setting]["extension"]);
      $metadata["cardinality"]=1;
    } else {
    // multiple files are set as this : 
      if (is_dir($destination) && !isset($metadata["cardinality"])) {
	// count the number of files in the folder:
	$cardinality=0;
	$d=opendir($destination);
	while ($c=readdir($d)) {
	  if (is_file($destination."/".$c))
	    $cardinality++;
	}
	closedir($d);
	$metadata["cardinality"]=$cardinality;
      }
    }
    // multiple cardinality files may require a finalization function call 
    $adapterObject->filePathTranscodeEnd($media,$metadata,$settings[$setting]);

    if (!$metadata) {
      return false;
    }
    return $metadata;
  } // transcode()


  /* ------------------------------------------------------------ */
  /** 
   * Compute the black bands to add (or not) and the proper aspect ratio
   * of the video. (4:3 or 16:9)
   * @param $meta array the hashset of the metadata of the media
   * @return array an hashset with the aspect ratio and the black band to put on top/bottom/left/right. 
   * TODO : if the settings ask to keep 4/3 16/9 3/4 9/16 ratio
   */
  function computeOutputSize($meta,$setting=null) {
    $found=false;
    foreach($meta["tracks"] as $track) {
      if ($track["type"]==TRACK_TYPE_VIDEO) {
	$meta=$track;
	$found=true;
	break;
      }
    }
    if (!$found) {
      return false;
    }
    $params=array();
    $x=$meta["width"]; $y=$meta["height"];
    if (isset($meta["PAR1"]) && isset($meta["PAR2"])) {
      // compute realX and realY from X/Y using Pixel Aspect Ratio
      $x=$x*(intval($meta["PAR1"])/intval($meta["PAR2"]));
      // Here a PAL video of 4:3 720x576 has now 768/576 == 4/3  
      // And  a PAL video of 16:9 720x576 has now 1024/576 == 16/9  
    }
    $params["invert"]=false;
    if (($x/$y)<1) {
      $params["invert"]=true;
      $z=$y;      $y=$x;      $x=$z;
    }
    // Depending on the original aspect ratio, we keep the usual 4/3 or 16/9,
    // or we set it to keep the original ratio
    if (($x/$y)<1.85 && ($x/$y)>1.70) {
      $params["ratio"]="16:9";
      $params["realratio"]=16/9;
    } 
    else if (($x/$y)<1.40 && ($x/$y)>1.26) {
      $params["ratio"]="4:3";
      $params["realratio"]=4/3;
    }
    else if ( ($x/$y)<1.05 && ($x/$y)>0.95 ) {
      $params["ratio"]="1:1";
      $params["realratio"]=1;
    }
    else {
      $params["ratio"]=intval($x).":".intval($y);
      $params["realratio"]=($x/$y);
    }
    return $params;
  }
  


} /* class Ffmpeg */

