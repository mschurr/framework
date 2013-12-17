<?php
/************************************************
 * Authentication Library
 ************************************************
 
 You will need to implement (using the Auth API):
 	* Route, controller, and view for logins.
	* Route, controller for logouts.
	
 We recommend implementing (using the Auth API):
	* A place to notify users of failed logins.
	* A place to notify users of their last login(s), and to report them fraudulent.
	* Restricted access to certain features based on whether or not the user has entered their password this session.
 
 The Authentication library draws user and group information from the configured User Service and Group Service.
 
 Class methods act independently of the user viewing the page (these should be used primarily for administrative purposes). These are not subject to security measures.
 
 Instance methods act on the session of the user viewing the page (these should be used when performing an action specific to the current user's session). These are subject to security
 measures on the current user's session (e.g. throttling) and may modify the current user's session when called.
 
 Recommended Driver Implementation Details:
 	* Throttling should occur at the rate (for each failed attempt, in seconds): 0 0 0 2 4 8 16 30 60 60 60....
	* Throttling should occur both on the current client and the user account in question.
	* Error messages should not give potential attackers any useful information.
	
 As you implement user-interface controllers, keep these things in mind:
 	* Don't use secret questions.
	* Make sure the user driver enforces good password practices.
	* Password recovery tokens should be stored as hashes. Don't reset; prompt the user to change their password. Throttle attempts.
	* You may wish to never use persistent logins depending on your application's security requirements.
	* Protect registration process against bots and brute force username guessing.
	* Warn users about using persistent logins on public computers. Persistent logins should be opt-in.
	
 Possible extensions:
 	* Two-factor authentication.
*/

/**
 * The Auth class. This class does not contain any implementation; all calls (both static and instance) are passed to the implementing Auth_Driver.
 */
class Auth
{
	protected $driver;
	protected static $driver_class;
	
	public function __construct($session = null, $driver=null)
	{
		if(is_null($driver)) {
			$driver = Config::get('auth.driver', function(){
				throw new Exception("You must configure an authentication driver in order to use the auth library.");	
			});
		}
		if(is_string($driver)) {
			$class = 'Auth_Driver_'.$driver;
		
			if(!class_exists($class)) {
				import('auth-'.$driver);
			}
			
			$driver = new $class($session);
		}
		if($driver instanceof Auth_Driver) {
			$this->driver =& $driver;
			self::$driver_class = get_class($driver);
		}
	}
		
	public function __isset($key) {
		return isset($this->driver->{$key});
	}
	
	public function __get($key) {
		return $this->driver->{$key};
	}
	
	public function __call($name, $args) {
		return call_user_func_array(array($this->driver, $name), $args);
	}
		
	public static function __callStatic($name, $args)
	{
		if(self::$driver_class === null) {
			$driver = Config::get('auth.driver', function(){
				throw new Exception("You must configure an authentication driver in order to use the auth library.");	
			});
			
			$class = 'Auth_Driver_'.$driver;
		
			if(!class_exists($class)) {
				import('auth-'.$driver);
			}
			
			self::$driver_class = $class;
		}
		return call_user_func_array(array(self::$driver_class, $name), $args);
	}
}

/**
 * An extended exception that provides two errors: a public one that is safe to display to the end user, and
 * a private one that should only be looked at internally for debugging. Utilized by the Auth driver API.
 */
class AuthException extends Exception
{
	protected $publicErrorMessage;
	
	public function __construct($message = null, $publicErrorMessage = null, $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->publicErrorMessage = $publicErrorMessage;
	}
	
	public function __toString()
	{
		return $this->publicErrorMessage;
	}
	
	public function getErrorMessage()
	{
		return $this->publicErrorMessage;
	}
}

/**
 * A wrapper interface for details about an authentication attempt. Utilized by the Auth driver API.
 */
abstract class Auth_Attempt implements ArrayAccess
{	
	public final function __get($k) {
		if($k == 'ipaddress')
			return $this->ip();
		if($k == 'userid')
			return $this->userid();
		if($k == 'user')
			return App::getUserService()->load($this->userguid());
		if($k == 'timestamp')
			return $this->timestamp();
		if($k == 'successful')
			return $this->successful();
		if($k == 'fraudulent')
			return $this->fraudulent();
		trigger_error("Unauthorized Access");
	}
	
	public final function __isset($k) {
		if($k == 'ipaddress' || $k == 'user' || $k == 'userid' || $k == 'timestamp' || $k == 'successful' || $k == 'fraudulent')
			return true;
	}
	
	public final function offsetGet($offset) {
		return $this->__get($offset);
	}
	
	public final function offsetExists($offset) {
		return $this->__isset($offset);
	}
	
	public final function offsetUnset($offset){trigger_error("Unauthorized Access");}
	public final function offsetSet($offset,$value){trigger_error("Unauthorized Access");}

	public abstract /*string*/ function ipaddress();
	public abstract /*int*/ function userid();
	public abstract /*int*/ function timestamp();
	public abstract /*bool*/ function successful();
	public abstract /*bool*/ function fraudulent();
}

/**
 * A simple implementation of the Auth_Attempt interface. Implementing drivers may choose to use a different approach.
 */
class Auth_Attempt_Standard extends Auth_Attempt
{
	protected $ipaddress;
	protected $userid;
	protected $timestamp;
	protected $successful;
	protected $fraudulent;
	
	public function __construct($ipaddress,$userid,$timestamp,$successful,$fraudulent)
	{
		$this->ipaddress = $ipaddress;
		$this->userid = (int) $userid;
		$this->timestamp = (int) $timestamp;
		$this->successful = (bool) $successful;
		$this->fraudulent = (bool) $fraudulent;
	}
	
