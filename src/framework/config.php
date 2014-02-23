<?php
/**************************************************
 * Configuration Library
 **************************************************
 
 You can use this class to store values (all primitive types and arrays) that persist through page views
 across all nodes for all users forever.
 
 This class is best used for storing small, configuration values; do not store large amounts of data. You 
 will significantly hinder application performance. To store large amounts of data, use the Storage class.
 
 There are two types of configuration variables: hard-coded and on-the-fly. Hard-coded values can not be
 overwritten from within the application runtime and may only be defined in webapp.php.
 
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

TODO: 
	persistance, hard coding not overwritable
	'mailer.name'       => 'crush@riceapps.org',
	'mailer.email'      => 'crush@riceapps.org',
	'mailer.host'       => '.hostmonster.com',
	'mailer.port'       => '465',
	'mailer.user'       => 'crush@riceapps.org',
	'mailer.pass'       => '',
	'mailer.crypt'      => 'ssl',
	'recaptcha.publicKey' => '',
	'recaptcha.privateKey' => ''
*/

/*
	Useful Configuration Values:
		cache.connection
		cache.prefix
		cache.memcached		=   array(array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100))
		captcha.driver 		= 	cookies|session|recaptcha
		database.default
		database.connections = 
		'sqlite' => array(
			'driver'   => 'sqlite',
			'database' => __DIR__.'/../database/production.sqlite',
			'prefix'   => '',
		),

		'mysql' => array(
			'driver'    => 'mysql',
			'host'      => 'localhost',
			'database'  => 'database',
			'username'  => 'root',
			'password'  => '',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),

		'pgsql' => array(
			'driver'   => 'pgsql',
			'host'     => 'localhost',
			'database' => 'database',
			'username' => 'root',
			'password' => '',
			'charset'  => 'utf8',
			'prefix'   => '',
			'schema'   => 'public',
		),

		'sqlsrv' => array(
			'driver'   => 'sqlsrv',
			'host'     => 'localhost',
			'database' => 'database',
			'username' => 'root',
			'password' => '',
			'prefix'   => '',
		),
		crypt.key
		time.zone
		app.debug
		app.url
		crypt.defaultkey
		session.driver		mysql|cache|file
		session.cache		true|false (does not work if the driver is "cache")
		session.cookie
		session.cryptkey			(encryption key)
		
*/

class Config
{
	protected static $driver = 'filesystem';
	protected static $vars = array();
	
	/***
	  * public static object get( string $key, object $default=null )
	  * - returns the configuration variable $key or (on failure) the provided default value
	  * - $default may be an object of any type (including closures)
	  */
	public static function get($key, $default=null)
	{
		if(isset(self::$vars[$key]))
			return self::$vars[$key];
		return value($default);
	}
	
	/***
	 * public static void set( string $key, object $value )
	 * - sets the configuration variable $key to $value
	 * - $value may be an object of any type (including closures)
	 */
	public static function set($key, $value=null)
	{
		if(is_array($key) && $value === null) {
			foreach($key as $k => $v)
				self::set($k, $v);
			return;
		}
		
		self::$vars[$key] = value($value);
	}
	
	public static function has($key)
	{
	}
	
	public static function remember($key, $closure)
	{
	}
	
	public static function _restore($key, $object)
	{
	}
	
	protected static function load()
	{
	}
	
	protected static function save()
	{
		// change behavior based on app::isRunning
	}
}
?>