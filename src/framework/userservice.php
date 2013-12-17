<?php
/**************************************
 * User Service Provider
 **************************************
 
 This file defines the public API for user services.
 
*/

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
	public /*bool*/ function passwordMeetsConstraints($username, $password) /*throws UserServiceException*/
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
	public abstract /*array<int>*/ function _privileges();
	public abstract /*void*/ function _hasprivilege(/*int*/$id);
	public abstract /*void*/ function _addprivilege(/*int*/$id);
	public abstract /*void*/ function _removeprivilege(/*int*/$id) /*throws Exception*/;
	
	// -------------------------------------------------------------------------
	
	public /*array<int>*/ function privileges()
	{
		$privileges = $this->_privileges();
		
		foreach($this->groups() as $group) {
			$privileges = array_merge($privileges, $group->privileges());
		}
		
		return array_unique($privileges, SORT_NUMERIC);
	}
	
	public /*void*/ function hasprivilege(/*int*/$id)
	{
		if( $this->_hasprivilege($id) )
			return true;
			
		foreach($this->groups() as $group) {
			if($group->hasprivilege($id))
				return true;
		}
		
		return false;
	}
	
	public /*void*/ function addprivilege(/*int*/$id)
	{
		$this->_addprivilege($id);	
	}
	
	public /*void*/ function removeprivilege(/*int*/$id) /*throws Exception*/
	{
		$this->_removeprivilege($id);
		
		if($this->hasprivilege($id)) {
			throw new Exception("Removal failed, user inherits privilege from group.");
		}
	}
	
	public /*array<Group_Provider>*/ function groups()
	{
		return App::getGroupService()->groupsForUser($this);
	}
		
	public /*void*/ function hasprivileges(/*array<int>*/$ids)
	{
		foreach($ids as $id) {
			if(!$this->hasprivilege($id))
				return false;
		}
		
		return true;
	}
	
	
	public /*void*/ function addprivileges(/*array<int>*/$ids)
	{
		foreach($ids as $id) {
			$this->addprivilege($id);
		}
	}
	
	
	public /*void*/ function removeprivileges(/*array<int>*/$ids) /*throws Exception*/
	{
		foreach($ids as $id) {
			$this->removeprivilege($id);
		}
	}
	
	public /*string*/ function __toString()
	{
		return $this->username();
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
		if($property == 'privileges') return $this->privileges();
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
			|| $property == 'privileges'
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