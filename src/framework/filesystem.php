<?php
/*
	This library aims to rewrite the default PHP filesystem functions into an Object-Oriented library that makes more sense.
	
	Usage:
		$file = new File(FILE_ROOT.'/file.txt');
		
		Directories are also files.
			$dir = new File(FILE_ROOT);
			$dir = new File(FILE_ROOT.'/');
		
		Providing the trailing "/" for directories is optional - however, if the file does not exist and you call a function the creates the file, it will 
		only be created as directory if the trailing "/" is provided. Otherwise, it will be created as a file with no extension.
		
		Each file has properties. These properties can be accessed using $file->property or $file['property'].
			e.g. $file['name'] or $file->name
			
		All properties are LAZY LOADED. No hits will occur to the filesystem until a property is actually accessed. Properties are calculated once and cached.
		Creating a new File object does not access the disk at all - it incurs only the overhead required to create the object.
		
		Available Properties:
			path				- the file (or directory)'s absolute path
			directory			- the absolute path of the file (or directory)'s parent directory
			name				- the file (or directory)'s name
			exists				- whether or not the file (or directory) exists
			mime				- (For Files Only)the file's mime type
			extension			- (For Files Only)the file's extension
			content				- (For Files Only) the file's content
			json				- (For Files Only) the file's content, decoded from json
			size				- the file (or directory)'s size in bytes
			lastModified		- the unix timestamp of last modification to the file (or directory)
			isDirectory			- whether or not the object is a directory
			isFile				- whether or not the object is a file
			isReadable			- whether or not the file (or directory) is readable
			isWriteable			- whether or not the file (or directory) is writeable
			files				- (For Directories Only) an array of <File> objects representing files in the directory
			empty				- whether or not the file (or directory) is empty
			temporary			- whether or not the file (or directory) is temporary
			hex					- hexadecimal string representing value of file contents (useful for sql blob queries)
			md5					- md5 hash of file contents
			sha1				- sha1 hash of file contents
				
		It is possible to modify file properties directly. These will perform an action on the file.
			e.g. $file['name'] = 'newname.log';
			e.g. $file->name = 'newname.log';
				
		Modifiable Properties:
			path			- moves/renames the file to locate it at the new path
			directory		- moves the file to the new directory
			name			- renames the file
			extension		- changes the file's extension (aliases: type, ext)
			content			- writes to the file. overwrites existing content. creates file if needed. (aliases: contents)
			                  NOTE: it is possible to append ($file->content .= morecontent), but using the ->append(content) method is more efficient because it does not require existing file content to be loaded to memory 
			json			- writes a php object to the file as json. overwrites existing content.  creates file if needed.
			serial			- writes a php object to the file using serialize(). overwrites existing content. creates file if needed.
			
		File objects can be iterated over, provided the object represents a directory (equivalent to saying foreach($dir->files)).
			foreach($dir as $file_object)
				echo $file_object->path;
		
		Casting a file as a string will yield the file's contents.
			$contents = (str) $file;
			$response->write($file);
			
		Files also have actions:
			touch()
				- updates access and modify time of the file to now. creates the file if it doesnt exist.
			
			copyTo(path)
				- creates a copy of the file at the provided path
			
			delete()
				- removes the file from the system
				
			put(content)
				- writes content to the file. creates file if needed. existing content is overwritten.
				
			append(content)
				- appends content to the file. creates file if needed.
			
			moveTo(target,mkdir=true)
				- moves the file to the target path (including file name). if mkdir is set, directories will be created if neccesary. returns true on success or false on fail.
			
			rename(newname)
				- changes the name of the file in its current directory
				
			make() ~ aliases: create()
				- makes the file if it does not exist (as an empty directory or empty file).
			
			assetURL()
				- attempts to make a URL to this file (if it's in the static directory) or returns null.
			
	-----------
	
	This library also provides access to more general file system functions.
	
	FileSystem::diskUsage($path)
		- Returns the size (in bytes) of a path. Recursively calculates size if a directory is provided.
		
	FileSystem::findMime($ext)
		- Returns the mime type for a given file extension.
		
	FileSystem::formatBinarySize($bytes)
		- Returns a human readable size for the provided bytes (e.g. 5.2 MB).
		
	// TODO: allow size comparison in HR format... eg file->sizeCompare('10M')
*/

class File implements Iterator, ArrayAccess, Countable
{
	// Provided Values
	protected $path;
	protected $makeAsDirectory = false;
	
	public function __construct($path)
	{
		if(substr($path, -1) == '/')
			$this->makeAsDirectory = true;
		$this->path = rtrim($path,'/');
	}
	
