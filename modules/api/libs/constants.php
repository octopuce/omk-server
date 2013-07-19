<?php

/** ************************************************************
 * Here are some constants used by the task processor for the API
 * of the OpenMediaKit Transcoder
 * They are useless outside of the code of the omkt.
 */

/* In which status can be a task */
define("STATUS_MIN",0); define("STATUS_MAX",3);  // min/max value of this flag.
/* The task is ready and should be processed as soon as possible */
define("STATUS_TODO",0);
/* The task is currently processed and locked by a processor somewhere */
define("STATUS_PROCESSING",1);
/* The task has been tried, but something wrong happened */
define("STATUS_ERROR",2);
/* The task has been processed successfully */
define("STATUS_DONE",3);



/* Which tasks are defined */
define("TASK_MIN",1); define("TASK_MAX",5); // min/max value of this flag.
/* The omkt must download a media */
define("TASK_DOWNLOAD",1);
/* The omkt must process a media to know its metadata */
define("TASK_DO_METADATA",2);
/* The omkt must send metadata information to the client (or metadata error) */
define("TASK_SEND_METADATA",3);
/* The omkt must process a media to transcode it to something else */
define("TASK_DO_TRANSCODE",4);
/* The omkt must tell the client that a transcode is available (or failed) */
define("TASK_SEND_TRANSCODE",5);


/* In which status can be a media */
define("MEDIA_MIN",0); define("MEDIA_MAX",4);  // min/max value of this flag.
/* The media is available remotely, not yet locally */
define("MEDIA_REMOTE_AVAILABLE",0);
/* The media is available locally, not yet metadata-parsed */
define("MEDIA_LOCAL_AVAILABLE",1);
/* The media is available locally, but its metadata parsing failed */
define("MEDIA_METADATA_FAILED",2);
/* The media is available locally and we know its metadata */
define("MEDIA_METADATA_OK",3);
/* The media has been expired and deleted */
define("MEDIA_EXPIRED",4);


/* In which status can be a transcode */
define("TRANSCODE_MIN",0); define("TRANSCODE_MAX",2);  // min/max value of this flag.
/* The transcode has been asked, not yet processed */
define("TRANSCODE_ASKED",0);
/* The transcode has been processed successfully */
define("TRANSCODE_PROCESSED",1);
/* The transcode has not been processed due to some error */
define("TRANSCODE_ERROR",2);



/* A file with an undefined duration */
define("DURATION_UNDEFINED",-1);

/* The different kind of track we are dealing with: */
define("TRACK_TYPE_AUDIO","audio");
define("TRACK_TYPE_VIDEO","video");
define("TRACK_TYPE_SUBTITLE","subtitle");
define("TRACK_TYPE_OTHER","other");


/* the ADAPTER_NEW_MEDIA_* constants are returned by the addNewMedia() method of an adapter */
/* The provided url is invalid */
define("ADAPTER_NEW_MEDIA_INVALID",0);
/* The provided url is valid but does not requires a download task. It is already here */
define("ADAPTER_NEW_MEDIA_NODOWNLOAD",1);
/* The provided url is valid and requires a download task. The adapter's downloader will treat the associated tasks */
define("ADAPTER_NEW_MEDIA_DOWNLOAD",2);

/* All the API Errors */
define("API_ERROR_OK",0);
define("API_ERROR_MANDATORY",1);
define("API_ERROR_NOKEY",2);
define("API_ERROR_DISABLED",3);
define("API_ERROR_RATELIMIT",4);
define("API_ERROR_BADPARAM",5);
define("API_ERROR_CREATEMEDIA",6);
define("API_ERROR_ALREADY",7);
define("API_ERROR_NOTPUBLIC",8);
define("API_ERROR_APPNOTALLOWED",9);
define("API_ERROR_CREATEACCOUNT",10);
define("API_ERROR_ADAPTERNOTALLOWED",405);
define("API_ERROR_ADAPTERNOTSUPPORTED",400);
define("API_ERROR_BADURL",13);
define("API_ERROR_CODEERROR",14);
define("API_ERROR_NOQUEUE",15);
define("API_ERROR_NOTFOUND",404);
define("API_ERROR_BADRANGE",216);
define("API_ERROR_BADMEDIA",505);
define("API_ERROR_EXEC",503); // error during a query or something similar (fatal, but not 'code error')

//define("API_ERROR_",);
