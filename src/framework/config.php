<?php
/**************************************************
 * Configuration Library
 **************************************************
 
 You can use this class to store values (all primitive types and arrays) that persist through page views
 across all nodes for all users forever.
 
 This class is best used for storing small, configuration values; do not store large amounts of data. You 
 will significantly hinder application performance. To store large amounts of data, use a different solution.
 
 There are two types of configuration variables: hard-coded and on-the-fly. Hard-coded values can not be
 overwritten from within the application runtime and may only be defined in config.php.

 You may store any type of serializable data in the configuration system. This includes all primitive data types,
  strings, arrays, and most objects by default. In some cases, an object may need to implement the Serializable
  interface in order to be stored.
 
 Configuration values will have an effect on the behavior of various framework components. Here is a list 
 of all configuration values that have significance in the framework:
 
 	NAME					ACCEPTED VALUES							DEFAULT					DESCRIPTION
 	config.driver			db 										null
	cache.driver			filesystem,memcached,apc,array,redis	filesystem				Determines the Cache driver to load.
	cache.file				<path>									~/cache/cache.dat		A storage location for the Cache file for certain drivers. Okay to use a RAMDISK location.
	cache.directory			<path>									~/cache					A storage location for Cache files for certain drivers. Okay to use a RAMDISK.								
	cookies.secretKey		<string>														Used to validate cookies with HMAC hashing. Throws an error if not set and you attempt to use cookies.
	csrf.driver				cookies|session							session					Determines where to store CSRF tokens.
	locale.default			<string>								"en"					Determines the default locale.
	users.driver			db																Determines the User Services driver to load.
	groups.driver			db																Determines the Group Services driver to load.
	session.driver 			cache
	auth.driver				db																Determines the Auth driver to load.
	auth.cas.port			<int>															For CAS Auth Driver: the server port
	auth.cas.host			<string>														For CAS Auth Driver: the server host name
	auth.cas.path			<string>														For CAS Auth Driver: the server path
	auth.cas.cert			<path>															For CAS Auth Driver: the location of server certificate on disk
	document.titlesuffix	<string>								""						The suffix to append to all HTML document titles.
	database.driver			mysql									mysql					Determines the Database driver to load.
	database.user			<string>														The database username.
	database.pass			<string>														The database password.
	database.host			<string>														The database hostname or ipaddress.
	database.port			<int>															The database server port.
	database.name			<string>														The name of database to use
	http.loadbalanced		<bool>									false					Whether or not the server is behind a load balancer and should pull IP/PROTO from X-Forwarded-Proto and X-Forwarded-For headers.
	app.development			<bool>									true 					Whether or not to use development mode (displays pretty stack traces)
	mailer.name
	mailer.email
	mailer.host
	mailer.port
	mailer.user
	mailer.pass
	mailer.crypt    		<string>														Example: 'ssl'
	recaptcha.publicKey
	recaptcha.privateKey
	time.zone
*/

class Config
{
	protected static $staticVariables = array();
	protected static $dynamicVariables = array();
	protected static $dirtyVariables = array();
	protected static $loaded = false;
	protected static /*Config_Storage_Driver*/ $driver;
	
	/**
	 * Returns the configuration value stored for $key.
	 * If the value does not exist, returns $default instead. If $default is a closure, it will be evaluated.
	 */
	public static /*mixed*/ function get(/*string*/ $key, /*mixed*/ $default=null)
	{
		if(isset(self::$staticVariables[$key]))
			return self::$staticVariables[$key];
		if(!self::$loaded)
			self::_load();
		if(isset(self::$dynamicVariables[$key]))
			return self::$dynamicVariables[$key];
		return value($default);
	}
	
	/**
	 * Sets the configuration values stored for $key to $value.
	 * If $value is a closure, it will be evaluated.
	 * If $value is null, $key can be an array map of keys to values.
	 */
	public static /*void*/ function set(/*mixed*/ $key, /*mixed*/ $value=null)
	{
		// Handle updating an array of values.
		if(is_array($key) && $value === null) {
			foreach($key as $k => $v)
				self::set($k, $v);
			return;
		}
		
		// Handle updating runtime variables.
		if(App::isRunning()) {
			if(isset(self::$staticVariables[$key]))
				throw new RuntimeException("Attempted to overwrite hard coded configuration value at runtime.");
			
			if(!self::$loaded)
				self::_load();

			self::$dirtyVariables[$key] = true;

			if($value === null)
				unset(self::$dynamicVariables[$key]);
			else
				self::$dynamicVariables[$key] = $value;
		}
		// Handle updating hard-coded variables.
		else {
			if($value === null)
				unset(self::$staticVariables[$key]);
			else
				self::$staticVariables[$key] = $value;
		}
	}
	
	/** 
	 * Returns whether or not any information is stored for $key.
	 */
	public /*bool*/ static function has(/*string*/ $key)
	{
		if(isset(self::$staticVariables[$key]))
			return true;
		if(!self::$loaded)
			self::_load();
		return isset(self::$dynamicVariables[$key]);
	}
	
	/**
	 * Forgets the stored configuration value for $key.
	 */
	public static /*void*/ function forget(/*string*/ $key)
	{
		self::set($key, null);
	}

	/**
	 * Stores $value for $key if and only if a value does not already exist for $key.
	 */
	public static /*void*/ function remember(/*String*/ $key, /*mixed*/ $value)
	{
		if(self::has($key))
			return;

		self::set($key, $value);
	}

	public static /*void*/ function _load()
	{
		// Configuration has been loaded.
		self::$loaded = true;

		// Figure out which driver to use.
		$driver_name = self::get('config.driver', null);

		// Don't do anything if no driver is configured.
		if($driver_name === null) {
			return;
		}	

		// Attempt to instantiate the driver.
		$driver_class = 'Config_Storage_Driver_'.$driver_name;

		if(!class_exists($driver_class))
			import('config-'.$driver_name);

		self::$driver = new $driver_class();

		// Load the configuration variables from the driver.
		self::$dynamicVariables = self::$driver->load();
	}
	
	public static /*void*/ function _save()
	{
		// If there are dirty variables that need saving, attempt a load.
		if(count(self::$dirtyVariables) > 0 && !self::$loaded)
			self::_load();

		// If a driver does not exist, we can do nothing.
		if(self::$driver === null) {
			// Unless there are things that need to be saved, in which case this is an error.
			if(count(self::$dirtyVariables) > 0) {
				throw new RuntimeException("Attempted to save configuration values at runtime, but no driver installed. You must set [config.driver].");
			}
			return;
		}

		// Write the dirty information to persistant storage.
		if(count(self::$dirtyVariables) > 0)
			self::$driver->save(self::$dynamicVariables, self::$dirtyVariables);
	}
}

abstract class Config_Storage_Driver
{
	/**
	 * Writes configured values into a persistent data store.
	 */
	public abstract /*void*/ function save(/*array<string:mixed>*/ &$data, /*array<string:bool> modified*/ &$modified);

	/**
	 * Reads configured values from a persistent data store.
	 */
	public abstract /*array<string:mixed>*/ function load();
}

?>