<?php
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
	protected /*array*/ $_parameters;
	protected /*array*/ $_variables;
	protected /*String*/ $_protocol;
	protected /*String*/ $_hostname;
	protected /*String*/ $_port;
	protected /*String*/ $_path;
	protected /*String*/ $_fragment;
	
	// account for domain in routing
	
	public function __construct($object, $action=null)
	{
		if($object instanceof File) {
		}
		
		if($object instanceof StorageFile) {
		}
		
		if($object instanceof Closure) {
		}
		
		if($object instanceof Controller) {
			if($action === null) {
			}
			else {
			}
		}
		
		if(is_string($object)) {
			if(str_startswith($object, 'file://')) {
			}
			elseif(str_startswith($object, 'http://') || str_startswith($object, 'https://') || str_startswith($object, '//')) {
			}
			elseif(str_startswith($object, '/')) {
			}
			else { // Controller
			}
		}
		
	}
	
	protected function valid()
	{
		return $this->_valid;
	}
	
	public function __toString()
	{
	}
	
	public function __get($param)
	{
		
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
	}
	
	public function appendQueryString($string)
	{
	}
	
	public function appendPathComponent($string)
	{
	}
	
	public function isInternal()
	{
	}
}

URL::init();