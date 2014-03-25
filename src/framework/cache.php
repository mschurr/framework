<?php
/*
	Cache Library
	-------------------------------------------------------------------------------------
	
	You can use this library to safely store bits of information in a cache.
	
	* The cache is a high-performance key-value store that is partitioned into sections. Data stored in the cache is available globally across page views.
	
	* Information stored in the cache is VOLATILE (usually it is stored in RAM). It will be lost on system restart. DO NOT STORE PERSISTENT DATA IN THE CACHE.
	
	* The cache is good for storing the results of time-intensive tasks that can be re-used rather than re-calculated. If the cache is wiped, we can always
		re-calculate the missing value. Database results are a good example of something to cache.
	
	* The cache is limited by a maximum memory size. When you attempt to insert something that will exceed the memory limit,
	    the cache will make space by deleting items in the cache in LEAST RECENTLY USED order.
		
	* Information stored in the cache will expire after 60 minutes (unless specified otherwise). Note that information IS NOT GUARANTEED to be available up until that point of time
		(it may be removed to make space for new entries). The system only guarantees that it will not be available after the expiration time.
		
	* Caches are one of the largest factors in application scalability. Caches can be scaled very easily by spreading the data across a cluster of servers (using a hash-map) and 
		PHP by can be scaled easily by adding more app servers behind load balancers. Databases are much more difficult to scale, so we alleviate the load on them by using caching.

	* You need to ensure that your backing data is always in-sync with the cached data. For example, if a user updates a record in the database, you need to update any records in the
		cache that might be affected, too. You should always UPDATE the cache; do not clear cache keys and rely on them to be re-calculated by the next request (this will result in poor performance).
		
	* You may store any type of object in the Cache, provided that the object is serializable. The includes primitive data types, strings, arrays, and most objects by default.
	  For certain objects, you may need to implement the Serializable interface.

	The cache service provides the following API:
	
	$value = Cache::remember($key, $value, $minutes=60)
		- Attempts to retrieve the data for $key from the cache; if it exists, returns the data.
		  If it does not exist, sets the data for $key to $value in the cache and returns $value.
	
	$value = Cache::rememberForever($key, $value)
		- Equivalent to Cache::remember, but the value will be stored with no expiration.
	
	Cache::forget($key)
		- Wipes information for $key from the cache.
	
	Cache::has($key)
		- Returns whether or not the cache has information for $key.
	
	Cache::get($key, $default=null)
		- Returns the information for $key in the cache (if it exists) or $default otherwise.
	
	
	Cache::put($key, $value, $minutes=60)
		- Stores information for $value to $key in the cache for $minutes.
	
	Cache::forever($key, $value)
		- Stores information for $value to $key in the cache forever.
	
	
	Cache::flush()
		- Clears the entire cache.
	
	Cache::all()
		- Returns an array of all keys stored in the cache.
		
	Cache::increment($key, $amount=1)
	Cache::decrement($key, $amount=1)
		- Increments or decrements the value stored at $key in the cache.
		  This operation is atomic, and should be used over instead of put and get.
	
	Any of the above functions can also be used on a section of the cache. Sections of the cache act like the global cache but are isolated from all other parts of the cache.
	For example, Cache::section('name')->remember($key, $value, $minutes=60)

*/


/*
TODO: implement check-and-set
gets(key) -> value, unique
cas(key, value, unique)
*/

class Cache
{
	protected static $driver;
	protected static $driver_name;
	protected static $loaded = false;
	
	/* Object Constructor */
	public static function init($driver=null, $config=array())
	{
		self::$driver_name = $driver;
		$class = 'Cache_Driver_'.self::$driver_name;
		
		if(!class_exists($class)) {
			import('cache-'.self::$driver_name);
		}
		
		self::$driver = new $class($config);
	}
	
	public static function __callStatic($name, $args) {
		if(!self::$loaded)
			self::init(Config::get('cache.driver', 'filesystem'));
		
		if(is_callable(array(self::$driver, $name)))
			return call_user_func_array(array(self::$driver, $name), $args);
		throw new Exception("The method 'Cache::".$name."' does not exist for driver '".self::$driver_name."'.");
	}
}

abstract class Cache_Driver
{	
	/* Initalizes the object. */
	protected $config;
	protected $prefix = '';
	
	public function __construct($config)
	{
		$this->config = $config;
		
		if(isset($config['prefix']))
			$this->prefix = $config['prefix'];
		
		$this->onLoad();
	}
	
	/* Destroys the object. */
	public function __destruct()
	{
		$this->onUnload();
	}
	
	/* Returns a cache section operator. */
	protected $sections = array();
	
