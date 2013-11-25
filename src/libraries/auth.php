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
}

abstract class User_Service_Provider
{
	public static abstract /*User_Provider*/ function load(/*int*/$guid);
	public static abstract /*User_Provider*/ function loadByName(/*String*/$name);
	public static abstract /*User_Provider*/ function loadByEmail(/*String*/$email);
	public static abstract /*array<Group_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null);
	public static abstract /*User_Provider*/ function create(/*String*/$username, /*String*/$password);
	public static abstract /*void*/ function delete(/*User_Provider*/$user);
	public static abstract /*bool*/ function login(/*String*/$username, /*String*/$password);
	public static abstract /*void*/ function logout();
	public static abstract /*bool*/ function usernameMeetsConstraints(/*String*/$username);
	
	const RESTRICTED_USERNAMES = array('admin','root','user','username','account','email');
	public static /*bool*/ function passwordMeetsConstraints($username, $password)
	{
		if(!self::usernameMeetsConstraints($username))
			return false;
		
		if(in_array(strtolower($username),self::RESTRICTED_USERNAMES))
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
	
	/* Array Access */
	public abstract /*bool*/ function offsetExists(/*mixed*/$offset);
	public abstract /*void*/ function offsetUnset(/*mixed*/$offset);
	public abstract /*mixed*/ function offsetGet(/*mixed*/$offset);
	public abstract /*void*/ function offsetSet(/*mixed*/$offset);
	
	/* Countable */
	public abstract /*int*/ function count();
	
	/* Iterator */
	public abstract /*void*/ function rewind();
	public abstract /*mixed*/ function current();
	public abstract /*mixed*/ function key();
	public abstract /*void*/ function next();
	public abstract /*bool*/ function valid();

	/* Magic Properties */
	public abstract /*mixed*/ function __get(/*String*/$property);
	public abstract /*void*/ function __set(/*String*/$property, /*mixed*/$value);
	public abstract /*bool*/ function __isset(/*String*/$property);
	public abstract /*void*/ function __unset(/*String*/$property);
	
	/* Constants */
	const SALT_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789{}[]@()!#^-_|+';
}