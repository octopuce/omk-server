<?php

require_once MODULES . '/servers/lib.php';

class UsersController extends AController {
  static private $contacts_types = array('in_email', 'in_tel', 'out_email', 'out_tel');

  /*
   * Simple utilisateur : redirection vers "Mon compte"
   * Administrateur : liste des utilisateurs
   */
  public function indexAction() {
    if (!is_admin()) {
      header('Location: ' . BASE_URL . 'users/me');
      exit;
    }
    global $db;

    $st = $db->q('SELECT uid, login, email, ' .
		 'IF(enabled, :oui, :non) as enabled, ' .
		 'IF(admin, :oui, :non) as admin FROM users ' .
		 'ORDER BY admin, login',
		 array('oui' => "X", 'non' => ""));
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
      $users[$k]['actions'] = l(_("Modifier"), 'users/edit/' . $uid) .
	' | ' . l(_("Supprimer"), 'users/delete/' . $uid) .
	' | ' . l(_("Se connecter"), 'users/impersonate/' . $uid);
    }

    $headers = array(
		     'name' => _('Nom'),
		     'email' => _('E-mail'),
		     'enabled' => _('Activé'),
		     'admin' => _('Admin.'),
		     );
    Hooks::call('users_list_headers', $headers);
    $headers['actions'] = _('Actions');


