<?php
define('API_WOT', 'wot');
define('API_WOWP', 'wowp');

/*
 * WG API Communication class
 * @Author Tim Roden <tim@timroden.ca>
 * 
 * Simple class to communicate with the Wargaming API
 * Various methods to get various data
 */ 
class WGAPI {		
	private $language = "en";
	private $server = "na";
	private $tld = "com";
	private $method = "GET";
	private $use_https = false;
	
	private $apikey = NULL;
	private $access_token = NULL;
	
	private $api_format_wgn = "api.worldoftanks.%s/wgn/%s/%s/";
	private $api_format_wot = "api.worldoftanks.%s/wot/%s/%s/";
	private $api_format_wowp = "api.worldofwarplanes.%s/wowp/%s/%s/";
		
	/* 
	 * Construct a new instance of the class
	 * @param String $apikey - Your application's API Key
	 * @param String $server - The server to make HTTP Requests to 
	 * 		Valid server strings: NA, RU, EU, SEA | ASIA
	 */
	function __construct($apikey, $server) {
		if(!function_exists('curl_version'))
			throw new Exception('WGAPI Class needs the cURL library to function. Cannot continue');
		
		if($apikey == NULL || !is_string($apikey)) 
			throw new InvalidArgumentException('apikey parameter may not be null, and must be a String');
		
		if($server == NULL) 
			throw new InvalidArgumentException('server parameter may not be null');
		
		$server = strtolower($server);
		
		$this->server = $server;
		
		if($server == "na") {
			$this->tld = "com";
		} elseif($server == "ru") {			
			$this->tld = "ru";
		} elseif($server == "eu") {
			$this->tld = "eu";
		} elseif($server == "sea" || $server == "asia") {
			$this->tld = "sea";
		} else {
			$this->server = "na";
			throw new InvalidArgumentException('invalid server specified');	
		}	
		
		$this->apikey = $apikey;
	}	
	
	/*
	 * Set the desired response language
	 * @param String $language - The language to use 	 
	 */
	function setLang($language) {
		if($language == NULL)
			throw new InvalidArgumentException('language parameter may not be null');
		
		$this->language = $language;
	}
	
	/* 
	 * Set the desired method for querying the API
	 * @param String $method - The method to use. Must be POST or GET
	 */
	function setMethod($method) {
		if(($method != "GET" && $method != "POST") || $method == NULL)
			throw new InvalidArgumentException('invalid method specified - must be POST or GET');
			
		$this->method = $method;		
	}
	
	/*
	 * When we send a request, should it use HTTPS?
	 * @param bool $use_https - Whether or not we should use HTTPS
	 */	 
	function setHttps($use_https) {
		$this->use_https = $use_https;
	}
	
	/*
	 * Set the access token used for private data
	 * @param String $access_token - Access token obtained from authentication. See developer docs for more information	
	 */
	function setAccessToken($access_token) {
		$this->access_token = $access_token;	
	}
	///////////////////////
	/* Account Functions */
	///////////////////////
	/*
	 * Get a partial list of players filtered by name
	 * @link https://na.wargaming.net/developers/api_reference/wot/account/list/
	 *
	 * @param API $api - The API to use. USe the constant API_WOT for the WoT API, or API_WOWP for the WoWP API.
	 * @param String $search - Initial characters of the clan name or tag used for search
	 * @param int $limit - Number of returned entries. Max value: 100 if value != int || value > 100, 100 is used
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString	 
	 */
	function accountList($api, $search, $limit = 100, $fields = array()) {
		if($limit > 100 || !is_int($limit)) 
			$limit = 100;
		
		if($search == NULL) 
			throw new InvalidArgumentException('search parameter may not be null');
				
		$request_data = array('search' => $search);
		
		if($limit != 100)
			$request_data['limit'] = $limit;
			
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;
			
		$uri = $this->api_format_wot;
		
		if($api == API_WOWP)
			$uri = $this->api_format_wowp;
			
		return $this->doRequest(sprintf($uri, $this->tld, "account", "list"), $request_data);
	}
	
	/*
	 * Get player details
	 * @link https://na.wargaming.net/developers/api_reference/wot/account/info/
	 *
	 * @param API $api - The API to use. USe the constant API_WOT for the WoT API, or API_WOWP for the WoWP API.
	 * @param mixed $account_id - A single member or a list of members
	 * @param Array $fields - List of response fields. See developer docs for more information
	 *
	 * @return jsonString	
	 */
	function accountInfo($api, $account_id, $fields = array()) {
		return $this->formStandardAccountRequest($api, "info", $account_id, $fields, true);	
	}
	
