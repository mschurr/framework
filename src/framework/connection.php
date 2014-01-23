<?php
/* 
	Tools for managing the HTTP connection or getting information about the connection state.
*/

class Connection
{	
	public function __get($k) {
		if($k == 'timedOut')
			return $this->timedOut();
		elseif($k == 'aborted')
			return $this->aborted();
		elseif($k == 'active')
			return $this->active();
		else
			return null;
	}

	public function timedOut() {
		/* Returns whether or not the connection timed out. */
		return connection_status() === CONNECTION_TIMEOUT;
	}

	public function aborted() {
		/* Returns whether or not the connection was aborted. */
		return connection_status() === CONNECTION_ABORTED;
	}

	public function active() {
		/* Returns whether or not the connection is active. */
		return connection_status() === CONNECTION_NORMAL;
	}
	
	public function drop() {
		/* This function drops (disconnects) the client. Useful if you want to perform background processing. */
		ignore_user_abort(true);
		
		ob_start();
		header("HTTP/1.1 200 OK", true);
		header("Connection: close", true);
		header("Content-Type: text/plain", true);
		header("Content-Encoding: none", true);
		echo PHP_EOL;
        header("Content-Length: ".ob_get_length(), true);
		ob_end_flush(); 
		flush();
		session_write_close();
		flush();
		
		if(function_exists('fastcgi_finish_request'))
			fastcgi_finish_request();
		
		while(connection_status() === CONNECTION_NORMAL) {
			echo EOL;
			flush();
			sleep(2);
		}
		
		return true;
	}
	
	public function keepAlive($continue, $aborted=false) {
		/* This causes the server to keep the connection to the client alive until the provided closure returns FALSE or the user disconnects. Useful for long polling.
			If the client disconnects early, the aborted function is called.
		 */
		
		// Use Chunked Transfer Encoding
		do {
			if($continue() === false) {
				$this->drop();
				break;
			}
			
			sleep(1);
			
			if($this->aborted()) {
				if(is_callable($aborted))
					$aborted();
				break;
			}			
		}
		while(true);
	}
}