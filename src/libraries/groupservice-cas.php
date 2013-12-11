<?php

class Group_Service_Provider_cas extends Group_Service_Provider
{
	public /*Group_Provider*/ function load(/*int*/$guid)
	{
		return null;
	}
	
	public /*array<Group_Provider>*/ function groups(/*int*/ $offset=0, /*int*/$limit=null)
	{
		return array();
	}
	
	public /*Group_Provider*/ function create(/*String*/$name)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*void*/ function delete(/*Group_Provider*/$group)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}	
}

class Group_Provider_cas extends Group_Provider
{
	public /*void*/ function __construct(/*int*/$id)
	{
	}
	
	public /*string*/ function name()
	{
		throw new Exception("CAS does not allow access to user data.");			
	}
	
	public /*void*/ function setName(/*string*/$name)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*array<int>*/ function privelages()
	{
		return array();
	}
	
	public /*bool*/ function hasPrivelage(/*int*/$id)
	{
		return false;
	}
	
	public /*bool*/ function hasPrivelages(/*array<int>*/$privelages)
	{
		return false;
	}
	
	public /*void*/ function addPrivelage(/*int*/$id)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*void*/ function addPrivelages(/*array<int>*/$privelages)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*void*/ function removePrivelage(/*int*/$id)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*void*/ function removePrivelages(/*array<int>*/$privelages)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null)
	{
		return array();
	}
	
	public /*bool*/ function hasUser(/*User_Provider*/$user)
	{
		return false;
	}
	
	public /*void*/ function removeUser(/*User_Provider*/$user)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*void*/ function addUser(/*User_Provider*/$user)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*bool*/ function hasUsers(/*array<User_Provider>*/$users)
	{
		return false;
	}
	
	public /*void*/ function removeUsers(/*array<User_Provider>*/$users)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public /*void*/ function addUsers(/*array<User_Provider>*/$users)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}	
	
	
	public /*void*/ function __destruct()
	{
	}
}