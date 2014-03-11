<?php
/**
 * Can be embedded in forms to detect whether or not the user is human. Useful for preventing spam and/or mass submissions.
 */
class CAPTCHA
{
	protected static /*string*/ $error = null;

	public static /*string*/ function embed() {
		return recaptcha_get_html(
			Config::get('recaptcha.publicKey', function(){
				throw new Exception("You must configure [recaptcha.publicKey] to use recaptcha.");
			}),
			null, // Error
			true // Use SSL
		);
	}

	public static /*bool*/ function isHuman() {
		$request = App::getRequest();
		$privateKey = Config::get('recaptcha.privateKey', function(){
			throw new Exception("You must configure [recaptcha.privateKey] to use recaptcha.");
		});

		if(!isset($request->post['recaptcha_challenge_field']) || !isset($request->post['recaptcha_response_field'])) {
			static::$error = 'The captcha was not filled out.';
			return false;
		}

		$response = recaptcha_check_answer(
			$privateKey,
			$request->ip,
			$request->post['recaptcha_challenge_field'],
			$request->post['recaptcha_response_field']
		);

		static::$error = $response->error;
		return $response->is_valid;
	}

	public /*string*/ function error()	{
		return static::$error;
	}
}
