<?php
/************************************************
 * Route Library
 ************************************************

	This library allows you to control what code will be executed based on the provided request.

	--------------------------------
	BASICS
	--------------------------------

	You may use the following commands to Route URLs to controllers.
	Continue reading to find out what a controller is, and what the valid options are.

	Route::get(uri_pattern, controller, options)
	Route::post(uri_pattern, controller, options)
	Route::any(uri_pattern, controller, options)

	--------------------------------
	URI PATTERNS
	--------------------------------

	You may specify a simply URI pattern that is the exact URL:

		Route::get('/', 'ControllerClass');

			--> Matches exactly /

		Route::get('/home', 'ControllerClass');
			
			--> Matches exactly /home or /home/

	You may also use variable in your patterns:

		Route::get('/profile/{username}', 'ControllerClass');

			--> Matches /profile/anyusername
			--> The target method must have the following signature:
				function($username)

				* Note: The above function header assumes a method on a controller class.
				  For a Closure controller, the signature is:
				  function(Request $request, Response $response, $username)

	You can use multiple variables:

		Route::get('/thread/{thread_id}/{message_id}', 'ControllerClass');

			--> The target method must have the following signature:
				function($thread_id, $message_id)

	You may also use optional variables in your uris.
	Optional variables must occur at the end of the pattern, and you may only use one per pattern.

		Route::get('/profile/{username?}', 'ControllerClass');

			--> Matches /profile
			--> Matches /profile/anyusername
			--> The target method must have the following signature:
				function($username = 'default_value')

	You can combine optional variables and required variables:

		Route::get('/thread/{thread_id}/{message_id?}', 'ControllerClass');

			--> The target method must have the following signature:
				function($thread_id, $message_id='default')

	--------------------------------
	WILDCARDS
	--------------------------------

	You may use wildcards in your routes as follows:

	Route::get('/path/*', 'ControllerClass');

	The wildcard must appear at the end of the URL.
	The above route will not match /path/. If you wish to also match /path/, specify an additional route:

	Route::get('/path/', 'ControllerClass');

	--------------------------------
	CONTROLLERS
	--------------------------------

	A valid routing target is any controller. We define a controller as one of the following:
		* A closure that takes a Request, Response, and any url variables as inputs
		* A method on a class that inherits from Controller.

	When specifying a Controller, you might use the following (in string form):
		ClassName
		ClassName@method
		Path.ClassName
		Path.ClassName@method

	If you do not explicitly name a method, the system will assume the method name is the same as the HTTP verb (e.g. 'get').
	If the controller does not implement the method you specify, an HTTP 405 Method Not Allowed error will be thrown.

	Examples:

	Route::get('/', function(Request $request, Response $response){
		return View::make('hello');
	})
	
	Route::get('/', 'MyController');
	class MyController extends Controller {
		public function get() {
			return View::make('hello');
		}
	}

	--------------------------------
	OPTIONS
	--------------------------------

	name     string   a unique name that identifies this route
	host     string   the hostname that this route should be performed on
	secure   bool     whether or not to require HTTPS

	Example:

	Route::get('/', 'MyController', array(
		'name' => 'home',
		'host' => 'www.example.com',
		'secure' => true
	));

	--------------------------------
	CONSTRAINTS
	--------------------------------

	You may wish to only perform a route if the URL variables match a regular expression.
	The following example will only route when topic_id and post_id are integers.

	Route::get('/topic/{topic_id}/{post_id}', 'Topic@show')
		->where('topic_id', '[0-9]+')
		->where('post_id', '[0-9]+');

	--------------------------------
	FILTERS
	--------------------------------

	A filter is a closure that accepts two parameters: Request and Response.
	A filter will also take any URL variables as parameters if applicable.

	A filter may return...
		true - if the route should be performed
		false - if the route should not be performed
		(int) - an HTTP error code
		(View) - a View to display to the user

	If your filter returns an integer or View, these values will only have an effect
	 if all other constraints and filters on the route are met.

	Route::get('/profile/{username}', 'MyController')
		->where(function(Request $request, Response $response, $username){
			if($request->session->auth->loggedIn)
				return true;
			return 403; // Unauthorized
		});

	--------------------------------
	HANDLING ERRORS
	--------------------------------

	If an error occurs processing a route you define, a RoutingException will be thrown.

	--------------------------------
	ERROR ROUTES
	--------------------------------

	You can define routes for HTTP error codes. If an HTTP error is intercepted, your route
	will be used to generate the error page.

	Route::error(404, function(Request $request, Response $response){
		return View::make('errors.404');
	});

	If development mode is disabled, all PHP syntax errors, runtime errors, and uncaught
	exceptions will throw a 500 Internal Server Error. If you define an error route for
	500 errors, your route will be used to generate the page.

	--------------------------------
	GROUPING
	--------------------------------

	You may also group routes together to apply the same options (or filter) to all of them.
	You may nest group and/or filter statements.

	Route::group(options, function(){
		... define routes in here ...
	});

	Route::filter(filter, function(){
		... define routes in here ...
	});
*/