	public function withUploadInfo($file_info)
	{
		$this->cache['temporary'] = true;
		$this->cache['name'] = $file_info['name'];
		$this->cache['mime'] = $file_info['mime'];
		$this->cache['extension'] = pathinfo($file_info['name'], PATHINFO_EXTENSION);
		return $this;
	}
	
	// On-Demand Values
	protected $cache = array();
	protected $alias = array(
		'parent  ' => 'directory',
		'ext'      => 'extension',
		'type'     => 'extension',
		'writable' => 'writeable',
		'contents' => 'content'
	);
	
	public function resetCache()
	{
		$this->cache = array();
	}
	
	public function __isset($key)
	{
		if($key == 'path')
			return true;
		return isset($this->cache[$key]);
	}
	
	public function __toString()
	{
		return $this->content;
	}
	
	public function __set($k, $v)
	{
		// Handle Aliases
		if(isset($this->alias[$k]))
			return $this->__set($this->alias[$k], $v);
			
		// Handle Path (move)
		if($k == 'path') {
			$this->moveTo($v, true);
		}
		
		// Handle Directory (move)
		elseif($k == 'directory') {
			$v = rtrim($v, '/');
			$this->moveTo($v.'/'.$this->name, true);
		}
		
		// Handle Name (rename)
		elseif($k == 'name') {
			$this->rename($v);
		}
		
		// Handle Extension (rename)
		elseif($k == 'extension') {
			$name = pathinfo($this->path, PATHINFO_FILENAME);
			$this->rename($name.'.'.$v); 
		}
		
		// Handle Content (write)
		elseif($k == 'content')
			$this->put($v);
		
		// Handle JSON (write)
		elseif($k == 'json')
			$this->put(to_json($v));
			
		// Handle Serial (write)
		elseif($k == 'serial')
			$this->put(serialize($v));
	}
		
	public function __get($k)
	{
		if($k == 'path')
			return $this->path;
		if(isset($this->alias[$k]))
			return $this->{$this->alias[$k]};
		if(isset($this->cache[$k]))
			return $this->cache[$k];
		
		if($k == 'directory')
			$value = pathinfo($this->path, PATHINFO_DIRNAME);
		elseif($k == 'name')
			$value = pathinfo($this->path, PATHINFO_BASENAME);
		elseif($k == 'exists')
			$value = (file_exists($this->path));
		elseif($k == 'mime')
			$value = isset(FileSystem::$extToMime[$this->extension]) ? FileSystem::$extToMime[$this->extension] : 'application/octet-stream';
		elseif($k == 'extension')
			$value = ($this->isFile) ? pathinfo($this->path, PATHINFO_EXTENSION) : null;
		elseif($k == 'content')
			$value = ($this->exists) ? file_get_contents($this->path) : null;
		elseif($k == 'md5')
			$value = ($this->exists) ? md5_file($this->path) : null;
		elseif($k == 'hex')
			$value = ($this->exists) ? $this->getHex() : null;
		elseif($k == 'sha1')
			$value = ($this->exists) ? sha1_file($this->path) : null;
		elseif($k == 'json')
			$value = ($this->exists) ? from_json($this->content) : array();
		elseif($k == 'serial') {
			$value = ($this->exists) ? unserialize($this->content) : array();
		}
		elseif($k == 'size')
			$value = ($this->exists) ? (($this->isDirectory) ? FileSystem::diskUsage($this->path) : filesize($this->path)) : null;
		elseif($k == 'lastModified')
			$value = ($this->exists) ? filemtime($this->path) : null;
		elseif($k == 'isDirectory')
			$value = ($this->exists && is_dir($this->path));
		elseif($k == 'isWriteable')
			$value = ($this->exists) ? is_writable($this->path) : is_writable($this->directory);
		elseif($k == 'isReadable')
			$value = ($this->exists && is_readable($this->path));
		elseif($k == 'isFile')
			$value = ($this->exists && !is_dir($this->path));
		elseif($k == 'files') {
			if($this->isDirectory) {
				$value = array();
				
				foreach(scandir($this->path) as $fname) {
					if($fname == '.' || $fname == '..')
						continue;
					
					$value[] = new File($this->path.'/'.$fname);
				}
			}
			else {
				$value = array();
			}
		}
		elseif($k == 'empty') {
			if($this->isDirectory)
				$value = (!$this->exists || count(scandir($this->path)) <= 2);
			else
				$value = ($this->size === 0 || $this->size === null);
		}
		
		if(!isset($value))
			return null;
		
		$this->cache[$k] = $value;
		return $value;
	}
	
