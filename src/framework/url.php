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
	protected $_url = "";
	
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
			elseif(str_startswith($object, 'mailto:')) {
			}
			elseif(str_startswith($object, 'http://') || str_startswith($object, 'https://') || str_startswith($object, '//')) {
				$this->_url = $object;
				$this->_valid = true;
			}
			elseif(str_startswith($object, '/')) {
				$this->_url = $object;
				$this->_valid = true;
			}
			elseif(str_startswith($object, '.')) {
			}
			else { // Controller
			}
		}
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
	}
}

URL::init();