/**
 * A simple exception for unrecoverable routing errors.
 */
class RoutingException extends Exception {}

/**
 * A class for holding an individual route.
 */
class RouteObject
{
	public /*string*/ $uri;
	public /*array<string>*/ $methods;
	public /*mixed*/ $action;
	protected /*array<string:string>*/ $wheres = array();
	protected /*array<Closure>*/ $filters = array();
	protected /*array<string>*/ $parameters = array();
	protected /*array<string:string>*/ $options = array();
	protected /*mixed*/ $error = null;
	public /*bool*/ $secure = null;
	public /*string*/ $host = null;
	public /*string*/ $name = null;

	public function __construct(/*array<string>*/ $methods, /*string*/ $uri, /*mixed*/ $action)
	{
		$this->methods = (array) $methods;
		$this->uri = $uri;
		$this->action = $action;
	}

	public /*String*/ function uriWithParameters(array $parameters)
	{
		$index = 0;
		$uri = preg_replace_callback("/\/\{(.[A-Za-z0-9-_.\?]+)\}/s", function($matches) use(&$parameters, &$index) {
			$key = $matches[1];
			if(isset($parameters[$key]))
				return '/'.$parameters[$key];
			
			if(isset($parameters[$index])) {
				$v = '/'.$parameters[$index];
				$index++;
				return $v;
			}

			if(substr($key, -1, 1) != '?')
				throw new Exception("You did not provide enough URL parameters.");
			return "";
		}, $this->uri);

		return $uri;
	}

	/**
	 * Returns whether or not the URI matches the request; does not evaluate filters.
	 */
	protected function _match(Request $request)
	{
		// Check if the path matches the route.
		if($request->path == $this->uri) {
			return true;
		}
		
		// Check wildcard ending routes.
		if($this->uri == '/*') {
			return true;
		}
		
		if(substr($this->uri,-1,1) == '*' && strlen($request->path) > strlen($this->uri) - 1) {
			if(substr($this->uri,0,-1) == substr($request->path,0,strlen($this->uri)-1)) {
				return true;
			}
		}

		// Check URLs with an optional vairable. Return array of matched variables.
		if(substr($this->uri,-2,2) == '?}') {
			$routeURI = $this->uri;

			$uriWithoutVariable = preg_replace("/\/\{(.[A-Za-z0-9-_.]+)\?\}$/s","",$this->uri);
			$uriWithVariable = substr($this->uri,0,-2).'}';

			$this->uri = $uriWithoutVariable;
			if($this->matches($request)) {
				$this->uri = $routeURI;
				return true;
			}

			$this->uri = $uriWithVariable;
			if($this->matches($request)) {
				$this->uri = $routeURI;
				return true;
			}

			$this->uri = $routeURI;
			return false;
		}
		
		// Check URLs with required variables. Return array of matched variables.
		if(strpos($this->uri,"}") !== false) {
			
			// Replace Variable Names
			$regex = preg_replace("/\/\{(.[A-Za-z0-9-_.]+)\}/s", "/(.[A-Za-z0-9-_.]*)", $this->uri);
			
			// Escape Slashes
			$regex = str_replace("/","\/",$regex);
			
			// Create Regex
			$regex = '/^'.$regex.'\/$/s';
			
			// Check for Match
			if(preg_match($regex, $request->path.'/', $matches)) {
				// Build Array
				preg_match_all('/\{(.[A-Za-z0-9-_.]+)\}/s', $this->uri, $key_matches);
				$parameters = array();
				
				foreach($key_matches[1] as $i => $k)
					$parameters[$k] = $matches[$i+1];
				
				$this->parameters = $parameters;
				return true;
			}
		}
		
		return false;
	}

