<?php
/**
 *	Foundation Web Framework
 *	@author Matthew Schurr
 *
 * This file loads and initalizes all of the libraries essential to framework operation.
 */

/**
 * This function always returns true; it can be used to check if a file was included.
 */
function included() {
	return true;
}

// This framework only supports PHP versions >= 5.3.0.
if (version_compare(phpversion(), '5.3.0', '<') == true) {
	die('You must install PHP >= 5.3.0 in order to use this framework.');
}

// Establish directory root constants.
define('FILE_ROOT', pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_DIRNAME));
define('FRAMEWORK_ROOT', FILE_ROOT.'/vendor/mschurr/framework/src');

// Establish a default time zone (may be overridden later by user configuration).
date_default_timezone_set('UTC');

// Establish other useful constants.
define('EOL', PHP_EOL);

// Load the error handling module.
require(FRAMEWORK_ROOT.'/framework/errors.php');
ErrorManager::engageDevelopmentHandler();

// Set PHP configuration values to optimize framework performance.
ini_set('expose_php',false);
ini_set('magic_quotes_gpc','Off');
ini_set('register_globals','Off');
ini_set('default_charset','UTF-8');
ini_set('memory_limit','100M');
ini_set('max_execution_time','60');
ini_set('max_input_time','200');
ini_set('upload_max_filesize','70M');
ini_set('post_max_size', '70M');
ini_set('max_file_uploads', 10);
ini_set('safe_mode','Off');
ini_set('allow_url_fopen','Off');

// Rename the session cookie so that it does not expose PHP.
// The framework does not use built-in sessions, but poorly written third party extensions might.
ini_set('session.name', 'x-sessiond');

// Load the framework modules.
require(FRAMEWORK_ROOT.'/framework/utility.php');
require(FRAMEWORK_ROOT.'/framework/timer.php');
require(FRAMEWORK_ROOT.'/framework/registry.php');
require(FRAMEWORK_ROOT.'/framework/framework.php');
require(FRAMEWORK_ROOT.'/framework/filesystem.php');
require(FRAMEWORK_ROOT.'/framework/cache.php');
require(FRAMEWORK_ROOT.'/framework/config.php');
require(FRAMEWORK_ROOT.'/framework/log.php');
require(FRAMEWORK_ROOT.'/framework/connection.php');
require(FRAMEWORK_ROOT.'/framework/useragent.php');
require(FRAMEWORK_ROOT.'/framework/output.php');
require(FRAMEWORK_ROOT.'/framework/response.php');
require(FRAMEWORK_ROOT.'/framework/request.php');
require(FRAMEWORK_ROOT.'/framework/app.php');
require(FRAMEWORK_ROOT.'/framework/controller.php');
require(FRAMEWORK_ROOT.'/framework/route.php');
require(FRAMEWORK_ROOT.'/framework/url.php');
require(FRAMEWORK_ROOT.'/framework/html.php');
require(FRAMEWORK_ROOT.'/framework/redirect.php');
require(FRAMEWORK_ROOT.'/framework/cookies.php');
require(FRAMEWORK_ROOT.'/framework/models.php');
require(FRAMEWORK_ROOT.'/framework/localization.php');
require(FRAMEWORK_ROOT.'/framework/document.php');
require(FRAMEWORK_ROOT.'/framework/views.php');
require(FRAMEWORK_ROOT.'/framework/blade.php');
require(FRAMEWORK_ROOT.'/framework/database.php');
require(FRAMEWORK_ROOT.'/framework/sessions.php');
require(FRAMEWORK_ROOT.'/framework/auth.php');
require(FRAMEWORK_ROOT.'/framework/userservice.php');
require(FRAMEWORK_ROOT.'/framework/groupservice.php');
require(FRAMEWORK_ROOT.'/framework/csrf.php');
require(FRAMEWORK_ROOT.'/framework/mail.php');
require(FRAMEWORK_ROOT.'/framework/security-recaptcha.php');
require(FRAMEWORK_ROOT.'/framework/security-captcha.php');

// Load the user's application.
if(!file_exists(FILE_ROOT.'/config.php'))
	throw new Exception('You must define a configuration file. To do this, rename the provided "config-template.php" file to "config.php".');

require(FILE_ROOT.'/config.php');
Config::_load();

// Determine whether or not errors should be suppressed.
if(Config::get('app.development', true) === false) {
	ErrorManager::disengageDevelopmentHandler();
	ErrorManager::engageProductionHandler();
}

require(FILE_ROOT.'/webapp.php');

// Run the user's application.
App::run();
Config::_save();