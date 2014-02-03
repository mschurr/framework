<?php

//namespace mschurr\FileObject;
use \Iterator;
use \ArrayAccess;
use \Countable;
use \InvalidArgumentException;

/**
 * Provides an class enabling iteration over a file in chunks of size $bytes.
 * Allows access to any given chunk via ArrayAccess in O(1) time.
 * Allows access to the number of chunks via Countable in O(1) time.
 * Allows the chunks to be converted into an array (WARNING: it may not be possible to load large files into memory).
 */
class FileChunkIterator implements Iterator, ArrayAccess, Countable
{
	protected $file;
	protected $bytes;

	public /*void*/ function __construct(File &$file, $bytes, $offset=null, $length=null) // TODO: offset,length
	{
		if(!$file->exists)
			throw new FileDoesNotExistException;
		if(!$file->isFile)
			throw new FileOperationInvalidException;
		if($bytes <= 0 || !is_int($bytes))
			throw new InvalidArgumentException;
		if(!$file->isReadable)
			throw new FileNotReadableException;

		$this->file =& $file;
		$this->bytes = $bytes;
	}

	// #ArrayAccess
	public /*void*/ function offsetSet(/*int*/ $key, /*binary*/ $value)
	{
		throw new FileOperationInvalidException;
	}

	public /*void*/ function offsetUnset(/*int*/ $key)
	{
		throw new FileOperationInvalidException;
	}

	public /*binary*/ function offsetGet(/*int*/ $key)
	{
		if(!$this->offsetExists($key))
			throw new InvalidArgumentException;

		$h = fopen($this->file->path, 'r');
		
		if(fseek($h, $key * $this->bytes, SEEK_SET) === -1)
			return null;
		
		if(feof($h))
			return null;
			
		$data = fread($h, $this->bytes);
		
		fclose($h);
		
		return $data;
	}

	public /*bool*/ function offsetExists(/*int*/ $key)
	{
		if (!is_integer($offset)) {
			throw new InvalidArgumentException;
		}
		return $key < $this->count();
	}


	// #Countable
	public /*int*/ function count()
	{
		return ceil($this->file->size / $this->bytes);
	}


	// #Iterator
	protected $_handle;
	protected $_key;
	
	public /*mixed*/ function current()
	{
		return fread($this->_handle, $this->bytes);
	}
	
	public /*scalar*/ function key()
	{
		return $this->_key;
	}
	
	public /*void*/ function rewind()
	{
		if($this->_handle) {
			fclose($this->_handle);
		}
		
		$this->_handle = fopen($this->file->path, 'r');
		$this->_key = 0;
	}
	
	public /*void*/ function next()
	{
		$this->_key++;
	}
	
	public /*boolean*/ function valid()
	{
		return !feof($this->_handle);
	}
	
	public /*void*/ function __destruct()
	{
		if($this->_handle) {
			fclose($this->_handle);
		}
	}
	

	// Other Functions
	public /*array<binary>*/ function &toArray()
	{
		$h = fopen($this->file->path, 'r');
		$array = array();

		while(!feof($h)) {
			$array[] = fread($h, $this->bytes);
		}

		fclose($h);
		return $array;
	}

	public /*String*/ function __toString()
	{
		return '<'.get_class($this).'@'.$this->file->path.'>';
	}
}