<?php
/**
 * Cookie Class
 * ---------------------------------------
 *
 	This class aims to simplify operations with HTTP Cookies.
 
	Reading Cookies:
		Cookies::has(String $name) 			// returns true or false
		Cookies::get(String $name)			// returns a Cookie object
		Cookies::getAll() 					// returns an array ( (String) COOKIE_NAME => (Cookie) COOKIE_OBJECT, ... )
		isset($request->cookies['name'])	// equivalent to Cookies::has('name')
		$request->cookies['name']			// equivalent to Cookies::get('name')
		
	Writing Cookies:
		Cookies::delete(Cookie $cookie)					// deletes the passed Cookie
		Cookies::delete(String $name)					// deletes cookies with the passed name
		Cookies::put(Cookie $cookie)					// sends a cookie to the client
		$response->cookies['name'] = Cookie $cookie;	// equivalent to Cookies::put($cookie). notice that 'name' is irrelvant in this case.
		$response->cookies[] = Cookie $cookie; 			// equivalent to Cookies::put($cookie)
		$response->cookies->add(Cookie $cookie)			// equivalent to Cookies::put($cookie)
		$response->with(Cookie $cookie)					// equivalent to Cookies::put($cookie)
		$response->addCookie(Cookie $cookie)			// equivalent to Cookies::put($cookie)
		unset($response->cookies['name'])				// allows you to cancel sending of the pending cookie with 'name' (if you sent it with put in the same request)
		$response->cookies->remove(String $name)
		$response->cookies->remove(Cookie $cookie)
		$response->cookies->delete(String $name)
		$response->cookies->delete(Cookie $cookie)
		$response->cookies['name'] 						// gets the Cookie object of the pending cookie with 'name'
		isset($response->cookies['name'])			    // whether or not a pending cookie exists with the given name
		$response->cookies->has(Cookie $cookie)			// whether or not a pending cookie exists with the given name
		$response->cookies->has(String $name)			// whether or not a pending cookie exists with the given name
		len($response->cookies) 						// number of pending cookies
		foreach($response->cookies as 
			String $name => Cookie $cookie) 			// iterate through pending cookies
		
	Properties of Cookies
		$cookie = new Cookie($name, $value); // note value is optional
		$cookie->delete(); // deletes the cookie (should be used on cookies you read)
		$cookie->send(); // sends the cookie (this function really doesn't need to be called directly)
		
		NOTE: Properties can be accessed $cookie->property or $cookie['property']
		$cookie->name		String
		$cookie->httponly	true|false		- Send cookie only over http. Defaults to true.
		$cookie->secure		true|false		- Send cookie only over ssl. Defaults to whether or not request was made over ssl.
		$cookie->domain		String			- Defaults to current domain
		$cookie->path		String			- Defaults to /
		$cookie->expiry		Integer			- Should be a timestamp. Defaults to two weeks.
		$cookie->value		String			- Defaults to null.
		
	Preventing Tampering
		The cookie library will automatically check whether or not cookies you set were tampered with.
		If a cookie has been tampered with, the API will treat it as if it does not exist (e.g. Cookies::has('name') will return FALSE).
 */
 
class CookieRegistry implements ArrayAccess, Countable, Iterator
{
	// Methods
	public function add($o)
	{
		Cookies::registerPending($v);
	}
	
	public function push($o)
	{
		Cookies::registerPending($v);
	}
	
	public function has($o)
	{
		return Cookies::hasPending($o);
	}
	
	public function remove($o)
	{
		Cookies::removePending($o);
	}
	
	public function delete($o)
	{
		Cookies::removePending($o);
	}
	
	// Iterator
	protected $_position = 0;
	
	public function current()
	{
		$pending =& Cookies::getAllPending();
		$keys = array_keys($pending);
		return $pending[$keys[$this->_position]];
	}
	
	public function key()
	{
		$pending =& Cookies::getAllPending();
		$keys = array_keys($pending);
		return $keys[$this->_position];
	}
	
	public function next()
	{
		$this->_position += 1;
	}
	
	public function rewind()
	{
		$this->_position = 0;
	}
	
	public function valid()
	{
		return $this->_position < $this->count();
	}
	
	// PropertyAccess
	public function __isset($o)
	{
		return Cookies::hasPending($o);
	}
	
	public function __get($o)
	{
		return Cookies::getPending($o);
	}
	
	public function __unset($o)
	{
		Cookies::removePending($o);
	}
	
	public function __set($o, $v)
	{
		Cookies::registerPending($v);
	}
	
	// ArrayAccess
	public function offsetExists($o)
	{
		return Cookies::hasPending($o);
	}
	
	public function offsetGet($o)
	{
		return Cookies::getPending($o);
	}
	
	public function offsetUnset($o)
	{
		Cookies::removePending($o);
	}
	
	public function offsetSet($o, $v)
	{
		Cookies::registerPending($v);
	}
	
	// Countable
	public function count()
	{
		return Cookies::getNumPending();
	}
}

