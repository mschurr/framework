<?php

//namespace mschurr\FileObject;

class FileIterator extends FileIteratorGeneric
{
	// #Iterator

	protected $_handle;
	protected $_key;
	protected $_current;

	public /*int*/ function key()
	{
		return $this->_key;
	}

	public /*void*/ function next()
	{
		$this->_key++;
		$filter =& $this->filter;

		while(true) {
			$value = readdir($this->_handle);

			if($value === false)
				break;

			if($value == '.' || $value == '..')
				continue;

			$value = File::open($this->file->canonicalPath.'/'.$value);

			if($this->filter !== null && $filter($value) === false)
				continue;

			break;
		}


		$this->_current = $value;
	}

	public /*File*/ function current()
	{
		return $this->_current;
	}

	public /*void*/ function rewind()
	{
		if($this->_handle) {
			closedir($this->_handle);
			$this->_handle = null;
		}

		$this->_handle = opendir($this->file->canonicalPath);

		$this->_key = -1;
		$this->next();
	}

	public /*bool*/ function valid()
	{
		return $this->_current !== false;
	}

	public /*void*/ function __destruct()
	{
		if($this->_handle) {
			closedir($this->_handle);
		}
	}
}