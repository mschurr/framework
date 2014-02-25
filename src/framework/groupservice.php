<?php
/**************************************
 * Group Service Provider
 **************************************
 
 This API provides a system of managing user privileges by placing them into groups.

 Privileges are simply integers; you will need to keep track of what IDs you have used and what they 
  mean in the context of your application.

 To get a reference to group services:
 	App::getGroupService()
 	$controller->groups

*/

class GroupServiceException extends Exception {}

/**
 * Provides an interface for interacting with groups.
 */
abstract class Group_Service_Provider
{
	/**
	 * Loads a group by it's unique id.
	 */
	public abstract /*Group_Provider*/ function load(/*int*/$guid);

	/**
	 * Returns an array of up to $limit groups starting at $offset.
	 */
	public abstract /*array<Group_Provider>*/ function groups(/*int*/ $offset=0, /*int*/$limit=50);

	/**
	 * Returns an array of the groups a user is currently in.
	 */
	public abstract /*array<Group_Provider>*/ function groupsForUser(User_Provider $user);

	/**
	 * Creates a new group with the provided name and returns it.
	 */
	public abstract /*Group_Provider*/ function create(/*String*/$name);

	/**
	 * Deletes the provided group.
	 */
	public abstract /*void*/ function delete(/*Group_Provider*/$group);
}

/**
 * Provides an interface for interacting with a single group.
 */
abstract class Group_Provider
{
	/**
	 * Instantiates the object for a given group id.
	 */
	public abstract /*void*/ function __construct(/*int*/$id);

	/**
	 * Returns the group's unique id.
	 */
	public abstract /*int*/ function id();

	/**
	 * Returns the group's name.
	 */
	public abstract /*string*/ function name();

	/**
	 * Updates the group's name.
	 */
	public abstract /*void*/ function setName(/*string*/$name);

	/**
	 * Returns an array of the privileges granted to the group.
	 */
	public abstract /*array<int>*/ function privileges();

	/**
	 * Returns whether or not the group has a particular privilege.
	 */
	public abstract /*bool*/ function hasPrivilege(/*int*/$id);

	/**
	 * Grants a privilege to the group (if it does not already exist).
	 */
	public abstract /*void*/ function addPrivilege(/*int*/$id);

	/**
	 * Revokes a privelege from the group (if it exists).
	 */
	public abstract /*void*/ function removePrivilege(/*int*/$id);

	/**
	 * Returns an array of up to $limit users in the group starting at $offset.
	 */
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=50);

	/**
	 * Returns whether or not the group has a particular user.
	 */
	public abstract /*bool*/ function hasUser(/*User_Provider*/$user);

	/**
	 * Removes a user from the group (if that user is a member).
	 */
	public abstract /*void*/ function removeUser(/*User_Provider*/$user);

	/**
	 * Adds a user to the group (if that user is not a member already).
	 */
	public abstract /*void*/ function addUser(/*User_Provider*/$user);

	/**
	 * Deallocates the object and cleans up any resources.
	 */
	public abstract /*void*/ function __destruct();
	
	/**
	 * Returns whether or not all of the privileges in the provided array are granted to the group.
	 */
	public /*bool*/ function hasPrivileges(/*array<int>*/$privileges)
	{
		foreach($privileges as $id)
			if(!$this->hasPrivilege($id))
				return false;
				
		return true;
	}
	
	/**
	 * Grants all of the privileges in the array to the group.
	 */
	public /*void*/ function addPrivileges(/*array<int>*/$privileges)
	{
		foreach($privileges as $id)
			$this->addPrivilege($id);
	}
	
	/**
	 * Revokes all of the privileges in the array from the group.
	 */
	public /*void*/ function removePrivileges(/*array<int>*/$privileges)
	{
		foreach($privileges as $id)
			$this->removePrivilege($id);
	}
	
	/**
	 * Returns whether or not the group has all of the users in the provided array.
	 */
	public /*bool*/ function hasUsers(/*array<User_Provider>*/$users)
	{
		foreach($users as $user)
			if(!$this->hasUser($user))
				return false;
		
		return true;
	}
	
	/**
	 * Removes all of the users in the provided array from the group.
	 * Users who are not members will be ignored.
	 */
	public /*void*/ function removeUsers(/*array<User_Provider>*/$users)
	{
		foreach($users as $user)
			$this->removeUser($user);
	}
	
	/**
	 * Adds all of the users in the provided array to the group.
	 * Users who are already members will be ignored.
	 */
	public /*void*/ function addUsers(/*array<User_Provider>*/$users)
	{
		foreach($users as $user)
			$this->addUser($user);
	}
}
