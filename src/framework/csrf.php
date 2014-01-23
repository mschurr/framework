<?php

class CSRF
{
	public static function driver()
	{
		return Config::get('csrf.driver', 'session');
	}
	
	public static function enforce($name, $value)
	{
		if(self::check($name,$value))
			return true;
		throw new Exception("CSRF Attack Intercepted");
	}
	
	public static function make($name, $ignore=true)
	{
		$id = self::_generateID();
		$key = '_csrf_'.md5($name);
		
		if(self::driver() == 'cookies') {
			if(Cookies::has($key) && $ignore)
				return Cookies::get($key)->value;
			
			$cookie = new Cookie($key);
			$cookie->value = $id;
			$cookie->expiry = time() + 3600;
			$cookie->path = '/';
			$cookie->domain = App::getRequest()->domain;
			$cookie->secure = App::getRequest()->secure;
			$cookie->httponly = true;
			$cookie->send();
		}
		else {
			$session =& App::getSession();
			
			if($session->has($key) && $ignore)
				return $session[$key];
			
			$session[$key] = $id;
		}
		
		return $id;
	}
	
	public static function reset($name)
	{
		return self::make($name, false);
	}
	
	public static function check($name, $value)
	{
		$key = '_csrf_'.md5($name);
		
		if(self::driver() == 'cookies') {
			if(Cookies::has($key) && Cookies::get($key)->value === $value) {
				self::reset($name);
				return true;
			}
		}
		else {
			$session =& App::getSession();
			
			if($session->has($key) && $session[$key] === $value) {
				self::reset($name);
				return true;
			}
		}
		
		self::reset($name);
		return false;
	}
	
	protected static $_idchars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
	protected static function _generateID()
	{
		// Seed the random number generator.
		mt_srand(microtime(true) * 1000000);
		
		// Create a unique session identifier.
		$length = 100 + mt_rand(25,50);
		$id = "";
		
		while(strlen($id) < $length) {
			$id .= substr(self::$_idchars, mt_rand(0, strlen(self::$_idchars)-1), 1);
		}
		
		return $id;
	}
}