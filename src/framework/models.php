<?php

class Model
{
	public function __construct()
	{
	}
	
	// --- Static Functions
	public static function load($path)
	{
	}
	
	public static function make(/* String name, [Mixed parameter1,[...]]*/)
	{
		$n = func_get_args();
	}
}

/*abstract class ModelTemplate
{
	protected $_data;
	protected $_name;
	
	public function __constuct(&$db_result)
	{
		$this->onLoad();
	}
	
	public function __destruct()
	{
		// save changes
	}
	
	public function onLoad()
	{
	}
	
	public abstract function delete();
	public abstract function insert();
	public abstract function update();
	public abstract function replace();
	public abstract function create();
	public abstract function drop();
}*/