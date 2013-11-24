<?php
$title = _('Job Queue Status');
$breadcrumb = array('/status' => 'Status');
if (isset($_GET["user"])) 
  $breadcrumb[$_SERVER["REQUEST_URI"]]='Last Jobs of a USER';
else 
  if (isset($_GET["mediaid"])) 
    $breadcrumb[$_SERVER["REQUEST_URI"]]='Jobs of a MEDIA';
  else
    if (isset($_GET["last"])) 
      $breadcrumb[$_SERVER["REQUEST_URI"]]='Last 100 Job Queue ';
    else
      $breadcrumb[$_SERVER["REQUEST_URI"]]='Job Queue TODO';

require 'left.php';
require VIEWS . '/header.php';
?>
<style>
  td.col_enabled,
  td.col_admin,
  td.col_accounting,
  td.col_actions {
  text-align: center;
  }
table.list col:first-child {background: #FF0}
</style>

<?php 
if ($message) {
  echo "<p><div class=\"info\">$message</div></p>\n";
}

$a_status=array(
		0 => _("Todo"),
		1 => _("Processing"),
		2 => _("Error"),
		3 => _("Done"),
		);
$a_tasks=array(
	       1 => _("Download"),
	       2 => _("Do Metadata"),
	       3 => _("Send Metadata"),
	       4 => _("Do Transcode"),
	       5 => _("Send Transcode"),
	       );

if (empty($queue)) {
  echo "<div class=\"error\">";
  __("Empty queue or no requested queue job found");
  echo "</div>";
} else {
  echo "<table class=\"list\">\n";
  echo "<tr>";
  echo "<th>"._("Queue ID")."</th>";
  echo "<th>"._("User")."</th>";
  echo "<th>"._("Local Media")."</th>";
  echo "<th>"._("Remote Media")."</th>";
  echo "<th>"._("Job")."</th>";
  echo "<th>"._("Queued")."</th>";
  echo "<th>"._("Done")."</th>";
  echo "<th>"._("Status")."</th>";
  echo "<th>"._("Retry")."</th>";
  echo "<th>"._("Details")."</th>";
  echo "</tr>\n";

  foreach($queue as $q) {
    echo "<tr>";
    echo "<td>".$q["id"]."</td>";
    echo "<td><a href=\"/status/job?user=".$q["uid"]."\">".substr($q["email"],0,60)."</a></td>";
    echo "<td><a href=\"/status/job?mediaid=".$q["mediaid"]."\">".$q["mediaid"]."</a></td>";
    echo "<td>".$q["remoteid"]."</td>";
    echo "<td>".$a_tasks[$q["task"]]."</td>";
    echo "<td>".red_if( (time()-$q["ts"])>7200 && $q["datedone"]=="0000-00-00 00:00:00" , date_my2fr($q["datequeue"],true) );
    echo "</td>";
    if (substr($q["datedone"],0,10)==substr($q["datequeue"],0,10))
      $done=substr($q["datedone"],11,5);
    else
      $done=date_my2fr($q["datedone"],true);
    echo "<td>".(($q["datedone"]=="0000-00-00 00:00:00")?_("Not done yet"):$done);
    echo "</td>";
    echo "<td>".red_if($q["status"]==2,$a_status[$q["status"]])."</td>";
    echo "<td>".(($q["retry"]==1)?"<span class=\"error\">"._("Failed")."</span>":$q["retry"])."</td>";
    echo "<td>";
    $t=unserialize($q["params"]);
    foreach($t as $k=>$v) {
      if ($k=="url") {
	echo "$k: <a href=\"$v\">".substr($v,strrpos($v,"/"))."</a> &nbsp; ";
      } else 
	echo "$k: $v &nbsp; ";    
    }
    echo "</td>";
    echo "</tr>";
  }
  echo "</table>\n";
}

?>

<?php require VIEWS . '/footer.php'; ?>
