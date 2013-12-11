<?php

abstract class Group_Service_Provider
{
	public abstract /*Group_Provider*/ function load(/*int*/$guid);
	public abstract /*array<Group_Provider>*/ function groups(/*int*/ $offset=0, /*int*/$limit=50);
	public abstract /*array<Group_Provider>*/ function groupsForUser(User_Provider $user);
	public abstract /*Group_Provider*/ function create(/*String*/$name);
	public abstract /*void*/ function delete(/*Group_Provider*/$group);
}

abstract class Group_Provider
{
	public abstract /*void*/ function __construct(/*int*/$id);
	public abstract /*int*/ function id();
	public abstract /*string*/ function name();
	public abstract /*void*/ function setName(/*string*/$name);
	public abstract /*array<int>*/ function privelages();
	public abstract /*bool*/ function hasPrivelage(/*int*/$id);
	public abstract /*void*/ function addPrivelage(/*int*/$id);
	public abstract /*void*/ function removePrivelage(/*int*/$id);
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=50);
	public abstract /*bool*/ function hasUser(/*User_Provider*/$user);
	public abstract /*void*/ function removeUser(/*User_Provider*/$user);
	public abstract /*void*/ function addUser(/*User_Provider*/$user);
	public abstract /*void*/ function __destruct();
	
	public /*bool*/ function hasPrivelages(/*array<int>*/$privelages)
	{
		foreach($privelages as $id)
			if(!$this->hasPrivelage($id))
				return false;
				
		return true;
	}
	
	public /*void*/ function addPrivelages(/*array<int>*/$privelages)
	{
		foreach($privelages as $id)
			$this->addPrivelage($id);
	}
	
	
	public /*void*/ function removePrivelages(/*array<int>*/$privelages)
	{
		foreach($privelages as $id)
			$this->removePrivelage($id);
	}
	
	public /*bool*/ function hasUsers(/*array<User_Provider>*/$users)
	{
		foreach($users as $user)
			if(!$this->hasUser($user))
				return false;
		
		return true;
	}
	
	public /*void*/ function removeUsers(/*array<User_Provider>*/$users)
	{
		foreach($users as $user)
			$this->removeUser($user);
	}
	
	public /*void*/ function addUsers(/*array<User_Provider>*/$users)
	{
		foreach($users as $user)
			$this->addUser($user);
	}
}