class Cookies
{
	// Received Cookies
	protected static $cookies = array();
	
	public static function init()
	{
		foreach($_COOKIE as $name => $value) {
			$cookie = new Cookie($name, $value, true);
			
			if($cookie->valid)
				self::$cookies[$name] = $cookie;
		}
	}
	
	public static function has($name)
	{
		return isset(self::$cookies[$name]);
	}
	
	public static function get($name)
	{
		if(isset(self::$cookies[$name])) {
			return self::$cookies[$name];
		}
		return null;
	}
	
	public static function delete($cookie)
	{
		if(is_string($cookie)) {
			$cookie = new Cookie($cookie);
			$cookie->delete();
		}
		else {
			$cookie->delete();
		}
	}
	
	public static function put($cookie)
	{
		$cookie->send();
	}
	
	public static function getAll()
	{
		return self::$cookies;
	}
	
	// Pending Cookies
	protected static $pending = array();
	
	public static function registerPending($cookie)
	{
		self::$pending[$cookie->name] =& $cookie;
	}
	
	public static function hasPending($cookie)
	{
		if(is_string($cookie))
			return isset(self::$pending[$cookie]);
		return isset(self::$pending[$cookie->name]);
	}
	
	public static function removePending($cookie)
	{
		if(is_string($cookie))
			unset(self::$pending[$cookie]);
		unset(self::$pending[$cookie->name]);
	}
	
	public static function getPending($cookie)
	{
		if(isset(self::$pending[$cookie]))
			return self::$pending[$cookie];
		return null;
	}
	
	public static function &getAllPending()
	{
		return self::$pending;
	}
	
	public static function getNumPending()
	{
		return sizeof(self::$pending);
	}
	
	public static function writePendingToBrowser()
	{
		foreach(self::$pending as $cookie) {
			setcookie($cookie->name, $cookie->getFinalValue(), $cookie->expiry, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httponly);
		}
	}
}

class Cookie implements ArrayAccess
{
	const HASH_ALGO = 'sha256';
	const HASH_SIZE = 64;
	
	public function __construct($name, $value=null, $fromExisting=false)
	{
		$this->name = $name;
		$this->httponly = true;
		$this->secure = false;
		$this->domain = URL::getCurrentDomain();
		$this->path = '/';
		$this->expiry =  time() + 1209600;
		$this->value = $value;
		$this->secret = Config::get('cookies.secretKey', function(){
			throw new Exception("To use cookies, you must set the 'cookies.secretKey' configuration value.");
		});
		$this->valid = false;
		
		if($fromExisting === true)
			$this->validate();		
	}
	
	/* Properties */
	protected $_properties = array();
	
	public function get($property)
	{
		if(isset($this->_properties[$property]))
			return $this->_properties[$property];
		return null;
	}
	
	public function has($property)
	{
		return isset($this->_properties[$property]);
	}
	
	public function forget($property)
	{
		unset($this->_properties[$property]);
	}
	
	public function set($property, $value)
	{
		$this->_properties[$property] = $value;
	}
	
	/* ArrayAccess and PropertyAccess*/
	public function __get($key)
	{
		return $this->get($key);
	}
	
	public function __set($key, $value)
	{
		return $this->set($key, $value);
	}
	
	public function __isset($key)
	{
		return $this->has($key);
	}
	
	public function __unset($key)
	{
		return $this->forget($key);
	}
	
	public function offsetExists($key)
	{
		return $this->has($key);
	}
	
	public function offsetGet($key)
	{
		return $this->get($key);
	}
	
	public function offsetSet($key, $value)
	{
		return $this->set($key, $value);
	}
	
	public function offsetUnset($key)
	{
		return $this->forget($key);
	}
	
	/* Deletes the cookie from the client's browser. */
	public function delete()
	{
		// To delete the cookie, make a blank one with the same name that expires in a minute.
		$this->httponly = false;
		$this->secure = false;
		$this->expiry = time() + 60;
		$this->value = '';
		$this->send();
	}
	
	/* Sends the cookie to the client's browser. */
	public function send()
	{
		// Let the Cookie class handle it.
		Cookies::registerPending($this);
	}
	
	/* Checks the HMAC signature of a cookie to ensure it is valid. */
	protected function validate()
	{
		if(strlen($this->value) < self::HASH_SIZE + 3)
			return;
			
		if(strpos($this->value, '::') === false)
			return;
			
		$last = strrpos($this->value, '::');
		$signature = substr($this->value, $last+2);
		$value = substr($this->value, 0, $last);
		
		if(hash_hmac(self::HASH_ALGO, $value, $this->secret) !== $signature)
			return;
		
		$this->value = $value;
		$this->valid = true;
	}
	
	/* Returns the signed and/or encrypted value to send to the browser. */
	public function getFinalValue()
	{
		// We need to get the value plus the HMAC signature.
		return $this->value.'::'.hash_hmac(self::HASH_ALGO, $this->value, $this->secret);
	}
}