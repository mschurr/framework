<?php
/**
 * NOTICE: The people who wrote the phpCAS implementation (a dependency of the driver) did not think things through very well.
 *
 *         1) The library is not programmed for distributed systems. If you have more than a single application node, you're out of luck.
 *              --> Possible Workaround #1: Have your load balancer enforce session stickiness
 *              --> Possible Workaround #2: Implement SessionHandlerInterface as a wrapper around the framework Session class.
 *
 *         2) phpCAS writes to stdout in Exception constructors... what?
 *              --> It's probably possible to work around this by wrapping the CAS calls in an output buffer.
 *
 *         3) phpCAS does not clear the login information that is cached in $_SESSION when you explicitly call logout().... what?
 *              --> I put in a workaround for this in User_Service_Provider_cas.
 *
 *         4) This system **probably** does not work securely and/or properly if you are running behind a load balancer.
 *            phpCAS does not respect the X-Forwarded-Proto header which may result in ssl links being generated as http over port 443.
 *
 *         Recommend re-implementing phpCAS from scratch in a more sensible way that is integrated into the framework.
 *         If I have time later, I'll come back and do that. For now, it works.
 *         
 */
require_once(FRAMEWORK_ROOT."/external/phpCAS/CAS.php");

class User_Service_Provider_cas extends User_Service_Provider
{
	protected $db;

	/**
	 * Instantiates the object.
	 */
	public function __construct()
	{
		$this->db =& App::getDatabase();
	}

	/**
	 * Checks the provided login credentials; returns a user if successful or null on failure.
	 */
	public /*User_Provider*/ function login(/*String*/$username, /*String*/$password)
	{
		$error = function(){ throw new Exception("You have not properly configured the server for CAS authentication."); };
		list($host, $port, $context, $cert) = array(
			Config::get('auth.cas.host', $error),
			(int) Config::get('auth.cas.port', $error),
			Config::get('auth.cas.path', $error),
			Config::get('auth.cas.cert', $error)
		);

		phpCAS::client(CAS_VERSION_2_0, $host, $port, $context);
		phpCAS::setCasServerCACert($cert);

		if(phpCAS::isAuthenticated()/* && phpCAS::renewAuthentication()*/) {
			$user = $this->loadByName(phpCAS::getUser());

			// If we have not yet seen this user, we need to make a database record.
			if($user !== null)
				return $user;

			$stmt = $this->db->prepare("INSERT INTO `users` (`username`) VALUES (?);");
			$res = $stmt->execute(phpCAS::getUser());
			
			return $this->load($res->insertId);
		}
		else {
			phpCAS::forceAuthentication();
			return null;
		}
	}

	/**
	 * Notify the service provider that a user logged out.
	 */
	public /*void*/ function logout(/*User_Provider*/ $user)
	{
		$error = function(){ throw new Exception("You have not properly configured the server for CAS authentication."); };
		
		list($host, $port, $context, $cert) = array(
			Config::get('auth.cas.host', $error),
			(int) Config::get('auth.cas.port', $error),
			Config::get('auth.cas.path', $error),
			Config::get('auth.cas.cert', $error)
		);

		phpCAS::client(CAS_VERSION_2_0, $host, $port, $context);
		phpCAS::setCasServerCACert($cert);

		if(phpCAS::isAuthenticated() && phpCAS::getUser() == $user->username) {
			if (session_id() !== "") {
				// This is REALLY hacky... destroy the built in session that phpCAS is using to cache authentication
				//   which (for some reason) does not get reset by the library when you explicitly call the logout function. What?
				session_unset();
                session_destroy();
            }
			phpCAS::logoutWithRedirectService( (string)URL::current() );
			die();
		}
	}

	/**
	 * Returns the user with the given id.
	 */
	public /*User_Provider*/ function load(/*int*/$guid)
	{
		$user = new User_Provider_cas($guid);

		if($user->valid())
			return $user;

		return null;
	}

	/**
	 * Returns the user with the given name.
	 */
	public /*User_Provider*/ function loadByName(/*String*/$name)
	{
		if(str_contains($name, "%"))
			return null;
			
		$statement = $this->db->prepare("SELECT `userid` FROM `users` WHERE `username` LIKE ? LIMIT 1;");
		$query = $statement->execute($name);
		
		if(len($query) == 0)
			return null;
			
		return $this->load($query['userid']);
	}

	/**
	 * Notify the service provider that a user logged in succesfully.
	 */
	public /*void*/ function userDidLogin(/*User_Provider*/$user)
	{
		return;	
	}

	/**
	 * Returns the user with the given email.
	 */
	public /*User_Provider*/ function loadByEmail(/*String*/$email)
	{
		throw new UserServiceException("This driver does not support ::loadByEmail.");
	}

	/**
	 * Returns an array of up to $limit users starting at $offset.
	 */
	public /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null)
	{
		throw new UserServiceException("This driver does not support ::users.");
	}

	/**
	 * Creates a user with the provided username and password and returns it.
	 * Throws an exception on failure.
	 */
	public /*User_Provider*/ function create(/*String*/$username, /*String*/$password) /*throws UserServiceException*/
	{
		throw new UserServiceException("This driver does not support ::create.");
	}
	
	/**
	 * Deletes the provided user.
	 */
	public /*void*/ function delete(/*User_Provider*/$user)
	{
		throw new UserServiceException("This driver does not support ::delete.");
	}

	/**
	 * Returns whether or not a provided username meets service provider constraints.
	 */
	public /*bool*/ function usernameMeetsConstraints(/*String*/$username) /*throws UserServiceException*/
	{
		return true;
	}
}

class User_Provider_cas extends User_Provider_db
{
	public /*void*/ function setUsername(/*String*/$username)
	{
		throw new Exception("This driver does not support ->setUsername.");
	}

	public /*void*/ function setPassword(/*String*/$password)
	{
		throw new Exception("This driver does not support ->setPassword.");
	}
	
	public /*bool*/ function checkPassword(/*String*/$password)
	{
		throw new Exception("This driver does not support ->checkPassword.");
	}
}