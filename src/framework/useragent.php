<?php

class UserAgent
{
	protected $raw;
	
	public function __construct($ua_string)
	{
	}
	
	/*
		Valid Options:
			windows, mac, linux, bsd, windows xp, windows vista, windows 7, windows 8, chromeos, debian, ubuntu, openbsd, freebsd
			x64, x32, x86
			mobile, iphone, android, blackberry
			bot, robot, user-based
			firefox, chrome, safari, opera, ie, flock, netscape
	*/
	
	public function is($opt)
	{
	}
	
	/*
		charsets, languages, compressions, filetypes
	*/
	
	public function accepts($opt)
	{
	}
	
	
	// todo: implement user agent parameters: user_agent, isRobot, isMobile, isBrowser, languages, encodings, accept mimes, ...
	
	public function __toString()
	{
	}	
}