<?php

//require_once MODULES . '/servers/lib.php';

class UsersController extends AController {
  static private $contacts_types = array('in_email', 'in_tel', 'out_email', 'out_tel');

  /*
   * Users are redirected to "my account"
   * Administrators are shown the user list.
   */
  public function indexAction() {
    if (!is_admin()) {
      header('Location: ' . BASE_URL . 'users/me');
      exit;
    }
    global $db;

    $st = $db->q('SELECT uid, login, email, ' .
		 'IF(enabled, :yes, :no) as enabled, ' .
		 'IF(admin, :yes, :no) as admin FROM users ' .
		 'ORDER BY admin, login',
		 array('yes' => "X", 'no' => ""));
    $users = array();
    while ($data = $st->fetch()) {
      $users[] = array(
		       '_' => $data,
		       'name' => l($data->login, 'users/show/' . $data->uid),
		       'email' => $data->email,
		       'enabled' => $data->enabled,
		       'admin' => $data->admin,
		       );
    }
    Hooks::call('users_list_users', $users);
    foreach ($users as $k => $user) {
      $uid = $user['_']->uid;
      $users[$k]['actions'] = l(_("Edit"), 'users/edit/' . $uid) .
	' | ' . l(_("Delete"), 'users/delete/' . $uid) .
	' | ' . l(_("Connect as"), 'users/impersonate/' . $uid);
    }

    $headers = array(
		     'name' => _('Name'),
		     'email' => _('Email'),
		     'enabled' => _('Enabled'),
		     'admin' => _('Admin.'),
		     );
    Hooks::call('users_list_headers', $headers);
    $headers['actions'] = _('Actions');


    $this->render('list', array('users' => $users, 'headers' => $headers));
  }

  /*
   * Show one user account infos (for admins only)
   */
  public function showAction($params) {
    if (!is_admin())
      not_found();
    global $db;

    $info = trim($params[0]);
    if (is_numeric($info)) {
      $uid = intval($info);
      $user = $db->qone('SELECT uid, login, email, ' .
			'IF(enabled, :yes, :no) as enabled, ' .
			'IF(admin, :yes, :no) as admin, apikey ' .
			'FROM users WHERE uid = :uid',
			array('yes' => _("yes"), 'no' => _("no"), 'uid' => $uid));
    }
    else {
      $login = $info;
      $user = $db->qone('SELECT uid, login, email, ' .
			'IF(enabled, :yes, :no) as enabled, ' .
			'IF(admin, :yes, :no) as admin, apikey ' .
			'FROM users WHERE login = :login',
			array('yes' => _("yes"), 'no' => _("no"), 'login' => $login));
    }
    if ($user == false)
      not_found();

    $this->render('show', array('user' => $user));
  }


  /* Check a form for the user editor */
  private static function verifyForm($data, $op) {
    $errors = array();
    if ($op != 'meedit') {
      if (empty($data['login']))
	$errors[] = _("Please set the login name");
    }

    switch ($op) {
    case 'add':
      if (empty($data['pass']))
	$errors[] = _("Please set a password");
      elseif ($data['pass'] != $data['pass_confirm'])
	$errors[] = _("The passwords are different, please check");
      break;
    case 'edit':
    case 'meedit':
      if ($data['pass'] != $data['pass_confirm'])
	$errors[] = _("The passwords are different, please check");
      break;
    }
    if (empty($data['email']))
      $errors[] = _("The email address is mandatory");
    return $errors;
  }

  /*
   * Add a user (for admins only)
   */
  public function addAction() {

    if (!is_admin())
      not_found();

    $errors = array(); // OK if no problem

    if (!empty($_POST)) {
      $errors = $this->verifyForm($_POST, 'add');

      if (empty($errors)) {
        global $db;
	$apikey=$this->generateApiKey();
        $db->q('INSERT INTO `users` (login, pass, email, enabled, admin, apikey) VALUES(?, ?, ?, ?, ?, ?)',
               array(
                     $_POST['login'],
                     crypt($_POST['pass'],$this->getSalt()),
                     $_POST['email'],
                     ($_POST['enabled'] == 'on') ? 1 : 0,
                     ($_POST['admin'] == 'on') ? 1 : 0,
		     $apikey,
                     )
               );
	$uid = $db->lastInsertId();
	$args = array(
		      'uid' => $uid,
		      'login' => $_POST['login'],
		      'pass' => $_POST['pass'],
		      'email' => $_POST['email'],
		      'enabled' => ($_POST['enabled'] == 'on'),
		      'admin' => ($_POST['admin'] == 'on'),
		      'apikey' => $apikey,
		      );
	Hooks::call('users_add', $args);

        // Message + redirection
	header('Location: ' . BASE_URL . 'users/show/' . $uid . '?msg=' . _("Ajout OK..."));
	exit;
      }
    }


    /*
     * Valeurs pour pré-remplir le formulaire
     *
     * Deux cas possibles...
     * 1/ On vient d'arriver sur la page ( empty($_POST) ) :
     * on pré-rempli le formulaire avec... rien (nouvel utilisateur)
     *
     * 2/ On à validé le formulaire, mais il y a une erreur :
     * on pré-rempli le formulaire avec les données de la saisie.
     */
    $form_data = (empty($_POST)) ? array() : $_POST; 

    $this->render('form', array('op' => 'add', 'data' => $form_data, 'errors' => $errors));
  }


