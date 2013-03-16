#!/usr/bin/env php
<?php

   /** ************************************************************
    * Crontab, launch daily processes
    */

require_once __DIR__ . '/../../../common.php';
require_once __DIR__ . '/../libs/api.php';

$api=new Api();

$api->cronDaily();

