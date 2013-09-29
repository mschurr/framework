<?php
// alerts of failed logins
// notify last login and report fraudulent
// ability to terminate all persistent sessions
// tokens older than certain time are invalid
// method to tell whether user has entered their password this session (or was logged in via a token) to restrict access to certain functions

class Auth
{
	protected $_session;
	protected $_provider;
	
	public function __construct(Session_Driver $session, $driver = null)
	{
		$this->_session = $session;
		
		if(is_null($driver))
			$driver = Config::get('auth.driver', 'db');
		
		$class = 'User_Service_Provider_'.$driver;
		
		if(!class_exists($class)) {
			import('auth-'.$driver);
		}
		
		$this->driver = new $class();
		
		// Authentication Logic and Expiration
		
		// According to the session, is the user already logged in?
		
		// If not, check for a persistent login token. If the token is valid, log the user in and invalidate it [throttle]. Generate a new persistent token and set it. (NOTE TOKENS STORED AS HASHES IN DB)
		
		// Let's check for sessionexpiration
		
		// Make sure account exists and is not banned / is valid
		
		// Fingerprint changes invalidate login
	}
	
	public function __destruct()
	{
	}
	
	public function __get($k)
	{
		if($k == 'user')
			return $this->_provider;
		if($k == 'loggedIn' || $k == 'isLoggedIn')
			return $this->loggedIn();
		return null;
	}
	
	public function loggedIn()
	{
		return false;
	}
	
	public function hasPrivelage($id)
	{
	}
	
	public function logout()
	{
		// invalidate any persistent tokens
	}
	
	public function __isset($k)
	{
		if($k == 'user' || $k == 'loggedIn' || $k == 'isLoggedIn')
			return true;
		return false;
	}
	
	public function user()
	{
		return $this->_provider;
	}
	
	public function check($username, $password)
	{
	}
	
	public function login($userid, $remember=false)
	{
	}
	
	public function attempt($username, $password, $remember=false)
	{
		// [throttle] 0 0 0 2 4 8 16 30 60 60 60
		// no error info
		
		// on success, regenerate session and write changes to session
	}
}

abstract class User_Service_Provider
{
	// loadByGuid()
	// loadByName()
	// loadByEmail()
}

abstract class Group_Service_Provider
{
	// loadByGuid()
}

abstract class Group_Provider
{
	// hasPrivelage()
	// hasPrivelages()
	// listUsers()
}

abstract class User_Provider implements Iterator, ArrayAccess, Countable
{
	public static abstract function makeWithPassword($username, $password);
	public abstract function getEmail();
	public abstract function getUsername();
	public abstract function verify($password); // also rehashes
	public abstract function isBanned();
	public abstract function setBanned($state); // time|true|false
	public abstract function hasPrivelage($id);
	public abstract function hasPrivelages($array);
	public abstract function inGroup($id);
	public abstract function setGroup($id);
	public abstract function setProperty($name, $value);
	public abstract function getProperty($name);
	public abstract function save();
	public abstract function onLoad($guid);
	public abstract function onUnload();
	
	// ----- Concrete Implementations
	public function __construct()
	{
	}
	
	public function __destruct()
	{
	}
	
	public function passwordIsValid($username, $password)
	{
		// Restricted Usernames
		$restrict = array('admin', 'root', 'user', 'username', 'account', 'email');
		if(in_array(strtolower($username), $restrict))
			return false;
			
		// Username != Password
		if(strtolower($username) == strtolower($password)) {
			return false;
		}
		
		// Length
		if(strlen($password) > 100 || strlen($password) < 10)
			return false;
			
		// Commonly Used Passwords
		// TODO
		
		// Entropy Calculation and Threshold
		// TODO
		
		return true;
	}
}

/*
abstract class User_ProviderOld implements Iterator, ArrayAccess, Countable
{
	protected $data = array();
	protected $saltchars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789{}[]@()!#^-_|+';
	protected $codechars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
	
	// Master Implemented
	public function __construct()
	{
		$this->onLoad();
	}
	
	// Provider Implemented
	public abstract function onLoad();
	public abstract function verify($password);
	public abstract function load($id);
	public abstract function loadByName($username);
	public abstract function loadByEmail($email);
	public abstract function hasPrivelage($pid);
	public abstract function hasPrivelages($array);
	public abstract function inGroup($id=false);
	public abstract function setProperty($prop, $value);
	public abstract function save();
	
	// Object Properties
	
	public function __get($key)
	{
		if(isset($this->data[$key]))
			return $this->data[$key];
		return null;
	}
	
	public function __set($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function __isset($key, $value)
	{
		return isset($this->data[$key]);
	}
	
	public function __unset($key)
	{
		unset($this->data[$key]);
	}
	
	public function __len()
	{
		return sizeof($this->data);
	}
	
	// Countable
	
	public function count()
	{
		return sizeof($this->data);
	}
	
	// Iterator
	
	protected $__position = 0;
	
	public function rewind() {
		$this->__position = 0;
	}
	
	public function current() {
		$keys = array_keys($this->data);
		return $this->data[$keys[$this->__position]];
	}
	
	public function key() {
		$keys = array_keys($this->data);
		return $keys[$this->__position];
	}
	
	public function next() {
		++$this->__position;
	}
	
	public function valid() {
		$keys = array_keys($this->data);
		return isset($keys[$this->__position]) && isset($this->data[$keys[$this->__position]]);
	}
	
	// ArrayAcess
	
	public function offsetSet($offset, $value) {
		if(is_null($offset))
			return;
		return $this->__set($offset, $value);
	}
	
	public function offsetExists($offset) {
		return $this->__isset($offset);
	}
	
	public function offsetGet($offset) {
		return $this->__get($offset);
	}
	
	public function offsetUnset($offset) {
		return $this->__unset($offset);
	}
}*/