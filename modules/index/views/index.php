<?php

$infos = array($user);
?>

<?php require_once VIEWS . '/header.php'; ?>


<h2><?php __("Home page"); ?></h2>
<p><?php echo $msg; ?></p>


<dl>
  <dt><?php __("Identifiant"); ?></dt>
  <dd><?php echo $GLOBALS['me']['login']; ?></dd>

  <dt><?php __("Adresse e-mail"); ?></dt>
  <dd><?php echo $GLOBALS['me']['email']; ?></dd>
</dl>

<?php
Hooks::call('index_indexview');						 
?>

<?php require_once VIEWS . '/footer.php'; ?>
