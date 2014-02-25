<?php
/* This could really use some sort of memory caching... will add later. */

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
		$query = $this->db->query("SELECT `userid` FROM `users` ORDER BY `userid` ASC LIMIT ".(int)$offset.", ".(int)$limit.";");
		
		$result = array();
		
		foreach($query as $row) {
			$result[] = new User_Provider_db($row['groupid']);
		}
		
		return $result;
	}
	
	public /*User_Provider*/ function create(/*String*/$username, /*String*/$password) /*throws UserServiceException*/
	{
		if(!$this->usernameMeetsConstraints($username))
			throw new UserServiceException("INVALID_USERNAME", "That username is not valid.");

		if(!$this->passwordMeetsConstraints($username, $password))
			throw new UserServiceException("INVALID_PASSWORD", "Your password does not meet our constraints.");

		$stmt = $this->db->prepare("INSERT INTO `users` (`username`) VALUES (?);");
		$res = $stmt->execute($username);

		$user = $this->load($res->insertId);
		$user->setPassword($password);

		return $user;
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
		$stmt = $this->db->prepare("DELETE FROM `users` WHERE `userid` = ?;");
		$stmt->execute($user->id);
		$user->wasDeleted();
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
			throw new UserServiceException(null, "Username must be between 4 and 20 characters.");
			
		if(preg_match('/^([^A-Za-z0-9_]+)$/s', $username))
			throw new UserServiceException(null, "Usernames must contain only alphanumeric characters and underscores.");
		
		$statement = $this->db->prepare("SELECT `userid` FROM `users` WHERE `username` LIKE ? LIMIT 1;");
		$query = $statement->execute($username);
		
		if(len($query) > 0)
			throw new UserServiceException(null, "Username is already in use.");

		return true;
	}
}

class User_Provider_db extends User_Provider
{
	protected $id;
	protected $data = null;
	protected $_properties = null;
	protected $_dirty = false;
	protected $db;
	protected $service;
	
	public /*void*/ function __construct(/*int*/$id)
	{
		$this->id = $id;
		$this->db =& App::getDatabase();
		$this->service =& App::getUserService();

		$statement = $this->db->prepare("SELECT * FROM `users` WHERE `userid` = ? LIMIT 1;");
		$query = $statement->execute($id);

		if(len($query) == 0)
			return;

		$data = $query->row;
		$this->_properties = from_json($data['properties']);

		if(is_null($this->_properties))
			$this->_properties = array();

		unset($data['properties']);
		$this->data = $data;
	}
	
	public /*void*/ function __destruct()
	{
		if($this->_dirty && !$this->_deleted) {
			$this->data['properties'] = to_json($this->_properties);
			$stmt = $this->db->prepare("REPLACE INTO `users` (".sql_keys($this->data).") VALUES (".sql_values($this->data).");");
			$stmt->execute(sql_parameters($this->data));
		}
	}

	public /*int*/ function id()
	{
		return $this->id;
	}
	
	public /*bool*/ function valid()
	{
		return $this->data !== null;
	}
	
	public /*void*/ function setPassword(/*String*/$password)
	{
		if(!$this->service->passwordMeetsConstraints($this->username, $password))
			return;

		$this->data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
		$this->_dirty = true;
	}
	
	public /*bool*/ function checkPassword(/*String*/$password)
	{
		if(password_verify($password, $this->data['password_hash'])) {
			$info = password_get_info($this->data['password_hash']);

			if(password_needs_rehash($this->data['password_hash'], $info['algo'], $info['options'])) {
				$this->data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
				$this->_dirty = true;
			}

			return true;
		}

		return false;
	}

	public /*String*/ function email()
	{
		return $this->data['email'];
	}
	
	public /*void*/ function setEmail(/*String*/$email)
	{
		if(!$this->service->emailIsValid($email))
			return false;
		$this->data['email'] = $email;
		$this->_dirty = true;
	}
	
	public /*String*/ function username()
	{
		return $this->data['username'];
	}

	public /*void*/ function setUsername(/*String*/$username)
	{
		if(!$this->service->usernameMeetsConstraints($username))
			return;
		
		$this->data['username'] = $username;
		$this->_dirty = true;
	}
	
	public /*bool*/ function banned()
	{
		return $this->data['banned'] > time();
	}
	
	public /*void*/ function setBanned(/*int*/$expireTime)
	{
		$this->data['banned'] = $expireTime;
		$this->_dirty = true;
	}
	
	public /*array<string:mixed>*/ function properties()
	{
		return $this->_properties;
	}

	public /*mixed*/ function getProperty(/*string*/$name)
	{
		if(isset($this->_properties[$name]))
			return $this->_properties[$name];
		throw new UserServiceException;
	}

	public /*void*/ function setProperty(/*string*/$name, /*mixed*/$value)
	{
		$this->_properties[$name] = $value;
		$this->_dirty = true;
	}

	public /*bool*/ function hasProperty(/*string*/$name)
	{
		return isset($this->_properties[$name]);
	}

	public /*void*/ function deleteProperty(/*string*/$name)
	{
		unset($this->_properties[$name]);
		$this->_dirty = true;
	}

	protected $__privileges;
	public /*array<int>*/ function _privileges()
	{
		if($this->__privileges === null) {
			$stmt = $this->db->prepare("SELECT * FROM `user_privileges` WHERE `userid` = ?;");
			$rows = $stmt->execute($this->id);
			$this->__privileges = array();

			foreach($rows as $row)
				$this->__privileges[] = $row['privilegeid'];
		}

		return $this->__privileges;
	}

	public /*void*/ function _hasPrivilege(/*int*/$id)
	{
		return in_array($id, $this->_privileges());
	}

	public /*void*/ function _addPrivilege(/*int*/$id)
	{
		if($this->_hasPrivilege($id))
			return;
			
		$this->__privileges[] = (int) $id;
		
		$statement = $this->db->prepare("INSERT INTO `user_privileges` (`userid`, `privilegeid`) VALUES (?, ?);");
		$statement->execute($this->id, $id);
	}

	public /*void*/ function _removePrivilege(/*int*/$id) /*throws Exception*/
	{
		if(!$this->_hasPrivilege($id))
			return;
			
		$this->__privileges = array_diff($this->__privileges, array($id));
					
		$statement = $this->db->prepare("DELETE FROM `user_privileges` WHERE `userid` = ? AND `privilegeid` = ?;");
		$statement->execute($this->id, $id);
	}

	protected $_deleted = false;
	public function wasDeleted()
	{
		$this->_deleted = true;
	}
}