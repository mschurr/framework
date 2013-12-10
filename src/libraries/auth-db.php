<?php
class User_Service_Provider_db extends User_Service_Provider
{
	protected /*Database*/ $db;
	
	public function __construct()
	{
		$this->db =& App::getDatabase();
	}
	
	public /*User_Provider*/ function load(/*int*/$guid)
	{
		$user = new User_Provider_db($guid);
		
		if($user->exists())
			return $user;
		return null;
	}
	
	public /*User_Provider*/ function login(/*String*/$username, /*String*/$password)
	{
		
	}
	
	public /*void*/ function logout()
	{
		
	}
	
	public /*User_Provider*/ function loadByName(/*String*/$name){ return $this->load($name); }
	public /*User_Provider*/ function loadByEmail(/*String*/$email){ return $this->load(substr($name, 0, strpos($name,"@"))); }
	public /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null){ throw new Exception("CAS does not support user listing."); }
	public /*User_Provider*/ function create(/*String*/$username, /*String*/$password){ throw new Exception("CAS does not support user listing."); }
	public /*void*/ function delete(/*User_Provider*/$user){ throw new Exception("CAS does not support user listing."); }
	public /*bool*/ function usernameMeetsConstraints(/*String*/$username){ throw new Exception("CAS does not support user listing."); }
}

class User_Provider_db extends User_Provider
{
	protected $id;
	protected /*Database*/ $db;
	
	public /*void*/ function __construct(/*int*/$id){
		$this->id = $id;	
		$this->db =& App::getDatabase();
	}
	public /*String*/ function email(){
		return $this->id.'@'.substr(Config::get('auth.cas.host'),strrpos(Config::get('auth.cas.host'),'.'));
	}
	public /*void*/ function setEmail(/*String*/$email){
		throw new Exception("CAS does not allow modifications to user data.");
	}
	public /*String*/ function username(){
		return $this->id;	
	}
	public /*void*/ function setUsername(/*String*/$username){
		throw new Exception("CAS does not allow modifications to user data.");
	}
	public /*bool*/ function banned(){
		return false;
	}
	public /*void*/ function setBanned(/*int*/$expireTime){
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	public /*void*/ function setPassword(/*String*/$password){
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	public /*bool*/ function checkPassword(/*String*/$input){
		throw new Exception("CAS does not allow accessing user data.");
	}
	public /*void*/ function __destruct(){
	}
	public /*array<int>*/ function privelages(){
		return array();
	}
	public /*void*/ function hasPrivelage(/*int*/$id){
		return false;
	}
	public /*void*/ function hasPrivelages(/*array<int>*/$id){
		return false;	
	}
	public /*void*/ function addPrivelage(/*int*/$id){
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	public /*void*/ function addPrivelages(/*array<int>*/$id){
		throw new Exception("CAS does not allow modifications to user data.");		
	}
	public /*void*/ function removePrivelage(/*int*/$id) /*throws Exception*/{
		throw new Exception("CAS does not allow modifications to user data.");		
	}
	public /*void*/ function removePrivelages(/*array<int>*/$id) /*throws Exception*/{
		throw new Exception("CAS does not allow modifications to user data.");		
	}
	public /*array<Group_Provider>*/ function groups(){
		return array();	
	}
	public /*array<string:mixed>*/ function properties(){
		return array();	
	}
	public /*mixed*/ function getProperty(/*string*/$name){
		throw new Exception("CAS does not allow accesing user data.");		
	}
	public /*void*/ function setProperty(/*string*/$name, /*mixed*/$value){
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	public /*bool*/ function hasProperty(/*string*/$name){
		return false;
	}
	public /*void*/ function deleteProperty(/*string*/$name){
		throw new Exception("CAS does not allow modifications to user data.");		
	}
	public /*bool*/ function exists(){
		return true;	
	}
}

class Group_Service_Provider_db extends Group_Service_Provider
{
	protected /*Database*/ $db;
	
	public function __construct()
	{
		$this->db =& App::getDatabase();
	}
	
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

class Group_Provider_db extends Group_Provider
{
	protected /*Database*/ $db;
	
	public /*void*/ function __construct(/*int*/$id)
	{
		$this->db =& App::getDatabase();
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