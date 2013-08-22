<?php

class Session
{
	protected static $driver;
	protected static $driver_name;
	protected static $loaded = false;
	
	/* Object Constructor */
	public static function init($driver=null, $config=array())
	{
		self::$driver_name = $driver;
		$class = 'Session_Driver_'.self::$driver_name;
		
		if(!class_exists($class)) {
			import('session-'.self::$driver_name);
		}
		
		self::$driver = new $class($config);
	}
	
	public static function __callStatic($name, $args) {
		if(!self::$loaded)
			self::init(Config::get('session.driver', 'database'), array());
		
		if(is_callable(array(self::$driver, $name)))
			return call_user_func_array(array(self::$driver, $name), $args);
		throw new Exception("The method 'Session::".$name."' does not exist for driver '".self::$driver_name."'.");
	}
}

abstract class Session_Driver
{
	public function __construct($config)
	{
		$this->onLoad();
	}
	
	public function __destruct()
	{
		$this->onUnload();
	}
	
	// Abstract Methods (implemented by driver)
	public abstract function onLoad();	
	public abstract function onUnload();	
	public abstract function put($key, $value); // accept anything (serialize)
	public abstract function get($key, $default=null); // $default = anything (incl. closure)
	public abstract function has($key);
	public abstract function forget($key);
	public abstract function flush();
	public abstract function flash($k, $v=null); // $k = string or array
	public abstract function keep($k); // $k = string or array
}