	public function section($name)
	{
		if(!isset($sections[$name]))
			$sections[$name] = new Cache_Section($this, $name);
		return $sections[$name];
	}
	
	/* Aliases */
	public function gets($key, $closure, $minutes=60) { return $this->remember($key, $closure, $minutes); } 
	public function clear($key) { return $this->forget($key); }
	public function clearAll() { return $this->flush(); }
	public function forgetAll() { return $this->flush(); }
	public function getAll() { return $this->all(); }
	public function section_gets($section, $key, $closure, $minutes=60) { return $this->section_remember($section, $key, $closure, $minutes=60); }
	public function section_clear($section, $key) { return $this->section_forget($section, $key); }
	public function section_clearAll($section) { return $this->section_flush($section); } 
	public function section_forgetAll($section) { return $this->section_flush($section); } 
	public function section_getAll($section) { return $this->section_all($section); } 
	
	/* Gets a key from the cache (if it exists) or sets the key to $value (or its return value), and returns $value. */
	public function remember($key, $value, $minutes=60)
	{
		if($this->has($key))
			return $this->get($key);
			
		$value = value($value);
		$this->put($key, $value, $minutes);
		return $value;
	}
	
	public function rememberForever($key, $value)
	{
		if($this->has($key))
			return $this->get($key);
			
		$value = value($value);
		$this->forever($key, $value);
		return $value;
	}
	
	/* Gets a key from a sectionof the cache (if it exists) or sets the key to $value (or its return value), and returns $value */
	public function section_remember($section, $key, $closure, $minutes=60)
	{
		if($this->section_has($section, $key))
			return $this->section_get($section, $key);
			
		$value = value($value);
		$this->section_put($section, $key, $value, $minutes);
		return $value;
	}
	
	public function section_rememberForever($section, $key, $closure)
	{
		if($this->section_has($section, $key))
			return $this->section_get($section, $key);
			
		$value = value($value);
		$this->section_forever($section, $key, $value);
		return $value;
	}
	
	// ---- The following methods must be implemented at a driver level.
	
	/* Automatically called when the driver is unloaded. */
	public abstract function onUnload();
	
	/* Automatically called when the driver is loaded. */
	public abstract function onLoad();
	
	/* Clears a key from the cache. */
	public abstract function forget($key);
	
	/* Returns whether or not the cache has a key. */
	public abstract function has($key);
	
	/* Returns a key from the cache (if it exists) or $default on failure. $default may be a closure. */
	public abstract function get($key, $default=null);
	
	/* Stores a value in the cache for a maximum time. Availablity is not guaranteed for this period, but the cached value will be purged after this period. $value may be closure. */
	public abstract function put($key, $value, $minutes=60);
	public abstract function forever($key, $value);
	
	/* Wipes all entries in the cache. */
	public abstract function flush();
	
	/* Gets all the available keys in the cache. */
	public abstract function all();
	
	/* Increments a key (if it exists) or sets it to $count if it doesn't. */
	public abstract function increment($key, $count=1);
	
	/* Decrements a key (if it exists) or sets it to -$count if it doesn't. */
	public abstract function decrement($key, $count=1);	
	
	/* Wipes all entries in a section of the cache. */
	public abstract function section_flush($section);
	
	/* Returns whether a section of the cache has a key. */
	public abstract function section_has($section, $key);
	
	/* Stores a value in a section of the cache for a maximum time. Availablity is not guaranteed for this period, but the cached value will be purged after this period. $value may be closure. */
	public abstract function section_put($section, $key, $value, $minutes=60);
	public abstract function section_forever($section, $key, $value);
	
	/* Gets a key from a section of the cache (if it exists) or $default on failure. $default may be a closure. */
	public abstract function section_get($section, $key, $default=null);
	
	/* Gets all the available keys in the section of the cache. */
	public abstract function section_all($section);
	
	/* Clears a key in a section of the cache. */
	public abstract function section_forget($section, $key);
	
	/* Increments a key in a section of the cache (if it exists) or sets it to $count if it doesn't. */
	public abstract function section_increment($section, $key, $count=1);
	
	/* Decrements a key in a section of the cache (if it exists) or sets it to -$count if it doesn't. */
	public abstract function section_decrement($section, $key, $count=1);
}

class Cache_Section
{
	protected $driver;
	protected $name;
	
	public function __construct(&$driver, $name)
	{
		$this->driver =& $driver;
		$this->name = $name;
	}
	
	public function __call($method, $args)
	{
		$args = array_merge(array($this->name), $args);
		return call_user_func_array(array($this->driver, 'section_'.$method), $args);
	}
	
	public function __get($name)
	{
		if(isset($this->{$name}))
			return $this->{$name};
	}
}