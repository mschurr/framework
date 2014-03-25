<?php
/**************************************
 * User Service Provider
 **************************************
 
 This API provides a system of managing user accounts.

 To get a reference to user services:
 	App::getUserService()
 	$this->users on a Controller 
*/

/**
 * An exception that will be thrown by user services.
 * This exception contains both a public message and a private message.
 * The public message can be safely displayed to the end user.
 * The private message is solely for the developer's use.
 */
class UserServiceException extends Exception
{
	protected $publicErrorMessage;
	
	/**
	 * Instantiates the exception.
	 */
	public /*void*/ function __construct($message = null, $publicErrorMessage = null, $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->publicErrorMessage = $publicErrorMessage;
	}
	
	/**
	 * Converts the exception to a string.
	 */
	public /*String*/ function __toString()
	{
		return $this->publicErrorMessage;
	}
	
	/**
	 * Returns the public error message.
	 */
	public /*String*/ function getErrorMessage()
	{
		return $this->publicErrorMessage;
	}
}

/**
 * A generic user service provider interface. Application developers should use this interface when interacting
 *  with users in aggregate to enable easy swapping of drivers.
 */
abstract class User_Service_Provider
{
	/**
	 * Returns the user with the given id.
	 */
	public abstract /*User_Provider*/ function load(/*int*/$guid);

	/**
	 * Returns the user with the given name.
	 */
	public abstract /*User_Provider*/ function loadByName(/*String*/$name);

	/**
	 * Returns the user with the given email.
	 */
	public abstract /*User_Provider*/ function loadByEmail(/*String*/$email);

	/**
	 * Returns an array of up to $limit users starting at $offset.
	 */
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null);

	/**
	 * Creates a user with the provided username and password and returns it.
	 * Throws an exception on failure.
	 */
	public abstract /*User_Provider*/ function create(/*String*/$username, /*String*/$password) /*throws UserServiceException*/;
	
	/**
	 * Deletes the provided user.
	 */
	public abstract /*void*/ function delete(/*User_Provider*/$user);

	/**
	 * Checks the provided login credentials; returns a user if successful or null on failure.
	 */
	public abstract /*User_Provider*/ function login(/*String*/$username, /*String*/$password);

	/**
	 * Notify the service provider that a user logged in succesfully.
	 */
	public abstract /*void*/ function userDidLogin(/*User_Provider*/$user);

	/**
	 * Notify the service provider that a user logged out.
	 */
	public abstract /*void*/ function logout(/*User_Provider*/ $user);

	/**
	 * Returns whether or not a provided username meets service provider constraints.
	 */
	public abstract /*bool*/ function usernameMeetsConstraints(/*String*/$username) /*throws UserServiceException*/;
	
	/**
	 * Returns whether or not a provided username, password combination meets service provider constraints.
	 */
	private static $restricted = array('admin','root','user','username','account','email');
	public /*bool*/ function passwordMeetsConstraints(/*String*/$username, /*String*/$password) /*throws UserServiceException*/
	{
		if(in_array(strtolower($username),self::$restricted))
			throw new UserServiceException("PASSWORD_INVALID", "That username is reserved; please choose another one.");
			
		if(strtolower($username) == strtolower($password))
			throw new UserServiceException("PASSWORD_INVALID", "You must choose a password different than your username.");
			
		if(strlen($password) < 10 || strlen($password) > 100)
			throw new UserServiceException("PASSWORD_INVALID", "Your password must be between 10 and 100 characters in length.");
			
		// TODO: Commonly Used Passwords
		// TODO: Entropy Calculation and Threshold
		
		return true;
	}

	/**
	 * Returns whether or not an email address is valid.
	 */
	public /*bool*/ function emailIsValid($email)/*throws UserServiceException*/
	{
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
			throw new UserServiceException("INVALID_EMAIL", "You must enter a valid email address.");
		return true;
	}
}

abstract class User_Provider implements ArrayAccess, Iterator, Countable
{
	/**
	 * Instantiates a user object with the provided id.
	 */
	public abstract /*void*/ function __construct(/*int*/$id);

	/**
	 * Returns the user's unique id.
	 */
	public abstract /*int*/ function id();

	/**
	 * Returns the user's email address.
	 */
	public abstract /*String*/ function email();

	/**
	 * Sets the user's email address.
	 */
	public abstract /*void*/ function setEmail(/*String*/$email);

	/**
	 * Returns the user's username.
	 */
	public abstract /*String*/ function username();

	/**
	 * Sets the user's username.
	 */
	public abstract /*void*/ function setUsername(/*String*/$username);

	/**
	 * Returns whether or not the user is banned.
	 */
	public abstract /*bool*/ function banned();

	/**
	 * Sets the user to be banned until the provided unix timestamp.
	 */
	public abstract /*void*/ function setBanned(/*int*/$expireTime);

	/**
	 * Changes the user's password; the password parameter should be in plaintext.
	 * Recommend calling Session->Auth->terminateAllOtherSessionsForCurrentUser after changing password.
	 */
	public abstract /*void*/ function setPassword(/*String*/$password);

