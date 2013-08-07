<?php

class Response
{
	public $out;
	public $headers = array(
		'Content-Type' => 'text/html;charset=UTF-8',
		'Server' => 'Restricted'
	);
	public $status = 200;
	public $setCookies = array();
	
	public function __construct()
	{
		$this->out = new Output();
	}
		
	public function enableCaching($http=true, $content=true, $session_specific=false)
	{
		// Enables HTTP Caching, indicating the page's content is "static" in a sense. Can be combined with compression.
	}
	
	public function enableCompression()
	{
		// Enables HTTP Compression, saving server-side bandwidth at the cost of CPU cycles.
	}
	
	public function send()
	{
		header('HTTP/1.1 '.$this->status.' '.$this->status_text[$this->status], true);
		
		foreach($this->headers as $k => $v) {
			header($k.':'. $v, true);
		}
		
		foreach($this->setCookies as $cookie) {
			Cookies::put($cookie);
		}
		
		$this->out->send();
	}
	
	public function error($code, $message = false, $critical = false)
	{
		$this->status = $code;
		$this->out->clear();
		$this->headers['Content-Type'] = 'text/html;charset=UTF-8';
		
		$this->out->write("
		<!DOCTYPE HTML>
		<html>
			<head>
				<title>".$this->status." ".$this->status_text[$this->status]."</title>
			</head>
			<body>
				<h1>".$this->status." ".$this->status_text[$this->status]."</h1>
				".($message !== false ? $message : '')."
			</body>
		</html>
		");
		
		if($critical === true) {
			$this->send();
			die();
		}
	}
	
	public function __toString()
	{
		$request = $_SERVER['SERVER_PROTOCOL']." ".$this->status." ".$this->status_text[$this->status].EOL;
		foreach($this->headers as $k => $v) {
			$request .= $k.": ".$v.EOL;
		}
		$request .= $this->out->get();
		return($request);
	}
	
	public function addCookie($obj)
	{
		if($obj instanceof Cookie)
			$this->setCookies[$obj->getName()] =& $obj;
	}
	
	public function pass($path, $name=false, $extra_headers=array())
	{
		// Passes a file through to the end-user, allowing them to download it.
		// Supports HTTP Range headers, allowing clients to pause and resume downloads.
	}
	
	public function dump()
	{
		ob_start();
		call_user_func_array('var_dump', func_get_args());
		$r = ob_get_contents();				
		ob_end_clean();
		
		$r = str_replace(' ', '&nbsp;', $r);
		$r = nl2br($r);
		$r = '<span style="font-family: Monospace;">'.$r.'</span>';
		$this->write($r);
	}
	
	public function clear()
	{
		return $this->out->clear();
	}
	
	public function write($s)
	{
		return $this->out->write($s);
	}
	
	public function json($data)
	{
		$this->out->clear();
		$this->headers['Content-Type'] = 'application/json';
		
		if(is_array($data))
			$data = to_json($data);
			
		$this->out->write($data);
	}
	
	public function with($obj)
	{
		if($obj instanceof BladeView) {
			$obj->prepare();
			$tmp =& $this->out->document; // force creation of a document
			$this->out->setView($obj);
		}
		if($obj instanceof Cookie) {
			$this->setCookies[$obj->getName()] =& $obj;
		}
		return $this;
	}
	
	protected $status_text = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	);
	
	public function __get($s)
	{
		if($s == 'document')
			return $this->out->document;
		
		return null;
	}
}