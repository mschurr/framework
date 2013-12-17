<?php

class Auth_Driver_db extends Auth_Driver
{
	protected $user;
	protected $db;
	protected $request;
	protected $response;
	
	// -------------- Class Methods Requiring Implementation
	
	/* Terminate all persistent and active sessions for a given user. */
	public static /*void*/ function terminateAllSessionsForUser(User_Provider $user)
	{
		$statement = $this->db->prepare("DELETE FROM `auth_tokens` WHERE `userid` = ?;");
		$statement->execute(array($user->id));
		
		$statement = $this->db->prepare("DELETE FROM `session_tokens` WHERE `userid` = ?;");
		$statement->execute(array($user->id));
	}
	
	/* Returns the failed login attempts for a given user since a unix timestamp. */
	public static /*int*/ function countFailedLoginsForUserSince(User_Provider $user, $time=0)
	{
		$statement = $this->db->prepare("SELECT COUNT(*) AS `count` FROM `auth_attempts` WHERE `userid` = ? AND `timestamp` >= ? AND `successful` = 0;");
		$query = $statement->execute(array($user->id, $time));
		return $query['count'];
	}
	
	public static /*array<Auth_Attempt>*/ function failedLoginsForUserSince(User_Provider $user, $time=0, $limit=50, $offset=0)
	{
		$statement = $this->db->prepare("SELECT * FROM `auth_attempts` WHERE `userid` = ? AND `timestamp` >= ? AND `successful` = 0 LIMIT ".(int)$offset.",".(int)$limit.";");
		$query = $statement->execute(array($user->id, $time));
		
		$result = array();
		
		foreach($query as $row) {
			$attempt = new Auth_Attempt_Standard($row['ipaddress'],$row['userid'],$row['timestamp'],$row['successful'],$row['fraudulent']);
			$result[] = $attempt;
		}
				
		return $result;
	}
	
	/* Returns the all login attempts for a given user since a unix timestamp. */
	public static /*int*/ function countLoginsForUserSince(User_Provider $user, $time=0)
	{
		$statement = $this->db->prepare("SELECT COUNT(*) AS `count` FROM `auth_attempts` WHERE `userid` = ? AND `timestamp` >= ?;");
		$query = $statement->execute(array($user->id, $time));
		return $query['count'];
	}
	
	public static /*array<Auth_Attempt>*/ function loginsForUserSince(User_Provider $user, $time=0, $limit=50, $offset=0)
	{
		$statement = $this->db->prepare("SELECT * FROM `auth_attempts` WHERE `userid` = ? AND `timestamp` >= ? LIMIT ".(int)$offset.",".(int)$limit.";");
		$query = $statement->execute(array($user->id, $time));
		
		$result = array();
		
		foreach($query as $row) {
			$attempt = new Auth_Attempt_Standard($row['ipaddress'],$row['userid'],$row['timestamp'],$row['successful'],$row['fraudulent']);
			$result[] = $attempt;
		}
				
		return $result;
	}
	
	/* Returns the login attempts reported fradulent for a given user since a unix timestamp. */
	public static /*int*/ function countFraudulentLoginsForUserSince(User_Provider $user, $time=0)
	{
		$statement = $this->db->prepare("SELECT COUNT(*) AS `count` FROM `auth_attempts` WHERE `userid` = ? AND `timestamp` >= ? AND `fraudulent` = 1;");
		$query = $statement->execute(array($user->id, $time));
		return $query['count'];
	}
	
	public static /*array<Auth_Attempt>*/ function fraudulentLoginsForUserSince(User_Provider $user, $time=0, $limit=50, $offset=0)
	{
		$statement = $this->db->prepare("SELECT * FROM `auth_attempts` WHERE `userid` = ? AND `timestamp` >= ? AND `fraudulent` = 1 LIMIT ".(int)$offset.",".(int)$limit.";");
		$query = $statement->execute(array($user->id, $time));
		
		$result = array();
		
		foreach($query as $row) {
			$attempt = new Auth_Attempt_Standard($row['ipaddress'],$row['userid'],$row['timestamp'],$row['successful'],$row['fraudulent']);
			$result[] = $attempt;
		}
				
		return $result;
	}
	
