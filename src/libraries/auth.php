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

abstract class Group_Service_Provider
{
	public static abstract /*Group_Provider*/ function load(/*int*/$guid);
	public static abstract /*array<Group_Provider>*/ function groups(/*int*/ $offset=0, /*int*/$limit=null);
	public static abstract /*Group_Provider*/ function create(/*String*/$name);
	public static abstract /*void*/ function delete(/*Group_Provider*/$group);
}

abstract class Group_Provider
{
	public abstract /*void*/ function __construct(/*int*/$id);
	public abstract /*string*/ function name();
	public abstract /*void*/ function setName(/*string*/$name);
	public abstract /*array<int>*/ function privelages();
	public abstract /*bool*/ function hasPrivelage(/*int*/$id);
	public abstract /*bool*/ function hasPrivelages(/*array<int>*/$privelages);
	public abstract /*void*/ function addPrivelage(/*int*/$id);
	public abstract /*void*/ function addPrivelages(/*array<int>*/$privelages);
	public abstract /*void*/ function removePrivelage(/*int*/$id);
	public abstract /*void*/ function removePrivelages(/*array<int>*/$privelages);
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null);
	public abstract /*bool*/ function hasUser(/*User_Provider*/$user);
	public abstract /*void*/ function removeUser(/*User_Provider*/$user);
	public abstract /*void*/ function addUser(/*User_Provider*/$user);
	public abstract /*bool*/ function hasUsers(/*array<User_Provider>*/$users);
	public abstract /*void*/ function removeUsers(/*array<User_Provider>*/$users);
	public abstract /*void*/ function addUsers(/*array<User_Provider>*/$users);
	public abstract /*void*/ function __destruct();
}

abstract class User_Service_Provider
{
	public static abstract /*User_Provider*/ function load(/*int*/$guid);
	public static abstract /*User_Provider*/ function loadByName(/*String*/$name);
	public static abstract /*User_Provider*/ function loadByEmail(/*String*/$email);
	public static abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null);
	public static abstract /*User_Provider*/ function create(/*String*/$username, /*String*/$password);
	public static abstract /*void*/ function delete(/*User_Provider*/$user);
	public static abstract /*User_Provider*/ function login(/*String*/$username, /*String*/$password);
	public static abstract /*void*/ function logout();
	public static abstract /*bool*/ function usernameMeetsConstraints(/*String*/$username);
	
	private static $restricted = array('admin','root','user','username','account','email');
	public static /*bool*/ function passwordMeetsConstraints($username, $password)
	{
		if(!self::usernameMeetsConstraints($username))
			return false;
		
		if(in_array(strtolower($username),self::$restricted))
			return false;
			
		if(strtolower($username) == strtolower($password))
			return false;
			
		if(strlen($password) < 10 || strlen($password) > 100)
			return false;
			
		// TODO: Commonly Used Passwords
		// TODO: Entropy Calculation and Threshold
		
		return true;
	}
}

abstract class User_Provider implements ArrayAccess, Iterator, Countable
{
	public abstract /*void*/ function __construct(/*int*/$id);
	public abstract /*String*/ function email();
	public abstract /*void*/ function setEmail(/*String*/$email);
	public abstract /*String*/ function username();
	public abstract /*void*/ function setUsername(/*String*/$username);
	public abstract /*bool*/ function banned();
	public abstract /*void*/ function setBanned(/*int*/$expireTime);
	public abstract /*void*/ function setPassword(/*String*/$password);
	public abstract /*bool*/ function checkPassword(/*String*/$input);
	public abstract /*void*/ function __destruct();
	public abstract /*array<int>*/ function privelages();
	public abstract /*void*/ function hasPrivelage(/*int*/$id);
	public abstract /*void*/ function hasPrivelages(/*array<int>*/$id);
	public abstract /*void*/ function addPrivelage(/*int*/$id);
	public abstract /*void*/ function addPrivelages(/*array<int>*/$id);
	public abstract /*void*/ function removePrivelage(/*int*/$id) /*throws Exception*/;
	public abstract /*void*/ function removePrivelages(/*array<int>*/$id) /*throws Exception*/;
	public abstract /*array<Group_Provider>*/ function groups();
	public abstract /*array<string:mixed>*/ function properties();
	public abstract /*mixed*/ function getProperty(/*string*/$name);
	public abstract /*void*/ function setProperty(/*string*/$name, /*mixed*/$value);
	public abstract /*bool*/ function hasProperty(/*string*/$name);
	public abstract /*void*/ function deleteProperty(/*string*/$name);
	
	// -------------------------------------------------------------------------
	
	/* Iterator */
	private $__position = 0;
	private $__array;
	private $__keys;
	public /*void*/ function rewind() {
		$this->__position = 0;
		$this->__array = $this->properties();
		$this->__keys = array_keys($this->__array);
	}
	public /*mixed*/ function current() {
		return $this->__array[$this->__keys[$this->__position]];
	}
	public /*mixed*/ function key() {
		return $this->__keys[$this->__position];
	}
	public /*void*/ function next() {
    	++$this->__position;
	}
	public /*bool*/ function valid() {
        return isset($this->__keys[$this->__position]) && isset($this->__array[$this->__keys[$this->__position]]);
	}

	/* Magic Properties */
	public /*mixed*/ function __get(/*String*/$property) {
		if($property == 'email') return $this->email();
		if($property == 'username') return $this->username();
		if($property == 'banned') return $this->banned();
		if($property == 'privelages') return $this->privelages();
		if($property == 'groups') return $this->groups();
		if($property == 'properties') return $this->properties();
		return $this->getProperty($property);
	}
	public /*void*/ function __set(/*String*/$property, /*mixed*/$value) {
		if($property === null) throw new Exception("You can not set a null property.");
		if($property == 'email') return $this->setEmail($value);
		if($property == 'username') return $this->setUsername($value);
		if($property == 'banned') return $this->setBanned($value);
		return $this->setProperty($property, $value);
	}
	
	public /*bool*/ function __isset(/*String*/$property) {
		return ($this->hasProperty($property)
			|| $property == 'email'
			|| $property == 'username'
			|| $property == 'banned'
			|| $property == 'privelages'
			|| $property == 'groups'
			|| $property == 'properties');
	}
	
	public /*void*/ function __unset(/*String*/$property) {
		$this->deleteProperty($property);
	}
	
	/* Array Access */
	public /*bool*/ function offsetExists(/*mixed*/$offset) { return $this->__isset($offset); }
	public /*void*/ function offsetUnset(/*mixed*/$offset) { return $this->__unset($offset); }
	public /*mixed*/ function offsetGet(/*mixed*/$offset) { return $this->__get($offset); }
	public /*void*/ function offsetSet(/*mixed*/$offset, /*mixed*/$value) { return $this->__set($offset, $value);}
	
	/* Countable */
	public /*int*/ function count() { return sizeof($this->properties()); }
	
	/* Constants */
	const SALT_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789{}[]@()!#^-_|+';
}