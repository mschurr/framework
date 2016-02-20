<?php

class FileIteratorRecursive extends FileIteratorGeneric
{
	// #Iterator

	protected /*array*/ $iterators;
	protected /*FileIterator*/ $currentIterator;
	protected /*File*/ $_current;
	protected /*int*/ $_key;

	public /*int*/ function key()
	{
		return $this->_key;
	}

	public /*void*/ function next()
	{
		// Prepare the key.
		if($this->_key === null)
			$this->_key = 0;
		else
			$this->_key++;

		$filter =& $this->filter;

		while(true) {
			// Handle the iterator queue.
			if($this->currentIterator === null) {
				try {
					//echo 'Dequeueing Iterator: ';
					$file = $this->iterators->dequeue();
					$this->currentIterator = new FileIterator($file, null);
					$this->currentIterator->rewind();
					//echo (string) $this->currentIterator.PHP_EOL;
				}
				catch(RuntimeException $e) {
					//echo 'Iterator Queue Empty! Terminating...'.PHP_EOL;
					$this->_current = false;
					return;
				}
			}

			// Go through the current iterator.
			$found = false;

			while($this->currentIterator->valid()) {
				$file = $this->currentIterator->current();
				$this->currentIterator->next();

				if($file->isDirectory) {
					//echo 'Enqueueing Directory: '.$file->canonicalPath.PHP_EOL;
					$this->iterators->enqueue($file);
					//continue;
				}

				if($this->filter !== null && $filter($file) === false)
					continue;

				$this->_current = $file;
				$found = true;
				break;
			}

			if(!$this->currentIterator->valid())
				$this->currentIterator = null;

			if($found)
				break;
		}
	}

	public /*File*/ function current()
	{
		return $this->_current;
	}

	public /*void*/ function rewind()
	{
		$this->iterators = new SplQueue();
		$this->currentIterator = null;
		//echo 'Enqueueing Base Directory...'.PHP_EOL;
		$this->iterators->enqueue($this->file);
		$this->_key = null;
		$this->_current = null;
		$this->next();
	}

	public /*bool*/ function valid()
	{
		return $this->_current !== false;
	}
}
