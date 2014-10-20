<?php
require_once(FRAMEWORK_ROOT."/plugins/CAS.php");

use mschurr\framework\plugins\CAS\CASConfig;
use mschurr\framework\plugins\CAS\CASUser;
use mschurr\framework\plugins\CAS\CASAuthenticationException;
use mschurr\framework\plugins\CAS\CASAuthenticator;

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

	protected /*CASAuthenticator*/ function getAuthenticator() {
		$error = function() {
			throw new Exception("You have not properly configured the server for CAS authentication.");
		};

		$config = new CASConfig();
		$config->host = Config::get('auth.cas.host', $error);
		$config->path = Config::get('auth.cas.path', $error);

		$authenticator = new CASAuthenticator($config);
		return $authenticator;
	}

	/**
	 * Checks the provided login credentials; returns a user if successful or null on failure.
	 */
	public /*User_Provider*/ function login(/*String*/$username, /*String*/$password)
	{
		$authenticator = $this->getAuthenticator();
		$request = App::getRequest();
		$response = App::getResponse();
		$destination = isset($request->get['destination']) ? $request->get['destination'] : URL::to('/');

    try {
      $casUser = $authenticator->startAuthentication($request, $response, $destination);
      if ($casUser) {
      	$user = $this->loadByName($casUser->username);

      	// If we have not yet seen this user, we need to make a database record.
      	if($user !== null) {
					return $user;
      	}

				$stmt = $this->db->prepare("INSERT INTO `users` (
					`username`,
					`email`,
					`banned`,
					`password_cost`,
					`password_salt`,
					`password_hash`,
					`properties`
				) VALUES (?, '', 0, 0, '', '', '{}');");

				$res = $stmt->execute($casUser->username);

				return $this->load($res->insertId);
      }
    } catch (CASAuthenticationException $e) {}

    return null;
	}

	/**
	 * Notify the service provider that a user logged out.
	 */
	public /*void*/ function logout(/*User_Provider*/ $user)
	{
		$authenticator = $this->getAuthenticator();
		$request = App::getRequest();
		$response = App::getResponse();
		$destination = isset($request->get['destination']) ? $request->get['destination'] : URL::to('/');
    $authenticator->endAuthentication($response, $destination);
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
