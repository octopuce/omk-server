<?php

require_once MODULES . '/users/libs/users.php';

class UsersController extends AController {


  function UsersController() {
    $this->user=new Users();
  }


  /*
   * Users are redirected to "my account"
   * Administrators are shown the user list.
   */
  public function indexAction() {
    check_user_identity();

    if (!is_admin()) {
      header('Location: ' . BASE_URL . 'users/me');
      exit;
    }
    global $db;

    $st = $db->q('SELECT uid, email, ' .
		 'IF(enabled, :yes, :no) as enabled, ' .
		 'IF(admin, :yes, :no) as admin, url FROM users ' .
		 'ORDER BY admin DESC, email',
		 array('yes' => "X", 'no' => ""));
    $users = array();
    while ($data = $st->fetch()) {
      $users[] = array(
		       '_' => $data,
		       'email' => l($data->email, 'users/show/' . $data->uid),
		       'enabled' => $data->enabled,
		       'admin' => $data->admin,
		       'url' => $data->url,
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
		     'email' => _('Email'),
		     'enabled' => _('Enabled'),
		     'admin' => _('Admin.'),
		     'url' => _('Application URL'),
		     );
    Hooks::call('users_list_headers', $headers);
    $headers['actions'] = _('Actions');


    $this->render('list', array('users' => $users, 'headers' => $headers));
  }

  /*
   * Show one user account infos (for admins only)
   */
  public function showAction($params) {
    check_user_identity();

    if (!is_admin())
      not_found();
    global $db;

    $info = trim($params[0]);
    $uid = intval($info);
    $user = $db->qone('SELECT uid, email, ' .
		      'IF(enabled, :yes, :no) as enabled, ' .
		      'IF(admin, :yes, :no) as admin, url, apikey ' .
		      'FROM users WHERE uid = :uid',
		      array('yes' => _("yes"), 'no' => _("no"), 'uid' => $uid));
    if ($user == false)
      not_found();

    $this->render('show', array('user' => $user));
  }


  /* Check a form for the user editor */
  private static function verifyForm($data, $op) {
    $errors = array();
    if (empty($data['email'])) {
      $errors[] = _("The email address is mandatory");
    }
    if ($data['pass'] != $data['pass_confirm']) {
      $errors[] = _("The passwords are different, please check");  
    }
    if ($op=="add" && empty($data['pass'])) {
      $errors[] = _("Please set a password");
    }
    return $errors;
  }

    
  /*
   * Add a user (for admins only)
   */
  public function addAction() {
    check_user_identity();

    if (!is_admin())
      not_found();

    $errors = array(); // OK if no problem

    if (!empty($_POST)) {
      $errors = $this->verifyForm($_POST, 'add');

      if (empty($errors)) {
	$_POST["apikey"]=Users::generateApiKey();
	$uid=$this->user->addUser($_POST);
	if ($uid) {
	  $args = array(
			'uid' => $uid,
			'pass' => $_POST['pass'],
			'email' => $_POST['email'],
			'enabled' => ($_POST['enabled'] == 'on'),
			'admin' => ($_POST['admin'] == 'on'),
			'url' => $_POST['url'],
			'apikey' => $apikey,
			);
	  Hooks::call('users_add', $args);
	  // Message + redirection
	  header('Location: ' . BASE_URL . 'users/show/' . $uid . '?msg=' . _("User successfully added."));
	} else {
	  // Message + redirection
	  header('Location: ' . BASE_URL . 'users?msg=' . _("An error occurred when adding the user."));
	}
	exit;
      }
    }

    $form_data = (empty($_POST)) ? array() : $_POST; 
    $form_data['enabled']=1; // enabled by default
    $this->render('form', array('op' => 'add', 'data' => $form_data, 'errors' => $errors));
  } // addAction



  /*
   * Edit a user (admins only)
   */
  public function editAction($params) {
    check_user_identity();

    if (!is_admin())
      not_found();
    global $db;
    $uid = intval($params[0]);
    $user = $db->qone('SELECT uid, email, enabled, admin, url, apikey FROM users WHERE uid = ?', array($uid));

    if ($user == false)
      not_found();

    $errors = array(); 

    if (!empty($_POST)) {
      $errors = self::verifyForm($_POST, 'edit');

      if (empty($errors)) {
        $db->q('UPDATE users SET email=?, enabled=?, admin=?, url=? WHERE uid=?',
               array(
                     $_POST['email'],
                     ($_POST['enabled'] == 'on') ? 1 : 0,
                     ($_POST['admin'] == 'on') ? 1 : 0,
                     $_POST['url'],
		     $user->uid,
                     )
               );

	$old_user = $user;
	$user = $db->qone('SELECT uid, email, enabled, admin, url FROM users WHERE uid=?', array($user->uid));
	$args = array('old_user' => $old_user, 'new_user' => $user);
	Hooks::call('users_edit', $args);

	if (!empty($_POST['pass'])) {
	  if (!empty($_POST['notify_new_passwd'])) {
	    $this->mail_notify_new_passwd($user->email, $user->login, $_POST['pass']);
	  }
	  if (!empty($_POST['notify_new_account'])) {
	    $this->mail_notify_new_account($user->email, $user->login, $_POST['pass']);
	  }

	  $db->q('UPDATE users SET pass=? WHERE uid=?', array(crypt($_POST['pass'],Users::getSalt()), $user->uid));

	  $args = array('uid' => $user->uid, 'email' => $user->email, 'pass' => $_POST['pass']);
	  Hooks::call('users_edit_pass', $args);
	}

        // Message + redirection
	header('Location: ' . BASE_URL . 'users/show/' . $user->uid . '?msg=' . _("User changed..."));
	exit;
      }
    }

    /*
     * Valeurs pour pré-remplir le formulaire
     *
     * Deux cas possibles...
     * 1/ On vient d'arriver sur la page ( empty($_POST) ) :
     * on pré-rempli le formulaire avec les données de l'utilisateur
     *
     * 2/ On à validé le formulaire, mais il y a une erreur :
     * on pré-rempli le formulaire avec les données de la saisie.
     */

    if (empty($_POST)) {
      $form_data = get_object_vars($user); // get_object_vars : stdClass -> array
    }
    else {
      $form_data = $_POST;
    }

    $this->render('form', array('op' => 'edit', 'data' => $form_data, 'errors' => $errors));
  }


  /*
   * Delete a user (admin only)
   */
  public function deleteAction($params) {
    check_user_identity();

    if (!is_admin())
      not_found();
    global $db;
    $uid = intval($params[0]);
    $user = $db->qone('SELECT uid, email, enabled, admin, url FROM users WHERE uid = ?', array($uid));
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
    check_user_identity();

    if (!is_admin())
      not_found();
    global $db;
    $uid = intval($params[0]);
    $user = $db->qone('SELECT uid, email, enabled, admin FROM users WHERE uid=?', array($uid));
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
    check_user_identity();

    setcookie('impersonate', '0', 1, '/');
    header('Location: ' . BASE_URL .'users');
    exit;
  }


  /*
   * Validate a user's email
   */
  public function validateAction($params) {
    global $db;
    $uid = intval($params[0]);
    $key = $params[1];
    if (!$uid || !preg_match('#^[0-9a-fA-F]{5}$#',$key)) {
      $errors[]=_("The address you entered is incorrect, please check the mail you received (1)");
      $this->render('index', array('errors' => $errors));
      exit();
    }
    $user = Users::get($uid);

    if ($user==false || $key!=substr(md5(RANDOM_SALT . "_" .$user["email"]),0,5)) {
      $errors[]=_("The address you entered is incorrect, please check the mail you received (2)");
      $this->render('index', array('errors' => $errors));
      exit();
    }

    $db->q('UPDATE users SET validated=1 WHERE uid=?',array($uid));

    $errors[]=_("Your account has been validated, you can now use the OpenMediakit Transcoder service");
    $this->render('index', array('errors' => $errors));
  }



  /*
   * My Account
   */
  public function meAction($params) {
    global $db;
    check_user_identity();

    $uid=$GLOBALS['me']['uid'];

    $user = $db->qone('SELECT uid, email, enabled, admin, url ' .
                      'FROM users WHERE uid = :uid',
                      array('uid' => $GLOBALS['me']['uid']));
    if ($user == false)
      not_found();

    if ($params[0] == 'edit') {
      $errors = array();

      if (!empty($_POST)) {
	$errors = self::verifyForm($_POST, 'meedit');

	if (empty($errors)) {
	  $db->q('UPDATE users SET email=? WHERE uid=?', array($_POST['email'], $user->uid));
	  $old_user = $user;
	  $user = $db->qone('SELECT uid, email, enabled, admin FROM users WHERE uid = ?', array($user->uid));
	  $args = array('old_user' => $old_user, 'new_user' => $user);
	  Hooks::call('users_edit', $args);

	  if (!empty($_POST['pass'])) {
	    $db->q('UPDATE users SET pass=? WHERE uid=?', array(crypt($_POST['pass'],Users::getSalt()), $user->uid));
	    $args = array('uid' => $user->uid, 'email' => $user->email, 'pass' => $_POST['pass']);
	    Hooks::call('users_edit_pass', $args);
	  }

	  // Message + redirection
	  header('Location: ' . BASE_URL . 'users/me?msg=' . _("User account changed..."));
	  exit;
	}
      }

      /*
       * Valeurs pour pré-remplir le formulaire
       *
       * Deux cas possibles...
       * 1/ On vient d'arriver sur la page ( empty($_POST) ):
       * on pré-rempli le formulaire avec les données de l'utilisateur
       *
       * 2/ On à validé le formulaire, mais il y a une erreur:
       * on pré-rempli le formulaire avec les données de la saisie.
       */

      if (empty($_POST)) {
	$form_data = get_object_vars($user); // get_object_vars : stdClass -> array
      } else {
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


private function mail_notify_new_passwd($email, $login, $pass) {
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
"),FULL_URL,$email,$pass);

  $headers = 'From: '.MAIL_FROMNAME.' <'.MAIL_FROM.'>'. "\r\n" .
    'Reply-To: '.MAIL_FROM. "\r\n" .
    'Content-type: text/plain; charset=utf-8' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

  mail($to, $subject, $message, $headers);
}

private function mail_notify_new_account($email, $login, $pass) {
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
"),FULL_URL,$email,$pass);

  $headers = 'From: '.MAIL_FROMNAME.' <'.MAIL_FROM.'>'. "\r\n" .
    'Reply-To: '.MAIL_FROM. "\r\n" .
    'Content-type: text/plain; charset=utf-8' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

  mail($to, $subject, $message, $headers);
}



} // UsersController 