  /*
   * Edit a user (admins only)
   */
  public function editAction($params) {

    if (!is_admin())
      not_found();
    global $db;
    $uid = intval($params[0]);
    $user = $db->qone('SELECT uid, login, email, enabled, admin FROM users WHERE uid = ?', array($uid));

    if ($user == false)
      not_found();

    $errors = array(); 

    if (!empty($_POST)) {
      $errors = self::verifyForm($_POST, 'edit');

      if (empty($errors)) {
        $db->q('UPDATE users SET login = ?, email = ?, enabled = ?, admin = ? WHERE uid = ?',
               array(
                     $_POST['login'],
                     $_POST['email'],
                     ($_POST['enabled'] == 'on') ? 1 : 0,
                     ($_POST['admin'] == 'on') ? 1 : 0,
		     $user->uid,
                     )
               );

	$old_user = $user;
	$user = $db->qone('SELECT uid, login, email, enabled, admin FROM users WHERE uid = ?', array($user->uid));
	$args = array('old_user' => $old_user, 'new_user' => $user);
	Hooks::call('users_edit', $args);

	if (!empty($_POST['pass'])) {
	  if (!empty($_POST['notify_new_passwd'])) {
	    $this->mail_notify_new_passwd($user->email, $user->login, $_POST['pass']);
	  }
	  if (!empty($_POST['notify_new_account'])) {
	    $this->mail_notify_new_account($user->email, $user->login, $_POST['pass']);
	  }

	  $db->q('UPDATE users SET pass = ? WHERE uid = ?', array(crypt($_POST['pass'],$this->getSalt()), $user->uid));

	  $args = array('uid' => $user->uid, 'login' => $user->login, 'pass' => $_POST['pass']);
	  Hooks::call('users_edit_pass', $args);
	}

        // Message + redirection
	header('Location: ' . BASE_URL . 'users/show/' . $user->uid . '?msg=' . _("Mise à jour OK..."));
	exit;
      }
    }

    /*
     * Valeurs pour pré-remplir le formulaire
     *
     * Deux cas possibles...
     * 1/ On vient d'arriver sur la page ( empty($_POST) ) :
     * on pré-rempli le formulaire avec les données de l'utilisateur
     *
     * 2/ On à validé le formulaire, mais il y a une erreur :
     * on pré-rempli le formulaire avec les données de la saisie.
     */

    if (empty($_POST)) {
      $form_data = get_object_vars($user); // get_object_vars : stdClass -> array

      foreach (self::$contacts_types as $type) {
	$contacts = $db->qlistone('SELECT contact FROM contacts WHERE uid = ? AND type = ? ORDER BY contact',
				  array($uid, $type));
        $form_data['contacts_' . $type] = implode("\n", $contacts);
      }
    }
    else {
      $form_data = $_POST;
    }

    $this->render('form', array('op' => 'edit', 'login' => $user->login, 'data' => $form_data, 'errors' => $errors));
  }


  /*
   * Delete a user (admin only)
   */
  public function deleteAction($params) {
    if (!is_admin())
      not_found();
    global $db;
    $uid = intval($params[0]);
    $user = $db->qone('SELECT uid, login, email, enabled, admin FROM users WHERE uid = ?', array($uid));
    if ($user == false)
      not_found();

    if (!empty($_POST['op'])) {
      if ($_POST['op'] == _("Yes, Delete")) {
	$db->q('DELETE FROM users WHERE uid = ?', array($uid));
	$args = array($user);
	Hooks::call('users_delete', $args);
        // Message + redirection
	header('Location: ' . BASE_URL . 'users?msg=' . sprintf(_("User %s successfully deleted"), $user->login));
	exit;
      }
      else {
        // Message + redirection
	header('Location: ' . BASE_URL . 'users/show/' . $uid . '?msg=' . _("Nothing has been deleted"));
	exit;
      }
    }
    $this->render('delete', array('user' => $user));
  }

  /*
   * Connect as another user (for admins only)
   */
  public function impersonateAction($params) {
    if (!is_admin())
      not_found();
    global $db;
    $uid = intval($params[0]);
    $user = $db->qone('SELECT uid, login, email, enabled, admin FROM users WHERE uid = ?', array($uid));
    if ($user == false)
      not_found();
    setcookie('impersonate', $user->uid, 0, '/');
    header('Location: ' . BASE_URL);
    exit;
  }