	/*
	 * Get details on a player's vehicles
	 * @link https://na.wargaming.net/developers/api_reference/wot/account/tanks/
	 *
	 * @param mixed $account_id - A single member or a list of members
	 * @param Array $fields - List of response fields. See developer docs for more information
	 *
	 * @return jsonString	
	 */
	function accountVehicles($account_id, $fields = array()) {
		return $this->formStandardAccountRequest(API_WOT, "tanks", $account_id, $fields, true);	
	}	
	
	////////////////////	
	/* Clan Functions */
	////////////////////	
	/*
	 * Get a partial list of clans filtered by name or tag
	 * @link https://na.wargaming.net/developers/api_reference/wot/clan/list/ 
	 *
	 * @param String $search - Initial characters of the clan name or tag used for search
	 * @param int $limit - Number of returned entries. Max value: 100 if value != int || value > 100, 100 is used
	 * @param String $order_by - Sorting. See developer docs for valid values
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString	 
	 */
	function clanList($search, $limit = 100, $order_by = "", $fields = array()) {
		if($limit > 100 || !is_int($limit)) 
			$limit = 100;
		
		if($search == NULL) 
			throw new InvalidArgumentException('search parameter may not be null');
				
		$request_data = array('search' => $search);
		
		if($limit != 100)
			$request_data['limit'] = $limit;
			
		if($order_by != "")
			$request_data['order_by'] = $order_by;
			
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;
			
		return $this->doRequest(sprintf($this->api_format_wgn, $this->tld, "clans", "list"), $request_data);
	}
	
	/*
	 * Get details of a clan
	 * @link https://na.wargaming.net/developers/api_reference/wot/clan/info/
	 *
	 * @param mixed $clan_id - A single clan or a list of clans
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */
	function clanInfo($clan_id, $fields = array()) {
		return $this->formStandardClanRequest("info", $clan_id, $fields);
	}
	
	/*
	 * Get a clan's battle list
	 * @link https://na.wargaming.net/developers/api_reference/wot/clan/battles/
	 *
	 * @param mixed $clan_id - A single clan or a list of clans
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */
	function clanBattles($clan_id, $fields = array()) {
		return $this->formStandardClanRequest("battles", $clan_id, $fields);
	}
	
	/* 
	 * Get top 100 clans sorted by rating
	 * @link https://na.wargaming.net/developers/api_reference/wot/clan/top/
	 *
	 * @param String $time - Time delta. See developer docs for more information
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */
	function clanTop($time = "current_season", $fields = array()) {
		$request_data = array();
		
		if($time != "current_season") 
			$request_data['time'] = $time;
			
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;
			
		return $this->doRequest(sprintf($this->api_format_wot, $this->tld, "clan", "top"), $request_data);	
	}
	
	/*
	 * Get a clan's province list
	 * @link https://na.wargaming.net/developers/api_reference/wot/clan/provinces/
	 *
	 * @param mixed $clan_id - A single clan or a list of clans
	 * @param Array $fields - List of response fields. See developer docs for more information		
	 *
	 * @return jsonString
	 */
	function clanProvinces($clan_id, $fields = array()) {
		return $this->formStandardClanRequest("provinces", $clan_id, $fields);
	}
	
	/*
	 * Get a clan's victory points
	 * @link https://na.wargaming.net/developers/api_reference/wot/clan/victorypoints/
	 *
	 * @param mixed $clan_id - A single clan or a list of clans
	 * @param Array $fields - List of response fields. See developer docs for more information		 	
	 *
	 * @return jsonString
	 */
	function clanVictoryPoints($clan_id, $fields = array()) {
		return $this->formStandardClanRequest("victorypoints", $clan_id, $fields);
	}	
	
	/*
	 * Get a log of a clan's victory points
	 * @link https://na.wargaming.net/developers/api_reference/wot/clan/victorypointshistory/
	 *
	 * @param mixed $clan_id - A single clan or a list of clans
	 * @param int $limit - Number of results, between 20 and 100
	 * @param time $since - Stage start time
	 * @param time $until - Stage end time
	 * @param int $offset - Offset
	 * @param Array $fields - List of response fields. See developer docs for more information		 	
	 *
	 * @return jsonString
	 */
	function clanVictoryPointsHistory($clan_id, $limit = 0, $since = 0, $until = 0, $offset = NULL, $fields = array()) {
		if($clan_id == NULL) 
			throw new InvalidArgumentException('clan_id parameter may not be null');
			
		$request_data = array('clan_id' => $clan_id);
		
		if($limit != 0) {
			if($limit < 20) {
				$limit = 20;
			} elseif($limit > 100) {
				$limit = 100;
			}
			$request_data['limit'] = $limit;	
		}
		
		if($since != 0)
			$request_data['since'] = $since;
			
		if($until != 0) 
			$request_data['until'] = $until;
			
		if($offset != NULL)
			$request_data['offset'] = $offset;
			
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;
			
		return $this->doRequest(sprintf($this->api_format_wot, $this->tld, "clan", "victorypointshistory"), $request_data);		
	}
	
