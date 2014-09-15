<?php

//namespace mschurr\FileObject;

/**
 * Returns the directory containing the PHP script that started this process.
 */
function __directory__() {
	$path = $_SERVER['SCRIPT_FILENAME'];
	$path = str_replace("\\", "/", $path);
	return substr($path, 0, strrpos($path, "/"));
}

function __getcwd__() {
    $path = getcwd();
    $path = str_replace("\\", "/", $path);
    return $path;
}

function evaluatePath($path) {
    if(isAbsolutePath($path))
        return $path;

	if(substr($path,-1,1) != '/')
        $path = $path.'/';

     if($path == './' || $path == '../')
        return $path;

    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
	for($n=1; $n>0; $path=preg_replace($re, '/', $path, -1, $n)) {}

	while(substr($path, -1) == '/')
		$path = substr($path, 0, -1);

    return $path;
}

function evaluateRelativePath($base, $rel_path) {
	// Ensure Base Ends With Slash
	if(substr($base,-1,1) != '/')
		$base = $base.'/';

	// Create Path
	$path = $base.$rel_path;

	// Replace '//' or '/./' or '/foo/../' with '/'
	$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
	for($n=1; $n>0; $path=preg_replace($re, '/', $path, -1, $n)) {}

	// Return Completed Path
    while(substr($path, -1) == '/')
        $path = substr($path, 0, -1);

	return $path;
}

function isAbsolutePath($file) {
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

/**
 * Given an existing path, convert it to a path relative to a given starting path
 *
 * @param string $endPath   Absolute path of target
 * @param string $startPath Absolute path where traversal begins
 *
 * @return string Path of target relative to starting path
 */
function makePathRelative($endPath, $startPath) {
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

function pathIsRelative($path) {
	if(strpos($path, ":/") !== false)
		return false;
    if(strpos($path, ":\\") !== false)
        return false;
	if(substr($path, 0, 1) == '/')
		return false;
	return true;
}

function formatFileSize($bytes) {
	if(!empty($bytes) && is_numeric($bytes))
	{
        $s = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $e = floor(log($bytes)/log(1024));

        $output = sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));

        return $output;
    }
	return('0 B');
}
