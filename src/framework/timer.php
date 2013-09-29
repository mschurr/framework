<?php
/**
 * This class is a simple timer. The timer starts when the object is instantiated.
 * 
 * Usage Example:
 *   $timer = new Timer();
 *   
 *   for($i = 1; $i < 1000000; $i++) {
 *	 }
 *
 *   echo 'The loop took '.$timer->reap().'ms!';
 */
class Timer {
	protected $init;
	
	public function __construct() {
		$time = explode(' ', microtime());
		$time = $time[1] + $time[0];
		$this->init = $time;
		unset($time);
	}
	
	/* Returns the time elapsed (in milliseconds) since the object was instantiated. */
	public function reap() {
		$time = explode(' ', microtime());
		$time = $time[1] + $time[0];
		return round(($time - $this->init), 3) * 1000;
	}
}