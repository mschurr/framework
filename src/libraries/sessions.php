<?php

class Session
{
	public static function init()
	{
	}
	
	public static function put($key, $value)
	{
	}
	
	public static function get($key, $default=null) // accept closures
	{
	}
	
	public static function has($key)
	{
	}
	
	public static function forget($key)
	{
	}
	
	public static function flush()
	{
	}
	
	public static function flash($k, $v=null) // accept key as a kv array too
	{
	}
	
	public static function keep($k) // accept a string or array of keys
	{
	}
}
