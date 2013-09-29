<?php
class HTMLDocument
{
	protected $title = 'Untitled Page';
	protected $meta = array(
		'content-type' => 'text/html;charset=utf-8',
		'keywords' => '',
		'description' => '',
		'viewport' => 'width=device-width, initial-scale=1.0'
	);
	protected $links = array();
	protected $scripts = array();
	protected $body = '';
	protected $head = '';
	protected $body_attr = array();
	
	public function getContent()
	{
		$html = '
			<!DOCTYPE html>
			<!--[if lt IE 7]><html class="no-js ie ie6 lte9 lte8 lte7 lte6"><![endif]-->
			<!--[if IE 7]><html class="no-js ie ie7 lte9 lte8 lte7"><![endif]-->
			<!--[if IE 8]><html class="no-js ie ie8 lte9 lte8"><![endif]-->
			<!--[if IE 9]><html class="no-js ie ie9 lte9"><![endif]-->
			<!--[if !IE]><!--><html class="no-js"><!--<![endif]-->
				<head>
					<title>'.escape_html($this->title. Config::get('document.titlesuffix', '')).'</title>';
					
					foreach($this->meta as $k => $v)
						$html .= '<meta name="'.escape_html($k).'" http-equiv="'.escape_html($k).'" content="'.escape_html($v).'" /> ';
					
					foreach($this->links as $k => $v)
						$html .= '<link rel="'.escape_html($v[0]).'" type="'.escape_html($v[1]).'" href="'.escape_html($v[2]).'" media="'.escape_html($v[3]).'" />';
					
					foreach($this->scripts as $k => $v)
						$html .= '<script src="'.escape_html($v).'" type="text/javascript"></script>';
					
					$html .='
					'.$this->head.'
				</head>
				<body';
				
				foreach($this->body_attr as $k => $v)
					$html .= ' '.escape_html($k).'="'.escape_html($v).'"';
				
				$html .= '>
					'.$this->body.'
				</body>
			</html>
		';
		return $html;
	}
	
	public function setIcon($href='favicon.ico')
	{
		$this->addLink('shortcut icon', 'image/x-icon', $href, '');
		return $this;
	}
	
	public function setTitle($string)
	{
		$this->title = $string;
		return $this;
	}
	
	public function setKeywords($string)
	{
		$this->setMeta('keywords', $string);
		return $this;
	}
	
	public function setDescription($string)
	{
		$this->setMeta('description',$string);
		return $this;
	}
	
	public function setMeta($key, $value)
	{
		$this->meta[$key] = $value;
		return $this;
	}
	
	public function addLink($rel, $type, $href, $media="screen,projection")
	{
		$this->links[md5($href)] = array($rel, $type, $href, $media);
		return $this;
	}
	
	public function write($string)
	{
		$this->body .= $string;
		return $this;
	}
	
	public function append($string)
	{
		$this->body .= $string;
		return $this;
	}
	
	public function prepend($string)
	{
		$this->body = $string.$this->body;
		return $this;
	}
	
	public function clear()
	{
		$this->body = '';
		return $this;
	}
	
	public function addScript($href)
	{
		$this->scripts[md5($href)] = $href;
		return $this;
	}
	
	public function addStyle($href)
	{
		$this->addLink('stylesheet', 'text/css', $href, 'screen,projection');
		return $this;
	}
	
	public function __toString()
	{
		return $this->getContent();
	}
	
	public function appendToHead($html)
	{
		$this->head .= $html;
		return $this;
	}
	
	public function setBody($k,$v)
	{
		$this->body_attr[$k] = $v;
		return $this;
	}
	
	
	// ----

	
	
	/*
	public function removeScript();
	public function removeStyle();
	public function h();
	public function eh();
	public function s();
	public function es();
	*/
}
?>