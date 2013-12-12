<?php
/* This could really use some sort of caching. */

class User_Service_Provider_db extends User_Service_Provider
{	
	public /*User_Provider*/ function load(/*int*/$guid)
	{
		$user = new User_Provider_db($guid);
		
		if($user->valid())
			return $user;
		return null;
	}
	
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
	
	public /*User_Provider*/ function loadByEmail(/*String*/$email)
	{
		if(str_contains($email, "%"))
			return null;
			
		$statement = $this->db->prepare("SELECT `userid` FROM `users` WHERE `email` LIKE ? LIMIT 1;");
		$query = $statement->execute($email);
		
		if(len($query) == 0)
			return null;
			
		return $this->load($query['userid']);
	}
	
	public /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null)
	{
		throw new UserServiceException("USER_LIST_NOT_IMPLEMENTED", "Listing is not allowed.");
	}
	
	public /*User_Provider*/ function create(/*String*/$username, /*String*/$password) /*throws UserServiceException*/
	{
		throw new UserServiceException("USER_CREATE_NOT_IMPLEMENTED", "Creation is not allowed.");
	}
	
	public /*User_Provider*/ function login(/*String*/$username, /*String*/$password)
	{
		$user = $this->loadByName($username);
		
		if($user === null)
			return null;
			
		if(!$user->checkPassword($password)) {
			return null;
		}
		
		return $user;
	}
	
	public /*void*/ function delete(/*User_Provider*/$user)
	{
		throw new UserServiceException("USER_DELETE_NOT_IMPLEMENTED", "Deletion is not allowed.");
	}
	
	protected $db;
	public function __construct()
	{
		$this->db =& App::getDatabase();
	}
	
	public /*void*/ function userDidLogin(/*User_Provider*/$user)
	{
	}
	
	public /*void*/ function logout(/*User_Provider*/ $user)
	{
	}
	
	public /*bool*/ function usernameMeetsConstraints(/*String*/$username) /*throws UserServiceException*/
	{
		if(strlen($username) > 20 || strlen($username) < 4)
			throw new UserServiceException("Username must be between 4 and 20 characters.");
			
		if(preg_match('/^([A-Za-z0-9_]+)$/s', $username))
			throw new UserServiceException("Usernames must contain only alphanumeric characters and underscores.");
		
		return true;
	}
}

class User_Provider_db extends User_Provider
{
	protected $id;
	protected $data = null;
	protected $db;
	
	public /*void*/ function __construct(/*int*/$id)
	{
		$this->id = $id;
		$this->db =& App::getDatabase();
		
		$statement = $this->db->prepare("SELECT * FROM `users` WHERE `userid` = ? LIMIT 1;");
		$query = $statement->execute($id);
		$this->data = $query->row;
	}
	
	public /*int*/ function id()
	{
		return $this->id;
	}
	
	public /*bool*/ function valid()
	{
		return $this->data !== null;
	}
	
	public /*String*/ function email()
	{
		return $this->data['email'];
	}
	
	public /*void*/ function setEmail(/*String*/$email)
	{
		// TODO: CHECK VALIDITY
		$this->data['email'] = $email;
		// TODO: WRITE TO DB
	}
	
	public /*String*/ function username()
	{
		return $this->data['username'];
	}
	
	public /*void*/ function setUsername(/*String*/$username)
	{
		// TODO: CHECK VALIDITY
		$this->data['username'] = $username;
		// TODO: WRITE TO DB
	}
	
	public /*bool*/ function banned()
	{
		return $this->data['banned'] > time();
	}
	
	public /*void*/ function setBanned(/*int*/$expireTime)
	{
		$this->data['banned'] = $expireTime;
		// TODO: WRITE TO DB
	}
	
	public /*void*/ function setPassword(/*String*/$password)
	{
		// TODO
	}
	
	public /*bool*/ function checkPassword(/*String*/$input)
	{
		// TODO
		return $input === $this->data['password_hash'];
	}
	
	public /*void*/ function __destruct()
	{
	}
	
	public /*array<string:mixed>*/ function properties()
	{
	}
	public /*mixed*/ function getProperty(/*string*/$name)
	{
	}
	public /*void*/ function setProperty(/*string*/$name, /*mixed*/$value)
	{
	}
	public /*bool*/ function hasProperty(/*string*/$name)
	{
	}
	public /*void*/ function deleteProperty(/*string*/$name)
	{
	}
	public /*array<int>*/ function _privelages()
	{
	}
	public /*void*/ function _hasPrivelage(/*int*/$id)
	{
	}
	public /*void*/ function _addPrivelage(/*int*/$id)
	{
	}
	public /*void*/ function _removePrivelage(/*int*/$id) /*throws Exception*/
	{
	}
}