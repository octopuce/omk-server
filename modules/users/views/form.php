<?php
if ($op == 'edit') {
  $title = $login;
  $breadcrumb = array('users' => 'Utilisateurs', 'users/show/' . $login => $login, '' => _("Modifier"));
}
elseif ($op == 'meedit') {
  $title = _("Modification de mes informations");
  $breadcrumb = array('users/me' => _("Mon compte"), '' => _("Modifier"));
}
else {
  $title = _("Ajouter un utilisateur");
  $breadcrumb = array('users' => 'Utilisateurs', '' => _("Ajouter"));
}


require VIEWS . '/header.php';
?>

<?php if(count($errors) > 0): ?>
<div class="flash error">
  <?php if(count($errors) == 1): ?>
  <p><?php echo $errors[0]; ?></p>
  <?php elseif(count($errors) > 1): ?>
  <ul>
    <?php foreach($errors as $err): ?>
    <li><?php echo $err; ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>
<?php endif; ?>


<form action="" method="post">
  <fieldset style="width: 49%; float: left;">
    <legend><?php __("Identification"); ?></legend>
    <?php if ($op != 'meedit'): ?>
    <?php input('login', _("Identifiant :"), 'text', $data['login']); ?>
    <?php endif; ?>

    <?php if ($op == 'edit' || $op == 'meedit'): ?>
    <?php $pass_options = array('details' => _("Laissez vide pour ne pas modifier le mot de passe.")); ?>
    <?php endif; ?>
    <?php echo html_field('password', 'pass', _("Mot de passe :"), null, null, $pass_options); ?>
    <?php echo html_field('password', 'pass_confirm', _("Mot de passe (confirmation) :"), null, null, $pass_options); ?>
    <?php if ($op == 'edit'): ?>
    <?php echo html_field('checkbox', 'notify_new_passwd', _("Notifier par e-mail du changement de mot de passe")); ?>
    <br />
    <?php echo html_field('checkbox', 'notify_new_account', _("Notifier par e-mail de la création du compte")); ?>
    <?php endif; ?>
    <br />

    <?php input('email', _("Adresse e-mail :"), 'text', $data['email']); ?>
    <?php if ($op != 'meedit'): ?>
    <?php input('admin', _("Admin :"), 'checkbox', $data['admin']); ?>
    <?php input('compta', _("Compta :"), 'checkbox', $data['compta']); ?>
    <?php input('enabled', _("Activé :"), 'checkbox', $data['enabled']); ?>
    <?php endif; ?>
  </fieldset>

  <fieldset>
    <legend><?php __("Contacts techniques"); ?></legend>
    <label><?php __("Personnes autorisées à agir au nom du client"); ?></label><br />
<?php echo html_field('textarea', 'contacts_in_email', _("E-mail"), $data['contacts_in_email'], null, array('details' => _("Un contact par ligne"),'class' => 'list')); ?>
    <?php echo html_field('textarea', 'contacts_in_tel', _("Téléphone"), $data['contacts_in_tel'], null, array('details' => _("Un contact par ligne"),'class' => 'list')); ?>
    <label><?php __("Personnes à prévenir en cas d'urgence"); ?></label><br />
    <?php echo html_field('textarea', 'contacts_out_email', _("E-mail"), $data['contacts_out_email'], null, array('details' => _("Un contact par ligne"),'class' => 'list')); ?>
    <?php echo html_field('textarea', 'contacts_out_tel', _("Téléphone mobile (SMS)"), $data['contacts_out_tel'], null, array('details' => _("Un contact par ligne"),'class' => 'list')); ?>
  </fieldset>
  <p class="submit"><input type="submit" value="<?php
if ($op == 'add') __("Ajouter l'utilisateur");
if ($op == 'edit') __("Modifier l'utilisateur");
if ($op == 'meedit') __("Modifier mes informations");
?>" /> - <input type="button" onclick="javascript:history.go(-1)" value="<?php __("Annuler"); ?>" /></p>
</form>

<?php require VIEWS . '/footer.php'; ?>
