<?php
/**
 * Session Driver
 * -----------------------------------------------------------------------------------------------------------------------
 *
 * This class by itself is abstract; it requires a concrete <Session_Driver> implementation.
 *
 * Will probably also add support for Iterator, Countable
 */

class Session implements ArrayAccess
{
	protected $driver = null;
	
	public function __construct($driver=null)
	{
		if(is_null($driver))
			$driver = Config::get('session.driver', 'cache');
		
		$class = 'Session_Driver_'.$driver;
		
		if(!class_exists($class)) {
			import('sessions-'.$driver);
		}
		
		$this->driver = new $class();
	}
	
	public function __get($key)
	{
		return $this->driver->get($key);
	}
	
	public function __set($key, $value)
	{
		return $this->driver->set($key, $value);
	}
	
	public function __isset($key)
	{
		return $this->driver->has($key);
	}
	
	public function __unset($key)
	{
		return $this->driver->forget($key);
	}
	
	public function offsetExists($key)
	{
		return $this->driver->has($key);
	}
	
	public function offsetGet($key)
	{
		return $this->driver->get($key);
	}
	
	public function offsetSet($key, $value)
	{
		return $this->driver->set($key, $value);
	}
	
	public function offsetUnset($key)
	{
		return $this->driver->forget($key);
	}
	
	public function __call($name, $args)
	{
		return call_user_func_array( array($this->driver, $name), $args );
	}
	
	public function __destruct()
	{
	}
	
	public function __toString()
	{
		return "<Session@".$this->driver->id().">";
	}
}

abstract class Session_Driver implements ArrayAccess
{
	protected $_auth;
	protected $_cookie;
	
	public function __construct()
	{
		$this->_cookie = Config::get('session.cookie', 'sessiond');
		$this->load();
		$this->_auth = new Auth($this);
	}
	
	public function __destruct()
	{
		$this->unload();
	}
	
	/* If the user is logged in, returns a <User_Provider> for the account. Returns null otherwise. */
	public function user()
	{
		return $this->_auth->user();
	}
	
	/* Returns an <Auth> object linked to the current session. */
	public function auth()
	{
		return $this->_auth;
	}
		
	/* Called automatically on driver load. */
	public abstract function load();
	
	/* Called automatically on driver unload. */
	public abstract function unload();
	
	/* Gets a session value (if it exists) or returns $default otherwise. */
	public abstract function get($key, $default=null);
	
	/* Sets a session value for the given key. */
	public abstract function set($key, $value);
	
	/* Returns whether or not the session has a value for the given key. */
	public abstract function has($key);
	
	/* Forgets the remembered value for the given key (if it exists). */
	public abstract function forget($key);
	
	/* Sets a value for the given key that will be available only on the next request. */
	public abstract function flash($key, $value=null);
	
	/* Keeps the value for a given key in memory that was flashed. */
	public abstract function keep($key);
	
	/* Returns the raw session identifier. You should not store this anywhere else unless you hash it first. */
	public abstract function id();
	
	/* Calculates and sets a new session identifier. Useful for preventing session fixation. Occurs automatically on certain events and periodically to prevent fixation. */
	public abstract function regenerate();

	/* Forces an immediate save of the session. */
	public abstract function save();
	
	// --- Magic Methods
	public function __get($key)
	{
		if($key == 'id')
			return $this->id();
		if($key == 'auth')
			return $this->auth();
		if($key == 'user')
			return $this->user();
		if($key == 'loggedIn')
			return $this->auth()->loggedIn();
		return $this->get($key);
	}
	
	public function __set($key, $value)
	{
		return $this->set($key, $value);
	}
	
	public function __isset($key)
	{
		if($key == 'id'
		|| $key == 'auth'
		|| $key == 'user'
		|| $key == 'loggedIn')
			return true;
		return $this->has($key);
	}
	
	public function __unset($key)
	{
		return $this->forget($key);
	}
	
	public function offsetExists($key)
	{
		if($key == 'id'
		|| $key == 'auth'
		|| $key == 'user'
		|| $key == 'loggedIn')
			return true;
		return $this->has($key);
	}
	
	public function offsetGet($key)
	{
		if($key == 'id')
			return $this->id();
		if($key == 'auth')
			return $this->auth();
		if($key == 'user')
			return $this->user();
		if($key == 'loggedIn')
			return $this->auth()->loggedIn();
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
}
