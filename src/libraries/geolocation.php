<?php

class Geolocation
{
	protected $method = '';
	protected $data = array();
	protected $lan = false;
	protected $lan_string = '';
	protected $ip;
	
	public function __construct($ip,$method='IPInfoDB')
	{
		// Handle Local Connection
		if($ip == '::1' || $ip == '127.0.0.1')
		{
			$this->lan = true;
			$this->lan_string = 'Local Machine';
		}
		
		if(substr($ip,0,8) == '192.168.')
		{
			$this->lan = true;
			$this->lan_string = 'Local Area Network';
		}
		
		if(substr($ip,0,3) == '10.')
		{
			$this->lan = true;
			$this->lan_string = 'Virtual Private Network';
		}
		
		if(substr($ip,0,6) == '5.140.')
		{
			$this->lan = true;
			$this->lan_string = 'Hamachi VPN';
		}
		
		if($this->lan)
			return true;
		
		// Handle Remote Connections
		if(!class_exists($method))
			return false;
			
		$this->method = $method;
		$this->ip = $ip;
		
		$data = call_user_func(array($method,'query'),$ip);
		
		if(is_array($data))
			$this->data = $data;
			
		return true;
	}
	
	// Returns a hash of the user's current location based on precision; Check against a previous hash to determine if location has changed.
	public function getIdentifier($precision = 2)
	{
		// Handle Local Area Connections
		if($this->lan)
			return (strlen($this->lan_string) > 0 ? md5($this->lan_string) : md5(uniqid()));
		
		// Precision 3 : City
		if($precision >= 3)
		{
			if(isset($this->data['country']) && isset($this->data['region']) && isset($this->data['city']))
				return md5($this->data['country'].'-'.$this->data['region'].'-'.$this->data['city']);
			else
				return md5(uniqid());
		}
		
		// Precision 2 : Region
		if($precision >= 2)
		{
			if(isset($this->data['country']) && isset($this->data['region']))
				return md5($this->data['country'].'-'.$this->data['region']);
			else
				return md5(uniqid());
		}
		
		// Precision 1 : Country
		if(isset($this->data['country']))
			return md5($this->data['country']);
		else
			return md5(uniqid());
	}
	
	// Returns the current location in human readable format.
	public function getLocation($precision = 2)
	{
		// Handle Local Area Connections
		if($this->lan)
			return (strlen($this->lan_string) > 0 ? $this->lan_string : null);
		
		// Precision 3 : City
		if($precision >= 3)
		{
			if(isset($this->data['country']) && isset($this->data['region']) && isset($this->data['city']))
				return $this->data['city'].', '.$this->data['region'].' ('.$this->data['country'].')';
			else
				return null;
		}
		
		// Precision 2 : Region
		if($precision >= 2)
		{
			if(isset($this->data['country']) && isset($this->data['region']))
				return $this->data['region'].' ('.$this->data['country'].')';
			else
				return null;
		}
		
		// Precision 1 : Country
		if(isset($this->data['country']))
			return $this->data['country'];
		else
			return null;
	}
	
	// ---------------------- Data Access Functions
	public function isLAN()
	{
		return $this->lan;
	}
	
	public function getLAN()
	{
		return $this->lan_string;
	}
	
	public function getIP()
	{
		return $this->ip;
	}
	
	public function getCity()
	{
		return (isset($this->data['city']) ? $this->data['city'] : null);
	}
	
	public function getCountry()
	{
		return (isset($this->data['country']) ? $this->data['country'] : null);
	}
	
	public function getCountryCode()
	{
		return (isset($this->data['country_short']) ? $this->data['country_short'] : null);
	}
	
	public function getLongitude()
	{
		return (isset($this->data['longitude']) ? $this->data['longitude'] : null);
	}
	
	public function getLatitude()
	{
		return (isset($this->data['latitude']) ? $this->data['latitude'] : null);
	}
	
	public function getRegion()
	{
		return (isset($this->data['region']) ? $this->data['region'] : null);
	}
	