	/* Returns the failed login attempts for a given ip since a unix timestamp. */
	public static /*int*/ function countFailedLoginsForAddressSince($ip, $time=0)
	{
		$statement = $this->db->prepare("SELECT COUNT(*) AS `count` FROM `auth_attempts` WHERE `ipaddress` = ? AND `timestamp` >= ? AND `successful` = 0;");
		$query = $statement->execute(array($ip, $time));
		return $query['count'];
	}
	
	public static /*array<Auth_Attempt>*/ function failedLoginsForAddressSince($ip, $time=0, $limit=50, $offset=0)
	{
		$statement = $this->db->prepare("SELECT * FROM `auth_attempts` WHERE `ipaddress` = ? AND `timestamp` >= ? AND `successful` = 0 LIMIT ".(int)$offset.",".(int)$limit.";");
		$query = $statement->execute(array($ip, $time));
		
		$result = array();
		
		foreach($query as $row) {
			$attempt = new Auth_Attempt_Standard($row['ipaddress'],$row['userid'],$row['timestamp'],$row['successful'],$row['fraudulent']);
			$result[] = $attempt;
		}
				
		return $result;
	}
		
	// -------------- Instance Methods Requiring Implementation
	
	/* Reports a fraudulent last successful login (before the one leading to this session) for the current user's account. */
	public /*void*/ function reportFraudulentLogin(Auth_Attempt $attempt)
	{
		$statement = $this->db->prepare("UPDATE `auth_attempts` SET `fraudulent` = 1 WHERE `userid` = ? AND `timestamp` = ? AND `ipaddress` = ?;");
		$query = $statement->execute(array($attempt->userid, $attempt->timestamp, $attempt->ipaddress));
	}
	
	/* Terminates all active sessions and persistent tokens for the current user account (excluding this one). */
	public /*void*/ function terminateAllOtherSessionsForCurrentUser()
	{
		if(!$this->loggedIn())
			return;
			
		$statement = $this->db->prepare("DELETE FROM `auth_tokens` WHERE `userid` = ? AND `token_hash` != ?;");
		$token = isset($this->request->cookies['_auth_token']) ? hash('sha512',$this->request->cookies['_auth_token']->value) : '';
		$query = $statement->execute(array($user->id, $token));
		
		$statement = $this->db->prepare("DELETE FROM `auth_sessions` WHERE `userid` = ? AND `token_hash` != ?;");
		$token = isset($this->session['_auth_session']) ? $this->session['_auth_session'] : '';
		$query = $statement->execute(array($user->id, $token));
	}
	
	/* Returns whether or not the user has entered their password this session within the last hour. Use this to restrict access to certain functions following persistent logins. */
	public /*bool*/ function userHasEnteredPasswordThisSession()
	{
		if(!isset($this->session['_auth_passwordentered']))
			return false;
			
		if(time() - $this->session['_auth_passwordentered'] > 3600)
			return false;
			
		return true;
	}
	protected /*void*/function userDidEnterPassword()
	{
		$this->session['_auth_passwordentered'] = time();
	}
	
	/* Terminates the current user's session and invalidates any persistent tokens associated with the current client. */
	public /*void*/ function logout($deletePersistent=true)
	{
		if(!$this->loggedIn())
			return;
		
		// Invalidate the persistent token in the database and the persistent token cookie.
		if(isset($this->request->cookies['_auth_token']) && $deletePersistent)
		{
			$statement = $this->db->prepare("DELETE FROM `auth_tokens` WHERE `userid` = ? AND `token_hash` = ?;");
			$statement->execute(array($this->user->id(), hash('sha512', $this->request->cookies['_auth_token']->value)));
			Cookies::delete('_auth_token');
		}
		
		// Invalidate the session token in the database.
		$statement = $this->db->prepare("DELETE FROM `auth_sessions` WHERE `userid` = ? AND `token_hash` = ?;");
		$statement->execute(array($this->user->id(), hash('sha512', $this->session['_auth_session'])));
		
		// Tell the user service to initiate a logout.
		$this->userService->logout($this->user);
		
		// Update the session.
		$this->user = null;
		unset($this->session['_auth_passwordentered']);
		unset($this->session['_auth_userid']);
		unset($this->session['_auth_active']);
	}
	
