<?php

/**
 * Provides an Exception for errors in the DefaultArrayMap classes.
 */
class DefaultArrayMapException extends Exception {}
class NotImplementedException extends Exception {}
class BadAccessException extends Exception {}

/**
 * Provides Exceptions for various invalid accesses.
 */
class InvalidParameterException extends Exception {}
class InvalidGetParameterException extends InvalidParameterException {}
class InvalidPostParameterException extends InvalidParameterException {}
class InvalidFileParameterException extends InvalidParameterException {}
class WrongRequestTypeException extends Exception {}
class ImmutableObjectException extends Exception {}

/**
 * Provides an implementation of an array in which bad read accesses will not cause an error, but instead return a default value.
 */
class DefaultArrayMap implements Countable, ArrayAccess, IteratorAggregate
{
	protected /*array*/ $_data;
	protected /*mixed*/ $_default;

	/**
	 * Initializes the array with the default values in $array.
	 * Array access takes ownership of the provided array (if any).
	 * Bad read access will return $default, unless default is a closure which takes the associated key as input (then $default($key) will be returned).
	 */
	public /*void*/ function __construct(/*mixed*/ $default = null, array &$array = null)
	{
		$this->_data =& $array;
		$this->_default = $default;
	}

	/**
	 * IteratorAggregate: Returns an iterator over the array's contents.
	 */
	public /*Traversable*/ function getIterator()
	{
		return new ArrayIterator($this->_data);
	}

	/**
	 * Countable: Returns the number of items stored in the array.
	 */
	public /*int*/ function count()
	{
		return count($this->_data);
	}

	/**
	 * ArrayAccess: Returns whether or not data exists in the array for $offset.
	 */
	public /*bool*/ function offsetExists(/*scalar*/ $offset)
	{
		return isset($this->_data[$offset]);
	}

	/**
	 * ArrayAccess: Handles array write (delete) access.
	 */
	public /*void*/ function offsetUnset(/*scalar*/ $offset)
	{
		if(isset($this->_data[$offset]))
			unset($this->_data[$offset]);
	}

	/**
	 * ArrayAccess: Handles array write access.
	 */
	public /*void*/ function offsetSet(/*scalar*/ $offset, /*mixed*/ $value)
	{
		if($offset === null) {
			$this->_data[] = $value;
		} else {
			$this->_data[$offset] = $value;
		}
	}

	/**
	 * ArrayAccess: Handles array read access.
	 */
	public /*mixed*/ function offsetGet(/*scalar*/ $offset)
	{
		if(isset($this->_data[$offset]))
			return $this->_data[$offset];
		if($this->_default instanceof Closure) {
			$fn = $this->_default;
			return $fn($offset);
		}
		return $this->_default;
	}

	/**
	 * Returns a new array of the object's contents.
	 */
	public /*array*/ function toArray()
	{
		return $this->_data;
	}

	/**
	 * Returns a string representation of the object.
	 */
	public /*String*/ function __toString()
	{
		return to_json($this->_data);
	}

	/**
	 * Returns a new, immutable copy of the object.
	 */
	public /*ImmutableDefaultArrayMap*/ function immutableCopy()
	{
		return new ImmutableDefaultArrayMap($this->copyOf($this->_data));
	}

	/**
	 * Returns a new, mutable copy of the object.
	 */
	public /*DefaultArrayMap*/ function mutableCopy()
	{
		return new DefaultArrayMap($this->copyOf($this->_data));
	}

	/**
	 * Returns a copy of the provided array.
	 */
	protected /*array*/ function copyOf(array $array)
	{
		// Arrays are passed by value, so simply return.
		return $array;
	}
}

/**
 * Immutable version of DefaultArrayMap. Attempts to modify the array throw a DefaultArrayMapException.
 */
class ImmutableDefaultArrayMap extends DefaultArrayMap
{
	/**
	 * ArrayAccess: Handles array write (delete) access.
	 * @Override
	 */
	public /*void*/ function offsetUnset(/*scalar*/ $offset)
	{
		throw new DefaultArrayMapException();
	}

