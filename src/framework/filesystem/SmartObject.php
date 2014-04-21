<?php
/******************************************************************************
 * Smart Object Class
 ******************************************************************************
 *
 * This class provides support for Objective-C style setters and getters.
 * This is useful for lazy instantiation or when modifying one property
 *  affects another.
 *
 * Whenever you read a smart property, the interpreter first looks for a method
 *  called getProperty().
 *
 * Whenever you write to a smart property, the interpreter first looks for a
 *  method called setProperty($value).
 *
 * If neither a setter or getter is defined, the property will act like a normal
 *  PHP instance variable.
 *
 * You can also use setters and getters to make certain properties public read-only.
 *
 * This also has the added bonus of allowing people to get/set class properties
 *  using the getProperty() and setProperty($value) methods directly in addition
 *  to the more readable ->property;
 *
 *
 * Sample Usage:
 *
 * class Person extends SmartObject
 * {
 * 		public function __construct() {
 *          // Pass through the properties this class should have.
 * 			super::__construct(array(
 * 				'name', 'age', 'occupation', 'children', 'birthdate'
 * 			));	
 *
 *          // Intitialize properties like you normally would.
 *          $this->occupation = 'Programmer';
 * 			$this->birthdate = '12-31-1990';
 * 		}
 *
 *		public function getChildren() {
 * 			// Here, we use a getter to perform lazy instantiation.
 * 			if(!$this->_properties['children'])
 *				// Do something expensive here like perform a database query.
 *				$this->_properties['children'] = ...;
 *
 *			return $this->_properties['children'];
 * 		}
 *
 *		public function setBirthdate($value) {
 * 			// We can perform some validation on $value here.
 *			// When we update their birthdate, we need to also recalculate the age.
 *			$this->_properties['age'] = ...;
 * 			$this->_properties['birthdate'] = $value;
 * 		}
 *
 *		public function setAge($value) {
 * 			// Perhaps we don't want the age property to be public writable.
 *			// Although it can still be written to directly within the class.
 *			throw new mschurr\FileObject\BadAccessException;
 *		}
 * }
 *
 */

//namespace mschurr\FileObject;
use \RuntimeException;
use \ArrayAccess;
use \BadAccessException;

/**
 * An exception representing access to an undefined property.
 */
/*class BadAccessException extends RuntimeException
{
}*/

/**
 * A container for holding smart object properties.
 */
class SmartObjectProperties implements ArrayAccess
{
	protected $data;
	protected $propertyKeys;

	public function __construct(&$propertyKeys)
	{
		$this->propertyKeys =& $propertyKeys;
		$this->data = array();
	}

	public function offsetSet($k, $v)
	{
		$this->data[$k] = $v;
	}

	public function offsetGet($k)
	{
		if(isset($this->data[$k]))
			return $this->data[$k];
		return null;
	}

	public function offsetExists($k)
	{
		return isset($this->propertyKeys[$k]);
	}

	public function offsetUnset($k)
	{
		unset($this->data[$k]);
	}

	public function clear()
	{
		$this->data = array();
	}
}

/**
 * A class that provides support for setters and getters in Objective-C style.
 */
abstract class SmartObject implements ArrayAccess
{
	protected $_properties;
	protected $_propertyKeys;
	protected $_defaultReadonly;

	public /*void*/ function __construct(array $properties, /*bool*/$defaultReadonly=false)
	{
		$this->_propertyKeys = array();
		$this->_defaultReadonly = $defaultReadonly;

		foreach($properties as $k => $v) {
			$this->_propertyKeys[$v] = true;
		}


		$this->_properties = new SmartObjectProperties($this->_propertyKeys);
	}

	public /*mixed*/ function __get(/*scalar*/ $key)
	{
		if(!isset($this->_propertyKeys[$key]))
			throw new BadAccessException($key);
		if(method_exists($this, 'get'.ucfirst($key)))
			return call_user_func_array(array($this, 'get'.ucfirst($key)), array());
		if(isset($this->_properties[$key]))
			return $this->_properties[$key];
		return null;
	}

	public /*mixed*/ function offsetGet(/*scalar*/ $key)
	{
		if(!isset($this->_propertyKeys[$key]))
			throw new BadAccessException;
		if(method_exists($this, 'get'.ucfirst($key)))
			return call_user_func_array(array($this, 'get'.ucfirst($key)), array());
		if(isset($this->_properties[$key]))
			return $this->_properties[$key];
		return null;
	}

	public /*void*/ function __set(/*scalar*/ $key, /*mixed*/ $value)
	{
		if(!isset($this->_propertyKeys[$key]))
			throw new BadAccessException($key);
		if(method_exists($this, 'set'.ucfirst($key))) {
			call_user_func_array(array($this, 'set'.ucfirst($key)), array($value));
			return;
		}
		if($this->_defaultReadonly === true)
			throw new BadAccessException($key);
		$this->_properties[$key] = $value;
	}


	public /*void*/ function offsetSet(/*scalar*/ $key, /*mixed*/ $value)
	{
		if(!isset($this->_propertyKeys[$key]))
			throw new BadAccessException;
		if(method_exists($this, 'set'.ucfirst($key))) {
			call_user_func_array(array($this, 'set'.ucfirst($key)), array($value));
			return;
		}
		if($this->_defaultReadonly === true)
			throw new BadAccessException;
		$this->_properties[$key] = $value;
	}

	public /*bool*/ function __isset(/*scalar*/ $key)
	{
		if(!isset($this->_propertyKeys[$key]))
			throw new BadAccessException;
		return isset($this->_propertyKeys[$key]);
	}

	public /*bool*/ function offsetExists(/*scalar*/ $key)
	{
		if(!isset($this->_propertyKeys[$key]))
			throw new BadAccessException;
		return isset($this->_propertyKeys[$key]);
	}

	public /*void*/ function __unset(/*scalar*/ $key)
	{
		if(!isset($this->_propertyKeys[$key]))
			throw new BadAccessException;
		$this->__set($key, null);
	}

	public /*void*/ function offsetUnset(/*scalar*/ $key)
	{
		if(!isset($this->_propertyKeys[$key]))
			throw new BadAccessException;
		$this->__set($key, null);
	}
}
