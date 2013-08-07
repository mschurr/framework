<?php
/*
	This library aims to rewrite the default PHP filesystem functions into an Object-Oriented library that makes more sense.
	
	is_uploaded_file move_uploaded_file
*/

class File
{
	protected $path;
	protected $directory;
	protected $name;
	protected $exists;
	protected $mime;
	protected $extension;
	protected $content;
	protected $json;
	protected $size;
	protected $lastModified;
	protected $isDirectory;
	protected $isWriteable;
	protected $isReadable;
	protected $isFile;
	protected $empty;
	
	public function __construct($path)
	{
		$this->path = $path;
	}
	
	
	
	public function __get($key)
	{
		// onDemand processing of the protected variables... saves memory
	}
	
	
	
	
	
	public function copyTo($path)
	{
	}
		
	public function touch()
	{
	}
	
	public function chmod($mode)
	{
	}
	
	public function chown($user)
	{
	}
	
	public function chgrp($group)
	{
	}
	
	public function alias($path) // symlink
	{
	}
	
	// TODO: Iterator if is direcotry; subdirectories; ls; makeChild;
	
	public function delete()
	{
		return FileSystem::delete($this->path);
	}
	
	public function put($content)
	{
		return FileSystem::put($this->path, $content);
	}
	
	public function append($content)
	{
		$this->content = null;
		return FileSystem::append($this->path, $content);
	}
	
	public function moveTo($target, $mkdir=false)
	{
		$this->path = $target;
		return FileSystem::move($this->path, $target, $mkdir);
	}
	
	public function rename($name)
	{
		return FileSystem::rename($this->path, $name);
	}
}

class FileSystem
{
	public static function open($path)
	{
		return new File($path);
	}
	
	public static function make($path, $make_dir=true)
	{
		$file = new File($path);
		
		if($file->exists)
			return false;
			
		// Check if directory exists. Make it if true. Otherrise, return false.
		
		// Make the empty file.
		
		return true;
	}
	
	public static function search($path, $term, $recursive=false)
	{
	}
	
	public static function searchInFiles($path, $term, $recursive=false)
	{
	}
	
	public static function searchInFile($path, $term)
	{
	}
	
	/*public function exists($path); // can be array
	public function get($path);
	public function put($path,$content);
	public function append($path,$content);
	public function move($path,$target,$mkdir=false); // alias RENAME
	public function delete($path);
	public function copy($path, $path2);
	public function extension($path);
	public function type($path);
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
	// symlink($orig, $targ)*/
	
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
    public function makePathRelative($endPath, $startPath)
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
	
	public function isAbsolutePath($file)
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
		'php' => 'text/plain'
	);
}
?>