	/* Returns whether or not this session is current logged in. */
	public /*bool*/ function loggedIn()
	{
		return $this->user !== null;
	}
	
	/* Returns the user that this session is currently logged into or null if not logged in. */
	public /*User_Provider*/ function user()
	{
		return $this->user;
	}
	
	/* Called automatically when the driver is deallocated from memory. */
	public /*void*/ function unload()
	{
	}
	
	/* Generates a long, randomly-generated token. */
	protected $_idchars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
	protected function generateToken()
	{
		// Seed the random number generator.
		mt_srand(microtime(true) * 1000000);
		
		// Create a unique session identifier.
		$length = 100 + mt_rand(25,50);
		$id = "";
		
		while(strlen($id) < $length) {
			$id .= substr($this->_idchars, mt_rand(0, strlen($this->_idchars)-1), 1);
		}
		
		return $id;
	}
	
	/* Logs the client in as the provided user. USE WITH CAUTION - NOT SUBJECT TO SECURITY MEASURES AND DOES NOT VERIFY PASSWORD. Should indicate that user has entered their password this session if not from token. */
	public /*void*/ function login(User_Provider $user, $persistent=false, $fromToken=false)
	{
		// Create and set the session token.
		$tokenSession = $this->generateToken();		
		$statement = $this->db->prepare("INSERT INTO `auth_sessions` (`userid`, `token_hash`, `expires`) VALUES (?, ?, ?);");
		$statement->execute(array($user->id(), hash('sha512',$tokenSession), time()+1209600));
				
		// If the login is from a token, invalidate the old token.
		if($fromToken) {
			$statement = $this->db->prepare("DELETE FROM `auth_tokens` WHERE `userid` = ? AND `token_hash` = ?;");
			$statement->execute(array($user->id(), hash('sha512',$this->request->cookies['_auth_token']->value)));
		}
				
		// Create and set the persistent token.
		if($persistent) {
			$token = $this->generateToken();
			$cookie = new Cookie('_auth_token', $token);
			$this->response->cookies->add($cookie);
			
			$statement = $this->db->prepare("INSERT INTO `auth_tokens` (`userid`, `token_hash`, `expires`) VALUES (?, ?, ?);");
			$statement->execute(array($user->id(), hash('sha512',$token), time()+1209600));
		}
		
		// Update local variables.
		$this->user = $user;
		$this->session['_auth_userid'] = $user->id();
		$this->session['_auth_active'] = time();
		$this->session['_auth_session'] = hash('sha512',$tokenSession);
		
		if(!$fromToken) {
			$this->userDidEnterPassword();
		}
		
		// Recalculate the session identifier to prevent fixation.
		$this->session->regenerate();
	}
	
	/* Registers a login attempt. */
	protected function registerLoginAttempt($username, $successful)
	{
		if($username instanceof User_Provider) {
			$userid = $username->id();
		}
		elseif($username === null) {
			$userid = 0;
		}
		else {
			$user = $this->userService->loadByName($username);	
			
			if($user === null)
				$userid = 0;
			else
				$userid = $user->id();
		}
			
		$statement = $this->db->prepare("INSERT INTO `auth_attempts` (`ipaddress`, `userid`, `timestamp`, `successful`, `fraudulent`) VALUES (?, ?, ?, ?, ?);");
		$statement->execute(array($this->request->ip, $userid, time(), $successful, 0));
	}
	
