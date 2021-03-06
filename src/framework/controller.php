<?php

abstract class Controller
{
	protected $response;
	protected $request;
	protected $_database;
	protected $_session;
	
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
				$this->_database =& App::database();
			return $this->_database;
		}
		if($k == 'document') {
			return $this->response->document;
		}
		if($k == 'session') {
			if(!($this->_session instanceof Session))
				$this->_session =& App::session();
			return $this->_session;
		}
		if($k == 'users')
			return App::getUserService();
		if($k == 'groups')
			return App::getGroupService();
		if($k == 'auth')
			return $this->session->auth();
		if($k == 'user')
			return $this->session->user();
		return null;
	}
	
	public function __isset($k)
	{
		if($k == 'database' || $k == 'db' || $k == 'document' || $k == 'session' || $k == 'auth' || $k == 'user' || $k == 'users' || $k == 'groups')
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
	
	private $__reference;
	public function __registerRoutingReference($s)
	{
		$this->__reference = $s;
	}
	public function __getRoutingReference()
	{
		return $this->__reference;
	}
	
	public function __toString()
	{
		return $this->__reference;
	}
}

if(function_exists('class_alias'))
	class_alias('Controller', 'RequestHandler');