<?php
/* This could really use some sort of caching. */

class User_Service_Provider_db
{
	public abstract /*User_Provider*/ function load(/*int*/$guid)
	{
	}
	
	public abstract /*User_Provider*/ function loadByName(/*String*/$name);
	
	public abstract /*User_Provider*/ function loadByEmail(/*String*/$email);
	
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null);
	public abstract /*User_Provider*/ function create(/*String*/$username, /*String*/$password) /*throws UserServiceException*/;
	public abstract /*void*/ function delete(/*User_Provider*/$user);
	public abstract /*User_Provider*/ function login(/*String*/$username, /*String*/$password);
	public abstract /*void*/ function userDidLogin(/*User_Provider*/$user);
	public abstract /*void*/ function logout(/*User_Provider*/ $user);
	public abstract /*bool*/ function usernameMeetsConstraints(/*String*/$username) /*throws UserServiceException*/;
}

class User_Provider_db
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
}