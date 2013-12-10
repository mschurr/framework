<?php

abstract class Group_Service_Provider
{
	public abstract /*Group_Provider*/ function load(/*int*/$guid);
	public abstract /*array<Group_Provider>*/ function groups(/*int*/ $offset=0, /*int*/$limit=null);
	public abstract /*Group_Provider*/ function create(/*String*/$name);
	public abstract /*void*/ function delete(/*Group_Provider*/$group);
}

abstract class Group_Provider
{
	public abstract /*void*/ function __construct(/*int*/$id);
	public abstract /*string*/ function name();
	public abstract /*void*/ function setName(/*string*/$name);
	public abstract /*array<int>*/ function privelages();
	public abstract /*bool*/ function hasPrivelage(/*int*/$id);
	public abstract /*bool*/ function hasPrivelages(/*array<int>*/$privelages);
	public abstract /*void*/ function addPrivelage(/*int*/$id);
	public abstract /*void*/ function addPrivelages(/*array<int>*/$privelages);
	public abstract /*void*/ function removePrivelage(/*int*/$id);
	public abstract /*void*/ function removePrivelages(/*array<int>*/$privelages);
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null);
	public abstract /*bool*/ function hasUser(/*User_Provider*/$user);
	public abstract /*void*/ function removeUser(/*User_Provider*/$user);
	public abstract /*void*/ function addUser(/*User_Provider*/$user);
	public abstract /*bool*/ function hasUsers(/*array<User_Provider>*/$users);
	public abstract /*void*/ function removeUsers(/*array<User_Provider>*/$users);
	public abstract /*void*/ function addUsers(/*array<User_Provider>*/$users);
	public abstract /*void*/ function __destruct();
}
