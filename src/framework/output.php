<?php

class Output
{
	protected $buffer = '';
	protected $_document = false;
	protected $view = false;
	
	public function setView(&$view)
	{
		$this->view =& $view;
	}
	
	public function __construct()
	{
	}
	
	public function __get($s) {
		if($s == 'document') {
			if($this->_document === false) {
				$this->_document = new HTMLDocument();
				$this->_document->write($this->buffer);
				$this->buffer = '';
			}
			
			return $this->_document;
		}
		return null;
	}
	
	public function setDocument(&$document)
	{
		$this->_document =& $document;
	}
	
	public function pass($file_path)
	{
		// Sanity check the parameter.
		if(is_string($file_path))
			$file = File::open($file_path);
		elseif($file_path instanceof File)
			$file = $file_path;
		else
			throw new Exception("The provided parameter does not represent a file.");
		
		if(!$file->exists)
			throw new Exception("The provided file does not exist.");
		
		// Get the response object.
		$response = App::getResponse();
		$request = App::getRequest();
				
		// Calculate and send the headers.
		$response->headers['Cache-Control'] = 'public, max-age=3600, must-revalidate';
		//$response->headers['Content-Disposition'] = 'inline; filename="'.$file->name.'"'; // also: attachment
		$response->headers['Etag'] = md5($file->lastModified);
		$response->headers['Last-Modified'] = $file->lastModified;
		$response->headers['Expires'] = gmdate("D, d M Y H:i:s", $file->lastModified).' GMT';
		$response->headers['Date'] = gmdate("D, d M Y H:i:s", time()).' GMT';
		if(in_array(strtolower($file->ext), array('ttf','eot','otf','woff','svg')))
			$response->headers['Access-Control-Allow-Origin'] = '*';
				
		$cached = false;
				
		if(isset($request->headers['If-None-Match']) && $request->headers['If-None-Match'] === $response->headers['Etag']) {
			$cached = true;	
		}
		
		if(isset($request->headers['If-Modified-Since'])) {
			$time = strtotime(preg_replace('/;.*$/','',$request->headers['If-Modified-Since']));
			
			if($time !== false && $time >= $file->lastModified)
				$cached = true;
		}
		
		if($cached === true) {
			$response->status = 304;
			$response->headers['Content-Length'] = 0;
			return;	
		}
		
		$response->status = 200;
		$response->headers['Content-Type'] = $file->mime;
		$response->headers['Content-Length'] = $file->size;		
		
		$response->send();
		
		// Send the body in chunks.
		foreach($file->chunk(1024) as $bytes) {
			echo $bytes;
		}
		
		/*
		--Additional Response Headers
		Content-Encoding
		Content-Transfer-Encoding
		Content-Range
		Accept-Ranges
		Age
		Pragma
		
		--Additional Request Headers
		Accept
		Accept-Charset
		Accept-Encoding
		Accept-Language
		Accept-Datetime
		Cache-Control
		If-Match
		If-Range
		If-Unmodified-Since
		Range
		TE
		User-Agent
		*/
	}
	
	public function write($out)
	{
		if($this->_document !== false) {
			$this->_document->write($out);
			return;
		}
		$this->buffer .= $out;
	}
	
	public function clear()
	{
		if($this->_document !== false)
			$this->document = false;
		if($this->_view !== false)
			$this->view = false;
		$this->buffer = '';
		return true;
	}
	
	public function send()
	{
		if($this->view !== false)
			$this->write($this->view->render());
		
		if($this->_document !== false)
			$this->buffer = $this->_document->getContent().$this->buffer;
		
		echo $this->buffer;
		
		$this->buffer = '';
		$this->document = false;
		$this->view = false;
	}
	
	public function get()
	{
		if($this->_document !== false)
			$this->buffer = $this->_document->getContent().$this->buffer;
		
		return $this->buffer;
	}
}

?>