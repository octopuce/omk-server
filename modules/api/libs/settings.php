<?php

/** ************************************************************
 * Here are the constants used by the task processor for the API
 * of the OpenMediaKit Transcoder
 * They are the standard settings used by the OMK 
 * Both the Client and the Transcoder know those settings and their 
 * associated description string (in english)
 */

$settings=array(
		/* Video FLV */
		array("id" => 1, "type" => "video", "slug" => "video 240p flv", 
		      "name" => "Video at very low definition and flash sorenson format", 
		      "technical" => "flv container, sorenson video at 280kb/s, mp3 audio at 64kb/s and 22KHz, 426x240 pixels on 16:9, 25fps"),

		array("id" => 2, "type" => "video", "slug" => "video 360p flv", 
		      "name" => "Video at low definition and flash sorenson format", 
		      "technical" => "flv container, sorenson video at 440kb/s, mp3 audio at 128kb/s and 44KHz, 640x360 pixels on 16:9, 25fps"),

		array("id" => 3, "type" => "video", "slug" => "video 360p flv", 
		      "name" => "Video at low definition and flash sorenson format", 
		      "technical" => "flv container, sorenson video at 440kb/s, mp3 audio at 128kb/s and 44KHz, 640x360 pixels on 16:9, 25fps"),

		/* Video MP4 */
		array("id" => 11, "type" => "video", "slug" => "video 240p mp4", 
		      "name" => "Video at very low definition and portable mp4 format", 
		      "technical" => "mp4 container, h264 video at 280kb/s, aac audio at 64kb/s and 22KHz, 426x240 pixels on 16:9, 25fps"),

		array("id" => 12, "type" => "video", "slug" => "video 360p mp4", 
		      "name" => "Video at low definition and portable mp4 format", 
		      "technical" => "mp4 container, h264 video at 440kb/s, aac audio at 128kb/s and 44KHz, 640x360 pixels on 16:9, 25fps"),

		array("id" => 13, "type" => "video", "slug" => "video 480p mp4", 
		      "name" => "Video at standard definition and portable mp4 format", 
		      "technical" => "mp4 container, h264 video at 680kb/s, aac audio at 128kb/s and 44KHz, 854x480 pixels on 16:9, 30fps"),

		array("id" => 14, "type" => "video", "slug" => "video 720p mp4", 
		      "name" => "Video at high definition and portable mp4 format", 
		      "technical" => "mp4 container, h264 video at 1650kb/s, aac audio at 192kb/s and 44KHz, 1280x720 pixels on 16:9, 30fps"),

		array("id" => 15, "type" => "video", "slug" => "video 1080p mp4", 
		      "name" => "Video at full-hd definition and portable mp4 format", 
		      "technical" => "mp4 container, h264 video at 4000kb/s, aac audio at 192kb/s and 44KHz, 1920x1080 pixels on 16:9, 30fps"),

		/* Video WEBM */
		array("id" => 21, "type" => "video", "slug" => "video 240p webm", 
		      "name" => "Video at very low definition and webm opensource format", 
		      "technical" => "webm container, vp8 video at 280kb/s, vorbis audio at 64kb/s and 22KHz, 426x240 pixels on 16:9, 25fps"),

		array("id" => 22, "type" => "video", "slug" => "video 360p webm", 
		      "name" => "Video at low definition and webm opensource format",
		      "technical" => "webm container, vp8 video at 440kb/s, vorbis audio at 128kb/s and 44KHz, 640x360 pixels on 16:9, 25fps"),

		array("id" => 23, "type" => "video", "slug" => "video 480p webm", 
		      "name" => "Video at standard definition webm opensource format",
		      "technical" => "webm container, vp8 video at 680kb/s, vorbis audio at 128kb/s and 44KHz, 854x480 pixels on 16:9, 30fps"),

		array("id" => 24, "type" => "video", "slug" => "video 720p webm", 
		      "name" => "Video at high definition and webm opensource format",
		      "technical" => "webm container, vp8 video at 1650kb/s, vorbis audio at 192kb/s and 44KHz, 1280x720 pixels on 16:9, 30fps"),

		array("id" => 25, "type" => "video", "slug" => "video 1080p webm", 
		      "name" => "Video at full-hd definition and webm opensource format",
		      "technical" => "webm container, vp8 video at 4000kb/s, vorbis audio at 192kb/s and 44KHz, 1920x1080 pixels on 16:9, 30fps"),

		/* Video MPEG */
		array("id" => 31, "type" => "video", "slug" => "video 240p mpeg", 
		      "name" => "Video at very low definition and mpeg broadcast format", 
		      "technical" => "mpeg container, mpeg2 video at 280kb/s, mpeg2 audio at 64kb/s and 22KHz, 426x240 pixels on 16:9, 25fps"),

		array("id" => 32, "type" => "video", "slug" => "video 360p mpeg", 
		      "name" => "Video at low definition and mpeg broadcast format",
		      "technical" => "mpeg container, mpeg2 video at 440kb/s, mpeg2 audio at 128kb/s and 44KHz, 640x360 pixels on 16:9, 25fps"),

		array("id" => 33, "type" => "video", "slug" => "video 480p mpeg", 
		      "name" => "Video at standard definition mpeg broadcast format",
		      "technical" => "mpeg container, mpeg2 video at 680kb/s, mpeg2 audio at 128kb/s and 44KHz, 854x480 pixels on 16:9, 30fps"),

		array("id" => 34, "type" => "video", "slug" => "video 720p mpeg", 
		      "name" => "Video at high definition and mpeg broadcast format",
		      "technical" => "mpeg container, mpeg2 video at 1650kb/s, mpeg2 audio at 192kb/s and 44KHz, 1280x720 pixels on 16:9, 30fps"),

		array("id" => 35, "type" => "video", "slug" => "video 1080p mpeg", 
		      "name" => "Video at full-hd definition and mpeg broadcast format",
		      "technical" => "mpeg container, mpeg2 video at 4000kb/s, mpeg2 audio at 192kb/s and 44KHz, 1920x1080 pixels on 16:9, 30fps"),

		/* Thumbnails JPG */
		array("id" => 101, "type" => "thumbnails", "slug" => "100 240p thumbs jpg", 
		      "name" => "100 thumbnails at 240p as jpg files", 
		      "technical" => "100 jpg at 80% quality, no more than 1 every 10 seconds, size 426x240"),

		array("id" => 102, "type" => "thumbnails", "slug" => "100 360p thumbs jpg", 
		      "name" => "100 thumbnails at 360p as jpg files", 
		      "technical" => "100 jpg at 80% quality, no more than 1 every 10 seconds, size 640x360"),

		array("id" => 103, "type" => "thumbnails", "slug" => "100 480p thumbs jpg", 
		      "name" => "100 thumbnails at 480p as jpg files", 
		      "technical" => "100 jpg at 80% quality, no more than 1 every 10 seconds, size 854x480"),

		array("id" => 104, "type" => "thumbnails", "slug" => "100 720p thumbs jpg", 
		      "name" => "100 thumbnails at 720p as jpg files", 
		      "technical" => "100 jpg at 80% quality, no more than 1 every 10 seconds, size 1280x720"),

		array("id" => 105, "type" => "thumbnails", "slug" => "100 1080p thumbs jpg", 
		      "name" => "100 thumbnails at 1080p as jpg files", 
		      "technical" => "100 jpg at 80% quality, no more than 1 every 10 seconds, size 1920x1080"),

		/* Small thumbnails JPG */
		array("id" => 110, "type" => "thumbnails", "slug" => "100 small thumbs jpg", 
		      "name" => "100 small thumbnails as jpg files", 
		      "technical" => "100 jpg at 80% quality, no more than 1 every 2 seconds, size 100x60 (width guaranteed)"),

		array("id" => 111, "type" => "thumbnails", "slug" => "100 medium thumbs jpg", 
		      "name" => "100 medium thumbnails as jpg files", 
		      "technical" => "100 jpg at 80% quality, no more than 1 every 2 seconds, size 200x120 (width guaranteed)"),


		); // all settings

  

