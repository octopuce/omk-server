<?php

$infos = array($user);
?>

<?php require_once VIEWS . '/header.php'; ?>


<h2><?php __("Home page"); ?></h2>
<p><?php echo $msg; ?></p>


<?php
Hooks::call('index_indexview');						 
?>

<?php require_once VIEWS . '/footer.php'; ?>
