<?php
/**
 * CAS Authentication Driver
 * @author Matthew Schurr
 *
 * This class implements an authentication driver using CAS and an SQL relational database system.
 */
class Auth_Driver_cas extends Auth_Driver_db
{
	public /*void*/ function load()
	{
		parent::load();
	}

	public /*bool*/ function check($password)
	{
		throw new AuthException(AuthExceptionType::NOT_SUPPORTED);
	}

	protected /*bool*/ function throttleByAccount($username)
	{
		return true;
	}
}


/*
CRON::register(function(){
	// periodically purge old/unused identifiers to keep table size low
});
*/