    $this->render('list', array('users' => $users, 'headers' => $headers));
  }

  /*
   * Voir la fiche d'un utilisateur (pour les administrateurs uniquement)
   */
  public function showAction($params) {
    if (!is_admin())
      not_found();
    global $db;

    $info = trim($params[0]);
    if (is_numeric($info)) {
      $uid = intval($info);
      $user = $db->qone('SELECT uid, login, email, ' .
			'IF(enabled, :oui, :non) as enabled, ' .
			'IF(admin, :oui, :non) as admin ' .
			'FROM users WHERE uid = :uid',
			array('oui' => _("oui"), 'non' => _("non"), 'uid' => $uid));
    }
    else {
      $login = $info;
      $user = $db->qone('SELECT uid, login, email, ' .
			'IF(enabled, :oui, :non) as enabled, ' .
			'IF(admin, :oui, :non) as admin ' .
			'FROM users WHERE login = :login',
			array('oui' => _("oui"), 'non' => _("non"), 'login' => $login));
    }
    if ($user == false)
      not_found();

    $contacts = array();
    foreach (self::$contacts_types as $type)
      $contacts[$type] = $db->qlistone('SELECT contact FROM contacts WHERE uid = ? AND type = ?',
                                       array($uid, $type));

    $servers = Servers::getServersByUid($uid, 'listone');

    $this->render('show', array('user' => $user, 'contacts' => $contacts, 'servers' => $servers));
  }

  private static function verifyForm($data, $op) {
    $errors = array();
    if ($op != 'meedit') {
      if (empty($data['login']))
	$errors[] = _("Vous devez indiquer un identifiant.");
    }

    switch ($op) {
    case 'add':
      if (empty($data['pass']))
	$errors[] = _("Vous devez indiquer un mot de passe.");
      elseif ($data['pass'] != $data['pass_confirm'])
	$errors[] = _("Le mot de passe et la confirmation ne correspondent pas.");
      break;
    case 'edit':
    case 'meedit':
      if ($data['pass'] != $data['pass_confirm'])
	$errors[] = _("Le mot de passe et la confirmation ne correspondent pas.");
      break;
    }

    if (empty($data['email']))
      $errors[] = _("Vous devez indiquer une adresse e-mail.");
    return $errors;
  }

  /*
   * Ajouter un utilisateur (pour les administrateurs uniquement).
   */
  public function addAction() {

    // Réservé aux administrateurs
    if (!is_admin())
      not_found();

    // Les erreurs lors de la soumission d'un formulaire.
    $errors = array(); // Par défaut, pas d'erreurs.

    // Si le formulaire à été soumis...
    if (!empty($_POST)) {
      // On vérifie le formulaire pour y trouver d'éventuels erreurs.
      $errors = self::verifyForm($_POST, 'add');

      // ... Et qu'il n'y a pas d'erreurs : on ajoute l'utilisateur dans la base de données
      if (empty($errors)) {
        global $db;
        $db->q('INSERT INTO `users` (login, pass, email, enabled, admin) VALUES(?, ?, ?, ?, ?)',
               array(
                     $_POST['login'],
                     crypt($_POST['pass']),
                     $_POST['email'],
                     ($_POST['enabled'] == 'on') ? 1 : 0,
                     ($_POST['admin'] == 'on') ? 1 : 0,
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
		      );
	Hooks::call('users_add', $args);

	$contacts = array();
	foreach (self::$contacts_types as $type) {
	  // $db->q('DELETE FROM contacts WHERE uid = ? AND type = ?', array($uid, $type));
	  $contacts[$type] = explode("\n", trim($_POST['contacts_' . $type]));
	  sort($contacts[$type], SORT_STRING);
	  foreach ($contacts[$type] as $contact) {
	    if (trim($contact) != '') {
	      $db->q('INSERT INTO contacts (uid, type, contact) VALUES (?, ?, ?)',
		     array($uid, $type, $contact));
	    }
	  }
	}


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
   * Modifier un utilisateur (pour les administrateurs uniquement).
   */
  public function editAction($params) {

    // Réservé aux administrateurs
    if (!is_admin())
      not_found();

    // On cherche l'utilisateur à modifier dans la base de données
    global $db;
    $uid = intval($params[0]);
    $user = $db->qone('SELECT uid, login, email, enabled, admin, compta FROM users WHERE uid = ?', array($uid));

    // S'il n'existe pas : Page not found.
    if ($user == false)
      not_found();

    // Les erreurs lors de la soumission d'un formulaire.
    $errors = array(); // Par défaut, pas d'erreurs.

    // Si le formulaire à été soumis...
    if (!empty($_POST)) {
      // On vérifie le formulaire pour y trouver d'éventuels erreurs.
      $errors = self::verifyForm($_POST, 'edit');

      // ... Et qu'il n'y a pas d'erreurs : on modifie l'utilisateur dans la base de données
      if (empty($errors)) {
        $db->q('UPDATE users SET login = ?, email = ?, enabled = ?, admin = ?, compta = ? WHERE uid = ?',
               array(
                     $_POST['login'],
                     $_POST['email'],
                     ($_POST['enabled'] == 'on') ? 1 : 0,
                     ($_POST['admin'] == 'on') ? 1 : 0,
                     ($_POST['compta'] == 'on') ? 1 : 0,
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

	  $db->q('UPDATE users SET pass = ? WHERE uid = ?', array(crypt($_POST['pass']), $user->uid));

	  $args = array('uid' => $user->uid, 'login' => $user->login, 'pass' => $_POST['pass']);
	  Hooks::call('users_edit_pass', $args);
	}

	$contacts = array();
	foreach (self::$contacts_types as $type) {
	  // TODO: améliorer ce bouzin foeach... DELETE + INSERT...
	  $db->q('DELETE FROM contacts WHERE uid = ? AND type = ?', array($user->uid, $type));
	  $contacts[$type] = explode("\n", trim($_POST['contacts_' . $type]));
	  sort($contacts[$type], SORT_STRING);
	  foreach ($contacts[$type] as $contact) {
	    if (trim($contact) != '') {
	      $db->q('INSERT INTO contacts (uid, type, contact) VALUES (?, ?, ?)',
		     array($user->uid, $type, $contact));
	    }
	  }
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
      // Lors d'une modification, on pré-remplit avec les données de l'utilisateur en question.
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
   * Supprimer un utilisateur (pour les administrateurs uniquement).
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
      if ($_POST['op'] == _("Oui, supprimer")) {
	$db->q('DELETE FROM users WHERE uid = ?', array($uid));
	$args = array($user);
	Hooks::call('users_delete', $args);
        // Message + redirection
	header('Location: ' . BASE_URL . 'users?msg=' . sprintf(_("Suppression de l'utilisateur %s OK..."), $user->login));
	exit;
      }
      else {
        // Message + redirection
	header('Location: ' . BASE_URL . 'users/show/' . $uid . '?msg=' . _("Suppression annulée..."));
	exit;
      }
    }
    $this->render('delete', array('user' => $user));
  }

  /*
   * Imiter un utilisateur (pour les administrateurs uniquement).
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
   * Arrêter d'imiter un utilisateur (disponible pour tout le monde)
   * Cette fonction ne fait que supprimer un cookie.
   */
  public function stopimpersonateAction() {
    setcookie('impersonate', '0', 1, '/');
    header('Location: ' . BASE_URL .'users');
    exit;
  }

  /*
   * Page "Mon compte"
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
      // Les erreurs lors de la soumission d'un formulaire.
      $errors = array(); // Par défaut, pas d'erreurs.

      // Si le formulaire à été soumis...
      if (!empty($_POST)) {
	// On vérifie le formulaire pour y trouver d'éventuels erreurs.
	$errors = self::verifyForm($_POST, 'meedit');

	// ... Et qu'il n'y a pas d'erreurs : on modifie l'utilisateur dans la base de données
	if (empty($errors)) {
	  $db->q('UPDATE users SET email = ? WHERE uid = ?', array($_POST['email'], $user->uid));
	  $old_user = $user;
	  $user = $db->qone('SELECT uid, login, email, enabled, admin FROM users WHERE uid = ?', array($user->uid));
	  $args = array('old_user' => $old_user, 'new_user' => $user);
	  Hooks::call('users_edit', $args);

	  if (!empty($_POST['pass'])) {
	    $db->q('UPDATE users SET pass = ? WHERE uid = ?', array(crypt($_POST['pass']), $user->uid));
	    $args = array('uid' => $user->uid, 'login' => $user->login, 'pass' => $_POST['pass']);
	    Hooks::call('users_edit_pass', $args);
	  }

	  $contacts = array();
	  foreach (self::$contacts_types as $type) {
	    // TODO: améliorer ce bouzin foeach... DELETE + INSERT...
	    $db->q('DELETE FROM contacts WHERE uid = ? AND type = ?', array($user->uid, $type));
	    $contacts[$type] = explode("\n", trim($_POST['contacts_' . $type]));
	    sort($contacts[$type], SORT_STRING);
	    foreach ($contacts[$type] as $contact) {
	      if (trim($contact) != '') {
		$db->q('INSERT INTO contacts (uid, type, contact) VALUES (?, ?, ?)',
		       array($uid, $type, $contact));
	      }
	    }
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
	// Lors d'une modification, on pré-remplit avec les données de l'utilisateur en question.
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


}