	public /*RouteObject*/ function named(/*string*/$name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * Determines if the Route matches the provided request.
	 */
	public /*bool*/ function matches(Request $request)
	{
		// Check Pattern
		if(!$this->_match($request))
			return false;

		// Check Method
		if(!in_array(strtolower($request->method), $this->methods))
			return false;

		// Check Secure
		if($this->secure === true && !$request->secure)
			return false;

		// Check Host
		if($this->host !== null && $request->domain != $this->host)
			return false;

		// Check Wheres
		foreach($this->wheres as $k => $reg) {
			if(!isset($this->parameters[$k]))
				return false;

			if(!preg_match('/^('.$reg.')$/s', $this->parameters[$k]))
				return false;
		}

		// Check Filters
		$_params = array_merge(array($request), $this->parameters);
		foreach($this->filters as $filter) {
			$value = call_user_func_array($filter, $_params);

			if($value === false)
				return false;
			if($value !== true) {
				$this->error = $value;
				return true;
			}
		}

		return true;
	}

	public /*mixed*/ function getAction()
	{
		return $this->action;
	}

	/**
	 * This function only works after matches(Request) has been called.
	 */
	public /*array<string>*/ function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * This function only works after matches(Request) has been called.
	 */
	public /*mixed*/ function getError()
	{
		return $this->error;
	}

	public /*RouteObject*/ function where(/*mixed*/ $key, /*mixed*/ $value=null)
	{
		if(is_array($key)) {
			foreach($key as $opt => $val)
				$this->where($opt, $val);
			return $this;
		}

		if($key instanceof Closure) {
			$this->filters[] = $key;
			return $this;
		}

		$this->wheres[$key] = $value;		
		return $this;
	}
}


class Route
{
	/**
	 * A list of routes.
	 */
	protected static /*RouteCollection*/ $routes;
	protected static /*array<int:mixed>*/ $errors = array();
	protected static /*SplStack*/ $groupStack = null;
	protected static /*Controller*/ $activeController;
	
	/**
	 * Notifies the routing system that a controller activated.
	 */
	public static function __registerActiveController(Controller $controller)
	{
		static::$activeController = $controller;
	}
	
	/**
	 * Returns the active controller.
	 */
	public static function __getActiveController()
	{
		return static::$activeController;
	}
	
	/**
	 * Notifies the routing system that the active controller resigned.
	 */
	public static function __activeControllerDidResign()
	{
		static::$activeController = null;
	}

	/**
	 * Executes a controller.
	 */
	protected static /*void*/ function execute(Request $request, Response $response, /*mixed*/ $target, /*array<string>*/ $parameters=array())
	{
		// If the controller is a closure, execute it.
		if($target instanceof Closure)
		{
			if(sizeof($parameters) > 0)
			{
				$params = array_merge(array($request, $response), $parameters);
				$result = call_user_func_array($target,$params);
			}
			else {
				$result = $target($request, $response);
			}
			
			static::callback($request, $response, $result);
			return;
		}

		// If the controller is a class, resolve it.
		$method = (strpos($target,"@") !== false ? substr($target,strpos($target,"@")+1) : strtolower($request->method));
		$controller_path = (strpos($target,"@") !== false ? substr($target,0,strpos($target,"@")) : $target);
		$controller_path = str_replace(".","/",$controller_path);
		$controller_namespace_path = str_replace("/","\\",$controller_path);
		$controller = (strpos($controller_path,"/") !== false ? substr($controller_path,strrpos($controller_path,"/")+1) : $controller_path);
		$controller_file = FILE_ROOT.'/controllers/'.$controller_path.'.php';
		$controller_class = $controller;
		
		if(!class_exists($controller)) {
			if(!file_exists($controller_file)) {
				throw new RoutingException('The controller class could not be found for "'.$target.'".');
				return;
			}
			
			require_once($controller_file);
			
			if(class_exists($controller)) { $controller_class = $controller; }
			elseif(class_exists('Controllers\\'.$controller_namespace_path)) { $controller_class = 'Controllers\\'.$controller_namespace_path; }
			else {
				throw new RoutingException('The controller class could not be found for "'.$target.'".');
				return;
			}
		}

		$handler = new $controller_class($request, $response);

		if(!is_subclass_of($handler,'Controller')) {
			throw new RoutingException('The controller "'.$target.'" must be a subclass of Controller.');
			return;
		}
		
		// Register the fact that this controller is active so that it can process View URLs.
		self::__registerActiveController($handler);
		
		// Remember the reference used to access this controller so we can make URLs to it.
		$handler->__registerRoutingReference((strpos($target,"@") !== false ? substr($target,0,strpos($target,"@")) : $target));
			
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
	
		self::__activeControllerDidResign();
		self::callback($request, $response, $result);
	}

