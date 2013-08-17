<?php
class Timer {
	protected $init;
	
	public function __construct() {
		$time = explode(' ', microtime());
		$time = $time[1] + $time[0];
		$this->init = $time;
		unset($time);
	}
	
	public function reap() {
		$time = explode(' ', microtime());
		$time = $time[1] + $time[0];
		return round(($time - $this->init), 3) * 1000;
	}
}