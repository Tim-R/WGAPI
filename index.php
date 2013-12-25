<?php

include 'WGAPI.php';

$wgapi = new WGAPI("9cf7ac06042810532dbebcd3ac2dd192", "NA");

$wgapi->setMethod("GET");

echo "<pre>";
print_r(json_decode($wgapi->clanMemberInfo("1001651922"), true));
echo "</pre>";

echo "<pre>";
print_r(json_decode($wgapi->clanTop("current_season", array('name')), true));
echo "</pre>";
?>