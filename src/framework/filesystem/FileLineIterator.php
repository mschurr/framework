<?php

//namespace mschurr\FileObject;
use \Iterator;
use \ArrayAccess;
use \Countable;
use \InvalidArgumentException;

class FileLineIterator implements Iterator, ArrayAccess, Countable
{
	protected /*File*/ $file;

	public /*void*/ function __construct(File &$file)
	{
		if (!$file->exists) {
			throw new FileDoesNotExistException;
		}

		if (!$file->isFile) {
			throw new FileOperationInvalidException;
		}


		if (!$file->isReadable) {
			throw new FileNotReadableException;
		}

		$this->file =& $file;
	}

	// #ArrayAccess

	/**
	 * Getting a line from the file occurs "quickly" given that the entire file is not held in memory.
	 */
	public function offsetGet($offset)
	{
		// Ensure that the offset exists.
		if(!$this->offsetExists($offset))
			throw new InvalidArgumentException;

		//var_dump('Looking for line '.($offset+1).' index '.$offset);

		// If the line number is too small to be indexed...
		if($offset < $this->_lines[0]) {
			$line = 0;
			$h = fopen($this->file->path, 'r');

			while(true) {
				$data = fgets($h, 4096);
				if($line === $offset)
					break;
				$line++;
			}

			fclose($h);
			return $data;
		}

		// If the line number is to large to be indexed...
		if($offset > $this->_lines[count($this->_lines)-1]) {
			$line = $this->_lines[count($this->_lines)-1];
			$h = fopen($this->file->path, 'r');


			if(fseek($h, $this->_index[$line], SEEK_SET) === -1)
				throw new FileException;

			while(true) {
				$data = fgets($h, 4096);
				if($line === $offset)
					break;
				$line++;
			}

			fclose($h);
			return $data;
		}

		// Utilize the index to guess.
		$min = 0;
		$max = count($this->_lines);
		$key = null;

		while($max >= $min) {

			$mid = floor(($min+$max)/2.0);

			if($this->_lines[$mid] === $offset) {
				$key = $mid;
				break;
			}
			elseif($this->_lines[$mid] < $offset) {
				$min = $mid + 1;
			}
			else {
				$max = $mid - 1;
			}
		}

		if($key === null)
			$key = $min-1;

		$line = $this->_lines[$key];
		$h = fopen($this->file->path, 'r');

		if(fseek($h, $this->_index[$line], SEEK_SET) === -1)
			throw new FileException;

		while(true) {
			$data = fgets($h, 4096);
			if($line === $offset)
				break;
			$line++;
		}

		fclose($h);
		return $data;
	}

	public function offsetSet($offset, $value)
	{
		throw new FileOperationInvalidException;
	}

	public function offsetExists($offset)
	{
		if (!is_integer($offset)) {
			throw new InvalidArgumentException;
		}
		if($offset < 0)
			return false;
		return $offset <= $this->count();
	}

	public function offsetUnset($offset)
	{
		throw new FileOperationInvalidException;
	}

	// #Countable
	protected /*int*/ $_count = null;
	protected /*array<int:int>*/ $_index;
	protected /*array<int>*/ $_lines;
	public /*int*/ function count()
	{
		if(is_null($this->_count)) {
			$this->_count = 0;
			$this->_index = array();
			$this->_lines = array();

			// Determine how many indexes to create.
			$indexes = ceil(min(1000, $this->file->size / 50));
			$indexBytes = ceil($this->file->size / $indexes);
			$last = 0;
			//echo 'Attempting to create '.$indexes.' indexes (every '.$indexBytes.' bytes)';

			$h = fopen($this->file->path, 'r');
			
			while(!feof($h)) {
				$content = fgets($h, 4096);
				$this->_count += 1;

				if(ftell($h) > $last + $indexBytes) {
					$last = ftell($h);
					$this->_index[$this->_count] = $last;
					$this->_lines[] = $this->_count;
				}	
			}
			
			//var_dump($this->_index);
			fclose($h);
		}
		
		return $this->_count;
	}

	// # Iterator
	protected $_handle;
	protected $_key;
	
	public /*mixed*/ function current()
	{
		return fgets($this->_handle, 4096);
	}
	
	public /*scalar*/ function key()
	{
		return $this->_key;
	}
	
	public /*void*/ function rewind()
	{
		if($this->_handle) {
			fclose($this->_handle);
			$this->_handle = null;	
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

	// # Other Functions

	public /*array<String>*/ function &toArray()
	{
		$array = file($this->file->path);
		return $array;
	}

	public /*String*/ function __toString()
	{
		return '<'.get_class($this).'@'.$this->file->path.'>';
	}
}