	// Helpers
	protected function getHex()
	{
		$handle = fopen($this->path, "rb");
		$contents = fread($handle, $this->size);
		$contents = bin2hex($contents);
		fclose($handle);
		return '0x'.strtoupper($contents);
	}
	
	// Actions	
	public function copyTo($destination)
	{
		$this->resetCache();
		return FileSystem::copy($this->path, $destination);
	}
		
	public function touch()
	{
		$this->resetCache();
		return FileSystem::touch($this->path.($this->makeAsDirectory ? '/' : ''));
	}
	
	public function delete()
	{
		if(!$this->exists)
			return;
		$this->resetCache();
		return FileSystem::delete($this->path, true);
	}
	
	public function put($content)
	{
		$this->resetCache();
		return FileSystem::put($this->path, $content);
	}
	
	public function append($content)
	{
		$this->resetCache();
		return FileSystem::append($this->path, $content);
	}
	
	public function moveTo($target, $mkdir=true)
	{
		if(FileSystem::move($this->path, $target, $mkdir)) {
			$this->resetCache();
			$this->path = $target;
			return true;
		}
		return false;
	}
	
	public function rename($name)
	{
		if(FileSystem::rename($this->path, $name)) {
			$this->resetCache();
			$this->path = $this->directory.'/'.$name;
			return true;
		}
		return false;
	}
	
	public function assetURL()
	{
		return URL::ofFilePath($this->path);
	}
	
	public function make()
	{
		if($this->exists)
			return;
			
		if($this->makeAsDirectory) {
			FileSystem::mkdir($this->path);
		}
		else {
			$this->put('');
		}
	}
	
	public function create()
	{
		return $this->make();
	}
	
	// -- Iterator Methods (for DIRECTORY CONTENTS)
	protected $__position = 0;
	
	public function rewind() {
		$this->__position = 0;
	}
	
	public function current() {
		return $this->files[$this->__position];
	}
	
	public function key() {
		return $this->__position;
	}
	
	public function next() {
		++$this->__position;
	}
	
	public function valid() {
		return isset($this->files[$this->__position]);
	}
	
	public function __len() {
		return sizeof($this->files);
	}
	
	public function count()
	{
		return $this->__len();
	}
	
	// -- ArrayAcess Methods (for FILE PROPERTIES)
	public function offsetSet($offset, $value) {
		if(is_null($offset))
			return; // Do not allow $file[] = $value
			
		return $this->__set($offset, $value);
	}
	
	public function offsetExists($offset) {
		return isset($this->{$offset});
	}
	
	public function offsetGet($offset) {
		return $this->{$offset};
	}
	
	public function offsetUnset($offset) {
		// Properties should not be deleteable.
	}
}

class FileSystem
{
	public static function open($path)
	{
		return new File($path);
	}
	
	public static function filename($path)
	{
		return pathinfo($path, PATHINFO_FILENAME);
	}
	
	public static function mkdir($path, $recursive=true, $mode=0777)
	{
		mkdir($path, $mode, $recursive);
		return true;
	}
	
	public static function make($path, $make_dir=true)
	{
		$f = new File($path);
		$dir = new File($f->directory);
		
		if(!$dir->exists)
		{
			if(!$make_dir)
				return false;
				
			$dir->make();
		}
		
		if($f->exists)
			return true;
			
		$f->content = '';
		return true;
	}
	
	public static function put($path, $data)
	{
		file_put_contents($path, $data);
		return true;
	}
	
	public static function append($path, $data)
	{
		file_put_contents($path, $data, FILE_APPEND);
		return true;
	}
	
	public static function exists($path)
	{	
		if(is_array($path)) {
			$exists = true;
			
			foreach($path as $file)
				$exists = ($exists && file_exists($file));
			
			return $exists;	
		}
		
		return file_exists($path);
	}
	
	public static function delete($path, $recursive=false)
	{
		$f = new File($path);
		
		if($f->isDirectory)
		{
			if(!$recursive)
			{
				return false;
			}
			
			foreach($f as $file) {
				$file->delete();
			}
		}
				
		unlink($path);
		return true;
	}
	
	public static function type($path)
	{
		$f = new File($path);
		if($f->exists)
			return $f->mime;
		return null;
	}
	
	public static function get($path)
	{
		$f = new File($path);
		return $f;
	}
	
	public static function move($path, $target, $mkdir=false)
	{
		$dir = pathinfo($target, PATHINFO_DIRNAME);
		if(!file_exists($dir) && !is_dir($dir) && $mkdir) {
			self::mkdir($dir, true);
		}
		
		if(file_exists($target)) {
			return false;
		}
		
		rename($path, $target);
		return true;
	}
	
	
	// -----------------------------------------------
	
	
	
	
	public static function search($path, $term, $recursive=false)
	{
	}
	
