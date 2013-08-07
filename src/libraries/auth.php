<?php

class Auth
{
	public static function check($username, $password)
	{
	}
	
	public static function login($userid, $remember=false)
	{
	}
	
	public static function attempt($username, $password, $remember=false)
	{
	}
	
	public static function user()
	/* Returns the currently authenticated user id or null on failure. */
	{
	}
	
	public static function validate($credentials)
	{
	}
	
	public static function once($credentials)
	{
	}
}