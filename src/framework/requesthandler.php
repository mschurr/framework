<?php

abstract class RequestHandler
{
	protected $response;
	protected $request;
	protected $_database;
	
	public function __construct(&$request, &$response)
	{
		$this->request =& $request;
		$this->response =& $response;
		$this->autorun();
	}
	
	public function autorun()
	{
	}
	
	public function __get($k)
	{
		if($k == 'database' || $k == 'db') {
			if(!($this->_database instanceof Database))
				$this->_database =& App::DB();
			return $this->_database;
		}
		if($k == 'document') {
			return $this->response->document;
		}
		return null;
	}
	
	public function __isset($k)
	{
		if($k == 'database' || $k == 'db')
			return true;
		if($k == 'document')
			return true;
		return false;
	}
	
	public function __unset($k)
	{
		if($k == 'database' || $k == 'db')
			unset($this->_database);
	}
	
	public function __set($k, $v)
	{
		if($k == 'database' || $k == 'db')
			$this->_database = $v;
	}
}