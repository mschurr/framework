<?php
/**
 * Redirection Class
 * --------------------------------------------------------------------------------
 * This class handles user redirection. To use it, simply call Redirect::to(URL);
 * Statements after the Redirect call will still be executed; if you wish to stop executing,
 *  you will need to return the redirect from the controller.
 */

class Redirect
{
	protected static /*Redirect*/ $redirect;
	
	/**
	 * Applies the last redirect to the response. Called automatically by the Framework.
	 */
	public static function apply(Response $response)
	{
		if(self::$redirect !== null) {
			$response->headers['Location'] = (string) self::$redirect->url;
			$response->status = 302;
			$response->out->clear();
		}
	}
	
	/**
	 * Redirects the client to the provided URL object or string URL. Calling this function does not stop execution of statements following the redirect,
	 *  although the user will still be redirected after the script ends and anything on the output buffer will not be displayed. 
	 */
	public static function to($url)
	{
		if($url instanceof Redirect) {
			self::$redirect = $url;
			return $url;
		}
		
		$redirect = new Redirect(URL::to($url));
		self::$redirect = $redirect;
		return $redirect;
	}
	
	// -------------------------------------------
	
	protected /*URL*/ $url;
	
	public function __construct(URL $url)
	{
		$this->url =& $url;
	}
}
?>