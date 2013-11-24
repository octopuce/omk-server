<?php
$title = _('Last Media');
$breadcrumb = array('/status' => 'Status');
if (isset($_GET["user"])) 
  $breadcrumb[$_SERVER["REQUEST_URI"]]='Last Media of a USER';
else 
  $breadcrumb[$_SERVER["REQUEST_URI"]]='Last Media';

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
table.list th { text-align: left }
table.list col:first-child {background: #FF0}
</style>

<?php 
if ($message) {
  echo "<p><div class=\"info\">$message</div></p>\n";
}

$a_status=array(
		0 => _("To Be Downloaded"),
		1 => _("Downloaded"),
		2 => _("Metadata FAILED"),
		3 => _("Metadata OK"),
		4 => _("Expired"),
		);

if (empty($media)) {
  echo "<div class=\"error\">";
  __("Empty media list or no media found for your request");
  echo "</div>";
} else {
  echo "<table class=\"list\">\n";
  echo "<tr>";
  echo "<th>"._("Media ID")."</th>";
  echo "<th>"._("User")."</th>";
  echo "<th>"._("Remote Media")."</th>";
  echo "<th>"._("Created")."</th>";
  echo "<th>"._("Adapter")."</th>";
  echo "<th>"._("Status")."</th>";
  echo "<th>"._("Details")."</th>";
  echo "</tr>\n";

  foreach($media as $m) {
    echo "<tr>";
    echo "<td><a href=\"/status/job?mediaid=".$m["id"]."\">".$m["id"]."</a></td>";
    echo "<td><a href=\"/status/media?user=".$m["owner"]."\">".substr($m["email"],0,60)."</a></td>";
    echo "<td>".$m["remoteid"]."</td>";
    echo "<td>".date_my2fr($m["datecreate"],true)."</td>";
    echo "<td>".$m["adapter"]."</td>";
    echo "<td>".red_if($m["status"]==2 || $m["status"]==4,$a_status[$m["status"]])."</td>";
    echo "<td>";
    $t=@unserialize($m["metadata"]);
    if (count($t)) {
      if (isset($t["mime"]))
	echo "Mime: ".$t["mime"]." &nbsp; ";
      if (isset($t["file_size"]))
	echo "Size: ".format_size($t["file_size"])." &nbsp; ";
      if (isset($t["box"]))
	echo "Box: ".$t["box"]." &nbsp; ";
      if (isset($t["tracks"])) {
	// we are not in the "one 'other' track" case (jpeg, other system etc.)
	if (isset($t["tracks"][0]) && isset($t["tracks"][0]["type"]) && ( $t["tracks"][0]["type"]!="other" || count($t["tracks"])>1) ) {
	  echo "<br/> Tracks: ";
	  $i=0;
	  foreach($t["tracks"] as $track) {
	    echo " ".$i."[ ";
	    if (isset($track["type"]))
	      echo $track["type"]." &nbsp; ";
	    if (isset($track["codec"]))
	      echo $track["codec"]." &nbsp; ";
	    echo " ] &nbsp; ";
	    $i++;
	  }
	}
      }
    }
    echo "</td>";
    echo "</tr>";
  }
  echo "</table>\n";
}

?>

<?php require VIEWS . '/footer.php'; ?>
