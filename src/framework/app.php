<?php

/*
	/app
	/config
	/document
	/filesystem/File
	/models
	/output
	/response
	/storage
	/url
	/useragent
	userservice-db
	groupservice-db
	
	initialize, errors (currently always shown)
	routing through /index.php for HHVM
	allow something like: Route::get('/', fn) Route::post('/', fn)
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
		Cookies::init();
		self::$running = true;
		Route::doRoute(self::$request, self::$response);
		Redirect::apply(self::$response);		
		self::$response->send();
		self::$running = false;
	}
	
	public static function abort($http_code, $message=false)
	{
		//Log::write('Application aborted with code '.$http_code.' and message '.$message.'.');
		//Route::doRouteError(self::$request, self::$response, $http_code);
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
	
	public static function storage()
	{
		return null;
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
		if(self::$session === null) {
			self::$session = new Session();
		}
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
	
	protected static $groupService;
	public static function &getGroupService()
	{
		if(self::$groupService == null) {
			$class = 'Group_Service_Provider_'.Config::get('groups.driver', function(){throw new Exception("You must configure groups.driver to use group services.");});
		
			if(!class_exists($class)) {
				import('groupservice-'.Config::get('groups.driver'));
			}
			
			self::$groupService = new $class();
		}
		return self::$groupService;
	}
	
	protected static $userService;
	public static function &getUserService()
	{
		if(self::$userService == null) {
			$class = 'User_Service_Provider_'.Config::get('users.driver', function(){throw new Exception("You must configure users.driver to use user services.");});
			
			if(!class_exists($class)) {
				import('userservice-'.Config::get('users.driver'));
			}
			
			self::$userService = new $class();
		}
		return self::$userService;
	}
}
App::init();
?>