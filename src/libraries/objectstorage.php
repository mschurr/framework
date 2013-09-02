<?php

class ObjectStorage
{
	
}

abstract class ObjectStorage_Driver
{
	public function __construct() {
	}
	
	public function __destruct() {
	}
	
	public function put($object, $group, $expires=-1) {
		// object is a file path
		// object instanceof File
		// object instanceof Closure
		// object is a string
	}
	
}


// store files and /or php objets

// DRIVERS: s3, rds, file