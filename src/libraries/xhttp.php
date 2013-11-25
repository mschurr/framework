<?php
class xHTTPResponse2
{
	// send()
	// cookie or cookies
	// toString
	// http version
	// http status
	// http headers
	// remote_adddr
	// size
	// type
	// body
	// timestamp
	/*
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
	*/
}

class xHTTPRequest2
{
	// toString
	// method
	// path
	// uri
	// host
	// cookie or cookies
	// get post file files
	// header headers
	// useragent|client|browser
}

class xHTTPResponse
{
	protected $data = array(
		'status' => '',
		'status_text' => '',
		'version' => 'HTTP/1.1',
		'headers' => array(),
		'body' => '',
		'size' => '', // in bytes
		'type' => '',
		'json' => '',
		'rss' => '',
		'xml' => '',
		'html' => '',
		'file' => ''
	);	
	
	public function __construct($data) {
		$this->data = array_merge($this->data, $data);
	}
	
	public function __get($name) {
		if(isset($this->data[$name]))
			return $this->data[$name];
		return null;
	}
	
	public function __isset($name) {
		return isset($this->data[$name]);
	}
}

class xHTTPClient
{
	protected $method = 'GET';
	protected $url = '';
	protected $headers = array();
	protected $data = array();
	protected $cookies = false;
	protected $user_agent = 'Mozilla/5.0 CGI/WebDaemon';
	protected $ch = false;
	protected $debug = false;
	
	public function __construct($cookies=false, $headers=array(), $user_agent=false)
	{
		// Merge Data
		$this->headers = $headers;
		$this->cookies = $cookies;
		
		if($user_agent !== false)
			$this->user_agent = $user_agent;
		
		// Initialize
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
		//curl_setopt($this->ch, CURLOPT_REFERER, "http://www.".DOMAIN."/");
		
		// Cookies
		if($this->cookies !== false)
		{
			if(is_array($this->cookies)) {
				$this->setCookies($this->cookies);
			}
			else {
				$this->enableCookies($this->cookies);
			}
		}
		
		// Assign Headers
		$this->setHeaders($this->headers);
	}
	
	protected function run() {
		$res = curl_exec($this->ch);
		$res_headers = curl_getinfo($this->ch);
				
		// Check For CURL Errors
		if (curl_errno($this->ch) != 0) {
			return false;
		}
		
		$header = substr($res, 0, $res_headers['header_size']);
		$body = substr($res, $res_headers['header_size']);
		
		$header_tmp = explode(PHP_EOL, $header);
		$headers=array();
		
		foreach($header_tmp as $i => $value) {
			if($i == 0) {
				$h = explode(" ",$value,3);
				$version = $h[0];
				$text = $h[2];
				$code = $h[1];
			}
			elseif(strpos($value, ":") !== false) {
				$h = explode(":", $value, 2);
				$headers[trim($h[0])] = trim($h[1]);
			}
		}
		
		$type = (str_contains($res_headers['content_type'],";") ? substr($res_headers['content_type'],0,strpos($res_headers['content_type'],';')) : $res_headers['content_type']);
		
		$response = new xHTTPResponse(array(
			'status' => $res_headers['http_code'],
			'status_text' => $text,
			'version' => $version,
			'headers' => $headers,
			'body' => $body,
			'size' => ($res_headers['size_download'] - $res_headers['header_size']),
			'type' => $type,
			'json' => ($type == 'application/json' ? from_json($body) : null),
			'file' => null
		));
		
		return $response;
	}
	
	public function enableCookies($jar_name) {
		$file = FILE_ROOT.'/cache/xhttp_'.md5($jar_name).'.dat';
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $file);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $file);
	}
	
	public function setCookies($cookies) {
		// $cookie_string = (str) "key=value;key2=value2" or (array) array(key => value,...+)
		
		if(is_array($cookies)) {
			$cookie_string = '';
			foreach($cookies as $key => $value)
				$cookie_string .= $key.'='.$value.';';
		}
		else {
			$cookie_string = $cookies;
		}
		
		curl_setopt($this->ch, CURLOPT_COOKIE, $cookie_string);	
	}
	
	public function __destruct() {
		curl_close($this->ch);	
	}
	
	public function lastError() {
		return curl_errno($this->ch).' '.curl_error($this->ch);
	}
	
	public function __toString()
	{
		return '<xHTTPClient Object>';
	}
	
	public function setHeaders($headers) {
		// $data = (array) array('header' => 'value')
		$data = array();
		foreach($headers as $k => $v)
			$data[] = $k.': '.$v;
		
		// CURLOPT_HTTPHEADER = array( 'Header: Value', ...+ );
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $data);	
	}
	
	public function get($url,$data=null) {
		curl_setopt($this->ch, CURLOPT_HTTPGET, true);
		curl_setopt($this->ch, CURLOPT_POST, false);
		curl_setopt($this->ch, CURLOPT_URL, $url);
		return $this->run();
	}
	
	public function post($url,$data) {
		// $data = array( FIELD => VALUE, ..+ )
		curl_setopt($this->ch, CURLOPT_HTTPGET, false);
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS ,$data);
		return $this->run();
	}
	
	public function xmlhttp($url,$data) {
		// $data = array( FIELD => VALUE, ..+ )
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
		
		// XML HTTP Request Headers
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
			'X-Request-With: XMLHttpRequest',
			'Pragma: no-cache',
			'Content-Type: application/json; charset=utf-8',
			'Cache-Control: no-cache',
			'Accept: application/json, text/javascript,*/* ; q=0.01',
		));
		
		return $this->run();
	}
	
	/*
		CURLOPT_BINARYTRANSFER
		curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
		CURLOPT_PUT CURLOPT_INFILE CURLOPT_INFILESIZE CURLOPT_UPLOAD
		CURLOPT_CUSTOMREQUEST = "DELETE" "PUT"		
	*/
}
