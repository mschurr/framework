<?php
/*
class AuthOld
{
	protected $_session;
	protected $_user;
	protected $_userService;
	protected $_groupService;
	protected $_driver;
	
	public function __construct(Session_Driver $session)
	{
		$this->_userService =& App::getUserService();
		$this->_groupService =& App::getGroupService();
		$this->_session =& $session;
		$this->_user = null;
		// _driver
		
		// Authentication Logic and Expiration
		
		// According to the session, is the user already logged in?
		
		// If not, check for a persistent login token. If the token is valid and not expired, log the user in and invalidate it [throttle]. Generate a new persistent token and set it. (NOTE TOKENS STORED AS HASHES IN DB)
		
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
			return $this->_user;
		if($k == 'loggedIn' || $k == 'isLoggedIn')
			return $this->loggedIn();
		throw new Exception("Access to undefined property Auth->".$k.".");
	}
	
	public function loggedIn()
	{
		return $this->_user !== null;
	}
	
	public function logout()
	{
		// invalidate any persistent tokens
		$this->_userService->logout();
	}
	
	public function __isset($k)
	{
		if($k == 'user' || $k == 'loggedIn' || $k == 'isLoggedIn')
			return true;
		return false;
	}
	
	public function user()
	{
		return $this->_user;
	}
	
	public function check($username, $password)
	{
	}
	
	public function login($user, $persistent=false)
	{
	}
	
	public  function attempt($username, $password, $persistent=false)
	{
		// [throttle] 0 0 0 2 4 8 16 30 60 60 60
		// no error info
		
		// Tell the user service provider to attempt the login.
		$user = $this->_userService->login($username, $password);
		
		if($user === null)
			return false;
			
		throw new Exception("the login succeeded");
		
		// on success, regenerate session and write changes to session
		
		
		return false; // todo
	}
}
*/