	public function getZip()
	{
		return (isset($this->data['zip']) ? $this->data['zip'] : null);
	}
	
	public function getHost()
	{
		return (isset($this->data['hostname']) ? $this->data['hostname'] : null);
	}
	
	public function getProvider()
	{
		return (isset($this->data['provider']) ? $this->data['provider'] : null);
	}
	
	public function getTimezone()
	{
		return (isset($this->data['timezone']) ? $this->data['timezone'] : null);
	}
}

class IPInfoDB
{
	protected static $expiretime = 86400; // Cache Life Time in Seconds
	protected static $results = array();
	protected static $rewrite = array(
		'statusCode' => 'response_code',
		'statusMessage' => 'response_message',
		'countryCode' => 'country_short',
		'countryName' => 'country',
		'regionName' => 'region',
		'cityName' => 'city',
		'zipCode' => 'zip',
		'latitude' => 'latitude',
		'longitude' => 'longitude',
		'timeZone' => 'timezone'
	);
	
	public static function query($ip)
	{
		// Check PHP Cache
		if(isset(self::$results[$ip]))
			return self::$results[$ip];
		
		// Check DB Cache
		$dbc = self::cache($ip);
		if(is_array($dbc) && (time() - $dbc['timestamp']) < self::$expiretime)
		{
			self::$results[$ip] = $dbc;	
			return $dbc;
		}
		
		// Issue New Query
		$qry = self::load($ip);
		if(is_array($qry))
		{
			self::$results[$ip] = $qry;
			self::writecache($ip);
			return $qry;
		}
		
		// If query failed and cache exists, return cache.
		if(is_array($dbc))
		{
			self::$results[$ip] = $dbc;	
			return $dbc;
		}
		
		self::$results[$ip] = false;
		return false;
	}
	
	protected static function writecache($ip)
	{
		// Generate Query
		$keys = '';
		$vals = '';
		
		self::$results[$ip]['timestamp'] = time();
		
		foreach(self::$results[$ip] as $k => $v)
		{
			$keys .= '`'.Handler::getDatabase()->sterilize($k).'`,';
			$vals .= "'".Handler::getDatabase()->sterilize($v)."',";
		}
		
		$keys = substr($keys,0,-1);
		$vals = substr($vals,0,-1);
		
		$q = "REPLACE INTO `core_iplocation_cache` (".$keys.") VALUES (".$vals.");";
		Handler::getDatabase()->query($q);
	}
	
	protected static function cache($ip)
	{
		// Check Cache
		$db = Handler::getDatabase();
		$q = $db->query("SELECT * FROM `core_iplocation_cache` WHERE `ipaddress` = '".$db->sterilize($ip)."';");
		
		// Return (array)data or false
		if($q[0] > 0)
			return $q[1][0];
		return false;
	}
	
	protected static $last = 0;
	protected static function load($ip)
	{
		// Check API Key
		if(strlen(Handler::getConfig()->get('IPINFODB_APIKEY')) < 10)
			return false;
			
		// Limit Queries to 1 / sec
		if((time() - self::$last) < 1)
			sleep(1);
			
		// Issue Query
		$ip = @gethostbyname($ip);
		self::$last = time();
		$url = 'http://api.ipinfodb.com/v3/ip-city/?key='.Handler::getConfig()->get('IPINFODB_APIKEY').'&ip='.$ip.'&format=xml';
		
		if(preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $ip))
		{
			$xml = @file_get_contents($url);
			
			try{
				$response = @new SimpleXMLElement($xml);
				
				foreach($response as $field => $value) {
					
					if( (string) $field == 'statusCode' && (string) $value != 'OK')
						return false;
					
					if(isset(self::$rewrite[ (string) $field]))
						$field = self::$rewrite[ (string) $field];
					
					if($field == 'country' || $field == 'city' || $field == 'region')
						$result[ (string) $field ] = mb_convert_case((string) $value,MB_CASE_TITLE);
					else
						$result[ (string) $field ] = (string) $value;
				}

				return $result;
			}
			catch(Exception $e) {
				return false;
			}
		}
		return false;
	}
}