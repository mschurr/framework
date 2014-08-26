<?php

/**
 * Encapsulates a raw HTTP Request.
 */
class Request
{
	protected $_headers;
	protected $_get;
	protected $_post;
	protected $_cookie;
	protected $_file;
	protected $_server;
	protected $_connection;
	protected $_client;
	protected $_method;
	protected $_path;
	protected $_uri;
	protected $_secure;
	protected $_domain;
	protected $_segment;
	protected $_timestamp;
	protected $_ip;
	protected $_auth;

	/**
	 * Instantiates a new Request object.
	 */
	public function __construct()
	{
		$this->_method = (isset($this->server['REQUEST_METHOD'])) ? $this->server['REQUEST_METHOD'] : 'GET';
		$this->_uri    = $this->server['REQUEST_URI'];

		$this->_path   = '/'.trim( (strpos($this->server['REQUEST_URI'],"?") === false ? $this->server['REQUEST_URI'] : substr($this->server['REQUEST_URI'], 0, strrpos($this->server['REQUEST_URI'],"?")))  ,'/\\');

		if($this->_path == '/index.php')
			$this->_path = '/';
		elseif($this->_path == '/index.html')
			$this->_path = '/';
		elseif(str_startswith($this->_path, "/index.php"))
			$this->_path = substr($this->_path, strlen("/index.php"));

		$this->_secure = (isset($this->server['https']) && $this->server['https'] !== 'off' && $_SERVER['https'] !== '0');
		$this->_secure = $this->_secure || (Config::get('http.loadbalanced',false) && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
		$this->_ip = (Config::get('http.loadbalanced',false) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$this->_domain = (isset($this->server['SERVER_NAME']) ? $this->server['SERVER_NAME'] : $this->server['SERVER_ADDR']);
		$this->_timestamp = time();
	}

	/**
	 * Returns whether or not this request matches a particular pattern.
	 * Patterns are equivalent to those used by Route commands.
	 */
	public function is($pattern)
	{
		return is_array(Route::is($pattern));
	}

	/**
	 * Returns the raw HTTP request.
	 */
	public function __toString()
	{
		$request = $this->server['SERVER_PROTOCOL']." ".$this->method." ".$this->server['REQUEST_URI'].PHP_EOL;
		foreach($this->headers as $k => $v) {
			$request .= str_replace("_","-",$k).": ".$v.PHP_EOL;
		}
			// can read raw from php://input
			foreach($this->post as $k => $v) {
				if(is_array($v))
					$request .= $k.'='.print_r($v,true).EOL;
				else
					$request .= $k.'='.urlencode($v).''.EOL;
			}
			foreach($this->files as $k => $v) {
				$request .= $k.'=(File)';
			}
			$request = substr($request,0,-1).PHP_EOL;

		return($request);
	}

	/**
	 * Returns whether or not a request property is set.
	 */
	public function __isset($k)
	{
		return ($this->__get($k) !== null);
	}

	/**
	 * Returns the raw request body.
	 */
	public /*bytes*/ function getRawRequestBody()
	{
		if($this->method === 'GET')
			throw new WrongRequestTypeException;
		if($this->headers['Content-Type'] === 'multipart/form-data')
			throw new WrongRequestTypeException; // Not supported as of PHP 5.5
		return file_get_contents("php://input");
	}

	/**
	 * Returns a deserialized version of the JSON sent in the request body.
	 * Throws an InvalidPostParameterException if the request body does not contain valid JSON.
	 */
	public /*Object*/ function getRequestJson()
	{
		if($this->method === 'GET')
			throw new WrongRequestTypeException;
		if($this->headers['Content-Type'] !== 'application/json')
			throw new InvalidPostParameterException("Request content is not json (application/json).");
		$data = from_json($this->getRawRequestBody());
		if($data === null)
			throw new InvalidPostParameterException("Request content is not valid json.");
		return $data;
	}

	/**
	 * Returns whether or not this request originates from a debugging-safe IP address.
	 */
	public /*bool*/ function hasDebugIP()
	{
		if($this->ip === '::1' || $this->ip === '127.0.0.1')
			return true;
		return false;
	}

	/**
	 * Oversees access to internal properties from external scope.
	 * Enables support for lazy instantiation of object properties.
	 */
	public /*mixed*/ function __get(/*scalar*/ $k)
	{
		if($k == 'cookie' || $k == 'cookies') {
			if(!$this->_cookie instanceof RequestDataWrapper) {
				$this->_cookie = new RequestDataWrapper(Cookies::getAll());
			}
			return $this->_cookie;
		} else if($k == 'get') {
			if(!$this->_get instanceof RequestDataWrapper)
				$this->_get = new RequestDataWrapper($_GET, RequestDataWrapper::GetType);
			return $this->_get;
		} else if($k == 'post' || $k == 'data') {
			if(!$this->_post instanceof RequestDataWrapper) {
				$this->_post = new RequestDataWrapper($_POST, RequestDataWrapper::PostType);
			}
			return $this->_post;
		} else if($k == 'file' || $k == 'files') {
			if(!$this->_file instanceof RequestDataWrapper) {
				$this->_file = new RequestDataWrapper(File::uploadedFiles(), RequestDataWrapper::FileType);
			}
			return $this->_file;
		} else if($k == 'session') {
			return App::getSession();
		} else if($k == 'auth') {
			return App::getSession()->auth;
		} else if($k == 'user') {
			return App::getSession()->auth->user;
		} else if($k == 'method') {
			return $this->_method;
		} else if($k == 'headers' || $k == 'header') {
			if(!$this->_headers instanceof RequestDataWrapper) {
				if(function_exists('getallheaders')) {
					/*
						So apparently this is perfectly valid:
							$array = array(
								"something-with-a-dash" => "value"
							)
						But:
							isset($array['something-with-a-dash']) returns false
							$array['something-with-a-dash'] throws an access error
						However this will print something:
							foreach($array as $k => $v)
								if($k === 'something-with-a-dash')
									echo $v;

						Really PHP?
					*/
					$data = array();

					foreach(getallheaders() as $k => $v) {
						$data[str_replace("-","_",$k)] = $v;
					}

					$this->_headers = new RequestDataWrapper($data);
				}
				else {
					// Provide emulated support for getallheaders() if PHP < 5.5.7
					$data = array();
					foreach($_SERVER as $k => $v) {
						if(substr($k, 0, 5) == 'HTTP_') {
							$k = strtolower(substr($k, 5));
							$segs = explode("_", $k);
							$key = "";

							for($i = 0; $i < sizeof($segs); $i++) {
								$key .= ucfirst($segs[$i]);
								if($i < sizeof($segs)-1)
									$key .= '_';
							}

							$data[$key] = $v;
						}
					}
					$this->_headers = new RequestDataWrapper($data);
				}
			}
			return $this->_headers;
		} else if($k == 'ip') {
			return $this->_ip;
		} else if($k == 'timestamp') {
			return $this->_timestamp;
		} else if($k == 'path') {
			return $this->_path;
		} else if($k == 'uri' || $k == 'query') {
			return $this->_uri;
		} else if($k == 'secure' || $k == 'ssl' || $k == 'encrypted' || $k == 'https') {
			return $this->_secure;
		} else if($k == 'host' || $k == 'domain') {
			return $this->_domain;
		} else if($k == 'segment' || $k == 'segments') {
			if(!$this->_segment instanceof RequestDataWrapper) {
				$this->_segment = new RequestDataWrapper(explode("/",trim($this->path,'/')));
			}
			return $this->_segment;
		} else if($k == 'server') {
			if(!$this->_server instanceof RequestDataWrapper)
				$this->_server = new RequestDataWrapper($_SERVER);
			return $this->_server;
		} else if($k == 'connection') {
			if(!$this->_connection instanceof Connection)
				$this->_connection = new Connection();
			return $this->_connection;
		} else if($k == 'client' || $k == 'browser' || $k == 'useragent') {
			if(!$this->_client instanceof UserAgent)
				$this->_client = new UserAgent($this->server['HTTP_USER_AGENT']);
			return $this->_client;
		} else {
			throw new BadAccessException($k);
		}
	}

	/**
	 * Handles calls to undefined functions or private functions from external scope.
	 * Forwards method calls to properties to their __invoke() method.
	 * @magic
	 */
	public /*mixed*/ function __call(/*scalar*/ $name, /*array<mixed>*/ $args)
	{
		if(!is_callable(array($this->__get($name), '__invoke')))
			throw new BadAccessException($name);
		return call_user_func_array(array($this->__get($name), '__invoke'), $args);
	}
}

class RequestDataWrapper implements IteratorAggregate, ArrayAccess, Countable
{
	protected $data = array();
	protected $type;

	const GetType = 0;
	const PostType = 1;
	const FileType = 2;

	public function __construct(array $data, $type = null)
	{
		$this->data = $data;
		$this->type = $type;
	}

	public function offsetGet($k) {
		$k = str_replace("-","_",$k);
		if(isset($this->data[$k]))
			return $this->data[$k];
		if($this->type == self::GetType)
			throw new InvalidGetParameterException($k);
		else if($this->type == self::PostType)
			throw new InvalidPostParameterException($k);
		else if($this->type == self::FileType)
			throw new InvalidFileParameterException($k);
		else
			throw new BadAccessException($k);
	}

	public function __get($k)
	{
		if(isset($this->data[$k]))
			return $this->data[$k];
		if($this->type == self::GetType)
			throw new InvalidGetParameterException($k);
		else if($this->type == self::PostType)
			throw new InvalidPostParameterException($k);
		else if($this->type == self::FileType)
			throw new InvalidFileParameterException($k);
		else
			throw new BadAccessException($k);
	}

	public function count()
	{
		return count($this->data);
	}

	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}

	public function __toString()
	{
		$string = '';
		foreach($this->data as $k => $v)
			$string .= '&'.urlencode($k).'='.urlencode($v);

		if(substr($string, 0, 1) == '&')
			$string = substr($string, 1);

		return $string;
	}

	public function __isset($k)
	{
		return isset($this->data[$k]);
	}

	public function __unset($k)
	{
		throw new ImmutableObjectException;
	}

	public function __invoke()
	{
		$args = func_get_args();

		if(count($args) === 1)
			return $this->__get($args[0]);

		throw new BadMethodCallException;
	}

	public function has($k)
	{
		return $this->__isset($k);
	}

	public function get($k)
	{
		return $this->__get($k);
	}

	public function __set($k, $v)
	{
		throw new ImmutableObjectException;
	}

	// Array Access
	public function offsetSet($offset, $value) {
		throw new ImmutableObjectException;
	}

	public function offsetExists($k) {
		$k = str_replace("-","_",$k);
		return isset($this->data[$k]);
	}

	public function offsetUnset($offset) {
		throw new ImmutableObjectException;
	}

	public function dump() {
		return print_r($this->data, true);
	}

	public function toArray() {
		return $this->data;
	}
}
