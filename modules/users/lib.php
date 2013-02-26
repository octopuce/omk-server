<?php
require_once __DIR__ . '/../../config.inc.php';
require_once LIBS . '/Db.php';
/*
try {
  $db = new Db($db);
}
catch (Exception $e) {
  echo _('Connexion à la base de données échouée :') . ' ' . $e->getMessage() . "\n";
  exit;
}
*/

class Users {
  public static function auth($_login, $_pass) {
    global $db;

    $me = $db->qone('SELECT * FROM users WHERE email=? AND enabled=1', array($_login), PDO::FETCH_ASSOC);
    $pass = crypt($_pass, $me['pass']);
  
    if ($pass!=$me['pass']) return false;
    return $me;
  }

  public static function getAllUsers($mode = '') {
    global $db;

    if ($mode == 'assoc')
      $users = $db->qassoc('SELECT uid, email FROM users ORDER BY email ASC');
    elseif ($mode == 'listone')
      $users = $db->qlistone("SELECT email FROM users ORDER BY email ASC");
    else
      $users = $db->qlist('SELECT uid, email, enabled, admin FROM users ORDER BY email ASC');

    return $users;
  }

  public static function getAllCustomers($mode = '') {
    global $db;

    if ($mode == 'assoc')
      $users = $db->qassoc('SELECT uid, email FROM users WHERE admin=0 ORDER BY email ASC');
    elseif ($mode == 'listone')
      $users = $db->qlistone("SELECT email FROM users WHERE admin=0 ORDER BY email ASC");
    else
      $users = $db->qlist('SELECT uid, email, enabled, admin FROM users WHERE admin=0 ORDER BY email ASC');

    return $users;
  }

  public static function addUser($informations = array()) {
    global $db;

    $pass = (empty($informations['pass'])) ? '' :  crypt($informations['pass']);
    $email = (empty($informations['email'])) ? '' :  (string)$informations['email'];
    $enabled = (!empty($informations['enabled']) && in_array($informations['enabled'], array(true, 'on', 1))) ? true : false;
    $admin = (!empty($informations['admin']) && in_array($informations['admin'], array(true, 'on', 1))) ? true : false;

    if (empty($login))
      return 0;

    $db->q('INSERT INTO `users` (uid, pass, email, enabled, admin) VALUES(NULL, ?, ?, ?, ?)',
	   array(
		 crypt($pass),
		 $email,
		 ($enabled) ? 1 : 0,
		 ($admin) ? 1 : 0,
		 )
	   );

    return $db->lastInsertId();
  }
}
