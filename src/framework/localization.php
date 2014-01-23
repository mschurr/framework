<?php

class Localization {
	protected static $language = 'en';
	protected static $cache = array();
	
	public static function choose($key)
	{
		if(isset(self::$cache[$key]))
			return self::$cache[$key];
		return null;
	}
	
	public static function make()
	{
		$args = func_get_args();		
		$key = $args[0];
		foreach($args as $k => $v)
			$args[$k] = (string) $v;
		$lang_string = self::choose($key);
		
		if(is_null($lang_string))
			return null;
		
		return call_user_func_array('sprintf', array_merge(array($lang_string),array_slice($args, 1, len($args)-1)));
	}
		
	public static function set($language)
	{
		self::$language = $language;
	}
	
	public static function load($path)
	{
		$path = trim(str_replace(".", "/", strtolower($path)),'/');
		$file = FILE_ROOT.'/localization/'.self::$language.'/'.$path.'.lang.php';
		
		if(file_exists($file)) {
			$strings = require($file);
			self::$cache = array_merge(self::$cache, $strings);
			return true;
		}
		return false;
	}
}