<?php

include 'WGAPI.php';

$wgapi = new WGAPI("9cf7ac06042810532dbebcd3ac2dd192", "NA");

$wgapi->setMethod("GET");

echo "<pre>";
print_r(json_decode($wgapi->clanInfo("1000012402", "726ddd4ab4e69fdd76fcaf6431592197036d42de"), true));
echo "</pre>";
?>