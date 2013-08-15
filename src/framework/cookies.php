<?php

class Cookie
{
	protected $name = NULL;
	protected $value = "";
	protected $expiry;
	protected $path = '/';
	protected $domain = '';
	protected $secure = false;
	protected $httponly = false;
	
	public function __construct($name = NULL)
	{
		$this->expiry = time() + 14*24*3600;
		$this->name = $name;
		$this->domain = URL::getCurrentDomain();
	}
	
	public function getValue()
	{
		return $this->value;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getExpiry()
	{
		return $this->expiry;
	}
	
	public function getPath()
	{
		return $this->path;
	}
	
	public function getDomain()
	{
		return $this->domain;
	}
	
	public function getSecure()
	{
		return $this->secure;
	}
	
	public function getHTTPOnly()
	{
		return $this->httponly;
	}
	
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
	
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}
	
	public function setExpiry($time)
	{
		$this->expiry = $time;
		return $this;
	}
	
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}
	
	public function setDomain($domain)
	{
		$this->domain = $domain;
		return $this;
	}
	
	public function setSecure($secure)
	{
		$this->secure = $secure;
		return $this;
	}
	
	public function setHTTPOnly($httponly)
	{
		$this->httponly = $httponly;
		return $this;
	}
	
	public function save()
	{
		Cookies::put($this);
		return $this;
	}
	
	public function delete()
	{
		Cookies::del($this);
		return NULL;
	}
	
	public function encrypt($key)
	{
		$ec = new AES_Encryption($key);
		$this->value = $ec->encrypt($this->value)->get();
		return $this;
	}
	
	public function decrypt($key)
	{
		$ec = new AES_Encryption($key);
		$this->value = $ec->decrypt($this->value)->get();
		return $this;
	}
}

class Cookies
{
	protected static $cookies = array();
	
	public static function init()
	{
		if(isset($_COOKIE)) {
			foreach($_COOKIE as $name => $value) {
				$cookie = new Cookie($name);
				$cookie->setValue($value);
				self::$cookies[$name] = $cookie;
			}
		}
	}
	
	public static function has($cookienm)
	{
		return isset(self::$cookies[$cookienm]);
	}
	
	public static function get($cookienm)
	/* Returns a (Cookie) with the given name (if it exists). Otherwise, returns (bool) False. */
	{
		if(isset(self::$cookies[$cookienm]))
			return self::$cookies[$cookienm];
		return false;
	}
	
	public static function put($cookie)
	/* Writes a cookie to the client. Returns void. */
	{
		setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiry(), $cookie->getPath(), $cookie->getDomain(), $cookie->getSecure(), $cookie->getHTTPOnly());
		return NULL;
	}
	
	public static function del($cookie)
	/* Deletes a cookie from the client. Returns void. */
	{
		setcookie($cookie->getName(), '', time() - 3600, '/', '.'.DOMAIN, false, false);
		return NULL;
	}
	
	public static function del_byname($cookienm)
	/* Deletes a cookie with the given name from the client. Returns void.*/
	{
		if(isset(self::$cookies[$cookienm]))
			return self::del(self::$cookies[$cookienm]);
		return NULL;
	}
	
	public static function getAll()
	/* Returns an array of all currently set cookies as array( (string) name => (Cookie) cookie, ..+). */
	{
		return self::$cookies;
	}
	
} Cookies::init();
?>