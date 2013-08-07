<?php
if(!function_exists('included'))
	die();

class Mail
{
	public static function send($template_info, $data, $fn)
	{
		// $template_info = html template name or array(html=>template,text=>template)
		// $data=kv array
		// $fn = function($message)
	}
	
	public static function queue() // same as send
	{
	}
	
	public static function later($seconds) // + same as send
	{
	}
	
	public static function queueOn($queuename) // +same as send
	{
	}
}

class MailMessage
{
	public function to($email, $alias=null)
	{
	}
	
	public function subject($string)
	{
	}
	
	public function cc($email, $alias=null)
	{
	}
	
	public function bcc($email, $alias=null)
	{
	}
	
	public function attach($path, $options)
	{
		// options = array(as => filename, mime => mime)
	}
	
	public function embed($path)
	{
		// embeds a file inline
	}
	
	public function embedData($data, $name=null)
	{
	}
}
?>