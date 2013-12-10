<?php
/*
 Route filters
	allow routes to be named using #name
	route::filter('filtername',function(return true or Redirect))
	route::filter('filtername',Controller[@method])
		befpre filters function($route,$req,$val) after filters ($route,$req,$resp,$val)
	
	named filters: attach by name to the route when routing
	attach filters also with Route::when('route_path', filter_name, [before|after], [http_method], [http_secure])
	Route::currentRouteName()
	
	filters should either return true/false OR perform some termination action (showing a view, redirecting, etc.) OR reference another controller
*/

/*
URI Matches:
	/path
	/path/*
	/path/{var}
	/path/{var_optional?}

Options:
	target  => (function($req, $res, ...){})|[Path]Controller[@Method]
	method  => POST|GET|PUT|DELETE|ANY
	secure  => YES|NO|ANY
	domain  => ANY|(str)
	name    => (str)
	pass    => false|true
	prio    => (int)
	filters => array(var => regex, closure ..+)				-- closure filters return true, false or a status code (if false, it just skips the route; if status code, it will error)
*/

class Route
{
	/**
	 * Executes a controller.
	 */
	protected static function executeController(&$request, &$response, &$target, $parameters=array())
	{
		// Closure Controllers
		if($target instanceof Closure) {
			if(sizeof($parameters) > 0) {
				$params = array_merge(array($request, $response),$parameters);
				$result = call_user_func_array($target,$params);
			}
			else {
				$result = $target($request, $response);
			}
			self::processControllerCallback($request, $response, $result);
			return;
		}
		
		// Class Controllers
		$method = (strpos($target,"@") !== false ? substr($target,strpos($target,"@")+1) : strtolower($request->method));
		$controller_path = (strpos($target,"@") !== false ? substr($target,0,strpos($target,"@")) : $target);
		$controller_path = str_replace(".","/",$controller_path);
		$controller = (strpos($controller_path,"/") !== false ? substr($controller_path,strrpos($controller_path,"/")+1) : $controller_path);
		$controller_file = FILE_ROOT.'/controllers/'.strtolower($controller_path).'.php';
	
		if(!class_exists($controller)) {
			if(!file_exists($controller_file)) {
				$response->error(500, 'The controller could not be found for "'.$target.'".');
				return;
			}
			
			require_once($controller_file);
			
			if(!class_exists($controller)) {
				$response->error(500, 'The controller could not be found for "'.$target.'".');
				return;
			}
		}
		
		
		$handler = new $controller($request, $response);
			
			
		if(!is_subclass_of($handler,'RequestHandler')) {
			$response->error(500, 'The controller "'.$target.'" must extend RequestHandler.');
			return;
		}
		
		if(!method_exists($handler, $method)) {
			self::doRouteError($request, $response, 405);
			return;
		}
	
		if(sizeof($parameters) > 0) {
			$result = call_user_func_array(array($handler,$method),$parameters);
		}
		else {
			$result = $handler->$method();
		}
	
		self::processControllerCallback($request, $response, $result);
	}
	
	/**
	 * Processes the return value of a Controller.
	 */
	protected static function processControllerCallback(&$request, &$response, &$value)
	{		
		// The controller returned null.
		if($value === null) {
			// We assume the controller has handled everything appropriately and do nothing.
			return;
		}
		
		// The controller returned a closure controller.
		if($value instanceof Closure) {
			self::executeController($request, $response, $value);
			return;
		}
		
		// The controller returned a string controller target.
		if(is_string($value)) {
			self::executeController($request, $response, $value);
			return;
		}
		
		// The controller returned an (int) HTTP Error Code.
		if(is_integer($value)) {
			// We need to call the error controller for the provided code.
			self::doRouteError($request, $response, $value);
			return;
		}
		
		// The controller returned a (BladeView) view.
		if($value instanceof BladeView) {
			// We need to attach the view to the response.
			$response->with($value);
			return;
		}
		
		// The controller returned a (Redirect) redirect or (URL) location.
		if($value instanceof Redirect || $value instanceof URL) {
			Redirect::to($value);
			return;
		}
		
		// The controller returned a file;
		if($value instanceof File) {
			$response->error(500, 'A controller returned File, but these are not implemented.');
			return;
		}
		
		// The  controller returned a storage file.
		if($value instanceof StorageFile) {
			$response->error(500, 'A controller returned StorageFile, but these are not implemented.');
			return;
		}
		
		// Otherwise, we must throw an error.
		$response->error(500, 'A controller returned an unexpected object or value.');
		return;
	}
	
