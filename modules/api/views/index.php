<?php
$title = _('API');
$breadcrumb = array('' => 'API');
//$sidebar = '<p>→ ' . l(_("Create a user"), 'users/add') . '</p>';
require VIEWS . '/header.php';
?>

<p>
  You are at the root of an instance of the Open Mediakit Transcoder API.
</p>
<p>
  You should not call those pages directly, but instead use a compatible OpenMediakit Client.
</p>

<?php require VIEWS . '/footer.php'; ?>
