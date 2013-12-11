<?php
require_once(FRAMEWORK_ROOT."/external/phpCAS/CAS.php");

class User_Service_Provider_cas extends User_Service_Provider
{
	public /*User_Provider*/ function load(/*int*/$guid)
	{
		$user = new User_Provider_cas($guid);
		
		if($user->exists())
			return $user;
		return null;
	}
	
	public /*User_Provider*/ function login(/*String*/$username, /*String*/$password)
	{
		$error = function(){ throw new Exception("You have not properly configured the server for CAS authentication."); };
		list($host, $port, $context, $cert) = array(
			Config::get('auth.cas.host', $error),
			(int) Config::get('auth.cas.port', $error),
			Config::get('auth.cas.path', $error),
			Config::get('auth.cas.cert', $error)
		);
		
		phpCAS::client(CAS_VERSION_2_0, $host, $port, $context);
		phpCAS::setCasServerCACert($cert);
		
		if(phpCAS::isAuthenticated()) {
			return $this->loadByName(phpCAS::getUser());
		}
		else {
			phpCAS::forceAuthentication();
			return null;
		}
	}
	
	public /*void*/ function logout(/*User_Provider*/ $user)
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
			phpCAS::logout();
		}
	}
	
	public /*User_Provider*/ function loadByName(/*String*/$name){ return $this->load($name); }
	public /*User_Provider*/ function loadByEmail(/*String*/$email){ return $this->load(substr($name, 0, strpos($name,"@"))); }
	public /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=null){ throw new Exception("CAS does not support user listing."); }
	public /*User_Provider*/ function create(/*String*/$username, /*String*/$password){ throw new Exception("CAS does not support user listing."); }
	public /*void*/ function delete(/*User_Provider*/$user){ throw new Exception("CAS does not support user listing."); }
	public /*bool*/ function usernameMeetsConstraints(/*String*/$username){ throw new Exception("CAS does not support user listing."); }
}

class User_Provider_cas extends User_Provider
{
	protected $id;
	
	public /*void*/ function __construct(/*int*/$id){
		$this->id = $id;	
	}
	public /*int*/ function id()
	{
		return $this->id;
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

