<?php


/*
	Useful Configuration Values:
		config.driver	 	= 	filesystem|memcached|database
		cache.driver 		= 	filesystem|memcached "file", "database", "apc", "memcached", "redis", "array"
		cache.file
		cache.connection
		cache.table
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
		locale.default
		app.debug
		app.url
		auth.driver
		auth.model
		auth.table
		auth.remindertemplate
		auth.remindertable
		auth.cookie
		auth.driver
		auth.timeout
		auth.cas.port
		auth.cas.host
		auth.cas.path
		auth.cas.cert
		crypt.defaultkey
		session.driver		mysql|cache|file
		session.cache		true|false (does not work if the driver is "cache")
		session.cookie
		session.cryptkey			(encryption key)
		document.titlesuffix
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
	
	public static function gets($key, $closure)
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
	}
}
?>