	/*
	 * Get member's clan information
	 * @link https://na.wargaming.net/developers/api_reference/wot/clan/membersinfo/
	 *
	 * @param mixed $member_id - A single member or a list of members
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */	
	function clanMemberInfo($member_id, $fields = array()) {
		if($member_id == NULL)
			throw new InvalidArgumentException('member_id parameter may not be null');
			
		$request_data = array('member_id' => $member_id);
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;
			
		return $this->doRequest(sprintf($this->api_format_wgn, $this->tld, "clans", "membersinfo"), $request_data);	
	}
	
	////////////////////////////
	/* Encyclopedia Functions */
	////////////////////////////	
	//////////////////////////////
	/* Players rating functions */	
	//////////////////////////////	
	/*
	 * Get a dictionary of rating types with details
	 * @link https://na.wargaming.net/developers/api_reference/wot/ratings/types/
	 *
	 * @param API $api - The API to use. USe the constant API_WOT for the WoT API, or API_WOWP for the WoWP API.
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */
	function ratingTypes($api, $fields = array()) {
		$request_data = array();
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;	
			
		$uri = $this->api_format_wot;
		
		if($api == API_WOWP)
			$uri = $this->api_format_wowp;
			
		return $this->doRequest(sprintf($uri, $this->tld, "ratings", "types"), $request_data);
	}
	
	/*
	 * Returns player ratings by specified IDs
	 * @link https://na.wargaming.net/developers/api_reference/wot/ratings/accounts/
	 *
	 * @param API $api - The API to use. USe the constant API_WOT for the WoT API, or API_WOWP for the WoWP API.
	 * @param mixed $account_id - A single account or a list of accounts
	 * @param String $type - Rating type. See developer docs for valid values.
	 * @param time $date - Date in UNIX time or ISO-8601 format
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */
	function ratingsAccount($api, $account_id, $type, $date = 0, $fields = array()) {
		if($account_id == NULL)
			throw new InvalidArgumentException('account_id parameter may not be null');
			
		if($type == NULL)
			throw new InvalidArgumentException('type parameter may not be null');
		
		
		$request_data = array('account_id' => $account_id, 'type' => $type);
		
		if($date != 0) 
			$request_data['date'] = $date;
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;	
			
		$uri = $this->api_format_wot;
		
		if($api == API_WOWP)
			$uri = $this->api_format_wowp;
			
		return $this->doRequest(sprintf($uri, $this->tld, "ratings", "accounts"), $request_data);			
	}
	
	/*
	 * Returns list of adjacent positions in specified rating
	 * @link https://na.wargaming.net/developers/api_reference/wot/ratings/neighbors/
	 *
	 * @param API $api - The API to use. USe the constant API_WOT for the WoT API, or API_WOWP for the WoWP API.
	 * @param mixed $account_id - A single account or a list of accounts
	 * @param String $type - Rating type. See developer docs for valid values.
	 * @param String $rank_field - Which field to get top players for
	 * @param time $date - Date in UNIX time or ISO-8601 format
	 * @param int $limit - Max. number of adjacent positions in rating
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */
	function ratingsNeighbors($api, $account_id, $type, $rank_field, $date = 0, $limit = NULL, $fields = array()) {
		if($account_id == NULL)
			throw new InvalidArgumentException('account_id parameter may not be null');
			
		if($type == NULL)
			throw new InvalidArgumentException('type parameter may not be null');
			
		if($rank_field == NULL)
			throw new InvalidArgumentException('rank_field parameter may not be null');		
		
		$request_data = array('account_id' => $account_id, 'type' => $type, 'rank_field' => $rank_field);
		
		if($date != 0) 
			$request_data['date'] = $date;
			
		if($limit != NULL)
			$request_data['limit'] = $limit;
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;	
		
		$uri = $this->api_format_wot;
		
		if($api == API_WOWP)
			$uri = $this->api_format_wowp;
			
		return $this->doRequest(sprintf($uri, $this->tld, "ratings", "neighbors"), $request_data);			
	}
	
