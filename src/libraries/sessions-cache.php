<?php
/**
 * Sessions Cache Driver
 * -----------------------------------------------------------------------------------------------------------------------
 *
 * This class implements the session driver using the Cache API. You should not instantiate this class directly; use the Session class.
 * You can find the public API documentation for the class in the Session class.
 */

class Session_Driver_cache extends Session_Driver
{
	protected $_id;
	protected $_vars = array();
	protected $_flash = array();
	protected $_flashwrite = array();
	protected $_changed = false;
	protected $_renewable = true;
	
	public function id()
	{
		if(!is_null($this->_id))
			return $this->_id;
		
		// Let's attempt to find our session identifier using cookies.
		if(Cookies::has($this->_cookie)) {
			$this->_id = Cookies::get($this->_cookie)->value;
			return $this->_id;
		}
		
		// If we couldn't find it, let's generate a new one and set the cookie (encrypted, secure, httponly).
		$this->regenerateID();
		
		// Return the identifier.
		return $this->_id;
	}
	
	protected function regenerateID()
	{
		// Set the ID to a random string.
		$this->_id = $this->_generateID();
		
		// Send the cookie.
		$cookie = new Cookie($this->_cookie);
		$cookie->value = $this->_id;
		$cookie->expiry = time() + 3600 * 24 * 14;
		$cookie->path = '/';
		$cookie->domain = App::getRequest()->domain;
		$cookie->secure = App::getRequest()->secure;
		$cookie->httponly = true;
		$cookie->send();
	}
	
	protected $_idchars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
	protected function _generateID()
	/* Returns a unique, randomly-generated session identifier */
	{
		// Seed the random number generator.
		mt_srand(microtime(true) * 1000000);
		
		// Create a unique session identifier.
		$length = 100 + mt_rand(25,50);
		$id = "";
		
		while(strlen($id) < $length) {
			$id .= substr($this->_idchars, mt_rand(0, strlen($this->_idchars)-1), 1);
		}
		
		return $id;
	}
	
	public function defaultVars()
	{
		return array(
			'origin_ip' => App::getRequest()->ip,
			'fingerprint' => md5(App::getRequest()->server['HTTP_USER_AGENT']),
			'is_https' => App::getRequest()->secure,
			'last_regenerate' => 0 // Force regeneration on creation to prevent fixation.
		);
	}
	
	public function load()
	{
		// Load our session variables from the cache.
		$object =& $this;
		$data = Cache::section('session')->get( sha1($this->id()), function() use (&$object) {
			return array( 'vars' => $object->defaultVars(), 'flash' => array() );
		});
		
		// Copy to instance if the session is not expired.
		if(!(isset($data['vars']['expire']) && $data['vars']['expire'] > time())) {
			$this->_vars = $data['vars'];
			$this->_flash = $data['flash'];
		}
		else {
			$this->_vars = $this->defaultVars();
		}
		
		// Update the renewable flag (if applicable).
		if(isset($data['vars']['renewable']))
			$this->_renewable = $data['vars']['renewable'];
		
		// Handle Variable Updates and Session Regeneration
		$regenerate = false;
		foreach($this->defaultVars() as $k => $v) {
			if($k != 'last_regenerate') {
				if($this->get($k) != $v) {
					$regenerate = true;
				}
				$this->set($k, $v);
			}
		}
		
		if((time() - $this->get('last_regenerate')) > 600) {
			$regenerate = true;
		}
		
		if($regenerate)
			$this->regenerate();
	}
	
	public function unload()
	{
		// We can save data here (if anything was changed).
		if($this->_changed || sizeof($this->_flashwrite) > 0 || sizeof($this->_flash) > 0) { 
			$this->save();
		}
	}
	
	protected function save()
	{
		if(!$this->_renewable)
			return;
		Cache::section('session')->put( sha1($this->id()), array(
			'vars' => $this->_vars,
			'flash' => $this->_flashwrite
		) );
	}
	
	public function get($key, $default=null)
	{
		if($key == 'id')
			return $this->id();
		if($key == 'auth')
			return $this->auth();
		if($key == 'user')
			return $this->user();
			
		if(isset($this->_vars[$key]))
			return $this->_vars[$key];
			
		if(isset($this->_flash[$key]))
			return $this->_flash[$key];
			
		return value($default);
	}
	
	public function set($key, $value)
	{
		$this->_changed = true;
		$this->_vars[$key] = value($value);
	}
	
	public function has($key)
	{
		return (isset($this->_vars[$key]) || isset($this->_flash[$key]));
	}
	
	public function forget($key)
	{
		$this->_changed = true;
		unset($this->_vars[$key]);
		unset($this->_flash[$key]);
	}
	
	public function flash($key, $value=null)
	{
		if($value === null && !is_array($key) && isset($this->_vars[$key])) {
			$this->_flash[$key] = $this->_vars[$key];
			$this->_flashwrite[$key] = $this->_flash[$key];
			unset($this->_vars[$key]);
		}
		
		if(!is_array($key) && $value !== null)
		{
			$this->_flash[$key] = value($value);
			$this->_flashwrite[$key] = $this->_flash[$key];
			unset($this->_vars[$key]);
		}
		
		if(is_array($key))
		{
			foreach($key as $k => $v) {
				$this->_flash[$k] = value($v);
				$this->_flashwrite[$k] = $this->_flash[$key];
				unset($this->_vars[$k]);
			}
		}
		
		$this->_changed = true;
	}
	
	public function keep($key)
	{
		$this->_changed = true;
		
		if(isset($this->_flash[$key])) {
			$this->_vars[$key] = $this->_flash[$key];
			unset($this->_flash[$key]);
		}
	}
	
	public function regenerate()
	{
		// Mark the session as invalid (in 60 seconds).
		$this->_vars['renewable'] = false;
		$this->_vars['expire'] = time() + 60;
		
		// We need to make sure to save.
		$this->save();
		
		// Let's mark the session as valid again.
		unset($this->_vars['expire']);
		unset($this->_vars['renewable']);
		
		// Let's change the session identifier and update the cookie.
		$this->regenerateID();
		
		// Let's update the variable.
		$this->_vars['last_regenerate'] = time();
		
		// Let's save changes.
		$this->save();
	}
}