<?php
// Configuration
require_once dirname(__FILE__) . '/../config.inc.php';

// Some libraries
require_once LIBS . '/utils.php';
require_once LIBS . '/html_utils.php';
require_once LIBS . '/users.php';
require_once LIBS . '/Db.php';
require_once LIBS . '/SimpleRouter.php';
require_once LIBS . '/Hooks.php';
require_once LIBS . '/lang.php';

// Default database
try {
  $db = new Db($db);
}
catch (Exception $e) {
  echo _('Cannot connect to the database:') . ' ' . $e->getMessage() . "\n";
  exit;
}

try {
  check_user_identity();

  // Router
  $r = new SimpleRouter();
  $r->route('/', 'index:index'); // Default route
  $r->run($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
}
catch (Exception $e) {
  echo _('Error:') . ' ' . $e->getMessage() . "\n";
}

/*
   Globals used in this application : 
   $me[] = array of key=>value pair : the current user from the User table.
   $class = name of the part of the application that we are executing (first part of the url which is http://domain.tld/class/action
   $action = the action requested. May be "" (=="list") or "edit", "doedit", "add", "doadd", "delete", "dodelete" ...
*/
