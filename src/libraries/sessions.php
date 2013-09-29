<?php
class Session implements ArrayAccess
{
	protected $driver = null;
	
	public function __construct($driver=null)
	{
		if(is_null($driver))
			$driver = Config::get('session.driver', 'cache');
		
		$class = 'Session_Driver_'.$driver;
		
		if(!class_exists($class)) {
			import('sessions-'.$driver);
		}
		
		$this->driver = new $class();
	}
	
	public function __get($key)
	{
		return $this->driver->get($key);
	}
	
	public function __set($key, $value)
	{
		return $this->driver->set($key, $value);
	}
	
	public function __isset($key)
	{
		return $this->driver->has($key);
	}
	
	public function __unset($key)
	{
		return $this->driver->forget($key);
	}
	
	public function offsetExists($key)
	{
		return $this->driver->has($key);
	}
	
	public function offsetGet($key)
	{
		return $this->driver->get($key);
	}
	
	public function offsetSet($key, $value)
	{
		return $this->driver->set($key, $value);
	}
	
	public function offsetUnset($key)
	{
		return $this->driver->forget($key);
	}
	
	public function __call($name, $args)
	{
		return call_user_func_array( array($this->driver, $name), $args );
	}
	
	public function __destruct()
	{
	}
	
	public function __toString()
	{
		return "<Session@".$this->driver->id().">";
	}
}

abstract class Session_Driver
{
	protected $_auth;
	protected $_prefix = 'SESSIOND::';
	protected $_cookie;
	protected $_key;
	
	public function auth() {
		return $this->_auth;
	}
	
	public function user()
	{
		return $this->_auth->user();
	}
	
	public function __construct() {
		$this->_key = Config::get('session.cryptkey', 'SessionCryptKey!');
		$this->_cookie = Config::get('session.cookie', 'sessiond');
		$this->_auth = new Auth($this);
		$this->load();
	}
	
	public function __destruct() {
		$this->unload();
	}
		
	/* Reserved Keywords: ->id, ->auth, ->user */
	public abstract function load();
	public abstract function unload();
	public abstract function get($key, $default=null);
	public abstract function set($key, $value);
	public abstract function has($key);
	public abstract function forget($key);
	public abstract function flash($key, $value=null);
	public abstract function keep($key);
	public abstract function id();
	public abstract function regenerate();
}