	/**
	 * Processes the callback value returned by a Route action.
	 */
	protected static /*void*/ function callback(Request $request, Response $response, $value)
	{
		// The controller returned null.
		if($value === null) {
			// We assume the controller has handled everything appropriately and do nothing.
			return;
		}
		
		// The controller returned a Closure; execute it.
		if($value instanceof Closure) {
			self::execute($request, $response, $value);
			return;
		}
		
		// The controller returned a string indicating another controller; execute it.
		if(is_string($value)) {
			self::execute($request, $response, $value);
			return;
		}
		
		// The controller returned an integer corresponding to an HTTP error code.
		if(is_integer($value)) {
			self::doRouteError($request, $response, $value);
			return;
		}
		
		// The controller returned a View; attach it to the response.
		if($value instanceof BladeView) {
			$response->with($value);
			return;
		}
		
		// The controller returned a (Redirect) redirect or (URL) location.
		if($value instanceof Redirect || $value instanceof URL) {
			Redirect::to($value);
			return;
		}
		
		// The controller returned a File; pass it to the user.
		if($value instanceof File) {
			$response->out->pass($value);
			return;
		}
		
		// The controller returned a StorageFile; pass it to the user.
		if($value instanceof StorageFile) {
			throw new RoutingException("A controller returned StorageFile, but these are not implemented.");
			return;
		}

		// The controller returned a Response.
		if($value instanceof Response) {
			if($value === $response)
				return;
			throw new RoutingException("A controller can only return the Response object to which it is attached.");
		}
		
		// We did not recognize the return value; throw an error.
		throw new RoutingException("A controller returned an unexpected object.");
	}

	/**
	 * Performs Routing based on the specified error code.
	 */
	public static /*void*/ function doRouteError(Request $request, Response $response, /*int*/ $code)
	{
		// If an error route exists, use it.
		if(isset(static::$errors[$code])) {
			$response->status = $code;
			self::execute($request, $response, static::$errors[$code]);
			return;
		}

		// Otherwise, throw the default response error.
		$response->error($code);
	}

	/**
	 * Performs Routing based on the request.
	 */
	public static /*RouteObject*/ $currentRoute = null;

	public static /*bool*/ function doRoute(Request $request, Response $response)
	{
		$active = null;

		// Look through the routes for a match.
		foreach(self::$routes as $route) {
			if($route->matches($request)) {
				$active = $route;
				break;
			}
		}

		// If we didn't find a match, throw a 404 error.
		if(is_null($active)) {
			self::doRouteError($request, $response, 404);
			return false;
		}

		// If a filter threw an error, we need to display it.
		if(!is_null($active->getError())) {
			self::callback($request, $response, $active->getError());
			return false;
		}

		// Otherwise, load the controller and execute it.
		static::$currentRoute = $active;
		static::execute($request, $response, $active->getAction(), $active->getParameters());
		return true;
	}

	/** 
	 * Routes a URI to the provided action with the provided options.
	 */
	public static /*RouteObject*/ function bind(/*array<string>*/ $methods, /*string*/ $uri, /*mixed*/ $action, /*array<string:mixed>*/ $options=array())
	{
		// Ensure that the URI has no trailing slash.
		while(substr($uri,-1,1) == '/' && strlen($uri) > 1) {
			$uri = substr($uri,0,-1);
		}

		// Ensure that the URI has a leading slash.
		if(substr($uri,0,1) !== '/')
			$uri = '/'.$uri;

		// Instantiate the route.
		$route = new RouteObject($methods, $uri, $action);

		// Account for group and filter options.
		if(!is_null(static::$groupStack)) {
			foreach(static::$groupStack as $groupOpts) {
				foreach($groupOpts as $key => $value) {
					if(!isset($options[$key])) {
						$options[$key] = $value;
					}
					elseif(is_array($options[$key])) {
						$options[$key] = array_merge($value, $options[$key]);
					}
				}
			}
		}

		// Account for options.
		if(isset($options['filters'])) {
			foreach($options['filters'] as $f)
				$route->where($f);
		}

		// Add the route to the collection and return it for chaining.
		if(static::$routes === null)
			static::$routes = new RouteCollection();

		$route->name = isset($options['name']) ? $options['name'] : null;
		$route->host = isset($options['host']) ? $options['host'] : null;
		$route->secure = isset($options['secure']) ? $options['secure'] : false;
		static::$routes->add($route);
		return $route;
	}

