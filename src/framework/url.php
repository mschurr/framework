<?php
/****************************************
 * URL Wrapper Library
 ****************************************
 
 This class provides a method of wrapping URLs as objects.
 
*/

class URL
{
	protected static $base;
	protected static $currDomain;
	
	public static function init()
	{
		self::$currDomain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
		self::$base = (isset($_SERVER['https']) && $_SERVER['https'] == 'on' ? 'https' : 'http');
		self::$base .= '://'.(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR']);
		self::$base .= ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ? ':'.$_SERVER['SERVER_PORT'] : '');
	}
	
	public static function getCurrentDomain()
	{
		return self::$currDomain;
	}
	
	public static function to($object, $action=null)
	{
		// If the object is already a URL, just return it.
		if($object instanceof URL) {
			return $object;
		}
		
		// For views and layouts, return a URL to the active controller.
		if($object instanceof BladeView || $object instanceof BladeLayout) {
			return self::to($object->getAssociatedController(), $action);
		}
		
		// For controllers, pass in the controller reference string.
		if($object instanceof Controller) {
			if($action !== null)
				return self::to($object->__getRoutingReference().'@'.$action);
			return self::to($object->__getRoutingReference());
		}
		
		// Otherwise, instantiate a new URL object.
		$url = new URL($object, $action);
		
		if($url->valid())
			return $url;
		return null;
	}
	
	public static function asset($path)
	{
		return new URL('/static/'.$path);
	}
	
	// ----------------------------------------------------------------------
	
	protected $_valid = false;
	protected $_url = "";
	
	public function __construct($object, $action=null)
	{
		if($object instanceof File) {
		}
		
		if($object instanceof StorageFile) {
		}
		
		if($object instanceof Closure) {
		}
		
		
		if(is_string($object)) {
			// File URLs
			if(str_startswith($object, 'file://')) {
			}
			// Emails
			elseif(str_startswith($object, 'mailto:')) {
			}
			// Absolute Paths
			elseif(str_contains($object, '://') || str_startswith($object, '//')) {
				$this->_url = $object;
				$this->_valid = true;
			}
			// Relative to Root Path
			elseif(str_startswith($object, '/')) {
				$this->_url = $object;
				$this->_valid = true;
			}
			// Relative to Current Path
			elseif(str_startswith($object, '.')) {
			}
			// Controller String
			else { 
				$this->__determineUrlForControllerString($object);			
			}
		}
	}
	
	protected function __determineUrlForControllerString($controller)
	{
		$possible = Route::__getRoutingOptionsForTarget($object);
		/*
			(IF ACTION SPECIFIED) TRY TO MATCH THE EXACT TARGET. IF SUCCESSFUL, THAT'S THE URL.
			TRY TO MATCH THE TARGET WITHOUT @METHOD FOR GET REQUESTS. IF SUCCESSFUL, THAT'S THE URL.
			TRY TO MATCH THE TARGET WITH ANY @METHOD FOR GET REQUESTS. IF SUCCESSFUL, THAT'S THE URL.
			TRY TO MATCH THE TARGET WITHOUT @METHOD FOR ANY REQUEST. IF SUCCESSFUL, THAT'S THE URL.
			TRY TO MATCH THE TARGET WITH ANY @METHOD FOR ANY REQUEST. IF SUCCESSFUL, THAT'S THE URL.
			OTHERWISE: NO URL FOUND
			
			keep track of secure, domain, uri; uris may have routing variables
		*/
	}
	
	public function valid()
	{
		return $this->_valid;
	}
	
	public function __toString()
	{
		return $this->_url;
	}
	
	public function __get($param)
	{
		if($param == 'protocol') {
			$parse = parse_url($this->_url);
			return $parse['scheme'];
		}
		if($param == 'hostname') {
			$parse = parse_url($this->_url);
			return $parse['host'];
		}
		//protected /*array*/ $_parameters;
		//protected /*array*/ $_variables;
		//protected /*String*/ $_protocol;
		//protected /*String*/ $_hostname;
		//protected /*String*/ $_port;
		//protected /*String*/ $_path;
		//protected /*String*/ $_fragment;
		//querystring
	}
	
	public function withParameters(array $parameters) // GET Parameters
	{
	}
	
	public function withRoutingVariables(array $options) // These are the routing variables.
	{
	}
	
	public function withFragment($string)
	{
	}
	
	public function makeRelativeTo(URL $url)
	{
		if(str_startswith($this->_url, './') || str_startswith($this->_url, '../')) {
		}
		if(str_startswith($this->_url, '/')) {
			$this->_url = $url->protocol.'://'.$url->hostname.$this->_url;
			$this->_valid = true;
		}
	}
	
	public function appendQueryString($string)
	{
	}
	
	public function appendPathComponent($string)
	{
	}
	
	public function equals(URL $url)
	{
		return $url->__toString() === $this->__toString();
	}
	
	public function isInternal()
	{
		return true;
	}
}

URL::init();