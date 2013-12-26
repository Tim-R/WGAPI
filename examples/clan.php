<?php
include '../WGAPI.php';
include 'apikey.php';

$wgapi = new WGAPI($apikey, "NA");
$wgapi->setMethod("POST");
?>
<pre>
<? print_r(json_decode($wgapi->clanInfo("1000012402,1000002161,1000011108", "", array("abbreviation", "members_count", "name", "motto")), true)); //Get the abbreviation, number of members, name, and motto of 1000012402, 1000002161 & 1000011108 ?> 
</pre>