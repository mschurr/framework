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
	
	public function pass($file_path, $range_start=false, $range_end=false)
	{
		// NOT_YET_IMPLEMENTED
		
		/*
		HTTP/1.1 200 or HTTP/1.1 304
		Content-Length
		Access-Control-Allow-Origin: *
		Content-Type
		Content-Range
		Accept-Ranges
		Age
		Content-Disposition: inline|attachment; filename=
		Etag
		Content-Transfer-Encoding
		Date
		Pragma
		Last-Modified
		Cache-Control: public, max-age=3600, must-revalidate
		Content-Encoding
		Expires
		
		
		Accept
		Accept-Charset
		Accept-Encoding
		Accept-Language
		Accept-Datetime
		Cache-Control
		If-Modified-Since
		If-None-Match
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
	}
	
	public function get()
	{
		if($this->_document !== false)
			$this->buffer = $this->_document->getContent().$this->buffer;
		
		return $this->buffer;
	}
}

?>