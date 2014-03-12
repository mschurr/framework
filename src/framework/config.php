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
 
 Configuration values will have an effect on the behavior of various framework components. Here is a list 
 of all configuration values that have significance in the framework:
 
 	NAME					ACCEPTED VALUES							DEFAULT					DESCRIPTION
 	config.driver		
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
	protected static $driver = 'filesystem';
	protected static $staticVariables = array();
	protected static $dynamicVariables = array();
	protected static $dirty = false;
	
	/**
	 * Returns the configuration value stored for $key.
	 * If the value does not exist, returns $default instead. If $default is a closure, it will be evaluated.
	 */
	public static /*mixed*/ function get(/*string*/ $key, /*mixed*/ $default=null)
	{
		if(isset(self::$staticVariables[$key]))
			return self::$staticVariables[$key];
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
		if(is_array($key) && $value === null) {
			foreach($key as $k => $v)
				self::set($k, $v);
			return;
		}
		
		if(App::isRunning()) {
			if(isset(self::$staticVariables[$key]))
				throw new Exception("Error: Attempted to overwrite hard coded configuration value.");
			else {
				self::$dirty = true;
				if($value === null)
					unset(self::$dynamicVariables[$key]);
				else
					self::$dynamicVariables[$key] = $value;
			}
		}
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
		return isset(self::$staticVariables[$key]) || isset(self::$dynamicVariables[$key]);
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

	public static function _load()
	{
		// note dont overwrite static variables
	}
	
	public static function _save()
	{
		// only save if dirty data
	}
}
?>