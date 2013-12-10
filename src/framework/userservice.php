<?php

abstract class User_Service_Provider
{
	public abstract /*User_Provider*/ function load(/*int*/$guid);
	public abstract /*User_Provider*/ function loadByName(/*String*/$name);
	public abstract /*User_Provider*/ function loadByEmail(/*String*/$email);
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null);
	public abstract /*User_Provider*/ function create(/*String*/$username, /*String*/$password);
	public abstract /*void*/ function delete(/*User_Provider*/$user);
	public abstract /*User_Provider*/ function login(/*String*/$username, /*String*/$password);
	public abstract /*void*/ function logout();
	public abstract /*bool*/ function usernameMeetsConstraints(/*String*/$username);
	
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
	public abstract /*String*/ function email();
	public abstract /*void*/ function setEmail(/*String*/$email);
	public abstract /*String*/ function username();
	public abstract /*void*/ function setUsername(/*String*/$username);
	public abstract /*bool*/ function banned();
	public abstract /*void*/ function setBanned(/*int*/$expireTime);
	public abstract /*void*/ function setPassword(/*String*/$password);
	public abstract /*bool*/ function checkPassword(/*String*/$input);
	public abstract /*void*/ function __destruct();
	public abstract /*array<int>*/ function privelages();
	public abstract /*void*/ function hasPrivelage(/*int*/$id);
	public abstract /*void*/ function hasPrivelages(/*array<int>*/$id);
	public abstract /*void*/ function addPrivelage(/*int*/$id);
	public abstract /*void*/ function addPrivelages(/*array<int>*/$id);
	public abstract /*void*/ function removePrivelage(/*int*/$id) /*throws Exception*/;
	public abstract /*void*/ function removePrivelages(/*array<int>*/$id) /*throws Exception*/;
	public abstract /*array<Group_Provider>*/ function groups();
	public abstract /*array<string:mixed>*/ function properties();
	public abstract /*mixed*/ function getProperty(/*string*/$name);
	public abstract /*void*/ function setProperty(/*string*/$name, /*mixed*/$value);
	public abstract /*bool*/ function hasProperty(/*string*/$name);
	public abstract /*void*/ function deleteProperty(/*string*/$name);
	
	// -------------------------------------------------------------------------
	
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