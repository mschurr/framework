<?php
/********************************************************
 * Storage Framework
 * ------------------------------------------------------
 *
 * This framework provides an interface for storing large, static binary files globally across all server nodes.
 *
 * You should ALWAYS use this framework for storing persistent files; do not store files directly into the filesystem.
 *   ---> On distributed systems, files stored directly in the file system will not persist beyond the lifetime of the current server node.
 *   ---> On distributed systems, files stored directly in the file system will not exist from the viewpoint of other server nodes.
 * This framework solves these problems.
 *
 * Example Uses:
 *    - Storing user-uploaded photos
 *
 * If the files stored by the driver are automatically made available publicly on your content delivery network, StorageFile->url should return that URL.
 * Otherwise, a URL will be generated and returned that proxies the file through an app server node.
 *
 * NOTE: This storage system does not provide any sort of access control. All files are assumed publicly accessible. If this is undesired behavior, subclass the driver of your choice
 *  and override "access". You will not be able to use a CDN if you do this; all requests must be proxied through the app server for the access function to be honored.
 *
 * Suggested Drivers:
 *    MongoDB
 *    AmazonS3
 *    RelationalDB  		* Not recommended; storing binary files in relational databases is a significant performance hit.
 *    LocalFilesystem		* DO NOT USE THIS ON DISTRIBUTED SYSTEMS! Use this only if you have a single application server with a persistent file system (e.g. test environments).
 *    <Your Own Driver>
 *
 **/
  
class StorageException extends Exception {}

abstract class StorageSection
{
	public abstract /*void*/ function put(File $file, array $metadata, /*int*/ $expires=-1)/*throws StorageException*/;
	public abstract /*bool*/ function has(/*string*/$hash);
	public abstract /*void*/ function delete(/*string*/$hash)/*throws StorageException*/;
	public abstract /*StorageFile*/ function get(/*string*/$hash)/*throws StorageException*/;
	public abstract /*void*/ function renew(/*string*/$hash, /*int*/$expires=-1)/*throws StorageException*/;
}

abstract class Storage extends StorageSection
{
	public abstract /*StorageSection*/ function section(/*string*/$name);
	public abstract /*bool*/ function access(/*string*/$hash, /*string*/$section, /*Session*/$session);
}

abstract class StorageFile// implements ArrayAccess
{
	public $url;
	public $section;
	public $hash;
	public $ext;
	public $name;
	public $mime;
	public $size;
	public $modified;
	public $expires;
	public $hex;
	public $md5;
	public $sha1;
	public $metadata;
	public $contents;
	public abstract function copyToInstance($path);

	public /*URL*/ function getUrl() {
		return URL::to('StorageController', $this->hash);
	}
}

/**
 * Handles routing for the storage driver; ensures that we can always return a valid URL to stored files.
 */
Route::get('/~storage/{hash}', 'StorageController');
class StorageController
{
	public function get($hash)
	{
		// If storage isn't configured, the request is bad.
		if(App::storage() === null)
			return 400;
		
		// Retrieve the file from the storage system.
		$file = App::storage()->get($hash);
		
		// If the file doesn't exist, return a 404.
		if($file === null)
			return 404;
			
		// Otherwise, pass the file to the end-user.
		return $file;
	}
}

