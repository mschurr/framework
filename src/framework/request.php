<?php
class Request
{
	public $method;
	public $path;
	public $url;
	public $secure = false;
	public $ajax = false; // todo
	public $domain;
	
	// todo: $connection = instanceof <Connection>
	// todo: implement user agent parameters: user_agent, isRobot, isMobile, isBrowser, languages, encodings, accept mimes, ...
	
	
	
	public function __construct()
	{	
		if(isset($this->server['REQUEST_METHOD'])) {
			$this->method = $this->server['REQUEST_METHOD'];
		}
		else {
			$this->method = 'GET';
		}
		
		
		
		$this->url = URL::to($this->server['REQUEST_URI']);
		$path = $this->server['REQUEST_URI'];
		$path = (strpos($path,"?") === false ? $path : substr($path,0,strpos($path,"?")));
		
		while(substr($path,-1,1) == '/' && strlen($path) > 1) {
			$path = substr($path,0,-1);
		}
		
		$this->path = $path;
		$this->domain = (isset($this->server['SERVER_NAME']) ? $this->server['SERVER_NAME'] : $this->server['SERVER_ADDR']);
		$this->secure = (isset($this->server['https']) && $this->server['https'] == 'on');
	}
	
	public function segment($idx)
	{
		// TODO
	}
	
	// Returns whether or not the request matches a route pattern.
	public function is($route_pattern)
	{
		return is_array(Route::is($route_pattern));
	}
	
	// Convert to HTTP/1.X Request String
	public function __toString()
	{
		$request = $this->server['SERVER_PROTOCOL']." ".$this->method." ".$this->server['REQUEST_URI'].PHP_EOL;
		foreach($this->headers as $k => $v) {
			$request .= $k.": ".$v.PHP_EOL;
		}
		if(isset($this->post)) {
			foreach($this->post as $k => $v) {
				$request .= $k.'='.urlencode($v).''.EOL;
			}
			$request = substr($request,0,-1).PHP_EOL;
		}
				
		return($request);
	}
	
	// Load On-Demand Data Registries
	protected $_headers;
	protected $_get;
	protected $_post;
	protected $_cookie;
	protected $_file;
	protected $_server;
	
	public function __get($k)
	{
		if($k == 'cookie' || $k == 'cookies') {
			if(!$this->_cookie instanceof RegistryObject)
				$this->_cookie = new RegistryObject($_COOKIE); // todo: convert to <Cookie>
			return $this->_cookie;
		}
		elseif($k == 'get') {
			if(!$this->_get instanceof RegistryObject)
				$this->_get = new RegistryObject($_GET);
			return $this->_get;
		}
		elseif($k == 'post') {
			if(!$this->_post instanceof RegistryObject)
				$this->_post = new RegistryObject($_POST);
			return $this->_post;
		}
		elseif($k == 'file' || $k == 'files') {
			if(!$this->_file instanceof RegistryObject)
				$this->_file = new RegistryObject($_FILES); // todo: <convert to FileSystem.File> $_FILES = {name => {name, type, size (bytes), tmp_name, error}}
			return $this->_file;
		}
		elseif($k == 'server') {
			if(!$this->_server instanceof RegistryObject)
				$this->_server = new RegistryObject($_SERVER);
			return $this->_server;
		}
		elseif($k == 'headers') {
			if(!$this->_headers instanceof RegistryObject) {
				if(function_exists('getallheaders')) {
					$this->_headers = new RegistryObject(getallheaders());
				}
				else {
					$this->_headers = new RegistryObject(array());
				}
			}
			return $this->_headers;
		}
		else {
			return null;
		}
	}
	
	public function __call($name, $args)
	{
		if(!is_callable(array($this->__get($name), '__invoke')))
			return null;
		return call_user_func_array(array($this->__get($name), '__invoke'), $args);
	}
}
?>