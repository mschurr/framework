<?php
if(!function_exists('included'))
	die();

class xHTTPResponse
{
	public $status;
	public $status_text;
	public $version = 'HTTP/1.1';
	public $headers = array();
	public $body;
	public $size; // in bytes
	public $mime;
	
	public function __construct($s, $st, $v, $h, $b, $z, $m) {
		// Provided Variables
		$this->status = $s;
		$this->status_text = $st;
		$this->version = $v;
		$this->headers = $h;
		$this->body = $b;
		$this->size = $z;
		$this->mime = $m;
		
		// Calculate Variables
		// $this->size
		// $this->json
		// $this->rss
		// $this->xml
		// $this->html
		// $this->content_type
		// $this->file, save contents to disk function?
	}
	
	public function __invoke() {
		$args = func_get_args();
	}
	
	public function __get($name) {
	}
}

class xHTTPClient
{
	// used for making multiple requests with persistent data
}

class xHTTPRequest
{
	protected $response = false;
	protected $method = 'GET';
	protected $url = '';
	protected $headers = array();
	protected $data = array();
	protected $cookies = false;
	protected $user_agent = 'Mozilla/5.0 CGI/WebDaemon';
	protected $ch = false;
	protected $debug = true;
	
	public function __construct($method, $url, $headers=array(), $data=array(), $cookies=false, $user_agent=false)
	{
		// Merge Data
		$this->method = strtolower($method);
		$this->url = $url;
		$this->headers = $headers;
		$this->data = $data;
		$this->cookies = $cookies;
		
		if($user_agent !== false)
			$this->user_agent = $user_agent;
		
		// Initialize
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_HEADER, $this->debug);
		curl_setopt($this->ch, CURLINFO_HEADER_OUT, $this->debug);
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
		
		// Make Request (method, url, data) and Set Response
		if(!is_callable(array($this, strtolower($this->method))))
			return false;
		
		$this->response = $this->$method($this->url, $this->data);
	}
	
	protected function run() {
		$res = curl_exec($this->ch);
		$res_headers = curl_getinfo($this->ch);
		
		// Check For CURL Errors
		if (curl_errno($this->ch) != 0) {
			return false;
		}
		
		$headers = substr($res, 0, $res_headers['header_size']);
		
		// Extract Status Code
		$s = $res_headers['http_code'];
		
		// Extract Status Text, HTTP Version
		
		// Extract Headers
		preg_match_all('//', $headers, $matches);
		print_r($matches);
		
		// Extract Body
		$b = substr($res, $res_headers['header_size']);
		
		// Extract Size in Bytes
		$z = $res_headers['size_download'] - $res_headers['header_size'];
		
		// Extract Content Type
		$m = (str_contains($res_headers['content_type'],";") ? substr($res_headers['content_type'],0,strpos($res_headers['content_type'],';')) : $res_headers['content_type']);
		
		$response = new xHTTPResponse($s, $st, $v, $h, $b, $z, $m);
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
		return '<xHTTPRequest Object>';
	}
	
	public function getResult()
	{
		return $this->response;
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
?>