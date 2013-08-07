<?php

class Captcha {
	/* This holds the CAPTCHA code. */
	public $code;
	public $id;
	
	public function __construct($identifier='default') {
		$driver = Config::get('captcha.driver', 'cookies');
	}
}

abstract class Captcha_Driver
{
	public function __construct() {
		$this->onLoad();
	}
	
	public function __destruct() {
		$this->onUnload();
	}
	
	public abstract function onLoad();
	public abstract function onUnload();
	public abstract function check($code=null);
	public abstract function getCode();
	public abstract function getHtml();
	public abstract function getCaptcha();
}
