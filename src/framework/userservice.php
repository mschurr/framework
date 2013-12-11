<?php
class UserServiceException extends Exception
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


abstract class User_Service_Provider
{
	public abstract /*User_Provider*/ function load(/*int*/$guid);
	public abstract /*User_Provider*/ function loadByName(/*String*/$name);
	public abstract /*User_Provider*/ function loadByEmail(/*String*/$email);
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null);
	public abstract /*User_Provider*/ function create(/*String*/$username, /*String*/$password) /*throws UserServiceException*/;
	public abstract /*void*/ function delete(/*User_Provider*/$user);
	public abstract /*User_Provider*/ function login(/*String*/$username, /*String*/$password);
	public abstract /*void*/ function userDidLogin(/*User_Provider*/$user);
	public abstract /*void*/ function logout(/*User_Provider*/ $user);
	public abstract /*bool*/ function usernameMeetsConstraints(/*String*/$username) /*throws UserServiceException*/;
	
	private static $restricted = array('admin','root','user','username','account','email');
	public /*bool*/ function passwordMeetsConstraints($username, $password)
	{
		if(!$this->usernameMeetsConstraints($username))
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
	public abstract /*int*/ function id();
	public abstract /*String*/ function email();
	public abstract /*void*/ function setEmail(/*String*/$email);
	public abstract /*String*/ function username();
	public abstract /*void*/ function setUsername(/*String*/$username);
	public abstract /*bool*/ function banned();
	public abstract /*void*/ function setBanned(/*int*/$expireTime);
	public abstract /*void*/ function setPassword(/*String*/$password);
	public abstract /*bool*/ function checkPassword(/*String*/$input);
	public abstract /*void*/ function __destruct();
	public abstract /*array<string:mixed>*/ function properties();
	public abstract /*mixed*/ function getProperty(/*string*/$name);
	public abstract /*void*/ function setProperty(/*string*/$name, /*mixed*/$value);
	public abstract /*bool*/ function hasProperty(/*string*/$name);
	public abstract /*void*/ function deleteProperty(/*string*/$name);
	public abstract /*array<int>*/ function _privelages();
	public abstract /*void*/ function _hasPrivelage(/*int*/$id);
	public abstract /*void*/ function _addPrivelage(/*int*/$id);
	public abstract /*void*/ function _removePrivelage(/*int*/$id) /*throws Exception*/;
	
	// -------------------------------------------------------------------------
	
	public /*array<int>*/ function privelages()
	{
		$privelages = $this->_privelages();
		
		foreach($this->groups() as $group) {
			$privelages = array_merge($privelages, $group->privelages());
		}
		
		return array_unique($privelages, SORT_NUMERIC);
	}
	
	public /*void*/ function hasPrivelage(/*int*/$id)
	{
		if( $this->_hasPrivelage($id) )
			return true;
			
		foreach($this->groups() as $group) {
			if($group->hasPrivelage($id))
				return true;
		}
		
		return false;
	}
	
	public /*void*/ function addPrivelage(/*int*/$id)
	{
		$this->_addPrivelage($id);	
	}
	
	public /*void*/ function removePrivelage(/*int*/$id) /*throws Exception*/
	{
		$this->_removePrivelage($id);
		
		if($this->hasPrivelage($id)) {
			throw new Exception("Removal failed, user inherits privelage from group.");
		}
	}
	
	public /*array<Group_Provider>*/ function groups()
	{
		return App::getGroupService()->groupsForUser($this);
	}
		
	public /*void*/ function hasPrivelages(/*array<int>*/$ids)
	{
		foreach($ids as $id) {
			if(!$this->hasPrivelage($id))
				return false;
		}
		
		return true;
	}
	
	
	public /*void*/ function addPrivelages(/*array<int>*/$ids)
	{
		foreach($ids as $id) {
			$this->addPrivelage($id);
		}
	}
	
	
	public /*void*/ function removePrivelages(/*array<int>*/$ids) /*throws Exception*/
	{
		foreach($ids as $id) {
			$this->removePrivelage($id);
		}
	}
	
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
		if($property == 'id') return $this->id();
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
			|| $property == 'id'
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