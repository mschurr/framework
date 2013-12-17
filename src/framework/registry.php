<?php
/*
	This is a simple registry that you can use to store information globally during the execution of the script.
	
	The registry is specific to each script's runtime.
*/

class Registry
{
	protected static $data = array();
	
	public static function put($key, $value)
	/* Puts a value in the registry. */
	{
		self::$data[$key] = $value;
	}
	
	public static function get($key, $default=null)
	/* Retrieves a value from the registry or returns $default if it is not found. */
	{
		if(isset(self::$data[$key]))
			return self::$data[$key];
		return value($default);
	}
	
	public static function gets($key, $default)
	/* Retrieves a value from the registry or sets the value to $default and returns $default if it is not found.*/
	{
		if(isset(self::$data[$key]))
			return self::$data[$key];
		$value = value($default);
		self::$data[$key] = $value;
		return $value;
	}
	
	public static function has($key)
	/* Returns whether or not the registry has a value. */
	{
		return isset(self::$data[$key]);
	}
	
	public static function all()
	/* Returns the entire registry. */
	{
		return self::$data;
	}
	
	public static function clear()
	/* Clears the registry. */
	{
		self::$data = array();
	}
}

class RegistryObject implements Iterator, ArrayAccess, Countable
{
	protected $data = array();
	protected $allow_modify = false;
	
	public function __construct($data, $allow_modify=false)
	{
		$this->data = $data;
		$this->allow_modify = $allow_modify;
	}
	
	public function __len()
	{
		return len($this->data);
	}
	
	public function count()
	{
		return len($this->data);
	}
	
	public function __value()
	{
		return $this->data;
	}
	
	public function __toString()
	{
		$string = '';
		foreach($this->data as $k => $v)
			$string .= '&'.urlencode($k).'='.urlencode($v);
			
		if(substr($string, 0, 1) == '&')
			$string = substr($string, 1);
			
		return $string;
	}
	
	public function __isset($k)
	{
		return isset($this->data[$k]);
	}
	
	public function __unset($k)
	{
		if($this->allow_modify)
			unset($this->data[$k]);
	}
	
	public function __get($k)
	{
		if( isset($this->data[$k]) )
			return $this->data[$k];
		throw new Exception("Access to undefined property: ".$k);
	}
	
	public function __invoke()
	{
		$args = func_get_args();
		
		if(len($args) == 0)
			return;
			
		if(len($args) == 1)
			return $this->__get($args[0]);
			
		if(len($args) == 2)
			return $this->__set($args[0], $args[1]);
			
		return;
	}
	
	public function has($k)
	{
		return $this->__isset($k);
	}
	
	public function get($k)
	{
		return $this->__get($k);
	}
	
	public function __set($k, $v)
	{
		if($this->allow_modify)
			$this->data[$k] = $v;
	}
	
	// Array Access
	public function offsetSet($offset, $value) {
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
}
