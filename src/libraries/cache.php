<?php
class Cache
{
	protected static $driver;
	protected static $driver_name;
	
	/* Object Constructor */
	public static function init($driver=null)
	{
		self::$driver_name = $driver;
		$class = 'Cache_Driver_'.self::$driver_name;
		
		if(!class_exists($class)) {
			import('cache-'.self::$driver_name);
		}
		
		self::$driver = new $class();
	}
	
	public static function __callStatic($name, $args) {
		return call_user_func_array(array(self, $name), $args);
	}
	
	/* @alias clear($key) */
	public static function forget($key)
	{
		return self::clear($key);
	}
	
	public static function gets($key, $closure); // gets a key (if it exists) or runs the closure and sets the key to the returned value. Returns the new value.
	public static function has($key);
	public static function forever($key, $value);
	public static function get($key, $default=null); // accept functions
	public static function put($key, $value, $minutes);
	public static function remember($key, $minutes, $fn_default); // returns value OR stores default value if doesnt exist and returns it
	public static function rememberForever($key, $fn_default);
	public static function getAll();
	public static function clear($key);
	public static function clearAll();
	public static function increment($key, $count=1);
	public static function decrement($key, $count=1);
	public static function section($section); // supports ->flush(), has(), forever(), get(), put(), remember(), rememberForever(), getAll(), clear(), clearAll(), increment(), decrement()
}

abstract class Cache_Driver
{	
	public function __construct()
	{
		$this->onLoad();
	}
	
	public function __destruct()
	{
		$this->onUnload();
	}
	
	
	
	/* Removes a key from the cache. */
	public abstract function forget($key);
	public function clear($key) { return $this->forget($key); }
	
	/* Automatically called when the driver is unloaded. */
	public abstract function onUnload();
	
	/* Automatically called when the driver is loaded. */
	public abstract function onLoad();
}