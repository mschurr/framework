<?php

class View
{
	protected static $shared = array();
	
	/* Makes a BladeView with the provided data and returns it. */
	public static function make($name, $data=array())
	{
		$view = new BladeView($name);
		$view->with($data);
		return $view;
	}
	
	/* Shares a variable with all views. */
	public static function share($name, $value)
	{
		self::$shared[$name] = $value;
	}
	
	/* Gets all shared view varaibles. */
	public static function getShared()
	{
		return self::$shared;
	}
	
	/* Makes a BladeView with the provided data, pushes it to the response object, and returns the view object.*/
	public static function show($name, $data=array())
	{
		$view = self::make($name, $data);
		App::getResponse()->with($view);
		return $view;
	}
}