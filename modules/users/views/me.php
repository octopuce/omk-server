<?php
$title = _("Mon compte");
$breadcrumb = array('' => $title);

$menu1 = html_list(_("Actions"), array(l(_("Modifier"), 'users/me/edit')));
$infos = array($user);
Hooks::call('users_me_links', $infos);
array_shift($infos);
$menu2 = html_list(_("Autres informations"), $infos);
$sidebar = $menu1 . $menu2;
require VIEWS . '/header.php';
?>

<table class="col"><tr><td><!-- colonne 1 -->

<h2><?php __("Informations générales"); ?></h2>
<dl>
  <dt><?php __("Identifiant"); ?></dt>
  <dd><?php echo $user->login; ?></dd>

  <dt><?php __("Adresse e-mail"); ?></dt>
  <dd><?php echo $user->email; ?></dd>
</dl>

<?php require __DIR__ . '/contacts.php'; ?>

<?php
   $infos = array($user);
   Hooks::call('users_me', $infos);
   array_shift($infos);
   echo implode("\n", $infos);
?>

</td><td><!-- colonne 2 -->

</td></tr></table>


<?php require VIEWS . '/footer.php'; ?>
