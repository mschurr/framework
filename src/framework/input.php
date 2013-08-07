<?php

class Input
{
	public static function register($array)
	{
		// sets post params from request class
	}
	
	public static function get($param, $default=null)
	{
		if(isset($_POST[$param]))
			return $_POST[$param];
		if(is_callable($default))
			return $default();
		return $default;
	}
	
	public static function get_safe($param, $default=null)
	{
		return escape_html(self::get($param, $default));
	}
	
	public static function validate($param, $closure)
	{
		return $closure(self::get($param));
	}
	
	public static function has($param)
	{
		return isset($_POST[$param]);
	}
	
	public static function all()
	{
		// Returns all input as kv array
	}
	
	public static function only() // $opt1, $opt2, ...
	{
		// Returns kv array containing only specified
	}
	
	public static function except() // $opt1, $opt2
	{
		// Returns kv array containing input except specified
	}
	
	public static function flash()
	{
	}
	
	public static function flashOnly()
	{
	}
	
	public static function flashExcept()
	{
	}
	
	public static function old($param, $default=null)
	{
	}
	
	public static function old_file()
	{
	}
	
	public static function file($file)
	{
		// returns a file obj w/ ->move(dest path, [new file name]), ->getRealPath(), ->getClientOriginalName(), getSize(), getMimeType()
	}
	
	public static function hasFile()
	{
	}
}

class UploadedFile
{
}
?>