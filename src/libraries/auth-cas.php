<?php
class User_Service_Provider_cas extends User_Service_Provider
{
	public static /*User_Provider*/ function load(/*int*/$guid)
	{
		$user = new User_Provider_cas($guid);
		
		if($user->exists())
			return $user;
		return null;
	}
	
	public static /*User_Provider*/ function login(/*String*/$username, /*String*/$password)
	{
		$error = function(){ throw new Exception("You have not properly configured the server for CAS authentication."); };
		list($host, $port, $context, $cert) = array(
			Config::get('auth.cas.host', $error),
			Config::get('auth.cas.port', $error),
			Config::get('auth.cas.path', $error),
			Config::get('auth.cas.cert', $error)
		);
		
		phpCAS::client(CAS_VERSION_2_0, $host, $port, $context);
		phpCAS::setCasServerCACert($cert);
		
		if(phpCAS::isAuthenticated()) {
			return self::loadByName(phpCAS::getUser());
		}
		else {
			phpCAS::forceAuthentication();
			return null;
		}
	}
	
	public static /*void*/ function logout()
	{
		$error = function(){ throw new Exception("You have not properly configured the server for CAS authentication."); };
		list($host, $port, $context, $cert) = array(
			Config::get('auth.cas.host', $error),
			Config::get('auth.cas.port', $error),
			Config::get('auth.cas.path', $error),
			Config::get('auth.cas.cert', $error)
		);
		
		phpCAS::client(CAS_VERSION_2_0, $host, $port, $context);
		phpCAS::setCasServerCACert($cert);
		
		if(phpCas::isAuthenticated()) {
			phpCAS::logoutWithRedirectService( (string)URL::to('/') );
		}
	}
	
	public static /*User_Provider*/ function loadByName(/*String*/$name){ return $this->load($name); }
	public static /*User_Provider*/ function loadByEmail(/*String*/$email){ return $this->load(substr($name, 0, strpos($name,"@"))); }
	public static /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null){ throw new Exception("CAS does not support user listing."); }
	public static /*User_Provider*/ function create(/*String*/$username, /*String*/$password){ throw new Exception("CAS does not support user listing."); }
	public static /*void*/ function delete(/*User_Provider*/$user){ throw new Exception("CAS does not support user listing."); }
	public static /*bool*/ function usernameMeetsConstraints(/*String*/$username){ throw new Exception("CAS does not support user listing."); }
}

class User_Provider_cas extends User_Provider
{
	protected $id;
	
	public /*void*/ function __construct(/*int*/$id){
		$this->id = $id;	
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

class Group_Service_Provider_cas extends Group_Service_Provider
{
	public static /*Group_Provider*/ function load(/*int*/$guid)
	{
		return null;
	}
	
	public static /*array<Group_Provider>*/ function groups(/*int*/ $offset=0, /*int*/$limit=null)
	{
		return array();
	}
	
	public static /*Group_Provider*/ function create(/*String*/$name)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}
	
	public static /*void*/ function delete(/*Group_Provider*/$group)
	{
		throw new Exception("CAS does not allow modifications to user data.");	
	}	
}

class Group_Provider_cas extends Group_Provider
{
	public abstract /*void*/ function __construct(/*int*/$id)
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
	
	
	public abstract /*void*/ function __destruct()
	{
	}
}