<?php
class Request
{	
	// Calculate Information
	public function __construct()
	{	
		$this->_method = (isset($this->server['REQUEST_METHOD'])) ? $this->server['REQUEST_METHOD'] : 'GET';
		$this->_uri    = $this->server['REQUEST_URI'];
		$this->_path   = '/'.trim( (strpos($this->server['REQUEST_URI'],"?") === false ? $this->server['REQUEST_URI'] : substr($this->server['REQUEST_URI'], 0, strrpos($this->server['REQUEST_URI'],"?")))  ,'/\\');
		$this->_secure = (isset($this->server['https']) && $this->server['https'] == 'on');
		$this->_secure = $this->_secure || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
		$this->_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$this->_domain = (isset($this->server['SERVER_NAME']) ? $this->server['SERVER_NAME'] : $this->server['SERVER_ADDR']);
		$this->_timestamp = time();
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
		
			foreach($this->post as $k => $v) {
				if(is_array($v))
					$request .= $k.'='.print_r($v,true).EOL;
				else
					$request .= $k.'='.urlencode($v).''.EOL;
			}
			$request = substr($request,0,-1).PHP_EOL;
				
		return($request);
	}
	
	// Load On-Demand Data Registries
	protected $_headers;
	protected $_get;
	protected $_post;
	protected $_cookie;
	protected $_file;
	protected $_server;
	protected $_connection;
	protected $_client;
	protected $_method;
	protected $_path;
	protected $_uri;
	protected $_secure;
	protected $_domain;
	protected $_segment;
	protected $_timestamp;
	protected $_ip;
	
	public function __isset($k)
	{
		return ($this->__get($k) !== null);
	}
	
	public function __get($k)
	{
		if($k == 'method')
			return $this->_method;
		elseif($k == 'ip')
			return $this->_ip;
		elseif($k == 'timestamp')
			return $this->_timestamp;
		elseif($k == 'path')
			return $this->_path;
		elseif($k == 'uri' || $k == 'query')
			return $this->_uri;
		elseif($k == 'secure' || $k == 'ssl' || $k == 'encrypted' || $k == 'https')
			return $this->_secure;
		elseif($k == 'host' || $k == 'domain')
			return $this->_domain;
		elseif($k == 'segment' || $k == 'segments') {
			if(!$this->_segment instanceof RegistryObject) {
				$this->_segment = new RegistryObject(explode("/",trim($this->path,'/')));
			}
			return $this->_segment;
		}
		elseif($k == 'cookie' || $k == 'cookies') {
			if(!$this->_cookie instanceof RegistryObject) {
				$this->_cookie = new RegistryObject(Cookies::getAll());
			}
			return $this->_cookie;
		}
		elseif($k == 'get') {
			if(!$this->_get instanceof RegistryObject)
				$this->_get = new RegistryObject($_GET);
			return $this->_get;
		}
		elseif($k == 'post' || $k == 'data') {
			if(!$this->_post instanceof RegistryObject)
				$this->_post = new RegistryObject($_POST);
			return $this->_post;
		}
		elseif($k == 'file' || $k == 'files') {
			if(!$this->_file instanceof RegistryObject) {
				$files = array();
				if(len($_FILES) > 0) {
					
					foreach($_FILES as $name => $info) {
						if($info['error'] === UPLOAD_ERR_OK) {
							$files[$name] = with(new File($info['tmp_name']))->withUploadInfo($info);
						}
					}
				}
				$this->_file = new RegistryObject($files);
				
				unset($files);
			}
			return $this->_file;
		}
		elseif($k == 'server') {
			if(!$this->_server instanceof RegistryObject)
				$this->_server = new RegistryObject($_SERVER);
			return $this->_server;
		}
		elseif($k == 'headers' || $k == 'header') {
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
		elseif($k == 'session') {
			return App::getSession();
		}
		elseif($k == 'connection' || $k == 'conn') {
			if(!$this->_connection instanceof Connection)
				$this->_connection = new Connection();
			return $this->_connection;
		}
		elseif($k == 'client' || $k == 'browser' || $k == 'useragent') {
			if(!$this->_client instanceof UserAgent)
				$this->_client = new UserAgent($this->server['HTTP_USER_AGENT']);
			return $this->_client;
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