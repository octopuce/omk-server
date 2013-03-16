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


  public function get($uid) {
    global $db;
    $uid=intval($uid);
    return $db->qone("SELECT * FROM users WHERE uid='$uid';", null, PDO::FETCH_ASSOC);
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

    $pass = (empty($informations['pass'])) ? '' :  crypt($informations['pass'],Users::getSalt());
    $email = (empty($informations['email'])) ? '' :  trim((string)$informations['email']);
    $enabled = (!empty($informations['enabled']) && in_array($informations['enabled'], array(true, 'on', 1))) ? true : false;
    $validated = (!empty($informations['validated']) && in_array($informations['validated'], array(true, 'on', 1))) ? true : false;
    $admin = (!empty($informations['admin']) && in_array($informations['admin'], array(true, 'on', 1))) ? true : false;
    $url = (empty($informations['url'])) ? '' :  trim((string)$informations['url']);
    $apikey=Users::generateApiKey();

    $db->q('INSERT INTO `users` (uid, pass, email, enabled, validated, admin, url, apikey, lastactiviyy) VALUES(NULL, ?, ?, ?, ?, ?, ?, ?, NOW())',
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


  /** Returns a random 8 characters password
   */
  public static function randomPass() {
    $random="aaabcdeeefghiiijknooopqrstuuuvwxyyyz23456789";
    $str="";
    for($i=0;$i<8;$i++) $str.=substr($random,rand()%strlen($random),1);
    return $str;
  }


  public static function sendValidationEmail($uid) {
    $me=Users::get($uid);
    if (!$me) return false;

    $key = substr(md5(RANDOM_SALT . "_" .$me["email"]),0,5);

    $to      = $me["email"];
    $subject = _("Email validation for the public OpenMediakit Transcoder");
    $message = sprintf(_("
Hi,

Someone, maybe you, created an account on a public OpenMediakit Transcoder service.

This email is sent to the subscription email to validate its ownership. Please click the link below if you want to confirm the account creation.

%s

Please note that you will not be able to use this public transcoder until your email has been validated, since we may need to contact you if something goes wrong with this service.

If you didn't asked for this account, please ignore this message.

--
Regards,

The OpenMediakit Transcoder public instance service at
%s
"),FULL_URL."users/validate/".$me["uid"]."/".$key,FULL_URL);

  $headers = 'From: '.MAIL_FROMNAME.' <'.MAIL_FROM.'>'. "\r\n" .
    'Reply-To: '.MAIL_FROM. "\r\n" .
    'Content-type: text/plain; charset=utf-8' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

  mail($to, $subject, $message, $headers);    
  }


  /** Returns a hash for the crypt password hashing function.
   * as of now, we use php5.3 almost best hashing function: SHA-256 with 1000 rounds and a random 16 chars salt.
   */
  private static function getSalt() {
    $salt = substr(str_replace('+', '.', base64_encode(pack('N4', mt_rand(), mt_rand(), mt_rand(), mt_rand()))), 0, 16);
    return '$5$rounds=1000$'.$salt.'$';
  }
  
  /** Generate an API key, an api key is a 32 characters secret shared between the transcoder and the application.
   * This key is used by API calls to ask for media jobs
   */
  private static function generateApiKey() {
    return md5(mt_rand().mt_rand().mt_rand().mt_rand());
  }
  

} /* Class Users */
