<?php
/**
 * FileSystem Cache Driver
 * -----------------------------------------------------------------------------------------------------------------------
 *
 * This class implements the cache driver for the local filesystem. You should not instantiate this class directly; use the Cache class.
 * You can find the public API documentation for the class in the Cache class.
 *
 * IMPORTANT: This driver should not be used on distributed systems; cache changes are saved to the current server node. For distributed 
 *  systems, use another driver, such as memcached.
 */

class Cache_Driver_filesystem extends Cache_Driver
{
	protected $storage;
	protected $section_exists = array();
	
	protected function translateKey($key, $section=false)
	{
		if(preg_match('/^([A-Za-z0-9_\-]+)$/s', $key) !== 1)
			throw new Exception('Cache keys must contain only [A-Z, a-z, 0-9, _, -].');
			
		if($section !== false) {
			if(preg_match('/^([A-Za-z0-9_\-]+)$/s', $section) !== 1)
			throw new Exception('Cache sections must contain only [A-Z, a-z, 0-9, _, -].');
		}
		
		if($section !== false && !isset($this->section_exists[$section])) {
			$f = new File($this->storage.'/'.$section.'/');
			if(!$f->exists)
				$f->make();
			$this->section_exists[$section] = true;
		}
		
		$f = new File($this->storage.'/'.($section === false ? '' : $section.'/').''.$key.'.json');
		return $f;
	}
	
	protected function grabSection($section)
	{
		if(preg_match('/^([A-Za-z0-9_\-]+)$/s', $section) !== 1)
			throw new Exception('Cache sections must contain only [A-Z, a-z, 0-9, _, -].');
		$f = new File($this->storage.'/'.$section.'/');
		return $f;
	}
	
	/* Automatically called when the driver is unloaded. */
	public function onUnload()
	{
	}
	
	/* Automatically called when the driver is loaded. */
	public function onLoad()
	{
		$dir = Config::get('cache.directory', FILE_ROOT.'/cache');
		$this->storage = $dir.'/fscache';
		
		$f = new File($this->storage.'/');
		if(!$f->exists)
			$f->create();
			
		// Let's clean expired entries.
		$lastClean = $this->get('__cacheCleanTime', 0);
		
		if( (time() - $lastClean) > 3600 ) {
			$this->cleanExpired($f);
			$this->put('__cacheCleanTime', time());
		}
	}
	
	public function cleanExpired($dir)
	{
		foreach($dir as $file)
		{
			if($file->isDirectory) {
				$this->cleanExpired($file);
			}
			else {
				$json = $file->serial;
				
				if($json[0] != -1 && time() > $json[0]) {
					$file->delete();
				}
			}
		}
	}
	
	/* Clears a key from the cache. */
	public function forget($key)
	{
		$this->translateKey($key)->delete();
	}
	
	/* Returns whether or not the cache has a key. */
	public function has($key)
	{
		$f = $this->translateKey($key);
		if($f->exists) {
			$json = $f->serial;
			
			if($json[0] != -1 && time() > $json[0])
				return false;
			
			return true;
		}
		return false;
	}
	
	/* Returns a key from the cache (if it exists) or $default on failure. $default may be a closure. */
	public function get($key, $default=null)
	{
		$f = $this->translateKey($key);
		if($f->exists) {
			$json = $f->serial;
			
			if($json[0] != -1 && time() > $json[0])
				return value($default);
			
			return $json[1];
		}
		return value($default);
	}
	
	/* Stores a value in the cache for a maximum time. Availablity is not guaranteed for this period, but the cached value will be purged after this period. $value may be closure. */
	public function put($key, $value, $minutes=60)
	{
		$f = $this->translateKey($key);
		$f->serial = array((time() + 60 * $minutes),value($value));
	}
	
	public function forever($key, $value)
	{
		$f = $this->translateKey($key);
		$f->serial = array(-1,value($value));
	}
	
	/* Wipes all entries in the cache. */
	public function flush()
	{
		$dir = new File($this->storage);
		
		foreach($dir as $file) {
			$file->delete();
		}
	}
	
	/* Gets all the available keys in the cache. */
	public function all()
	{
		$dir = new File($this->storage);
		
		$keys = array();
		
		foreach($dir as $file)
			$keys[] = FileSystem::filename($file->path);
		
		return $keys;
	}
	
	/* Increments a key (if it exists) or sets it to $count if it doesn't. */
	public function increment($key, $count=1)
	{
		$value = $this->get($key, 0);
		$value += $count;
		$this->put($key, $value);
	}
	
	/* Decrements a key (if it exists) or sets it to -$count if it doesn't. */
	public function decrement($key, $count=1)
	{
		$value = $this->get($key, 0);
		$value -= $count;
		$this->put($key, $value);
	}
	
	/* Wipes all entries in a section of the cache. */
	public function section_flush($section)
	{
		$dir = $this->grabSection($section);
		
		foreach($dir as $file) {
			$file->delete();
		}
	}
	
	/* Returns whether a section of the cache has a key. */
	public function section_has($section, $key)
	{
		return $this->translateKey($key, $section)->exists;
	}
	
	/* Stores a value in a section of the cache for a maximum time. Availablity is not guaranteed for this period, but the cached value will be purged after this period. $value may be closure. */
	public function section_put($section, $key, $value, $minutes=60)
	{
		$f = $this->translateKey($key, $section);
		$f->serial = array((time() + 60 * $minutes),value($value));	
	}
	
	public function section_forever($section, $key, $value)
	{
		$f = $this->translateKey($key, $section);
		$f->serial = array(-1,value($value));
	}
	
	/* Gets a key from a section of the cache (if it exists) or $default on failure. $default may be a closure. */
	public function section_get($section, $key, $default=null)
	{
		$f = $this->translateKey($key, $section);
		if($f->exists) {
			$json = $f->serial;
			
			if($json[0] != -1 && time() > $json[0])
				return value($default);
			
			return $json[1];
		}
		return value($default);
	}
	
	/* Gets all the available keys in the section of the cache. */
	public function section_all($section)
	{
		$dir = $this->grabSection($section);
		
		$keys = array();
		
		foreach($dir as $file)
			$keys[] = FileSystem::filename($file->path);
		
		return $keys;
	}
	
	/* Clears a key in a section of the cache. */
	public function section_forget($section, $key)
	{
		$this->translateKey($key, $section)->delete();
	}
	
	/* Increments a key in a section of the cache (if it exists) or sets it to $count if it doesn't. */
	public function section_increment($section, $key, $count=1)
	{
		$value = $this->section_get($section, $key, 0);
		$value += $count;
		$this->section_put($section, $key, $value);
	}
	
	/* Decrements a key in a section of the cache (if it exists) or sets it to -$count if it doesn't. */
	public function section_decrement($section, $key, $count=1)
	{
		$value = $this->section_get($section, $key, 0);
		$value -= $count;
		$this->section_put($section, $key, $value);
	}
}

/*
CRON::register(function(){
	// periodically purge old files
});
*/