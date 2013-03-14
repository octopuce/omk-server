<?php

require_once __DIR__ . '/../../../config.inc.php';
require_once LIBS . '/Db.php';

class Users {
  public static function auth($_login, $_pass) {
    global $db;

    $me = $db->qone('SELECT * FROM users WHERE email=? AND enabled=1', array($_login), PDO::FETCH_ASSOC);
    $pass = crypt($_pass, $me['pass']);
    //    print_r($me); echo "| "; print_r($pass); exit();
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

  public static function addUser($informations = array()) {
    global $db;

    $pass = (empty($informations['pass'])) ? '' :  crypt($informations['pass'],$this->getSalt());
    $email = (empty($informations['email'])) ? '' :  trim((string)$informations['email']);
    $enabled = (!empty($informations['enabled']) && in_array($informations['enabled'], array(true, 'on', 1))) ? true : false;
    $validated = (!empty($informations['validated']) && in_array($informations['validated'], array(true, 'on', 1))) ? true : false;
    $admin = (!empty($informations['admin']) && in_array($informations['admin'], array(true, 'on', 1))) ? true : false;
    $url = (empty($informations['url'])) ? '' :  trim((string)$informations['url']);
    $apikey=$this->generateApiKey();

    $db->q('INSERT INTO `users` (uid, pass, email, enabled, validated, admin, url, apikey) VALUES(NULL, ?, ?, ?, ?, ?, ?, ?)',
	   array(
		 $pass,
		 $email,
		 ($enabled) ? 1 : 0,
		 ($validated) ? 1 : 0,
		 ($admin) ? 1 : 0,
		 $url,
		 $apikey
		 )
	   );

    return $db->lastInsertId();
  }



  /** Returns a hash for the crypt password hashing function.
   * as of now, we use php5.3 almost best hashing function: SHA-256 with 1000 rounds and a random 16 chars salt.
   */
  private function getSalt() {
    $salt = substr(str_replace('+', '.', base64_encode(pack('N4', mt_rand(), mt_rand(), mt_rand(), mt_rand()))), 0, 16);
    return '$5$rounds=1000$'.$salt.'$';
  }
  
  /** Generate an API key, an api key is a 32 characters secret shared between the transcoder and the application.
   * This key is used by API calls to ask for media jobs
   */
  private function generateApiKey() {
    return md5(mt_rand().mt_rand().mt_rand().mt_rand());
  }


  /** Returns a random 8 characters password
   */
  private function randomPass() {
    $random="aaabcdeeefghiiijknooopqrstuuuvwxyyyz23456789";
    $str="";
    for($i=0;$i<8;$i++) $str.=substr($random,rand()%strlen($random),1);
    return $str;
  }
  

} /* Class Users */
