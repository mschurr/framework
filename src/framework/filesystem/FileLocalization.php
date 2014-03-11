<?php

//namespace mschurr\FileObject;
use \InvalidArgumentException;

/**
 * Provides an interface for localizing error messages.
 */
class FileLocalization
{
	protected static /*array<String:String>*/ $strings;
	protected static /*String*/ $language;

	/**
	 * Returns a formatted language string. The first parameter is the name
	 * of the template. Additional parameters are for substiutions.
	 */
	public static /*String*/ function make(/*...*/)
	{
		$args = func_get_args();

		if(count($args) < 1)
			throw new \InvalidArgumentException();

		if(self::$language === null)
			return $args[0];

		if(!isset(self::$strings[$args[0]]))
			return $args[0];

		return call_user_func_array('sprintf', array_merge(
				self::$strings[$args[0]],
				array_slice($args, 1)
		));
	}

	/**
	 * Loads the strings for the given language code.
	 */
	public static /*void*/ function load(/*String*/$language='en')
	{
		$file = __DIR__.'/lang/'.$language.'.php';

		if(!file_exists($file))
			throw new InvalidArgumentException();

		self::$language = $langauge;
		self::$strings = require(__DIR__.'/lang/'.$language.'.php');
	}
}