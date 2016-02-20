<?php

/**
 * A class that allows efficient, intuitive manipulation of the file system.
 */
class File extends SmartObject implements IteratorAggregate, Countable, Serializable
{
	// #########################################################
	// # Constants
	// #########################################################

	/**
	 * The default MIME type (used for any unknown file type).
	 */
	protected static /*String*/ $defaultMime = 'application/octet-stream';

	/**
	 * A mapping of file extensions to MIME types.
	 */
	protected static /*array<String:String>*/ $mimes = array(
		'txt' => 'text/plain',
		'html' => 'text/html',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'pdf' => 'application/pdf',
		'png' => 'image/png',
		'gif' => 'image/gif',
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		'ttf' => 'application/x-font-ttf',
		'woff' => 'application/font-woff',
		'svg' => 'image/svg+xml',
		'eot' => 'application/vnd.ms-fontobject',
		'otf' => 'application/octet-stream',
		'ico' => 'image/vnd.microsoft.icon',
		'swf' => 'application/x-shockwave-flash',
		'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls' => 'application/vnd.ms-excel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'csv' => 'text/csv',
		'ppt' => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'xml' => 'application/xml',
		'xslt' => 'application/xslt+xml',
		'py' => 'application/x-python',
		'php' => 'text/plain',
		'log' => 'text/plain',
		'json' => 'application/json'
	);

	/**
	 * Files with these extensions are considered images.
	 */
	protected static /*array<String>*/ $images = array(
		'png', 'gif', 'jpg', 'jpeg'
	);

	// #########################################################
	// # Static Methods
	// #########################################################

	/**
	 * Returns a new instance of File for the provided path.
	 */
	public static /*File*/ function open(/*String*/ $path)
	{
		return new File($path);
	}

	/**
	 * Returns an array mapping successfully uploaded files by input name to File objects.
	 */
	public static /*array<String:File>*/ function &uploadedFiles()
	{
		$files = array();

		// If there are any uploaded files...
		if(count($_FILES) > 0) {
			// Iterate through each uploaded file...
			foreach($_FILES as $name => $info) {
				// If the file was uploaded improperly, skip it.
				if($info['error'] !== UPLOAD_ERR_OK)
					continue;

				if($info['size'] === 0)
					continue;

				// Otherwise, create a new File object and pass the information.
				$file = new File($info['tmp_name'], $info);

				// And add it to the array.
				$files[$name] = $file;
			}
		}

		return $files;
	}

	// #########################################################
	// # Instance Variables
	// #########################################################

	/**
	 * Defines all of the properties accessible on File objects.
	 */
	public /*array<String>*/ $properties = array(
		'formattedSize', 'children', 'descendants', 'temporary',
		'isDirectory', 'isFile', 'isReadable', 'isWritable',
		'isLink', 'target', 'md5', 'sha1', 'isImage', 'uploaded',
		'uploadedName', 'uploadedExtension', 'uploadedMime',
		'lastAccessed', 'lastModified', 'hex', 'exists', 'empty',
		'inode', 'mime', 'size', 'path', 'extension', 'childFiles',
		'childDirectories', 'descendantDirectories', 'files',
		'descendantFiles', 'isWriteable', 'lines', 'canonicalPath',
		'subdirectories', 'fileName', 'name', 'ext', 'content',
		'parent', 'serial'

		/*
		'', 'group', 'owner',
		'permissions', '', 'directory', '', '',
		 '', 'json', '',

		properties needing write access:
			extension
			group
			owner
			permissions
			directory
			parent
			fileName
			content
			json
			serial

		aliases:
			type -> extension
			contents -> content
			destination -> target
			*/
	);

	// #########################################################
	// # Instance Methods
	// #########################################################

	public /*File*/ function getParent()
	{
		if(is_null($this->_properties['parent']))
			$this->_properties['parent'] = File::open(pathinfo($this->path, PATHINFO_DIRNAME));

		return $this->_properties['parent'];
	}

