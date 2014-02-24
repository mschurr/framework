<?php

class Mail
{
	protected static $mailer = null;

	public static function enqueue(Mail $message) /*throws MailException*/
	{
		self::send($message);
	}

	public static function send(Mail $message) /*throws MailException*/
	{
		if(static::$mailer === null) {
			$transport = Swift_SmtpTransport::newInstance(Config::get('mailer.host'), Config::get('mailer.port'), Config::get('mailer.crypt'))
				->setUsername(Config::get('mailer.user'))
				->setPassword(Config::get('mailer.pass'));

			static::$mailer = Swift_Mailer::newInstance($transport);
		}

		try {
			$swift = Swift_Message::newInstance()
				->setSubject($message->subject)
				->setFrom(array(Config::get('mailer.email') => Config::get('mailer.name')))
				->setTo(array($message->recipient => $message->recipientName));
			
			if($message->body)
				$swift->setBody($message->body, 'text/html');

			if($message->text)
				$swift->addPart($message->text, 'text/plain');

			foreach($message->attachments as $file)
				$swift->attach(
					Swift_Attachment::fromPath($file->canonicalPath, $file->mime)
						->setFilename($file->name)
						->setDisposition('inline')
				);

			$result = static::$mailer->send($swift);

			if(!$result)
				throw new MailException("Send Failed");
		}
		catch(Exception $e) {
			throw new MailException("Send Failed");
		}
	}

	public function __get($key)
	{
		return call_user_func_array(array($this, 'get'.ucfirst($key)), array());
	}

	public function __isset($key)
	{
		return method_exists($this, 'get'.ucfirst($key));
	}

	public function __unset($key)
	{
		call_user_func_array(array($this, 'set'.ucfirst($key)), array(null));
	}

	public function __set($key, $value)
	{
		call_user_func_array(array($this, 'set'.ucfirst($key)), array($value));
	}

	protected $_recipient;
	public function getRecipient()
	{ return $this->_recipient; }
	public function setRecipient($value)
	{ $this->_recipient = $value; }

	protected $_recipientName;
	public function getRecipientName()
	{ return $this->_recipientName; }
	public function setRecipientName($value)
	{ $this->_recipientName = $value; }

	protected $_subject;
	public function getSubject()
	{ return $this->_subject; }
	public function setSubject($value)
	{ $this->_subject = $value; }

	protected $_body;
	public function getBody()
	{ return $this->_body; }
	public function setBody($value)
	{ $this->_body = $value; }

	protected $_text;
	public function getText()
	{ return $this->_text; }
	public function setText($value)
	{ $this->_text = $value; }

	protected $_attachments = array();
	public function attach(File $file)
	{
		if(!$file->exists)
			throw new MailException("Attachment Failed: FILE DOES NOT EXIST!");

		$this->_attachments[] = $file;
	}
	public function getAttachments()
	{
		return $this->_attachments;
	}
}

class MailException extends Exception {}