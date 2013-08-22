<?php

class Auth
{
	public static function check($username, $password)
	{
	}
	
	public static function login($userid, $remember=false)
	{
	}
	
	public static function attempt($username, $password, $remember=false)
	{
	}
	
	public static function user()
	/* Returns the currently authenticated user id or null on failure. */
	{
	}
	
	public static function validate($credentials)
	{
	}
	
	public static function once($credentials)
	{
	}
}

abstract class UserProvider implements Iterator, ArrayAccess
{
	protected $data = array();
	protected $saltchars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789{}[]@()!#^-_|+';
	protected $codechars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
	
	// Master Implemented
	public function __construct()
	{
		$this->onLoad();
	}
	
	public function passwordConstraintCheck($password)
	{
		
	}
	
	// Provider Implemented
	public abstract function onLoad();
	public abstract function verify($password);
	public abstract function load($id);
	public abstract function loadByName($username);
	public abstract function loadByEmail($email);
	public abstract function hasPrivelage($pid);
	public abstract function hasPrivelages($array);
	public abstract function inGroup($id=false);
	public abstract function setProperty($prop, $value);
	public abstract function save();
	
	// Object Properties
	public function __get($key)
	{
		if(isset($this->data[$key]))
			return $this->data[$key];
		return null;
	}
	
	public function __set($key, $value)
	{
		$this->data[$key] = $this->setProperty($key, $value);
	}
	
	public function __isset($key, $value)
	{
		return isset($this->data[$key]);
	}
	
	public function __unset($key)
	{
		unset($this->data[$key]);
	}
	
	public function __len()
	{
		return sizeof($this->data);
	}
	
	// Iterator
	protected $__position = 0;
	
	public function rewind() {
		$this->__position = 0;
	}
	
	public function current() {
		$keys = array_keys($this->data);
		return $this->data[$keys[$this->__position]];
	}
	
	public function key() {
		$keys = array_keys($this->data);
		return $keys[$this->__position];
	}
	
	public function next() {
		++$this->__position;
	}
	
	public function valid() {
		$keys = array_keys($this->data);
		return isset($keys[$this->__position]) && isset($this->data[$keys[$this->__position]]);
	}
	
	// ArrayAcess
	public function offsetSet($offset, $value) {
		if(is_null($offset))
			return;
		return $this->__set($offset, $value);
	}
	
	public function offsetExists($offset) {
		return $this->__isset($offset);
	}
	
	public function offsetGet($offset) {
		return $this->__get($offset);
	}
	
	public function offsetUnset($offset) {
		return $this->__unset($offset);
	}
}