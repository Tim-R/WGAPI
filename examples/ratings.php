<?php
include '../WGAPI.php';
include 'apikey.php';

$wgapi = new WGAPI($apikey, "NA");
$wgapi->setMethod("POST");

echo "<pre>";
print_r(json_decode($wgapi->ratingsTop(API_WOT, "all", "battles_count", 0, NULL, array('battles_count')), true));
print_r(json_decode($wgapi->ratingsTop(API_WOWP, "all", "battles_count", 0, NULL, array('battles_count')), true));
echo "</pre>";
?>