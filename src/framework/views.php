<?php

class View
{
	protected static $shared = array();
	
	public static function make($name, $data=array())
	{
		$view = new BladeView($name);
		$view->with($data);
		return $view;
	}
	
	public static function share($name, $value)
	{
		self::$shared[$name] = $value;
	}
	
	public static function getShared()
	{
		return self::$shared;
	}
	
	public static function show($name, $data=array())
	{
		$view = self::make($name, $data);
		App::getResponse()->with($view);
		return $view;
	}
}