	/**
	 * Returns whether or not a plaintext password matches the saved hash.
	 */
	public abstract /*bool*/ function checkPassword(/*String*/$input);

	/**
	 * Deallocates the object and cleans up any open resources.
	 */
	public abstract /*void*/ function __destruct();

	/**
	 * Returns an array map of all properties set for this user.
	 */
	public abstract /*array<string:mixed>*/ function properties();

	/**
	 * Gets a property by its name. Throws an exception if the property is not set.
	 */
	public abstract /*mixed*/ function getProperty(/*string*/$name)/*throws Exception*/;

	/**
	 * Sets the property $name to $value for this user.
	 */
	public abstract /*void*/ function setProperty(/*string*/$name, /*mixed*/$value);

	/**
	 * Returns whether or not the property $name has been set for this user.
	 */
	public abstract /*bool*/ function hasProperty(/*string*/$name);

	/**
	 * Deletes a property from the provided user (if it exists).
	 */
	public abstract /*void*/ function deleteProperty(/*string*/$name);

	/**
	 * Returns a list of privileges bound to the user's account.
	 * This does not include privileges inherited from groups.
	 */
	public abstract /*array<int>*/ function _privileges();

	/**
	 * Returns whether or not a privilege has been granted to the user's account.
	 * This does not include privileges inherited from groups.
	 */
	public abstract /*void*/ function _hasPrivilege(/*int*/$id);

	/**
	 * Grants a privilege to the user's account.
	 */
	public abstract /*void*/ function _addPrivilege(/*int*/$id);

	/**
	 * Removes a privilege from the user's account.
	 * This will not remove privileges inherited from groups.
	 */
	public abstract /*void*/ function _removePrivilege(/*int*/$id);
	
	// -------------------------------------------------------------------------
	
	/**
	 * Returns an array of all privileges granted to this user.
	 * The user may have inherited some privileges from a group, or the privileges may be bound to the user.
	 */
	public /*array<int>*/ function privileges()
	{
		$privileges = $this->_privileges();
		
		foreach($this->groups() as $group) {
			$privileges = array_merge($privileges, $group->privileges());
		}
		
		return array_unique($privileges, SORT_NUMERIC);
	}
	
	/**
	 * Returns whether or not the user has been granted a privilege.
	 * The user may have inherited this privilege from a group, or it may be bound to the user.
	 */
	public /*void*/ function hasPrivilege(/*int*/$id)
	{
		if( $this->_hasPrivilege($id) )
			return true;
			
		foreach($this->groups() as $group) {
			if($group->hasPrivilege($id))
				return true;
		}
		
		return false;
	}
	
	/**
	 * Grants a privilege to the user.
	 */
	public /*void*/ function addPrivilege(/*int*/$id)
	{
		$this->_addPrivilege($id);	
	}
	
	/**
	 * Revokes a privilage from the user.
	 * Throws an exception if removal fails due to group inheritance.
	 */
	public /*void*/ function removePrivilege(/*int*/$id) /*throws Exception*/
	{
		$this->_removePrivilege($id);
		
		if($this->hasPrivilege($id)) {
			throw new Exception("Removal failed, user inherits privilege from group.");
		}
	}
	
	/**
	 * Returns an array of the groups that this user is a member of.
	 */
	public /*array<Group_Provider>*/ function groups()
	{
		return App::getGroupService()->groupsForUser($this);
	}
		
	/**
	 * Returns whether or not the user has all of the provided privileges.
	 */
	public /*void*/ function hasPrivileges(/*array<int>*/$ids)
	{
		foreach($ids as $id) {
			if(!$this->hasPrivilege($id))
				return false;
		}
		
		return true;
	}
	
	/**
	 * Grants the provided privileges to the user.
	 */
	public /*void*/ function addPrivileges(/*array<int>*/$ids)
	{
		foreach($ids as $id) {
			$this->addPrivilege($id);
		}
	}
	
	/**
	 * Revokes the provided privileges from the user.
	 * Throws an exception if the removal fails due to group inheritance.
	 */
	public /*void*/ function removePrivileges(/*array<int>*/$ids) /*throws Exception*/
	{
		foreach($ids as $id) {
			$this->removePrivilege($id);
		}
	}
	
	/**
	 * Converts the object to a string.
	 */
	public /*string*/ function __toString()
	{
		return $this->username();
	}
	
	/* Iterator - Allows the user to iterate over the user's properties. */
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

	/* Magic Properties - Allows undefined properties to be accessed dynamically. */
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
	
	/* Array Access - Allows the object to be accessed like an array. */
	public /*bool*/ function offsetExists(/*mixed*/$offset) { return $this->__isset($offset); }
	public /*void*/ function offsetUnset(/*mixed*/$offset) { return $this->__unset($offset); }
	public /*mixed*/ function offsetGet(/*mixed*/$offset) { return $this->__get($offset); }
	public /*void*/ function offsetSet(/*mixed*/$offset, /*mixed*/$value) { return $this->__set($offset, $value);}
	
	/* Countable - Allows the object to have a length. */
	public /*int*/ function count() { return sizeof($this->properties()); }
	
	/* Constants */
	const SALT_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789{}[]@()!#^-_|+';
}