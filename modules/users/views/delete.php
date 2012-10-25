<?php
$title = $user->login;
$breadcrumb = array('users' => 'Utilisateurs', 'users/show/' . $user->uid => $user->login, '' => _("Supprimer"));

require VIEWS . '/header.php';
?>
<form action="" method="post">
  <p><?php echo sprintf(_("Êtes-vous certain de vouloir supprimer l'utilisateur « %s » ?"), $user->login); ?></p>
  <?php
     $informations = array($user);
     Hooks::call('users_delete_infos', $informations);
     array_shift($informations);
     echo implode($informations);
     ?>
  <p>
    <input type="submit" name="op" value="<?php __("Oui, supprimer"); ?>" />
    <input type="submit" name="op" value="<?php __("Non, ne rien faire"); ?>" onclick="javascript:history.go(-1); return false;" />
  </p>
</form>

<?php require VIEWS . '/footer.php'; ?>