	/* Attempts to log the client in to the user with the provided username and password. You may pass any extra information required by your driver into $extra.
	   Returns true on success. If successful, modifies the current session to be logged in as the user and optionally sets persistent tokens.
	   This function is subject to security measures on the current client/session and associated user account (e.g. throttling).
	   If unsuccessful, throws an AuthException containing information about what went wrong (e.g. incorrect password).
	   Should indicate that the user has entered their password this session. */
	public /*bool*/ function attempt($username, $password, $persistent=false, array $extra=array()) /*throws AuthException*/
	{
		// Throttle Based on IP
		$statement = $this->db->prepare("SELECT COUNT(*) AS `count`, MAX(`timestamp`) AS `last` FROM `auth_attempts` WHERE `ipaddress` = ? AND `timestamp` >= ? AND `successful` = 0;");
		$query = $statement->execute(array($this->request->ip, time()-1800));
		$delay = $query->row['count'] >= sizeof($this->throttle) ? $this->throttle[sizeof($this->throttle)-1] : $this->throttle[$query->row['count']];
		
		if($delay > 0 && $query->row['last'] + $delay > time()) {
			throw new AuthException("CHECK_FAILED_THROTTLING_IP", "You must wait before trying again.");
			return false;
		}
		
		// Throttle by Account
		$throttleuser = $this->userService->loadByName($username);
		
		if($throttleuser === null) {
			// If the user does not exist, throw an error and register a failed attempt.
			$this->registerLoginAttempt($throttleuser, 0);
			throw new AuthException("LOGIN_FAILED_INVALID_USERNAME", "You entered an invalid username/password combination.");
			return false;
		}
		
		$statement = $this->db->prepare("SELECT COUNT(*) AS `count`, MAX(`timestamp`) AS `last` FROM `auth_attempts` WHERE `userid` = ? AND `timestamp` >= ? AND `successful` = 0;");
		$query = $statement->execute(array($throttleuser->id(), time()-1800));
		$delay = $query->row['count'] >= sizeof($this->throttle) ? $this->throttle[sizeof($this->throttle)-1] : $this->throttle[$query->row['count']];
		
		if($delay > 0 && $query->row['last'] + $delay > time()) {
			throw new AuthException("CHECK_FAILED_THROTTLING_USER", "You must wait before trying again.");
			return false;
		}
		
		// Tell the user service provider to attempt the login.
		$user = $this->userService->login($username, $password);
		
		// If the login failed, lets throw an error and register a failed attempt.
		if($user === null) {	
			$this->registerLoginAttempt($username, 0);
			throw new AuthException("LOGIN_FAILED_INVALID_PASSWORD", "You entered an invalid username/password combination.");
			return false;
		}
		
		// Make sure the user is not banned.
		if($user->banned()) {
			$this->registerLoginAttempt($user, 0);
			throw new AuthException("LOGIN_FAILED_BANNED", "You cannot log into a banned account.");
			return false;
		}
		
		// Register a successful attempt.
		$this->registerLoginAttempt($user, 1);
		
		// Log the user in.
		$this->login($user, $persistent);
		
		// Return success.
		return true;
	}
	
