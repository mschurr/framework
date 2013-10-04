<?php

class Redirect
{
	public static function _redirect($uri)
	{
		App::getResponse()->headers['Location'] = $uri;
		App::getResponse()->error(302);
		$rh = new RedirectHelper();
		return $rh;
	}
	
	public static function to($uri, $data=array())
	{
		return self::_redirect(URL::to($uri))->with($data);
	}
	
	public static function route($route, $data=array()) //named route [w/ named or unnamed parameters]
	{
		return self::_redirect(URL::route($route))->with($data);
	}
	
	public static function action($controller_method, $data=array()) // to controller [@action] [w/ named or unnamed params]
	{
		return self::_redirect(URL::action($controller_method))->with($data);
	}
	
	public static function cdn($path)
	{
	}
}

class RedirectHelper
{
	public function with($key, $val=null)
	{
		return $this;
	}
	
	public function withInput()
	{
		return $this;
	}
	
	public function withInputOnly()
	{
		return $this;
	}
	
	public function withInputExcept()
	{
		return $this;
	}
}
?>