  /*
   * Leave the connect as a user 
   */
  public function stopimpersonateAction() {
    setcookie('impersonate', '0', 1, '/');
    header('Location: ' . BASE_URL .'users');
    exit;
  }


  /*
   * My Account
   */
  public function meAction($params) {
    global $db;
    $uid=$GLOBALS['me']['uid'];

    $user = $db->qone('SELECT uid, login, email, enabled, admin ' .
                      'FROM users WHERE uid = :uid',
                      array('uid' => $GLOBALS['me']['uid']));
    if ($user == false)
      not_found();

    $contacts = array();
    foreach (self::$contacts_types as $type)
      $contacts[$type] = $db->qlistone('SELECT contact FROM contacts WHERE uid = ? AND type = ?',
                                       array($GLOBALS['me']['uid'], $type));

    if ($params[0] == 'edit') {
      $errors = array();

      if (!empty($_POST)) {
	$errors = self::verifyForm($_POST, 'meedit');

	if (empty($errors)) {
	  $db->q('UPDATE users SET email = ? WHERE uid = ?', array($_POST['email'], $user->uid));
	  $old_user = $user;
	  $user = $db->qone('SELECT uid, login, email, enabled, admin FROM users WHERE uid = ?', array($user->uid));
	  $args = array('old_user' => $old_user, 'new_user' => $user);
	  Hooks::call('users_edit', $args);

	  if (!empty($_POST['pass'])) {
	    $db->q('UPDATE users SET pass = ? WHERE uid = ?', array(crypt($_POST['pass'],$this->getSalt()), $user->uid));
	    $args = array('uid' => $user->uid, 'login' => $user->login, 'pass' => $_POST['pass']);
	    Hooks::call('users_edit_pass', $args);
	  }

	  // Message + redirection
	  header('Location: ' . BASE_URL . 'users/me?msg=' . _("Mise à jour OK..."));
	  exit;
	}
      }

      /*
       * Valeurs pour pré-remplir le formulaire
       *
       * Deux cas possibles...
       * 1/ On vient d'arriver sur la page ( empty($_POST) ) :
       * on pré-rempli le formulaire avec les données de l'utilisateur
       *
       * 2/ On à validé le formulaire, mais il y a une erreur :
       * on pré-rempli le formulaire avec les données de la saisie.
       */

      if (empty($_POST)) {
	$form_data = get_object_vars($user); // get_object_vars : stdClass -> array

	foreach (self::$contacts_types as $type) {
	  $contacts = $db->qlistone('SELECT contact FROM contacts WHERE uid = ? AND type = ? ORDER BY contact',
				    array($uid, $type));
	  $form_data['contacts_' . $type] = implode("\n", $contacts);
	}
      }
      else {
	$form_data = $_POST;
      }

      $this->render('form', array('op' => 'meedit', 'data' => $form_data, 'errors' => $errors));
    }
    else
      $this->render('me', array('user' => $user, 'contacts' => $contacts));
  }

  public function logoutAction() {
    $realm = 'OpenMediaKit Transcoder';
    header('WWW-Authenticate: Basic realm="' . $realm . '"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Logout';
    exit;
  }


function mail_notify_new_passwd($email, $login, $pass) {
  $to      = $email;
  $subject = _("Changement de mot de passe sur le Panel Octopuce");
  $message = sprintf(_("
Bonjour,

le mot de passe de votre compte sur le panneau de contrôle d'Octopuce vient d'être modifié.

Vous pouvez y accéder à l'adresse : %s

Votre nom d'utilisateur est : %s
Votre mot de passe est : %s

--
Cordialement,

L'équipe technique d'Octopuce
"),FULL_URL,$login,$pass);

  $headers = 'From: '.MAIL_FROMNAME.' <'.MAIL_FROM.'>'. "\r\n" .
    'Reply-To: '.MAIL_FROM. "\r\n" .
    'Content-type: text/plain; charset=utf-8' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

  mail($to, $subject, $message, $headers);
}

function mail_notify_new_account($email, $login, $pass) {
  $to      = $email;
  $subject = _("Votre compte sur le Panel Octopuce");
  $message = sprintf(_("
Bonjour,

votre compte sur le panneau de contrôle d'Octopuce vient d'être créé.

Vous pouvez y accéder à l'adresse : %s

Votre nom d'utilisateur est : %s
Votre mot de passe est : %s

Nous vous invitons à le modifier en cliquant sur 'Mon compte' puis 'Modifier'

--
Cordialement,

L'équipe technique d'Octopuce
"),FULL_URL,$login,$pass);

  $headers = 'From: '.MAIL_FROMNAME.' <'.MAIL_FROM.'>'. "\r\n" .
    'Reply-To: '.MAIL_FROM. "\r\n" .
    'Content-type: text/plain; charset=utf-8' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

  mail($to, $subject, $message, $headers);
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
  return md5(mt_rand().mt_rand().mt_rand()mt_rand());
}


}