	/**
	 * Routes an error.
	 */
	public static function doRouteError(&$request, &$response, $code)
	{
		// (If Available): Use User-Specified Error Pages
		if(isset(self::$error_bind[$code])) {
			$response->status = $code;
			$target = self::$error_bind[$code];
			self::executeController($request,$response,$target);
			return;
		}
		
		// Use Default Error
		$response->error($code);
	}
	
	/**
	 * Routes the request based on the path and other options.
	 */
	public static function doRoute(&$request, &$response)
	{		
		// Sort Routes by Priority
		if(self::$sort === true) {
			uasort(self::$routes, function($a, $b){
				if($a['prio'] > $b['prio'])
					return -1;
				elseif($a['prio'] < $b['prio'])
					return 1;
				return 0;
			});
		}
				
		// Check Routes
		$route = false;
		
		foreach(self::$routes as $pattern => $options) {
			// Check Pattern
			$parameters = self::is($pattern, $request);
			$throwFilterError = null;
			if(is_array($parameters)) {
				// Check Method
				$allowed = explode("|", $options['method']);
				if(!(in_array('ANY',$allowed) || in_array($request->method,$allowed)))
					continue;
					
				// Check Secure
				if($options['secure'] == 'NO' && $request->secure == true)
					continue;
				if($options['secure'] == 'YES' && $request->secure == false)
					continue;
								
				// Check Domain
				if($options['domain'] != 'ANY' && $request->domain != $options['domain'])
					continue;
				
				// Check Filters
				$passed = true;
				
				foreach($options['filters'] as $k => $reg) {
					if(is_string($reg)) {
						if(!preg_match('/^('.$reg.')$/s',$parameters[$k])) {
							$passed = false;
						}
					}
					elseif($reg instanceof Closure) {
						$_params = array_merge(array($request),$parameters);
						$value = call_user_func_array($reg,$_params);
						
						if($value === false) {
							$passed = false;
						}
						else if ($value !== true) {
							$throwFilterError = $value;
						}
					}
				}
				
				if($passed === false)
					continue;
				
				// Match Found! - Break
				$route = $pattern;
				break;
			}
		}
		
		// 404 Not Found
		if($route === false) {
			self::doRouteError($request, $response, 404);
			return;
		}
			
		// Check: Closure Filters
		if(isset($throwFilterError) && !is_null($throwFilterError)) {
			self::processControllerCallback($request, $response, $throwFilterError);
			return;
		}
			
		// Check: Passthroughs (NOT IMPLEMENTED)
		
		// Execute the controller.
		$target = self::$routes[$route]['target'];
		self::executeController($request, $response, $target, $parameters);
		return true;
	}
	
	public static function is($pattern, &$request)
	{
		// Check if the path matches the route.
		if($request->path == $pattern) {
			return array();
		}
		
		// Check wildcard ending routes.
		if($pattern == '/*') {
			return array();
		}
		
		if(substr($pattern,-1,1) == '*' && strlen($request->path) > strlen($pattern) - 1) {
			if(substr($pattern,0,-1) == substr($request->path,0,strlen($pattern)-1)) {
				return array();
			}
		}
		
		// Check URLs with required variables. Return array of matched variables.
		if(strpos($pattern,"}") !== false) {
			
			// Replace Variable Names
			$regex = preg_replace("/\/\{(.[A-Za-z0-9-_.]+)\}/s", "/(.[A-Za-z0-9-_.]*)", $pattern);
			
			// Escape Slashes
			$regex = str_replace("/","\/",$regex);
			
			// Create Regex
			$regex = '/^'.$regex.'\/$/s';
			
			// Check for Match
			if(preg_match($regex, $request->path.'/', $matches)) {
				// Build Array
				preg_match_all('/\{(.[A-Za-z0-9-_.]+)\}/s', $pattern, $key_matches);
				$parameters = array();
				
				foreach($key_matches[1] as $i => $k)
					$parameters[$k] = $matches[$i+1];
				
				// Return Array
				return $parameters;
			}
		}
		
		return false;
	}
	
