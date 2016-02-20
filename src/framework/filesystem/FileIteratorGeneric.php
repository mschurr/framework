<?php

abstract class FileIteratorGeneric implements Iterator, Countable
{
	protected /*File*/ $file;
	protected /*Closure*/ $filter;

	/**
	 * Constructs a generic filter iterator object.
	 */
	public /*void*/ function __construct(File &$file, Closure $filter=null)
	{
		if(!$file->exists)
			throw new FileDoesNotExistException;
		if(!$file->isDirectory)
			throw new FileOperationInvalidException;
		if(!$file->isReadable)
			throw new FileNotReadableException;

		// File objects are not immutable, so we should instantiate a copy of the object.
		$this->file = new File($file->canonicalPath);

		// Store the filter.
		$this->filter =& $filter;
	}

	/**
	 * Searches in file contents for files matching a regular expression.
	 */
	public /*array<File>*/ function &searchInFiles(/*String*/ $pattern)
	{
		$array = array();

		foreach($this as $file) {
			if(!$file->isFile)
				continue;

			if(preg_match($pattern, $file->content)) {
				$array[] = $file;
			}
		}

		return $array;
	}

	/**
	 * Searches in file contents for files matching a regular expression.
	 * The regular expression may not span multiple lines.
	 * This method is more memory efficient than searchInFiles.
	 */
	public /*array<File>*/ function &searchInFileLines(/*String*/ $pattern)
	{
		$array = array();

		foreach($this as $file) {
			if(!$file->isFile)
				continue;

			foreach($file->lines as $line) {
				if(preg_match($pattern, $line)) {
					$array[] = $file;
				}
			}
		}

		return $array;
	}

	/**
	 * Converts the iterator into an array containing the iteration items.
	 */
	public /*array<File>*/ function &toArray()
	{
		$array = array();

		foreach($this as $file) {
			$array[] = $file;
		}

		return $array;
	}

	/**
	 * Searches for files with names matching a given regular expression.
	 */
	public /*array<File>*/ function &search(/*String*/ $pattern)
	{
		$array = array();

		foreach($this as $file) {
			if(preg_match($pattern, $file->name)) {
				$array[] = $file;
			}
		}

		return $array;
	}

	/**
	 * Returns whether or not any files exist with names matching a given regular expression.
	 */
	public /*bool*/ function contains(/*String*/ $pattern)
	{
		foreach($this as $file) {
			if(preg_match($pattern, $file->name)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Casts the iterator as a String for debug purposes.
	 */
	public /*String*/ function __toString()
	{
		return '<'.get_class($this).'@'.$this->file->path.'>';
	}


	// #Countable

	protected $_count = null;

	public /*int*/ function count()
	{
		if(is_null($this->_count)) {
			$count = 0;

			foreach($this as $file)
				$count++;

			$this->_count = $count;
		}

		return $this->_count;
	}

	// Abstract Methods: Iterator, Countable
	public abstract function key();
	public abstract function rewind();
	public abstract function current();
	public abstract function valid();
	public abstract function next();
}