	/*
	 * Returns top players by specified parameter
	 * @link https://na.wargaming.net/developers/api_reference/wot/ratings/top/
	 *
	 * @param API $api - The API to use. USe the constant API_WOT for the WoT API, or API_WOWP for the WoWP API.
	 * @param String $type - Rating type. See developer docs for valid values.
	 * @param String $rank_field - Which field to get top players for
	 * @param time $date - Date in UNIX time or ISO-8601 format
	 * @param int $limit - Max. number of adjacent positions in rating
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */	
	function ratingsTop($api, $type, $rank_field, $date = 0, $limit = NULL, $fields = array()) {
		if($type == NULL)
			throw new InvalidArgumentException('type parameter may not be null');
			
		if($rank_field == NULL)
			throw new InvalidArgumentException('rank_field parameter may not be null');	
			
		$request_data = array('type' => $type, 'rank_field' => $rank_field);
				
		if($date != 0) 
			$request_data['date'] = $date;
			
		if($limit != NULL)
			$request_data['limit'] = $limit;
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;	
			
		$uri = $this->api_format_wot;
		
		if($api == API_WOWP)
			$uri = $this->api_format_wowp;
			
		return $this->doRequest(sprintf($uri, $this->tld, "ratings", "top"), $request_data);	
	}
	
	/*
	 * Returns dates with available ratings data
	 * @link https://na.wargaming.net/developers/api_reference/wot/ratings/dates/
	 *
	 * @param API $api - The API to use. USe the constant API_WOT for the WoT API, or API_WOWP for the WoWP API.
	 * @param String $type - Rating type. See developer docs for valid values.
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 *
	 * @return jsonString
	 */		
	function ratingDates($api, $type, $fields = array()) {
		if($type == NULL)
			throw new InvalidArgumentException('type parameter may not be null');
		
		$request_data = array('type' => $type);
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;	
			
		$uri = $this->api_format_wot;
		
		if($api == API_WOWP)
			$uri = $this->api_format_wowp;
			
		return $this->doRequest(sprintf($uri, $this->tld, "ratings", "dates"), $request_data);
	}
	
	////////////////////////////////
	/* Request builders / helpers */
	////////////////////////////////
	private function formStandardAccountRequest($api, $function, $account_id, $fields) {
		if($account_id == NULL) 
			throw new InvalidArgumentException('account_id parameter may not be null');
				
		$request_data = array('account_id' => $account_id);
		
		if($this->access_token != NULL)
			$request_data['access_token'] = $this->access_token;
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;		
		
		$uri = $this->api_format_wot;
		
		if($api == API_WOWP) 
			$url = $this->api_format_wowp;
			
		return $this->doRequest(sprintf($uri, $this->tld, "account", $function), $request_data);
	}	
	
	private function formStandardClanRequest($function, $clan_id, $fields) {
		if($clan_id == NULL) 
			throw new InvalidArgumentException('clan_id parameter may not be null');
			
		$request_data = array('clan_id' => $clan_id);
		
		if($this->access_token != NULL)
			$request_data['access_token'] = $this->access_token;
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;
			
	        // wot api for clans is partial deprecated...
	        if( $function === 'info' ) 
            		return $this->doRequest(sprintf($this->api_format_wgn, $this->tld, "clans", $function), $request_data);	
        	else 
            		return $this->doRequest(sprintf($this->api_format_wot, $this->tld, "clan", $function), $request_data);	
	}	
		
	///////////////////////////////////
	/* Web request related functions */
	///////////////////////////////////	
	private function doRequest($url, $data, $force_https = false) {
		$this->use_https || $force_https ? $prefix = "https://" : $prefix = "http://";
		
		$data['application_id'] = $this->apikey; //Add our API key to the request
		$data['language'] = $this->language; //Add our language to the request
		
		if(isset($data['fields'])) {
			$data['fields'] = implode(",", $data['fields']); //Format our fields so that the API actually understands them
		}
		
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); //Follow redirects, if any
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);	
		curl_setopt($curl, CURLOPT_HEADER, false); //We don't need the header
		curl_setopt($curl, CURLOPT_ENCODING, ""); // Accept any encoding
		
		$parameters = http_build_query($data);
		
		if($this->method == "GET") {		
			curl_setopt($curl, CURLOPT_URL, "{$prefix}{$url}?{$parameters}"); //Tack params onto the end of the URL, as per GET
		} elseif($this->method == "POST") {
			curl_setopt($curl, CURLOPT_URL, "{$prefix}{$url}");
			curl_setopt($curl, CURLOPT_POST, true); //We're POSTing the data
			curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters); //Set the data in the post fields
		}		
		
		if($this->use_https) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		}
		
		$response = curl_exec($curl); //Execute our request
		curl_close($curl);	
		
		if(!$response) 
			throw new Exception('Error querying API. Error: ' . curl_error($curl) . ' - Code: ' . curl_errno($curl));	
		
		return $response;
	}
}
?>
