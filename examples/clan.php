<?php
include '../WGAPI.php';

$wgapi = new WGAPI("9cf7ac06042810532dbebcd3ac2dd192", "NA");
$wgapi->setMethod("POST");
?>
<pre>
<? print_r(json_decode($wgapi->clanInfo("1000012402,1000002161,1000011108", "", array("abbreviation", "members_count", "name", "motto")), true)); //Get the abbreviation, number of members, name, and motto of 1000012402, 1000002161 & 1000011108 ?> 
</pre>