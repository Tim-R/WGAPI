<?php
include '../WGAPI.php';
include 'apikey.php';

$wgapi = new WGAPI($apikey, "NA");
$wgapi->setMethod("POST");

$accounts = json_decode($wgapi->accountList(API_WOT, "timroden"), true); //search for accounts matching "timroden"

if($accounts['count'] > 0) { //Have at least one result
	$account_id = $accounts['data'][0]['account_id']; //Get the first account id matching the query
	
	$account = $wgapi->accountInfo(API_WOT, $account_id); //Get account information based on ID
	
	echo "<pre>";
	print_r(json_decode($account, true)); //Print the information
	echo "</pre>";	
} else {
	echo "No accounts found!";
}
?>