<?php
/************************************************
 * Authentication Library
 ************************************************
 
 You will need to implement:
 	* Route, controller, and view for logins.
	* Route, controller for logouts.
	
 We recommend implementing:
	* A system to notify users of failed logins.
	* A system to notify users of their last login, and to report it fraudulent.
	* Restricted access to certain features based on whether or not the user has entered their password this session or was logged in via persistent tokens.
 
 The Authentication library draws user and group information from the configured User Service and Group Service Providers.
 
 Class methods act independently of the user viewing the page (these should be used primarily for administrative purposes). These are not subject to security measures.
 
 Instance methods act on the session of the user viewing the page (these should be used when performing an action specific to the current user). These are subject to security
 measures on the current user's session (e.g. throttling) and may modify the current user's session when called.
 
 Recommended Driver Implementation Details:
 	* Throttling should occur at the rate (for each failed attempt, in seconds): 0 0 0 2 4 8 16 30 60 60 60....
	* Throttling should occur both on the current client and the user account in question.
	* Error messages should not give potential attackers any useful information.
 
*/

class Auth
{
	protected $driver;
	protected $driver_class;
	
	public function __construct(Session_Driver $session, $driver=null)
	{
		if(is_null($driver)) {
			$driver = Config::get('auth.driver', function(){
				throw new Exception("You must configure an authentication driver in order to use the auth library.");	
			});
		}
		if(is_string($driver)) {
		
		}
		if($driver instanceof Auth_Driver) {
		
		}
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
 * A wrapper class for details about an authentication attempt. Utilized by the Auth driver API.
 */
abstract class Auth_Attempt implements ArrayAccess
{	
	public final function __get($k) {
		if($k == 'ip')
			return $this->ip();
		if($k == 'user')
			return App::getUserService()->load($this->userguid());
		if($k == 'timestamp')
			return $this->timestamp();
		if($k == 'successful')
			return $this->successful();
		trigger_error("Unauthorized Access");
	}
	
	public final function __isset($k) {
		if($k == 'ip' || $k == 'user' || $k == 'timestamp' || $k == 'successful')
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

	public abstract /*string*/ function ip();
	public abstract /*int*/ function userguid();
	public abstract /*int*/ function timestamp();
	public abstract /*bool*/ function successful();
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
	protected /*User_Service_Provider*/ $users;
	protected /*Group_Service_Provider */$groups;
	
	public final function __construct(Session_Driver $session)
	{
		$this->users =& App::getUserService();
		$this->groups =& App::getGroupService();
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
	
	/* Returns whether or not the user has entered their password this session. Use this to restrict access to certain functions following persistent logins. */
	public abstract /*bool*/ function userHasEnteredPasswordThisSession();
	
	/* Checks whether or not the provided password is correct for the account this session is currently logged into. Returns true on success.
	   If incorrect, throws an AuthException containing information about what went wrong. An exception is always thrown if the user is not logged in. 
	   This function is subject to security measures on the current client/session and associated user account (e.g. throttling) and should be used instead
	   of the equivalent User_Provider method when such measures are neccesary (ie only on user input). Should indicate that user has entered their password this session. */
	public abstract /*bool*/ function check($password) /*throws AuthException*/;
	
	/* Logs the client in as the provided user. USE WITH CAUTION - NOT SUBJECT TO SECURITY MEASURES AND DOES NOT VERIFY PASSWORD. Should indicate that user has entered their password this session. */
	public abstract /*void*/ function login(User_Provider $user, $persistent=false);
	
	/* Attempts to log the client in to the user with the provided username and password. You may pass any extra information required by your driver into $extra.
	   Returns true on success. If successful, modifies the current session to be logged in as the user and optionally sets persistent tokens.
	   This function is subject to security measures on the current client/session and associated user account (e.g. throttling).
	   If unsuccessful, throws an AuthException containing information about what went wrong (e.g. incorrect password).
	   Should indicate that the user has entered their password this session. */
	public abstract /*bool*/ function attempt($username, $password, $persistent=false, array $extra=array()) /*throws AuthException*/;
	
	/* Terminates the current user's session and invalidates any persistent tokens associated with the current client. */
	public abstract /*void*/ function logout();
	
	/* Returns whether or not this session is current logged in. */
	public abstract /*bool*/ function loggedIn();
	
	/* Returns the user that this session is currently logged into or null if not logged in. */
	public abstract /*User_Provider*/ function user();
	
	/* Called automatically when the driver is instantiated. */
	public abstract /*void*/ function load();
	
	/* Called automatically when the driver is deallocated from memory. */
	public abstract /*void*/ function unload();
}


