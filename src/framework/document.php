<?php
class HTMLDocumentNew implements ArrayAccess
{
	protected /*string*/ $title;
	protected /*array*/ $meta;
	protected /*array*/ $links;
	protected /*array*/ $scripts;
	protected /*array*/ $styles;
	protected /*String*/ $body;
	protected /*String*/ $head;
	protected /*array*/ $bodyAttributes;

	/**
	 * Throws a run-time exception.
	 * This method is only implemented to provide compatability.
	 */
	public /*void*/ function setContent()
	{
		throw new RuntimeException("The content property cannot be mutated.");
	}

	/**
	 * Returns the document title.
	 */
	public /*string*/ function getTitle()
	{
		return $this->title;
	}

	/**
	 * Sets the document title.
	 */
	public /*void*/ function setTitle($value)
	{
		$this->title = $value;
	}

	/**
	 * Returns the document meta keywords.
	 */
	public /*string*/ function getKeywords()
	{
		return $this->meta['keywords'];
	}

	/**
	 * Sets the document meta keywords.
	 */
	public /*void*/ function setKeywords(/*string*/$value)
	{
		$this->meta['keywords'] = $value;
	}

	/**
	 * Returns the document meta description.
	 */
	public /*string*/ function getDescription()
	{
		return $this->meta['description'];
	}

	/**
	 * Sets the document meta description.
	 */
	public /*void*/ function setDescription(/*string*/$value)
	{
		$this->meta['description'] = $value;
	}

	/**
	 * Appends text to the document body.
	 */
	public /*void*/ function write(/*string*/$html)
	{
		$this->body .= $html;
	}

	/**
	 * Appends text to the document body.
	 */
	public /*void*/ function append(/*string*/$html)
	{
		$this->body .= $html;
	}

	/**
	 * Prepends text to the document body.
	 */
	public /*void*/ function prepend(/*string*/$html)
	{
		$this->body = $html + $this->body;
	}

	/**
	 * Converts the object into a string returning the document content.
	 */
	public /*string*/ function __toString()
	{
		return $this->content;
	}

	/**
	 * Clears all content written to the document.
	 */
	public /*void*/ function clear()
	{
		$this->head = '';
		$this->body = '';
		$this->bodyAttributes = array();
		$this->links = array();
		$this->styles = array();
		$this->scripts = array();
		$this->title = '';
		$this->meta = array(
			'content-type' => 'text/html; charset=utf-8',
			'keywords' =>	 '',
			'description' => '',
			'viewport' => 'width=device-width, initial-scale=1.0'
		);
	}

	/**
	 * Initializes the object.
	 */
	public /*void*/ function __construct()
	{
		$this->clear();
	}

	/**
	 * Appends text to the document head.
	 */
	public /*void*/ function appendToHead($html)
	{
		$this->head .= $html;
	}

	/**
	 * Sets an attribute on the document body.
	 */
	public /*void*/ function setBodyAttribute(/*string*/ $attribute, /*string*/ $value)
	{
		$this->bodyAttributes[$attribute] = $value;
	}

	/**
	 * Gets an attribute on the document body or returns null.
	 */
	public /*string*/ function getBodyAttribute(/*string*/ $attribute)
	{
		if(isset($this->bodyAttributes[$attribute]))
			return $this->bodyAttributes[$attribute];
		return null;
	}

	// ----------------------------------

	public /*String*/ function getContent()
	{
	}

	public /*string*/ function getIcon()
	{

	}

	public /*void*/ function setIcon(/*string*/$href)
	{

	}

	public /*void*/ function addMeta($key, $value)
	{

	}

	public /*void*/ function addLink($rel, $type, $href, $media="screen,projection")
	{

	}

	/**
	 * Adds a script to the document.
	 */
	public /*void*/ function addScript(/*string*/ $href)
	{

	}

	/**
	 * Removes a script previously added to the document.
	 */
	public /*void*/ function removeScript(/*string*/ $href)
	{

	}

	/**
	 * Adds a cascading stylesheet to the document.
	 */
	public /*void*/ function addStyle(/*string*/ $href)
	{
		$this->addLink('stylesheet', 'text/css', (string) $href, 'screen, projection');
	}

	/**
	 * Removes a cascading stylesheet previously added to the document.
	 */
	public /*void*/ function removeStyle(/*string*/ $href)
	{
		
	}

// ------ Array Access
	public function offsetExists($offset)
	{
		return method_exists($this, 'get'.ucfirst($key));
	}
	
	public function offsetSet($offset, $value)
	{
		call_user_func_array(array($this, 'set'.ucfirst($offset)), array($value));
	}
	
	public function offsetGet($offset)
	{
		return call_user_func_array(array($this, 'get'.ucfirst($offset)), array());
	}
	
	public function offsetUnset($offset)
	{
		$this->__set($offset, null);
	}
	
// ------ Magic Methods
	public function __get($key)
	{
		return call_user_func_array(array($this, 'get'.ucfirst($key)), array());
	}
	
	public function __set($key, $value)
	{
		call_user_func_array(array($this, 'set'.ucfirst($key)), array($value));
	}
	
	public function __isset($key)
	{
		return method_exists($this, 'get'.ucfirst($key));
	}
	
	public function __unset($key)
	{
		$this->__set($key, null);
	}
}


class HTMLDocument // implements ArrayAccess
{
	protected $title = null;
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
				<head>';

					if($this->title !== null)
						$html .= '<title>'.escape_html($this->title. Config::get('document.titlesuffix', '')).'</title>';
					
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