	public /*bytes*/ function getContent()
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isReadable)
			throw new FileNotReadableException;

		$content = file_get_contents($this->path);

		if($content === false)
			throw new FileException("Unknown read error");

		return $content;
	}

	public /*mixed*/ function getSerial()
	{
		$v = unserialize($this->content);
		if($v === false)
			throw new FileOperationInvalidException;
		return $v;
	}

	public /*void*/ function setSerial($data)
	{
		$this->put(serialize($data), true);
	}

	public /*void*/ function setContent($data)
	{
		$this->put($data, true);
	}

	public /*String*/ function serialize()
	{
		return $this->canonicalPath;
	}

	public /*void*/ function unserialize(/*String*/ $serial)
	{
		$this->__construct($serial);
	}

	/**
	 * ALIASES
	 */
	public /*String*/ function getName()
	{
		return $this->fileName;
	}

	public /*void*/ function setName(/*String*/ $value)
	{
		$this->fileName = $value;
	}

	public /*String*/ function getExt()
	{
		return $this->extension;
	}

	public /*void*/ function setExt(/*String*/ $value)
	{
		$this->extension = $ext;
	}

	/**
	 *
	 */
	public /*String*/ function getFileName()
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isFile)
			throw new FileOperationInvalidException;

		if(is_null($this->_properties['fileName']))
			$this->_properties['fileName'] = pathinfo($this->path, PATHINFO_BASENAME);

		return $this->_properties['fileName'];
	}

	/**
	 *
	 */
	protected /*void*/ function updateCanonicalPath()
	{
		if(pathIsRelative($this->path)) {
			$path = evaluateRelativePath(__getcwd__(), $this->path);
			$this->_properties['canonicalPath'] = $path;
			return;
		}

		$this->_properties['canonicalPath'] = $this->path;
	}

	/**
	 * Returns a FileIterator over the current directory's subdirectories.
	 */
	public /*FileIterator*/ function getChildDirectories() /*throws FileException*/
	{
		return new FileIterator($this, function(File &$file){
			return $file->isDirectory;
		});
	}

	/**
	 * Returns a FileIterator over the current directory's files.
	 */
	public /*FileIterator*/ function getFiles() /*throws FileException*/
	{
		return new FileIterator($this, function(File &$file){
			return $file->isFile;
		});
	}

	/**
	 * Returns a FileIterator over the current directory's files.
	 */
	public /*FileIterator*/ function getSubdirectories() /*throws FileException*/
	{
		return new FileIterator($this, function(File &$file){
			return $file->isDirectory;
		});
	}

	/**
	 * Returns a FileIterator over the current directory's files.
	 */
	public /*FileIterator*/ function getChildFiles() /*throws FileException*/
	{
		return new FileIterator($this, function(File &$file){
			return $file->isFile;
		});
	}

	/**
	 * Returns a FileIteratorRecursive over the current directory's descendants, filtered for files only.
	 */
	public /*FileIteratorRecursive*/ function getDescendantFiles() /*throws FileException*/
	{
		return new FileIteratorRecursive($this, function(File &$file){
			return $file->isFile;
		});
	}

	/**
	 * Returns a FileIteratorRecursive over the current directory's descendants, filtered for directories only.
	 */
	public /*FileIteratorRecursive*/ function getDescendantDirectories() /*throws FileException*/
	{
		return new FileIteratorRecursive($this, function(File &$file){
			return $file->isDirectory;
		});
	}

	/**
	 * Returns the extension of the file.
	 */
	public /*String*/ function getExtension() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isFile)
			throw new FileOperationInvalidException;

		if(is_null($this->_properties['extension']))
			$this->_properties['extension'] = pathinfo($this->path, PATHINFO_EXTENSION);

		return $this->_properties['extension'];
	}

	/**
	 * Returns the MIME type of the file.
	 */
	public /*String*/ function getMime() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isFile)
			throw new FileOperationInvalidException;

		if(is_null($this->_properties['mime'])) {
			if(isset(static::$mimes[$this->extension]))
				$this->_properties['mime'] = static::$mimes[$this->extension];
			else
				$this->_properties['mime'] = static::$defaultMime;
		}

		return $this->_properties['mime'];
	}

	/**
	 * Returns the inode of the file.
	 */
	public /*int*/ function getInode() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(is_null($this->_properties['inode']))
			$this->_properties['inode'] = fileinode($this->path);

		return $this->_properties['inode'];
	}

	/**
	 * Returns whether or not the file (or directory) is empty.
	 */
	public /*bool*/ function getEmpty() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(is_null($this->_properties['empty'])) {
			if($this->isDirectory) {
				$this->_properties['empty'] = (count($this->children) === 0);
			}
			else {
				$this->_properties['empty'] = ($this->size === 0);
			}
		}

		return $this->_properties['empty'];
	}

	/**
	 * Returns whether or not the file exists.
	 */
	public /*bool*/ function getExists()
	{
		if(is_null($this->_properties['exists']))
			$this->_properties['exists'] = file_exists($this->path);

		return $this->_properties['exists'];
	}

	/**
	 * Returns a hexadecimal string of file contents.
	 * This property is not cached.
	 */
	public /*String*/ function &getHex() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isFile)
			throw new FileOperationInvalidException;
		if(!$this->isReadable)
			throw new FileNotReadableException;


		// Read the file in binary mode and generate the hex.
		$handle = fopen($this->path, 'rb');
		$contents = fread($handle, $this->size);
		$contents = bin2hex($contents);
		fclose($handle);
		$contents = '0x'.strtoupper($contents);
		return $contents;
	}

	/**
	 * Returns a unix timestamp of last modification time.
	 */
	public /*int*/ function getLastModified() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(is_null($this->_properties['lastModified']))
			$this->_properties['lastModified'] = filemtime($this->path);

		return $this->_properties['lastModified'];
	}

	/**
	 * Returns a unix timestamp of last access time.
	 */
	public /*int*/ function getLastAccessed() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(is_null($this->_properties['lastAccessed']))
			$this->_properties['lastAccessed'] = fileatime($this->path);

		return $this->_properties['lastAccessed'];
	}

	/**
	 * Returns an sha1 hash of file contents.
	 */
	public /*String*/ function getSha1() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isFile)
			throw new FileOperationInvalidException;
		if(!$this->isReadable)
			throw new FileNotReadableException;

		if(is_null($this->_properties['sha1']))
			$this->_properties['sha1'] = sha1_file($this->path);

		return $this->_properties['sha1'];
	}

	/**
	 * Returns an md5 hash of file contents.
	 */
	public /*String*/ function getMd5() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isFile)
			throw new FileOperationInvalidException;
		if(!$this->isReadable)
			throw new FileNotReadableException;

		if(is_null($this->_properties['md5']))
			$this->_properties['md5'] = md5_file($this->path);

		return $this->_properties['md5'];
	}

	/**
	 * Returns an iterator on files contained within the directory.
	 */
	public /*Traversable*/ function getIterator() /*throws FileException*/
	{
		return $this->children;
	}

	/**
	 * Returns the number of files contained within the directory.
	 */
	public /*int*/ function count() /*throws FileException*/
	{
		return count($this->children);
	}

	/**
	 * Returns the file's contents when cast as a string.
	 */
	public /*String*/ function __toString() /*throws FileException*/
	{
		return $this->content;
	}

	/**
	 * Returns whether or not the file is an image.
	 */
	public /*bool*/ function getIsImage() /*throws FileException*/
	{
		if(is_null($this->_properties['isImage']))
			$this->_properties['isImage'] = $this->hasExtension(static::$images);

		return $this->_properties['isImage'];
	}

	/**
	 * Returns the file's size in a human readable format.
	 */
	public /*String*/ function getFormattedSize() /*throws FileException*/
	{
		return formatFileSize($this->size);
	}

	/**
	 * Returns an Iterator/Countable on the directory's children.
	 */
	public /*FileIterator*/ function getChildren() /*throws FileException*/
	{
		if(is_null($this->_properties['children']))
			$this->_properties['children'] = $this->children(null);

		return $this->_properties['children'];
	}

	/**
	 * Returns an Iterator/Countable on the directory's descendants.
	 */
	public /*FileIteratorRecursive*/ function getDescendants() /*throws FileException*/
	{
		if(is_null($this->_properties['descendants']))
			$this->_properties['descendants'] = $this->descendants(null);

		return $this->_properties['descendants'];
	}

	/**
	 * Returns an FileLineIterator on the current file.
	 */
	public /*FileLineIterator*/ function getLines() /*throws FileException*/
	{
		if(is_null($this->_properties['lines']))
			$this->_properties['lines'] = new FileLineIterator($this);

		return $this->_properties['lines'];
	}

	/**
	 * Returns whether or not the file is a directory.
	 */
	public /*bool*/ function getIsDirectory() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(is_null($this->_properties['isDirectory']))
			$this->_properties['isDirectory'] = is_dir($this->path);

		return $this->_properties['isDirectory'];
	}

	/**
	 * Returns whether or not the file is a directory.
	 */
	public /*bool*/ function getIsFile() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(is_null($this->_properties['isFile']))
			$this->_properties['isFile'] = is_file($this->path);

		return $this->_properties['isFile'];
	}

	/**
	 * Returns whether or not the file is a directory.
	 */
	public /*bool*/ function getIsLink() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(is_null($this->_properties['isLink']))
			$this->_properties['isLink'] = is_link($this->path);

		return $this->_properties['isLink'];
	}

	/**
	 * Returns whether or not the file is readable.
	 */
	public /*bool*/ function getIsReadable() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(is_null($this->_properties['isReadable']))
			$this->_properties['isReadable'] = is_readable($this->path);

		return $this->_properties['isReadable'];
	}


	/**
	 * Returns whether or not the file is writable.
	 */
	public /*bool*/ function getIsWritable() /*throws FileException*/
	{
		if(is_null($this->_properties['isWritable'])) {
			if($this->exists)
				$this->_properties['isWritable'] = is_writable($this->path);
			else
				$this->_properties['isWritable'] = $this->parent->isWritable;
		}

		return $this->_properties['isWritable'];
	}

	/**
	 * Returns whether or not the file is writable.
	 */
	public /*bool*/ function getIsWriteable() /*throws FileException*/
	{
		return $this->getIsWritable();
	}

	/**
	 * Returns the target of the symlink (if this file is a symlink).
	 */
	public /*File*/ function getTarget() /*throws FileException*/
	{
		if(!$this->isLink)
			throw new FileOperationInvalidException;

		return File::open(readlink($this->path));
	}

	/**
	 * Returns the file's size in bytes.
	 */
	public /*int*/ function getSize() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(!$this->isReadable)
			throw new FileNotReadableException;

		if(is_null($this->_properties['size'])) {
			if ($this->isDirectory) {
				$size = 0;

				foreach($this->descendants as $file) {
					if(!$file->isDirectory) {
						$size += $file->size;
					}
				}

				$this->_properties['size'] = $size;
			}
			else {
				$this->_properties['size'] = filesize($this->path);
			}
		}

		return $this->_properties['size'];
	}

	/**
	 * Instantiates a new File object with the provided path.
	 */
	public /*void*/ function __construct(/*String*/ $initialPath, /*array*/ $uploadInfo=null)
	{
		// Let the smart object know what properties to use. Default to public read-only.
		parent::__construct($this->properties, true);

		// Handle the path.
		$this->_properties['path'] = evaluatePath($initialPath);
		$this->updateCanonicalPath();

		// Handle upload information.
		if(!is_null($uploadInfo)) {
			$this->_properties['uploaded'] = true;
			$this->_properties['temporary'] = true;
			$this->_properties['uploadedName'] = $uploadInfo['name'];
			$this->_properties['uploadedMime'] = $uploadInfo['type'];
			$this->_properties['uploadedExtension'] = pathinfo($uploadInfo['name'], PATHINFO_EXTENSION);

			if(!ctype_alnum($this->_properties['uploadedExtension']))
				$this->_properties['uploadedExtension'] = 'bin';
		}
		else {
			$this->_properties['uploaded'] = false;
			$this->_properties['temporary'] = false;
		}
	}

	/**
	 * Returns whether the file matches one of the provided file extensions.
	 */
	public /*bool*/ function hasExtension(/*array<String>|String*/$extension) /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isFile)
			throw new FileOperationInvalidException;

		$extension = (array) $extension;

		foreach($extension as $e) {
			if($this->extension === $e)
				return true;
			if($this->uploadedExtension !== null && $this->uploadedExtension === $e)
				return true;
		}

		return false;
	}

	/**
	 * Updates the access and modification timestamps of this file to now.
	 * If the file does not exist, it will be created as an empty file.
	 */
	public /*void*/ function touch() /*throws FileException*/
	{

		if(!$this->exists) {
			$this->create();
		}
		else {
			if(!$this->isWritable)
				throw new FileNotWritableException;

			unset($this->_properties['lastAccessed']);
			unset($this->_properties['lastModified']);
			touch($this->path, time(), time());
		}
	}

	/**
	 * Copies the file to be located in the destination directory,
	 */
	public /*void*/ function copyToDirectory(/*String*/$path, /*bool*/$createDestination=false) /*throws FileException*/
	{
		$this->copyTo($path.'/'.$this->name, $createDestination);
		$this->invalidateCache();
	}

	/**
	 * Moves the file to be located within the provided directory.
	 */
	public /*void*/ function moveToDirectory(/*String*/$path, /*bool*/$createDestination=false) /*throws FileException*/
	{
		$this->moveTo($path.'/'.$this->name, $createDestination);
		$this->invalidateCache();
	}

	/**
	 * Creates the file if it does not exist, using the optionally provided initial content.
	 * If $createParentDirectory is set, the parent folder will also be created recursively.
	 */
	public /*void*/ function create(/*binary*/$initialContent=null, /*bool*/$createParentDirectory=false) /*throws FileException*/
	{
		if($this->exists)
			throw new FileAlreadyExistsException;

		if(substr($this->path, -1, 1) == '/') {
			$this->createDirectory($createParentDirectory);
			return;
		}

		if(!$this->parent->exists) {
			if($createParentDirectory === true)
				$this->parent->createDirectory(true);
			else
				throw new FileDoesNotExistException;
		}

		$this->put($initialContent, false);
	}

	/**
	 * Writes the provided content to the file. Creates the file if it does not exist (though parent directories will not be created).
	 */
	public /*void*/ function put(/*binary*/$content, $overwrite=true) /*throws FileException*/
	{
		if($overwrite === false && $this->exists)
			throw new FileAlreadyExistsException;
		if($this->exists && !$this->isFile)
			throw new FileOperationInvalidException;
		if(!$this->exists && !$this->parent->isWritable)
			throw new FileNotWritableException;
		if($this->exists && !$this->isWritable)
			throw new FileNotWritableException;

		$this->lock();
		file_put_contents($this->path, $content);
		$this->release();
		$this->invalidateCache();
	}

	/**
	 * Truncates the contents of the file to a given length.
	 */
	public /*void*/ function truncate($bytes) /*throws FileException*/
	{
		if(!$this->isFile)
			throw new FileOperationInvalidException;
		if(!$this->isReadable)
			throw new FileNotReadableException;
		if(!$this->isWritable)
			throw new FileNotWritableException;
		if($bytes > $this->size)
			throw new InvalidArgumentException;

		$this->lock();
		$h = fopen($this->path, 'r+');
		ftruncate($h, $bytes);
		fclose($h);
		$this->release();
		$this->invalidateCache();
	}

	/**
	 * Deletes all of the contents of the directory and (optionally) the directory itself.
	 * This function is named differently from delete() to prevent accidental calls.
	 */
	public /*void*/ function deleteDirectory(/*bool*/$preserveDirectory=false) /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isDirectory)
			throw new FileOperationInvalidException;
		if(!$this->isWritable)
			throw new FileNotWritableException;

		foreach($this->children as $file) {
			if($file->isDirectory) {
				$file->deleteDirectory(false);
			}
			else {
				$file->delete();
			}
		}

		if($preserveDirectory === false)
			unlink($this->path);

		$this->invalidateCache();
	}

	/**
	 * Deletes this file (or non-empty directory).
	 * If this object represents a non-empty directory or does not exist, an exception is thrown.
	 */
	public /*void*/ function delete() /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if($this->isDirectory && count($this->children) > 0)
			throw new FileOperationInvalidException;
		if(!$this->isWritable)
			throw new FileNotWritableException;

		@unlink($this->path);

		$this->invalidateCache();
	}

	/**
	 * Returns an iterator on this directory's children (optionally filtered by a closure).
	 * The closure accepts a File as input and returns true (to include in results) or false otherwise.
	 */
	public /*FileIterator*/ function children(Closure $filter=null) /*throws FileException*/
	{
		return new FileIterator($this, $filter);
	}

	/**
	 * Returns an iterator on this directory's descendants (recursive) (optionally filtered by a closure).
	 * The closure accepts a File as input and returns true (to include in results) or false otherwise.
	 */
	public /*FileIteratorRecursive*/ function descendants(Closure $filter=null) /*throws FileException*/
	{
		return new FileIteratorRecursive($this, $filter);
	}

	/**
	 * Invalidates all cached properties when the file is modified.
	 */
	protected function invalidateCache()
	{
		$path = $this->path;
		$this->_properties->clear();
		$this->_properties['path'] = $path;
		$this->updateCanonicalPath();
	}

	/**
	 * Returns an Iterator over the file in chunks of $bytes bytes.
	 */
	public /*FileChunkIterator*/ function chunks($bytes) /*throws FileException*/
	{
		return new FileChunkIterator($this, $bytes);
	}

	/**
	 * Returns an Iterator over the file in chunks of $bytes bytes.
	 */
	public /*FileChunkIterator*/ function chunk($bytes) /*throws FileException*/
	{
		return new FileChunkIterator($this, $bytes);
	}

	/**
	 * Writes the provided content to the end of the file. Creates the file if it does not exist.
	 */
	public /*void*/ function append(/*binary*/ $content) /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;

		if(!$this->isWritable)
			throw new FileNotWritableException;

		$result = @file_put_contents($this->path, $content, FILE_APPEND);

		if($result !== true)
			throw new FileOperationFailedException;

		$this->invalidateCache();
	}

	/**
	 * Creates the path represented by this file as a directory.
	 * The creation will occur recursively is $createParentDirectory is set.
	 */
	public /*void*/ function createDirectory(/*bool*/$createParentDirectory=false) /*throws FileException*/
	{
		if(!$this->parent->exists) {
			if($createParentDirectory === true) {
				$this->parent->createDirectory(true);
			}
			else {
				throw new FileOperationFailedException;
			}
		}

		$res = @mkdir($this->path, 0777, false);

		if($res === false)
			throw new FileOperationFailedException;
	}

	/**
	 * Moves the file to be located at the provided path, optionally creating the destination directory.
	 * If a file already exists at $path, an exception is thrown.
	 * If the destination folder does not exist and $createDestination is false, an exception is thrown.
	 */
	public /*void*/ function moveTo(/*String*/$path, /*bool*/$createDestination=false)
	{
		$newFile = File::open($path);

		if($newFile->exists)
			throw new FileAlreadyExistsException;

		if(!$newFile->isWritable)
			throw new FileNotWritableException;

		if(!$newFile->parent->exists) {
			if($createDestination === true) {
				$newFile->parent->createDirectory(true);
			} else {
				throw new FileOperationFailedException;
			}
		}

		if($this->_properties['uploaded'] === true) {
			$result = @move_uploaded_file($this->path, $path);

			if($result !== true)
				throw new FileOperationFailedException;

			$this->_properties['path'] = $path;
			$this->invalidateCache();
			return;
		}

		$result = @rename($this->path, $path);

		if($result !== true)
			throw new FileOperationFailedException;

		$this->_properties['path'] = $path;
		$this->invalidateCache();
	}

	/**
	 * Searches for a regular expression pattern in file names of descendants of the current directory.
	 * In order to work, this object must represent a directory.
	 */
	public /*array<File>*/ function search(/*String*/$pattern, $recursive=true)
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isDirectory)
			throw new FileOperationInvalidException;

		$filter = function(File $file) {
			return preg_match('/^('.$pattern.')$/s', $file->canonicalPath);
		};

		if($recursive)
			return $this->descendants($filter)->toArray();
		else
			return $this->children($filter)->toArray();
	}

	// -------------------------------------------------------------------------------
	// The functions below are not yet implemented.
	// -------------------------------------------------------------------------------

	/**
	 * Copies the file (or directory) to the provided destination path.
	 * If this object represents a directory, the copy is recursive (contents are copied).
	 * If the destination directory does not exist and $createDestination is false, an exception is thrown.
	 * If the destination directory does not exist and $createDestination is true, the directory is created and copy occurs normally.
	 * If a file already exists at the path to be created, an exception is thrown.
	 */
	public /*void*/ function copyTo(/*String*/$path, /*bool*/$createDestination=false) /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isReadable)
			throw new FileNotReadableException;

		throw new FileException("NOT IMPLEMENTED");
	}

	/**
	 * Renames the file, preserving its location within its parent directory.
	 */
	public /*void*/ function rename(/*String*/ $newName) /*throws FileException*/
	{
		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Locks the file to prevent writes from other threads.
	 * The lock may be manually released using release(), or the lock will be
	 * auto-released when the object is deallocated.
	 */
	public /*void*/ function lock() /*throws FileException*/
	{
		//throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Releases any locks on the file acquired by this thread.
	 */
	public /*void*/ function release() /*throws FileException*/
	{
		//throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Destroys any internal resources.
	 */
	public /*void*/ function __destruct()
	{
		// TODO: Ensure any acquired locks are released.
	}

	/**
	 * Proxies the file through to the client browser and exits, optionally sending additional headers.
	 */
	public /*void*/ function passthrough($headers=array()) /*throws FileException*/
	{
		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Changes the file's permissions to $mode using chmod.
	 */
	public /*void*/ function chmod(/*binary*/$mode) /*throws FileException*/
	{
		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Changes the file's group to $group.
	 */
	public /*void*/ function chgrp(/*int*/$group) /*throws FileException*/
	{
		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Changes the file's owner to $owner.
	 */
	public /*void*/ function chown(/*int*/$owner) /*throws FileException*/
	{
		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Returns whether or not the file's contents match a regular expression pattern.
	 */
	public /*bool*/ function contains(/*String*/$pattern) /*throws FileException*/
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isFile)
			throw new FileOperationInvalidException;

		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Searchs for a regular expression pattern in file contents of descendants of the current directory.
	 * In order to work, this object must represent a directory.
	 */
	public /*array<File>*/ function searchInFiles(/*String*/$pattern, /*bool*/$recursive=true)
	{
		if(!$this->exists)
			throw new FileDoesNotExistException;
		if(!$this->isDirectory)
			throw new FileOperationInvalidException;

		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Creates the file as a symlink to $path.
	 */
	public /*void*/ function symlinkTo(/*String*/$path)
	{
		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}

	/**
	 * Creates the file as a hard link to $path.
	 */
	public /*void*/ function hardlinkTo(/*String*/$path)
	{
		throw new FileOperationFailedException("NOT IMPLEMENTED");
	}
}