	public /*string*/ function ipaddress() { return $this->ipaddress; }
	public /*int*/ function userid() { return $this->userid; }
	public /*int*/ function timestamp() { return $this->timestamp; }
	public /*bool*/ function successful() { return $this->successful; }
	public /*bool*/ function fraudulent() { return $this->fraudulent; }
}


/**
 * The interface that all authentication drivers must implement.
 */
abstract class Auth_Driver
{
	// -------------- Class Methods Requiring Implementation
	
	/* Terminate all persistent and active sessions for a given user. */
	public static abstract /*void*/ function terminateAllSessionsForUser(User_Provider $user);
	
	/* Returns the failed login attempts for a given user since a unix timestamp. */
	public static abstract /*int*/ function countFailedLoginsForUserSince(User_Provider $user, $time=0);
	public static abstract /*array<Auth_Attempt>*/ function failedLoginsForUserSince(User_Provider $user, $time=0, $limit=50, $offset=0);
	
	/* Returns the all login attempts for a given user since a unix timestamp. */
	public static abstract /*int*/ function countLoginsForUserSince(User_Provider $user, $time=0);
	public static abstract /*array<Auth_Attempt>*/ function loginsForUserSince(User_Provider $user, $time=0, $limit=50, $offset=0);
	
	/* Returns the login attempts reported fradulent for a given user since a unix timestamp. */
	public static abstract /*int*/ function countFraudulentLoginsForUserSince(User_Provider $user, $time=0);
	public static abstract /*array<Auth_Attempt>*/ function fraudulentLoginsForUserSince(User_Provider $user, $time=0, $limit=50, $offset=0);
		
	// -------------- Instance Methods
	protected /*Session_Driver*/ $session;
	protected /*User_Service_Provider*/ $userService;
	protected /*Group_Service_Provider */$groupService;
	
	public final function __construct(Session_Driver $session)
	{
	    $this->userService =& App::getUserService();
		$this->groupService =& App::getGroupService();
		$this->session =& $session;
		$this->load();
	}
	
	public final function __destruct()
	{
		$this->unload();
	}
	
	public final function __get($k)
	{
		if($k == 'user')
			return $this->user();
		if($k == 'loggedIn' || $k == 'isLoggedIn')
			return $this->loggedIn();
		trigger_error("Access to undefined property Auth_Driver->".$k.".");
	}
	
	public final function __isset($k)
	{
		if($k == 'user' || $k == 'loggedIn' || $k == 'isLoggedIn')
			return true;
		return false;
	}
	
	/* Terminates all active sessions and persistent tokens for the current user account (including this one). */
	public /*void*/ function terminateAllSessionsForCurrentUser()
	{
		if($this->loggedIn())
			self::terminateAllSessionsForUser($this->user());
	}
	
	// -------------- Instance Methods Requiring Implementation
	
	/* Reports a fraudulent last successful login (before the one leading to this session) for the current user's account. */
	public abstract /*void*/ function reportFraudulentLogin(Auth_Attempt $attempt);
	
	/* Terminates all active sessions and persistent tokens for the current user account (excluding this one). */
	public abstract /*void*/ function terminateAllOtherSessionsForCurrentUser();
	
	/* Returns whether or not the user has entered their password this session with the last hour. Use this to restrict access to certain functions following persistent logins. */
	public abstract /*bool*/ function userHasEnteredPasswordThisSession();
	
	/* Checks whether or not the provided password is correct for the account this session is currently logged into. Returns true on success.
	   If incorrect, throws an AuthException containing information about what went wrong. An exception is always thrown if the user is not logged in. 
	   This function is subject to security measures on the current client/session and associated user account (e.g. throttling) and should be used instead
	   of the equivalent User_Provider method when such measures are neccesary (ie only on user input). Should indicate that user has entered their password this session. */
	public abstract /*bool*/ function check($password) /*throws AuthException*/;
	
	/* Logs the client in as the provided user. USE WITH CAUTION - NOT SUBJECT TO SECURITY MEASURES AND DOES NOT VERIFY PASSWORD. Should indicate that user has entered their password this session if not from token and call userDidLogin on the User_Service_Provider. */
	public abstract /*void*/ function login(User_Provider $user, $persistent=false, $fromToken=false);
	
	/* Attempts to log the client in to the user with the provided username and password. You may pass any extra information required by your driver into $extra.
	   Returns true on success. If successful, modifies the current session to be logged in as the user and optionally sets persistent tokens.
	   This function is subject to security measures on the current client/session and associated user account (e.g. throttling).
	   If unsuccessful, throws an AuthException containing information about what went wrong (e.g. incorrect password).
	   Should indicate that the user has entered their password this session and call userDidLogin on the User_Service_Provider. */
	public abstract /*bool*/ function attempt($username, $password, $persistent=false, array $extra=array()) /*throws AuthException*/;
	
	/* Terminates the current user's session and (optionally) invalidates any persistent tokens associated with the current client. */
	public abstract /*void*/ function logout($deletePersistent=true);
	
	/* Returns whether or not this session is current logged in. */
	public abstract /*bool*/ function loggedIn();
	
	/* Returns the user that this session is currently logged into or null if not logged in. */
	public abstract /*User_Provider*/ function user();
	
	/* Called automatically when the driver is instantiated. Check for persistent tokens or existing session here. */
	public abstract /*void*/ function load();
	
	/* Called automatically when the driver is deallocated from memory. */
	public abstract /*void*/ function unload();
}


