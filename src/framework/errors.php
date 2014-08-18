<?php
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Exception as BaseException;

error_reporting(E_ALL);
ini_set('error_log', FILE_ROOT.'/webapp.log');
ini_set('log_errors', true);
ini_set('display_errors', false);

class ErrorManager
{
	protected static $run;

	public static function engageDevelopmentHandler()
	{
		static::$run = new Run;
		$handler = new PrettyPageHandler;

		$handler->addDataTable('Framework Data', array(

		));

		static::$run->pushHandler($handler);
		static::$run->register();
	}

	public static function disengageDevelopmentHandler()
	{
		static::$run->unregister();
	}

	public static function engageProductionHandler()
	{
		set_error_handler('ErrorManager::productionHandleError');
        set_exception_handler('ErrorManager::productionHandleException');
        register_shutdown_function('ErrorManager::productionHandleShutdown');
	}

	public static function productionHandleError($level, $message, $file = null, $line = null)
	{
		try {
			$response = new Response();
			Route::doRouteError(new Request(), $response, 500);
			$response->send();
			die();
		}
		catch(Exception $e) {
			echo '500 Internal Server Error';
			die();
		}
	}

	public static function productionHandleException(Exception $e)
	{
		if($e instanceof InvalidParameterException) {
			$code = 400;
		} else {
			$code = 500;
		}

		try {
			$response = new Response();
			Route::doRouteError(new Request(), $response, $code);
			$response->send();
			die();
		}
		catch(Exception $e) {
			echo $code.' Internal Server Error';
			die();
		}
	}

	public static function productionHandleShutdown()
	{
		if($error = error_get_last())
			return static::productionHandleError(
				$error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
	}
}