	/**
	 * ArrayAccess: Handles array write access.
	 * @Override
	 */
	public /*void*/ function offsetSet(/*scalar*/ $offset, /*mixed*/ $value)
	{
		throw new DefaultArrayMapException();
	}
}

/**
 * Returns whether or not an object is null.
 */
/*bool*/ function is_eq_null($object)
{
	return is_null($object) || ($object instanceof NullObject);
}

/**
 * Implementation of a nil object (responds to all accesses and selectors with nil).
 */
class NullObject implements Countable, ArrayAccess, Iterator
{
	/**
	 * Initializes the object.
	 */
	public /*void*/ function __construct(){}

	/**
	 * Countable: Returns the size of the object.
	 */
	public /*int*/ function count()
	{
		return 0;
	}

	/**
	 * Returns whether or not data exists for an object property.
	 */
	public /*bool*/ function __isset(/*scalar*/ $key)
	{
		return false;
	}

	/**
	 * Returns data for an object property.
	 */
	public /*mixed*/ function __get(/*scalar*/ $key)
	{
		return $this;
	}

	/**
	 * Returns whether another object is equivalent to this one.
	 */
	public /*bool*/ function equals(/*mixed*/ $object)
	{
		return is_null($object) || ($object instanceof NullObject);
	}

	/**
	 * Compares this object to another one.
	 */
	public /*int*/ function compareTo(/*mixed*/ $object)
	{
		if($this->equals($object))
			return 0;
		return -1;
	}

	/**
	 * Handles object being called as a function.
	 */
	public /*mixed*/ function __invoke(/*...$args*/)
	{
		return $this;
	}

	/**
	 * ArrayAccess: Handles reads when accessed as an array.
	 */
	public /*mixed*/ function offsetGet(/*scalar*/ $offset)
	{
		return $this;
	}

	/**
	 * ArrayAccess: Returns whether or not data exists when accessed as an array.
	 */
	public /*bool*/ function offsetExists(/*scalar*/ $offset)
	{
		return false;
	}

	/**
	 * ArrayAccess: Handles writes (deletes) when accessed as an array.
	 */
	public /*void*/ function offsetUnset(/*scalar*/ $offset){}

	/**
	 * ArrayAccess: Handles writes when accessed as an array.
	 */
	public /*void*/ function offsetSet(/*scalar*/ $offset, /*mixed*/ $value){}

	/**
	 * Handles write (delete) access to undefined properties.
	 */
	public /*void*/ function __unset(/*scalar*/ $key){}

	/**
	 * Handles write access to undefined properties.
	 */
	public /*void*/ function __set(/*scalar*/ $key, /*mixed*/ $value){}

	/**
	 * Returns a string representation of the object.
	 */
	public /*string*/ function __toString()
	{
		return "";
	}

	/**
	 * Handles calls to undefined functions.
	 */
	public /*mixed*/ function __call(/*string*/ $method, /*array<mixed>*/ $args)
	{
		return $this;
	}

	/**
	 * Cleans up internal references when object is destroyed.
	 */
	public /*void*/ function __destruct(){}

	/**
	 * Iterator: Returns the current object.
	 */
	public /*mixed*/ function current()
	{
		return $this;
	}

	/**
	 * Iterator: Returns the key for the current object.
	 */
	public /*scalar*/ function key()
	{
		return 0;
	}

	/**
	 * Iterator: Advances to the next object.
	 */
	public /*void*/ function next(){}

	/**
	 * Iterator: Returns whether or not data exists for current object.
	 */
	public /*bool*/ function valid()
	{
		return false;
	}

	/**
	 * Iterator: Restarts iteration from the beginning.
	 */
	public /*void*/ function rewind(){}

	// -------------------------------------------------------------
	// Static Implementation
	// -------------------------------------------------------------

	private static /*NullObject*/ $instance = null;

	/**
	 * Returns a singleton NullObject instance.
	 */
	public static /*NullObject*/ function sharedInstance()
	{
		if(static::$instance === null)
			static::$instance = new NullObject();
		return static::$instance;
	}
}
