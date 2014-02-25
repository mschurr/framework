<?php
/*
	#############################################################
	# URL Library
	#############################################################

	This library can be used to generate URLs dynamically. In this way, you can
	create URLs to controllers and (if you change the route to the controller), 
	all of the URLs will update, too.

	-----------------------------------------------
	Class Methods
	-----------------------------------------------

	URL::asset(path)

		Returns a URL to an asset within the static folder. You should always use this function to reference assets, as the function
		can be easily updated to point to a Content Distribution Network.

		e.g. URL::to('css/master.css')

	URL::route(name, ...parameters)
	
		Returns a URL to a Route by its name. If the route has URL variables, they should be specified following the name.

		e.g. URL::to('UserProfile', $userid)

	URL::to(target, ...parameters)

		Returns a URL to the provided target. If the target has URL variables, they should be specified following the name.

		Valid Targets:
			URL
			Redirect
			StorageFile - the file must exist
			File - the file must exist in the static directory
			BladeView - returns the URL to the active controller; accepts URL parameters
			BladeLayout - returns the URL to the active controller; accepts URL parameters
			RouteObject - returns the URL of a Route; accepts URL parameters
			Controller - returns the URL of a Controller; accepts URL parameters
			(string) any controller name (e.g. MyController or Path.Controller@method); accepts URL parameters
			(string) any absolute path (e.g. /path/to/page)
			(string) any absolute url (e.g. https://example.com or //example.com or ftp://ftp.com)
			(string) any application url (e.g. mailto:youraddress or javascript:function)
			(string) any relative path (e.g. ../path/to/file or ./path/to/file)
			(array) [Controller, (string)method] - returns the URL to a method on a Controller object; accepts URL parameters
			(array) [BladeView, (string)method] - returns the URL to a method on the active controller; accepts URL parameters
			(array) [BladeLayout, (string)method] - returns the URL to a method on the active controller; accepts URL parameters

		If the system fails to create a URL, a URLException is thrown.

	-----------------------------------------------
	Instance Methods
	-----------------------------------------------

	__toString()
		- Converts the object to a string containing an absolute URL.
*/

class URLException extends Exception {}

class URLType
{
	const ABSOLUTE_PATH = 1;
	const ABSOLUTE = 2;
	const APPLICATION = 3;
	const RELATIVE_PATH = 4;
	const ROUTE_URL = 5;
}

class URL
{
	protected static $base;
	protected static $domain;

	public static function init()
	{
		self::$domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
		self::$base = (isset($_SERVER['https']) && $_SERVER['https'] == 'on' ? 'https' : 'http');
		self::$base .= '://'.(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR']);
		self::$base .= ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ? ':'.$_SERVER['SERVER_PORT'] : '');
	}

	public static function getCurrentDomain()
	{
		return self::$domain;
	}

	public static function getBase()
	{
		return self::$base;
	}

	/**
	 * Returns the URL to a static asset.
	 */
	public static /*URL*/ function asset($path)
	{
		if(substr($path,0,1) == '/')
			$path = substr($path, 1);

		return URL::to('/static/'.$path);
	}

	/**
	 * Returns the URL to a route by its name.
	 */
	public static /*URL*/ function route($name /*,...$parameters*/)
	{
		$route = Route::getByName($name);
		return forward_static_call_array(array('URL', 'to'), array_merge(array($route), array_slice(func_get_args(), 1)));
	}

	/**
	 * Returns the URL to a target.
	 */
	public static /*URL*/ function to($target /*,...$parameters*/)
	{
		if($target instanceof URL)
			return $target;
		if($target instanceof Redirect)
			return $target->getURL();
		if(is_string($target)) {
			if(str_startswith($target, '/'))
				return new URL(URLType::ABSOLUTE_PATH, static::$base.$target, array_slice(func_get_args(), 1));
			if(str_startswith($target, '//') || str_contains($target, '://'))
				return new URL(URLType::ABSOLUTE, $target);
			if(str_startswith($target, 'mailto:') || str_startswith($target, 'javascript:'))
				return new URL(URLType::APPLICATION, $target);
			if(str_startswith($target, '.') || str_startswith($target, '..'))
				return new URL(URLType::RELATIVE_PATH, $target);

			// Assume Controller
			$route = Route::getRoutes()->getByAction($target);

			if($route !== null)
				return forward_static_call_array(array('URL', 'to'), array_merge(array($route), array_slice(func_get_args(), 1)));
		}
		if($target instanceof RouteObject) {
			return new URL(URLType::ROUTE_URL, $target, array_slice(func_get_args(), 1));
		}
		if($target instanceof File && str_startswith($target->canonicalPath, FILE_ROOT.'/static')) {
			return URL::asset(substr($target->canonicalPath, strlen(FILE_ROOT.'/static')));
		}
		if($target instanceof BladeView || $target instanceof BladeLayout) {
			return forward_static_call_array(array('URL', 'to'), array_merge(array($target->getAssociatedController()), array_slice(func_get_args(), 1)));
		}
		if($target instanceof Controller) {
			return forward_static_call_array(array('URL', 'to'), array_merge(array($target->__getRoutingReference()), array_slice(func_get_args(), 1)));
		}
		if(is_array($target) && $target[0] instanceof Controller)
		{
			return forward_static_call_array(array('URL', 'to'), array_merge(array($target[0]->__getRoutingReference().'@'.$target[1]), array_slice(func_get_args(), 1)));
		}
		if(is_array($target) && ($target[0] instanceof BladeView || $target[0] instanceof BladeLayout))
		{
			return forward_static_call_array(array('URL', 'to'), array_merge(array(
				array($target[0]->getAssociatedController(), $target[1])
			), array_slice(func_get_args(), 1)));
		}

		if(is_object($target))
			throw new URLException("Error: Unable to convert ".get_class($target)." into URL");
		else
			throw new URLException("Error: Unable to convert '".$target."' into URL");
	}

	/*
	#############################################################################
	*/

	protected $target;
	protected $type;
	protected $parameters = array();

	public function __construct($type, $target, $parameters=array())
	{
		$this->type = $type;
		$this->target = $target;
		$this->parameters = $parameters;

		if($this->type == URLType::ROUTE_URL) {
			try {
				$this->target = static::$base.$this->target->uriWithParameters($this->parameters);
				$this->type = URLType::ABSOLUTE;
			}
			catch(Exception $e) {
				throw new URLException($e);
			}		
		}
	}

	public function __toString()
	{
		return $this->target;
	}

	public function isInternal()
	{
		return false;
	}
}

URL::init();