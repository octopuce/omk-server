<?php
$title = $user->login;
$breadcrumb = array('users' => 'Utilisateurs', '' => $user->login);

$menu1 = html_list(_("Actions"),
		   array(l(_("Modifier"), 'users/edit/' . $user->uid),
			 l(_("Supprimer"), 'users/delete/' . $user->uid),
			 l(_("Se connecter"), 'users/impersonate/' . $user->uid))
		   );
$infos = array($user);
Hooks::call('users_show_links', $infos);
array_shift($infos);
$menu2 = html_list(_("Autres informations"), $infos);


if (!empty($servers)) {
  $links = array();
  foreach($servers as $server)
    $links[] = l($server, 'servers/show/' . $server);
  $menu3 = html_list(_("Informations des serveurs"), $links);
}


$sidebar = $menu1 . $menu2 . $menu3;

require VIEWS . '/header.php';
?>

<h2><?php __("Informations générales"); ?></h2>
<dl>
  <dt><?php __("Identifiant"); ?></dt>
  <dd><?php echo $user->login; ?></dd>

  <dt><?php __("Adresse e-mail"); ?></dt>
  <dd><?php echo $user->email; ?></dd>

  <dt><?php __("Actif ?"); ?></dt>
  <dd><?php echo $user->enabled; ?></dd>

  <dt><?php __("Administrateur ?"); ?></dt>
  <dd><?php echo $user->admin; ?></dd>
</dl>

<?php require __DIR__ . '/contacts.php'; ?>

<?php
   $infos = array($user);
   Hooks::call('users_show', $infos);
   array_shift($infos);
   echo implode("\n", $infos);
?>

<?php require VIEWS . '/footer.php'; ?>
