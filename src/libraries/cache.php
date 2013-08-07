<?php
if(!function_exists('included'))
	die();

class Cache
{
	protected static $driver;
	
	/* Object Constructor */
	public static function init()
	{
	}
	
	/* @alias clear($key) */
	public static function forget($key)
	{
		return self::clear($key);
	}
	
	public static function init();
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

abstract class CacheDriver
{
}
?>