	public static function searchInFiles($path, $term, $recursive=false)
	{
	}
	
	public static function searchInFile($path, $term)
	{
	}
	
	
	
	
	
	
	
	/*
	
	public function move($path,$target,$mkdir=false); //, use is_uploaded_file move_uploaded_file
	public function delete($path);
	public function copy($path, $path2);
	public function extension($path);
	public function touch($path);
	public function rename($path, $new_name)
	// chown($path(s), $user, $recursive=false)
	// chgrp($path(s), $group, $recursive=false)
	// size
	// lastModified
	// isDirectory
	// isWriteable
	// isFile
	// isReadable
	// ls
	// getSubdirectories
	// makeDirectory (Recursive?)
	// copyDirectory
	// deleteDirectory
	// emptyDirectory
	// chmod($path(s), $mode, $recursive=false)
	// symlink($src, $dest)*/
	
	public static function parsePath()
	/**/	
	{
	}
	
	public static function relPathToAbs($base,$rel_path)
	/* Returns the absolute path to a relative path given a base path. */
	{
		// Ensure Base Ends With Slash
		if(substr($base,-1,1) != '/')
			$base = $base.'/';
		
		// Create Path
		$path = $base.$rel_path;
		
		// Replace '//' or '/./' or '/foo/../' with '/'
		$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
		for($n=1; $n>0; $path=preg_replace($re, '/', $path, -1, $n)) {}
		
		// Return Completed Path
		return $path;
	}
	
	 /**
     * Given an existing path, convert it to a path relative to a given starting path
     *
     * @param string $endPath   Absolute path of target
     * @param string $startPath Absolute path where traversal begins
     *
     * @return string Path of target relative to starting path
     */
    public static function makePathRelative($endPath, $startPath)
    {
        // Normalize separators on windows
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $endPath = strtr($endPath, '\\', '/');
            $startPath = strtr($startPath, '\\', '/');
        }

        // Split the paths into arrays
        $startPathArr = explode('/', trim($startPath, '/'));
        $endPathArr = explode('/', trim($endPath, '/'));

        // Find for which directory the common path stops
        $index = 0;
        while (isset($startPathArr[$index]) && isset($endPathArr[$index]) && $startPathArr[$index] === $endPathArr[$index]) {
            $index++;
        }

        // Determine how deep the start path is relative to the common path (ie, "web/bundles" = 2 levels)
        $depth = count($startPathArr) - $index;

        // Repeated "../" for each level need to reach the common path
        $traverser = str_repeat('../', $depth);

        $endPathRemainder = implode('/', array_slice($endPathArr, $index));

        // Construct $endPath from traversing to the common path, then to the remaining $endPath
        $relativePath = $traverser.(strlen($endPathRemainder) > 0 ? $endPathRemainder.'/' : '');

        return (strlen($relativePath) === 0) ? './' : $relativePath;
    }
	
	public static function isAbsolutePath($file)
    {
        if (strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && substr($file, 1, 1) === ':'
                && (strspn($file, '/\\', 2, 1))
            )
            || null !== parse_url($file, PHP_URL_SCHEME)
        ) {
            return true;
        }

        return false;
    }
	
	// 0000000000000000000000000000000000000000000000000
	
	public static function formatBinarySize($bytes)
	{
        if(!empty($bytes) && is_numeric($bytes))
		{
            $s = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
            $e = floor(log($bytes)/log(1024));
 
            $output = sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));
 
            return $output;
        }
		return('0 B');
	}
	
	public static function diskUsage($path)
	{
		if(substr($path,-1) == '/')
			$path = substr($path,0,-1);
		
		if(!file_exists($path))
			return 0;
		
		$usage = 0;
		
		if(is_dir($path))
		{
			// Open Directory
			if($dh = opendir($path))
			{
				// Loop Through Items
				while (($file = readdir($dh)) !== false)
				{
					if($file != '.' && $file != '..')
					{
						if(is_dir($path.'/'.$file))
							$usage += self::diskUsage($path.'/'.$file);
						else
						{
							$usage += filesize($path.'/'.$file);
						}
					}
				}
				
				// Close Directory
				closedir($dh);
			}
		}
		else
		{
			$usage += filesize($path);
		}
		
		return $usage;
	}
	
	public static function findMime($ext) {
		if(isset(self::$extToMime[$ext]))
			return self::$extToMime[$ext];
		return 'application/octet-stream';
	}
	
	public static $extToMime = array(
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
		'py' => 'text/plain', //application/x-python
		'php' => 'text/plain',
		'log' => 'text/plain'
	);
}
?>