	protected static $routes = array();
	protected static $group = false;
	protected static $sort = false;
	protected static $groupopts;
	
	public static function bind($uri_match, $target, $options=array())
	{
		// Clean URI Trailing Slashes
		while(substr($uri_match,-1,1) == '/' && strlen($uri_match) > 1) {
			$uri_match = substr($uri_match,0,-1);
		}
		
		// Ensure Leading Slash
		if(substr($uri_match,0,1) !== '/')
			$uri_match = '/'.$uri_match;
		
		// Group Mode
		if(self::$group === true) {
			foreach(self::$groupopts as $k => $v) {
				if($k == 'filters') {
					$options[$k] = array_merge(self::$groupopts[$k], $v);
				}
				else {
					$options[$k] = $v;
				}
			}
		}
		
		// Process Default Options
		$options['target'] = $target;
		if(!isset($options['method']))
			$options['method'] = 'ANY';
		if(!isset($options['secure']))
			$options['secure'] = 'ANY';
		if(!isset($options['domain']))
			$options['domain'] = 'ANY';
		if(!isset($options['name']))
			$options['name'] = null;
		if(!isset($options['pass']))
			$options['pass'] = 'false';
		if(!isset($options['prio']))
			$options['prio'] = 0;
		elseif($options['prio'] != 0)
			self::$sort = true;
		if(!isset($options['filters']))
			$options['filters'] = array();
		
		// Complete Binding
		if(substr($uri_match,-2,2) == '?}') {
			$uri1 = preg_replace("/\/\{(.[A-Za-z0-9-_.]+)\?\}$/s","",$uri_match);
			$uri2 = substr($uri_match,0,-2).'}';
			self::$routes[$uri1] = $options;
			self::$routes[$uri2] = $options;
		}
		else {
			self::$routes[$uri_match] = $options;
		}
		
		// Return Helper Object
		$rh = new RouteHelper($uri_match);
		return $rh;
	}
	
	public static function _updateroute($route, $key, $value)
	{
		if(isset(self::$routes[$route]))
			self::$routes[$route][$key] = $value;
		return true;
	}
	
	public static function _updateroutefilter($route, $name, $regex)
	{
		if(isset(self::$routes[$route]['filters']))
			self::$routes[$route]['filters'][$name] = $regex;
		return true;
	}
	
	public static function group($options, $closure)
	{
		self::$group = true;
		self::$groupopts = $options;
		$closure();		
		self::$group = false;
		self::$groupopts = array();
	}
	
	public static function filter($filter, $closure)
	{
		self::group(array('filters' => array($filter)), $closure);
	}
	
	public static function get($uri_match, $target, $options=array())
	{
		$options['method'] = 'GET';
		return self::bind($uri_match, $target, $options);
	}
	
	public static function post($uri_match, $target, $options=array())
	{
		$options['method'] = 'POST';
		return self::bind($uri_match, $target, $options);
	}
	
	public static function any($uri_match, $target, $options=array())
	{
		$options['method'] = 'ANY';
		return self::bind($uri_match, $target, $options);
	}
	
	public static function all($uri_match, $target, $options=array())
	{ // @alias any
		return self::any($uri_match, $target, $options);
	}
	
	public static function pass($uri_match, $target, $options=array())
	{
		$options['method'] = 'GET';
		$options['pass'] = true;
		return self::bind($uri_match, $target, $options);
	}
	
	protected static $error_bind = array();
	public static function error($code, $target)
	{
		self::$error_bind[$code] = $target;
	}
}

class RouteHelper
{
	protected $route;
	protected $off = 0;
	
	public function __construct($route)
	{
		$this->route = $route;
	}
	
	public function where($opt1, $opt2=null)
	{
		if(is_array($opt1)) {
			foreach($opt1 as $k => $v) {
				Route::_updateroutefilter($this->route, $k, $v);
			}
		}
		elseif($opt1 instanceof Closure) {
			Route::_updateroutefilter($this->route, '_closure'.$this->off, $opt1);
			$this->off += 1;
		}
		else {
			Route::_updateroutefilter($this->route, $opt1, $opt2);
		}
		
		return $this;
	}
}

?>