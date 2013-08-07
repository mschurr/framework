<?php

class Crypt
{
	protected static $cache;
	protected static $iv;
	protected static $key;
	protected static $mode;
	protected static $cipher;
	
	public static function init() {
	}
	
	public static function setMode() {
	}
	
	public static function setCipher() {
	}
	
	public static function setKey($s) {
	}
	
	public static function encrypt($s) {
	}
	
	public static function decrypt($s) {
	}
} Crypt::init();

class AES_Encryption
{
	protected $data;
	protected $key;
	protected $iv;
	
	public function __construct($key)
	{
		return $this->set_key($key);
	}
	
	public function encrypt($data)
	{
		if(strlen($data) == 0) {
			$this->data = '';
			return $this;
		}
		
		$this->data = strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $data, MCRYPT_MODE_ECB, $this->iv)), '+/=', '-_,');
		return $this;
	}
	
	public function decrypt($data)
	{
		if(strlen($data) == 0) {
			$this->data = '';
			return $this;
		}
		
		$this->data = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, base64_decode(strtr($data, '-_,', '+/=')), MCRYPT_MODE_ECB, $this->iv));
		return $this;
	}
	
	public function getResult()
	{
		return $this->data;
	}
	
	public function get(){ 
		return $this->data;
	}
	
	public function get_key()
	{
		return $this->key;
	}
	
	public function set_key($key)
	{
		$this->key = hash('sha256', $key, true);
		$this->iv = mcrypt_create_iv(32, MCRYPT_RAND);
		return $this;
	}
}
?>