<?php

/*
	/app
	/config
	/crypt
	/document
	/models
	/output
	/redirect
	/response
	/url
	/connection
	/useragent
	/filesystem
	/route -- deal with returning views OR http error codes OR null OR Redirect
	
	lib{
		/auth, /auth-cas
	}
*/

class App
{
	protected static $request;
	protected static $response;
	protected static $errors = false;
	protected static $running = false;
	
	public static function init()
	{
		self::$request = new Request();
		self::$response = new Response();
	}
	
	public static function run()
	{
		self::$running = true;
		Cookies::init();
		Route::doRoute(self::$request, self::$response);		
		self::$response->send();
		self::$running = false;
	}
	
	public static function abort($http_code, $message=false)
	{
		self::$response->error($http_code, $message, true);
	}
	
	public static function displayErrors($mode=true)
	{
		self::$errors = $mode;
	}
	
	public static function getRequest()
	{
		return self::$request;
	}
	
	public static function getResponse()
	{
		return self::$response;
	}
	
	public static function getErrorMode()
	{
		return self::$errors;
	}
	
	public static function before($f) //closures, accept request, response params
	{
	}
	
	public static function after($f)
	{
	}
	
	public static function finish($f)
	{
	}
	
	public static function close($f)
	{
	}
	
	public static function shutdown($f)
	{
	}
	
	public static function down($f)
	{
	}
	
	public static function use_plugin($opt)
	{
	}
	
	public static function isRunning()
	{
		return self::$running;
	}
	
	protected static $db = null;
	public static function &database()
	{
		if(self::$db === null)
			self::$db = new Database();
		return self::$db;
	}
	
	public static function &db()
	{
		return self::database();
	}
	
	protected static $session = null;
	public static function &session()
	{
		if(self::$session === null)
			self::$session = new Session();
		return self::$session;
	}
	
	public static function &getDatabase()
	{
		return self::database();
	}
	
	public static function &getSession()
	{
		return self::session();
	}
}
App::init();
?>