	/**
	 * Applies a set of options to a group of routes.
	 */
	public static /*void*/ function group(array $options, Closure $routes)
	{
		if(is_null(self::$groupStack))
			self::$groupStack = new SplStack();

		self::$groupStack->push($options);

		$routes();

		self::$groupStack->pop();
	}

	/**
	 * Filters a group of routes.
	 */
	public static /*void*/ function filter(Closure $filter, Closure $routes)
	{
		self::group(array('filters' => array($filter)), $routes);
	}

	/**
	 * Routes a GET request to the provided action with the provided options.
	 */
	public static /*RouteObject*/ function get(/*string*/ $uri, /*mixed*/ $action, /*array<string:mixed>*/ $options=array())
	{
		return self::bind(array('get', 'head'), $uri, $action, $options);
	}

	/**
	 * Routes a POST request to the provided action with the provided options.
	 */
	public static /*RouteObject*/ function post(/*string*/ $uri, /*mixed*/ $action, /*array<string:mixed>*/ $options=array())
	{
		return self::bind(array('post', 'head'), $uri, $action, $options);
	}

	/**
	 * Routes any request to the provided action with the provided options.
	 */
	public static /*RouteObject*/ function any(/*string*/ $uri, /*mixed*/ $action, /*array<string:mixed>*/ $options=array())
	{
		return self::bind(array('post', 'get', 'head', 'put', 'delete', 'patch', 'update', 'options'), $uri, $action, $options);
	}

	/**
	 * Routes errors.
	 */
	public static /*void*/ function error(/*int*/ $code, /*mixed*/ $action)
	{
		static::$errors[$code] = $action;
	}

	/**
	 * Returns a Route by its name.
	 */
	public static /*RouteObject*/ function getByName(/*string*/ $name)
	{
		return static::$routes->getByName($name);
	}

	public static /*RouteCollection*/ function getRoutes()
	{
		return static::$routes;
	}
}

class RouteCollection implements \IteratorAggregate, \Countable
{
	private $routes = array();
	private $nameList = array();
	private $methodList = array();
	private $actionList = array();

	public /*Traversable*/ function getIterator()
	{
		return new \ArrayIterator($this->routes);
	}

	public /*RouteObject*/ function getByName(/*string*/ $name)
	{
		return isset($this->nameList[$name]) ? $this->nameList[$name] : null;
	}

	public /*RouteObject*/ function getByAction(/*string*/ $name)
	{
		if(isset($this->actionList[$name]))
			return $this->actionList[$name];

		if(strpos($name, "@") !== false) {
			$baseName = substr($name, 0, strpos($name, "@"));

			if(isset($this->actionList[$baseName]))
				return $this->actionList[$baseName];
		}

		if(isset($this->actionList[$name.'@get'])) {
			return $this->actionList[$name.'@get'];
		}

		if(isset($this->actionList[$name.'@post'])) {
			return $this->actionList[$name.'@post'];
		}

		return null;
	}

	public /*int*/ function count()
	{
		return count($this->routes);
	}

	public /*array<Route>*/ function toArray()
	{
		return $this->routes;
	}

	public /*void*/ function add(RouteObject $route)
	{
		$this->routes[$route->host.$route->uri] = $route;

		if(!is_null($route->name)) {
			$this->nameList[$route->name] = $route;
		}

		if(is_string($route->action)) {
			$this->actionList[$route->action] = $route;
		}

		foreach($route->methods as $method) {
			if(!isset($this->methodList[$method]))
				$this->methodList[$method] = array();

			$this->methodList[$method][$route->host.$route->uri] = $route;
		}
	}

	public /*Route*/ function get(/*string*/ $name)
	{
		return isset($this->routes[$name]) ? $this->routes[$name] : null;
	}

	public /*void*/ function remove(/*string|array<string>*/ $name)
	{
		foreach ((array) $name as $n) {
            unset($this->routes[$n]);
        }
	}
}