	/* Checks whether or not the provided password is correct for the account this session is currently logged into. Returns true on success.
	   If incorrect, throws an AuthException containing information about what went wrong. An exception is always thrown if the user is not logged in. 
	   This function is subject to security measures on the current client/session and associated user account (e.g. throttling) and should be used instead
	   of the equivalent User_Provider method when such measures are neccesary (ie only on user input). Should indicate that user has entered their password this session. */
	public /*bool*/ function check($password) /*throws AuthException*/
	{
		// Make sure we have a user.
		if(!$this->loggedIn())
			throw new AuthException("CHECK_FAILED_NO_USER", "The session is not logged in.");
				
		// Throttle Based on IP
		$statement = $this->db->prepare("SELECT COUNT(*) AS `count`, MAX(`timestamp`) AS `last` FROM `auth_attempts` WHERE `ipaddress` = ? AND `timestamp` >= ? AND `successful` = 0;");
		$query = $statement->execute(array($this->request->ip, time()-1800));
		$delay = $query->row['count'] >= sizeof($this->throttle) ? $this->throttle[sizeof($this->throttle)-1] : $this->throttle[$query->row['count']];
		
		if($delay > 0 && $query->row['last'] + $delay > time()) {
			throw new AuthException("CHECK_FAILED_THROTTLING_IP", "You must wait before trying again.");
			return false;
		}
		
		// Check password.
		if(!$this->user->checkPassword($password)) {
			$this->registerLoginAttempt($this->user, 0);
			throw new AuthException("CHECK_FAILED_INVALID_PASSWORD", "You entered an incorrect password.");
			return false;
		}
		
		// Inidicate Success.
		$this->userDidEnterPassword();
		return true;
	}
	
	/* Called automatically when the driver is instantiated. Check for persistent tokens or existing session here. */
	protected $throttle = array(0,0,0,2,4,8,16,30,60,120,300);
	
	protected function validateLogin()
	{
		// According to our local session, is the user already logged in?
		if(isset($this->session['_auth_userid']) && isset($this->session['_auth_active']) && isset($this->session['_auth_session'])) {
			
			// Has the session expired?
			if(time() - $this->session['_auth_active'] > 3600) {
				$this->logout(false);
				return;
			}
			
			// Has the token been revoked?
			$statement = $this->db->prepare("SELECT * FROM `auth_sessions` WHERE `token_hash` = ? AND `userid` = ? AND `expires` > ?;");
			$query = $statement->execute(array( $this->session['_auth_session'], $this->session['_auth_userid'], time() ));
			
			if(len($query) != 1) {
				$this->logout(false);
				return;
			}
						
			// Otherwise, the session is valid.
			$this->user = $this->userService->load($this->session['_auth_userid']);
			$this->session['_auth_active'] = time();
		}
	}
	
	protected function validateToken()
	{
		// If the user is not logged in, check for a persistent login token.
		if(!$this->loggedIn() && isset($this->request->cookie['_auth_token'])) {
			/// Throttle By IP
			$statement = $this->db->prepare("SELECT COUNT(*) AS `count`, MAX(`timestamp`) AS `last` FROM `auth_attempts` WHERE `ipaddress` = ? AND `timestamp` >= ? AND `successful` = 0;");
			$query = $statement->execute(array($this->request->ip, time()-1800));
			$delay = $query->row['count'] >= sizeof($this->throttle) ? $this->throttle[sizeof($this->throttle)-1] : $this->throttle[$query->row['count']];
	
			if($delay > 0 && $query->row['last'] + $delay > time()) {
				return;
			}
			
			// Check that the token exists and has not expired. If so, log the user in.
			$statement = $this->db->prepare("SELECT * FROM `auth_tokens` WHERE `token_hash` = ? AND `expires` > ?;");
			$query = $statement->execute(array(hash('sha512', $this->request->cookie['_auth_token']->value), time()));
			
			if(len($query) != 1) {
				// Log the failed attempt to prevent brute forcing.
				$this->registerLoginAttempt(null, 0);
				
				// Invalidate the cookie.
				Cookies::delete('_auth_token');
				return;
			}
			
			// Otherwise, log the user in.
			$user = $this->userService->load($query['userid']);
			
			if($user !== null) {
				$this->login($user, true, true);
			}
		}
	}
	
	public /*void*/ function load()
	{
		$this->db =& App::getDatabase();
		$this->request =& App::getRequest();
		$this->response =& App::getResponse();
		
		// Check for existing sessions.
		$this->validateLogin();
		
		$this->validateToken();
		
		// Make sure the account is not banned.
		if($this->loggedIn() && $this->user->banned())
			$this->logout();
	}
}

/*
CRON::register(function(){
	// periodically purge old/unused identifiers to keep table size low
});
*/