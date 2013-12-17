<?php
/*
	Basic PHP Web Framework
	@author Matthew Schurr
*/

function included() {
	return true;
}

if (version_compare(phpversion(), '5.3.0', '<') == true) {
	die('You must install PHP >= 5.3.0 in order to use this framework.');
}

define('FILE_ROOT', str_replace("/index.php","",$_SERVER['SCRIPT_FILENAME']));
define('FRAMEWORK_ROOT', FILE_ROOT.'/vendor/mschurr/framework/src');
define('EOL', PHP_EOL);
require(FRAMEWORK_ROOT.'/framework/errors.php');

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

require(FILE_ROOT.'/webapp.php');
App::run();