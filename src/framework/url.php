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
	
	public static function to($uri)
	{
		if(str_startswith($uri,"/")) {
			return self::$base.$uri;
		}
		if(str_startswith($uri,"//") || str_startswith($uri, "http://") || str_startswith($uri, "https://")) {
			return $uri;
		}
		return self::$base."/".$uri;		
	}
	
	public static function route($route)
	{
	}
	
	public static function action($controller)
	{
	}
	
	public static function cdn($path)
	{
		return self::$base.'/static/'.$path;
	}
	
	public static function asset($path)
	{
		return self::cdn($path);
	}
	
	public static function getCurrentDomain()
	{
		return self::$currDomain;
	}
}

URL::init();